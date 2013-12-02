<?PHP
    class http {
        private $ch;
        private $uid;
        public $postfollowredirs;
        public $getfollowredirs;

        function data_encode ($data, $keyprefix = "", $keypostfix = "") {
            assert( is_array($data) );
            $vars=null;
            foreach($data as $key=>$value) {
                if(is_array($value)) $vars .= $this->data_encode($value, $keyprefix.$key.$keypostfix.urlencode("["), urlencode("]"));
                else $vars .= $keyprefix.$key.$keypostfix."=".urlencode($value)."&";
            }
            return $vars;
        }

        function __construct () {
            $this->ch = curl_init();
            $this->uid = dechex(rand(0,99999999));
            curl_setopt($this->ch,CURLOPT_COOKIEJAR,'/tmp/cluewikibot.cookies.'.$this->uid.'.dat');
            curl_setopt($this->ch,CURLOPT_COOKIEFILE,'/tmp/cluewikibot.cookies.'.$this->uid.'.dat');
            curl_setopt($this->ch,CURLOPT_MAXCONNECTS,100);
            curl_setopt($this->ch,CURLOPT_CLOSEPOLICY,CURLCLOSEPOLICY_LEAST_RECENTLY_USED);
            curl_setopt($this->ch,CURLOPT_USERAGENT,"SoxBot PHP");
            $this->postfollowredirs = 0;
            $this->getfollowredirs = 1;
        }

        function post ($url,$data) {
//            echo 'POST: '.$url."\n";
            $time = microtime(1);
            curl_setopt($this->ch,CURLOPT_URL,$url);
            curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,$this->postfollowredirs);
            curl_setopt($this->ch,CURLOPT_MAXREDIRS,10);
            curl_setopt($this->ch,CURLOPT_HEADER,0);
            curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($this->ch,CURLOPT_TIMEOUT,30);
            curl_setopt($this->ch,CURLOPT_CONNECTTIMEOUT,10);
            curl_setopt($this->ch,CURLOPT_POST,1);
//            curl_setopt($this->ch,CURLOPT_POSTFIELDS, substr($this->data_encode($data), 0, -1) );
            curl_setopt($this->ch,CURLOPT_POSTFIELDS, $data);
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, array( 'Expect:' ) );
            $data = curl_exec($this->ch);
            //var_dump($data);
            global $logfd; if (!is_resource($logfd)) $logfd = fopen('php://stderr','w'); fwrite($logfd,'POST: '.$url.' ('.(microtime(1) - $time).' s) ('.strlen($data)." b)\n");
            return $data;
        }

        function get ($url) {
            //echo 'GET: '.$url."\n";
            $time = microtime(1);
            curl_setopt($this->ch,CURLOPT_URL,$url);
            curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,$this->getfollowredirs);
            curl_setopt($this->ch,CURLOPT_MAXREDIRS,10);
            curl_setopt($this->ch,CURLOPT_HEADER,0);
            curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($this->ch,CURLOPT_TIMEOUT,30);
            curl_setopt($this->ch,CURLOPT_CONNECTTIMEOUT,10);
            curl_setopt($this->ch,CURLOPT_HTTPGET,1);
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, array( 'Expect:' ) );
            $data = curl_exec($this->ch);
            //var_dump($data);
            global $logfd; if (!is_resource($logfd)) $logfd = fopen('php://stderr','w'); fwrite($logfd,'GET: '.$url.' ('.(microtime(1) - $time).' s) ('.strlen($data)." b)\n");
            return $data;
        }

        function __destruct () {
            curl_close($this->ch);
            @unlink('/tmp/cluewikibot.cookies.'.$this->uid.'.dat');
        }
    }

    class wikipediaquery {
        private $http;
        private $api;
        public $queryurl = 'http://en.wikipedia.org/w/query.php'; //Obsolete, but kept for compatibility purposes.

        function __construct () {
            global $__wp__http;
            if (!isset($__wp__http)) {
                $__wp__http = new http;
            }
            $this->http = &$__wp__http;
            $this->api = new wikipediaapi;
        }

        private function checkurl() {
            $this->api->apiurl = str_replace('query.php','api.php',$this->queryurl);
        }

        function getpage ($page) {
            $this->checkurl();
//            $ret = unserialize($this->http->get($this->queryurl.'?what=content&format=php&titles='.urlencode($page)));
//            foreach ($ret['pages'] as $page) {
//                return $page['content']['*'];
//            }
            $ret = $this->api->revisions($page,1,'older',true,null,true,false,false,false);
            if ( $ret['pageid'] == '-1' ) return "";
            else return $ret[0]['*'];
        }

        function getpageid ($page) {
            $this->checkurl();
//            $ret = unserialize($this->http->get($this->queryurl.'?what=content&format=php&titles='.urlencode($page)));
//            foreach ($ret['pages'] as $page) {
//                return $page['id'];
//            }
            $ret = $this->api->revisions($page,1,'older',false,null,true,false,false,false);
            return $ret['pageid'];
        }

        function contribcount ($user) {
            $this->checkurl();
//            $ret = unserialize($this->http->get($this->queryurl.'?what=contribcounter&format=php&titles=User:'.urlencode($user)));
//            foreach ($ret['pages'] as $page) {
//                return $page['contribcounter']['count'];
//            }
            $ret = $this->api->users($user,1,null,true);
            if ($ret !== false) return $ret[0]['editcount'];
            return false;
        }
    }

    class wikipediaapi {
        private $http;
        private $edittoken;
        private $tokencache = array();
        public $apiurl = 'http://en.wikipedia.org/w/api.php';

        function __construct () {
            global $__wp__http;
            if (!isset($__wp__http)) {
                $__wp__http = new http;
            }
            $this->http = &$__wp__http;
        }

        function login ($user,$pass,$token = null) {
            $reqarray = array('lgname' => $user, 'lgpassword' => $pass);
            if( !is_null( $token ) ) {
                $reqarray['lgtoken'] = $token;
            }

            $data = $this->http->post($this->apiurl.'?action=login&format=php',$reqarray);
            $x = unserialize($data);

            if( @$x['login']['result'] == "NeedToken" ) {
                return $this->login( $user, $pass, $x['login']['token'] );
            }
            return $x;
        }

        function getedittoken () {
            $tokens = $this->gettokens('Main Page');
            if ($tokens['edittoken'] == '') $tokens = $this->gettokens('Main Page',true);
            $this->edittoken = $tokens['edittoken'];
            return $tokens['edittoken'];
        }

        function gettokens ($title,$flush = false) {
            foreach ($this->tokencache as $t => $data) if (time() - $data['timestamp'] > 6*60*60) unset($this->tokencache[$t]);
            if (isset($this->tokencache[$title]) && (!$flush)) {
                return $this->tokencache[$title]['tokens'];
            } else {
                $tokens = array();
                $x = $this->http->get($this->apiurl.'?action=query&format=php&prop=info&intoken=edit|delete|protect|move|block|unblock|email&titles='.urlencode($title));
                $x = unserialize($x);
                foreach ($x['query']['pages'] as $y) {
                    $tokens['edittoken'] = $y['edittoken'];
                    $tokens['deletetoken'] = $y['deletetoken'];
                    $tokens['protecttoken'] = $y['protecttoken'];
                    $tokens['movetoken'] = $y['movetoken'];
                    $tokens['blocktoken'] = $y['blocktoken'];
                    $tokens['unblocktoken'] = $y['unblocktoken'];
                    $tokens['emailtoken'] = $y['emailtoken'];
                    $this->tokencache[$title] = array(
                            'timestamp' => time(),
                            'tokens' => $tokens
                                     );
                    return $tokens;
                }
            }
        }

        function recentchanges ($count = 10,$namespace = null,$dir = 'older',$ts = null) {
            $append = '';
            if ($ts !== null) { $append .= '&rcstart='.urlencode($ts); }
            $append .= '&rcdir='.urlencode($dir);
            if ($namespace !== null) { $append .= '&rcnamespace='.urlencode($namespace); }
            $x = $this->http->get($this->apiurl.'?action=query&list=recentchanges&rcprop=user|comment|flags|timestamp|title|ids|sizes&format=php&rclimit='.$count.$append);
            $x = unserialize($x);
            return $x['query']['recentchanges'];
        }

        function search ($search,$limit = 10,$offset = 0,$namespace = 0,$what = 'text',$redirs = false) {
            $append = '';
            if ($limit != null) $append .= '&srlimit='.urlencode($limit);
            if ($offset != null) $append .= '&sroffset='.urlencode($offset);
            if ($namespace != null) $append .= '&srnamespace='.urlencode($namespace);
            if ($what != null) $append .= '&srwhat='.urlencode($what);
            if ($redirs == true) $append .= '&srredirects=1';
            else $append .= '&srredirects=0';
            $x = $this->http->get($this->apiurl.'?action=query&list=search&format=php&srsearch='.urlencode($search).$append);
            $x = unserialize($x);
            return $x['query']['search'];
        }

        function logs ($user = null,$title = null,$limit = 50,$type = null,$start = null,$end = null,$dir = 'older') {
            $append = '';
            if ($user != null) $append.= '&leuser='.urlencode($user);
            if ($title != null) $append.= '&letitle='.urlencode($title);
            if ($limit != null) $append.= '&lelimit='.urlencode($limit);
            if ($type != null) $append.= '&letype='.urlencode($type);
            if ($start != null) $append.= '&lestart='.urlencode($start);
            if ($end != null) $append.= '&leend='.urlencode($end);
            if ($dir != null) $append.= '&ledir='.urlencode($dir);
            $x = $this->http->get($this->apiurl.'?action=query&format=php&list=logevents&leprop=ids|title|type|user|timestamp|comment|details'.$append);
            $x = unserialize($x);
            return $x['query']['logevents'];
        }


        function usercontribs ($user,$count = 50,&$continue = null,$dir = 'older') {
            if ($continue != null) {
                $append = '&ucstart='.urlencode($continue);
            } else {
                $append = '';
            }
            $x = $this->http->get($this->apiurl.'?action=query&format=php&list=usercontribs&ucuser='.urlencode($user).'&uclimit='.urlencode($count).'&ucdir='.urlencode($dir).$append);
            $x = unserialize($x);
            $continue = $x['query-continue']['usercontribs']['ucstart'];
            return $x['query']['usercontribs'];
        }

        function revisions ($page,$count = 1,$dir = 'older',$content = false,$revid = null,$wait = true,$getrbtok = false,$dieonerror = true,$redirects = false) {
            $x = $this->http->get($this->apiurl.'?action=query&prop=revisions&titles='.urlencode($page).'&rvlimit='.urlencode($count).'&rvprop=timestamp|ids|user|comment'.(($content)?'|content':'').'&format=php&meta=userinfo&rvdir='.urlencode($dir).(($revid !== null)?'&rvstartid='.urlencode($revid):'').(($getrbtok == true)?'&rvtoken=rollback':'').(($redirects == true)?'&redirects':''));
            $x = unserialize($x);
            if ($revid !== null) {
                $found = false;
                if (!isset($x['query']['pages']) or !is_array($x['query']['pages'])) {
                    if ($dieonerror == true) die('No such page.'."\n");
                    else return false;
                }
                foreach ($x['query']['pages'] as $data) {
                    if (!isset($data['revisions']) or !is_array($data['revisions'])) {
                        if ($dieonerror == true) die('No such page.'."\n");
                        else return false;
                    }
                    foreach ($data['revisions'] as $data2) {
                        if ($data2['revid'] == $revid) $found = true;
                        $user = $data2['user'];
                    }
                    unset($data,$data2);
                    break;
                }

                if ($found == false) {
                    if ($wait == true) {
                        sleep(1);
                        return $this->revisions($page,$count,$dir,$content,$revid,false,$getrbtok,$dieonerror);
                    } else {
                        if ($dieonerror == true) die('Revision error.'."\n");
                    }
                }
            }
            foreach ($x['query']['pages'] as $key => $data) {
                $data['revisions']['ns'] = $data['ns'];
                $data['revisions']['title'] = $data['title'];
                //$data['revisions']['user'] = $data['revisions']['user'];
                $data['revisions']['currentuser'] = $x['query']['userinfo']['name'];
//                $data['revisions']['currentuser'] = $x['query']['userinfo']['currentuser']['name'];
                if( isset($data['revisions']['continue']) ) $data['revisions']['continue'] = $x['query-continue']['revisions']['rvcontinue'];
                $data['revisions']['pageid'] = $key;
                return $data['revisions'];
            }
        }

        function users ($start = null,$limit = 1,$group = null,$reqirestart = false,&$continue = null) {
            $append = '';
            if ($start != null) $append .= '&aufrom='.urlencode($start);
            if ($group != null) $append .= '&augroup='.urlencode($group);
            $x = $this->http->get($this->apiurl.'?action=query&list=allusers&format=php&auprop=blockinfo|editcount|registration|groups&aulimit='.urlencode($limit).$append);
            $x = unserialize($x);
            $continue = $x['query-continue']['allusers']['aufrom'];
            if (($requirestart == true) and ($x['query']['allusers'][0]['name'] != $start)) return false;
            return $x['query']['allusers'];
        }


        function categorymembers ($category,$count = 500,&$continue = null,$namespace=null) {
            $append = '';
            if ($continue != null) {
                $append .= '&cmcontinue='.urlencode($continue);
            } else {
                $append .= '';
            }
            if( $namespace != null ) {
                $append .= '&cmnamespace='.$namespace;
            }
            //$category = 'Category:'.str_ireplace('category:','',$category);
            $x = $this->http->get($this->apiurl.'?action=query&list=categorymembers&cmtitle='.urlencode($category).'&format=php&cmlimit='.$count.$append);
            $x = unserialize($x);
            $continue = $x['query-continue']['categorymembers']['cmcontinue'];
            return $x['query']['categorymembers'];
        }

        function listcategories (&$start = null,$limit = 50,$dir = 'ascending',$prefix = null) {
            $append = '';
            if ($start != null) $append .= '&acfrom='.urlencode($start);
            if ($limit != null) $append .= '&aclimit='.urlencode($limit);
            if ($dir != null) $append .= '&acdir='.urlencode($dir);
            if ($prefix != null) $append .= '&acprefix='.urlencode($prefix);

            $x = $this->http->get($this->apiurl.'?action=query&list=allcategories&acprop=size&format=php'.$append);
            $x = unserialize($x);

            $start = $x['query-continue']['allcategories']['acfrom'];

            return $x['query']['allcategories'];
        }

        function backlinks ($page,$count = 500,&$continue = null,$filter = null) {
            if ($continue != null) {
                $append = '&blcontinue='.urlencode($continue);
            } else {
                $append = '';
            }
            if ($filter != null) {
                $append .= '&blfilterredir='.urlencode($filter);
            }

            $x = $this->http->get($this->apiurl.'?action=query&list=backlinks&bltitle='.urlencode($page).'&format=php&bllimit='.$count.$append);
            $x = unserialize($x);
            if( isset($x['query-continue' ]) ) $continue = $x['query-continue']['backlinks']['blcontinue'];
            else $continue = null;
            return $x['query']['backlinks'];
        }

        function embeddedin ($page,$count = 500,&$continue = null) {
            if ($continue != null) {
                $append = '&eicontinue='.urlencode($continue);
            } else {
                $append = '';
            }
            $x = $this->http->get($this->apiurl.'?action=query&list=embeddedin&eititle='.urlencode($page).'&format=php&eilimit='.$count.$append);
            $x = unserialize($x);
            $continue = $x['query-continue']['embeddedin']['eicontinue'];
            return $x['query']['embeddedin'];
        }

        function listprefix ($prefix,$namespace = 0,$count = 500,&$continue = null) {
            $append = '&apnamespace='.urlencode($namespace);
            if ($continue != null) {
                $append .= '&apfrom='.urlencode($continue);
            }
            $x = $this->http->get($this->apiurl.'?action=query&list=allpages&apprefix='.urlencode($prefix).'&format=php&aplimit='.$count.$append);
            $x = unserialize($x);
            $continue = $x['query-continue']['allpages']['apfrom'];
            return $x['query']['allpages'];
        }

        function edit ($page,$data,$summary = '',$minor = false,$bot = true) {
            $params = Array(
                'action' => 'edit',
                'format' => 'php',
                'title' => $page,
                'text' => $data,
                'token' => $this->getedittoken(),
                'summary' => $summary,
                ($minor?'minor':'notminor') => '1',
                ($bot?'bot':'notbot') => '1'
            );

            $x = $this->http->post($this->apiurl,$params);
            $x = unserialize($x);
            //var_export($x);
        }

        function move ($old,$new,$reason) {
            $tokens = $this->gettokens($old);
            $params = array(
                'action' => 'move',
                'format' => 'php',
                'from' => $old,
                'to' => $new,
                'token' => $tokens['movetoken'],
                'reason' => $reason
            );

            $x = $this->http->post($this->apiurl,$params);
            $x = unserialize($x);
            //var_export($x);
        }

        function rollback ($title,$user,$reason,$token = null) {
            if (($token == null) or ($token == '')) {
                $token = $wpapi->revisions($title,1,'older',false,null,true,true);
                if ($token[0]['user'] == $user) {
                    $token = $token[0]['rollbacktoken'];
                } else {
                    return false;
                }
            }
            $params = array(
                'action' => 'rollback',
                'format' => 'php',
                'title' => $title,
                'user' => $user,
                'summary' => $reason,
                'token' => $token,
                'markbot' => 0
            );

            $x = $this->http->post($this->apiurl,$params);
            $x = unserialize($x);
            var_export($x);
            return $x;
        }
    }

    class wikipediaindex {
        private $http;
        public $indexurl = 'http://en.wikipedia.org/w/index.php';
        private $postinterval = 0;
        private $lastpost;
        private $edittoken;

        function __construct () {
            global $__wp__http;
            if (!isset($__wp__http)) {
                $__wp__http = new http;
            }
            $this->http = &$__wp__http;
        }

        function post ($page,$data,$summery = '',$minor = false,$rv = null,$bot = true) {
            global $user;
            global $maxlag;
            global $irc;
            global $irctechchannel;
            global $run;
            global $maxlagkeepgoing;

            $wpq = new wikipediaquery; $wpq->queryurl = str_replace('index.php','query.php',$this->indexurl);
            $wpapi = new wikipediaapi; $wpapi->apiurl = str_replace('index.php','api.php',$this->indexurl);

            if ((!$this->edittoken) or ($this->edittoken == '')) $this->edittoken = $wpapi->getedittoken();
            if ($rv == null) $rv = $wpapi->revisions($page,1,'older',true);
            if (!$rv[0]['*']) $rv[0]['*'] = $wpq->getpage($page);
            //print_r($rv);
            //Fake the edit form.
            $now = gmdate('YmdHis', time());
            $token = htmlspecialchars($this->edittoken);
            $tmp = date_parse($rv[0]['timestamp']);
            $edittime = gmdate('YmdHis', gmmktime($tmp['hour'],$tmp['minute'],$tmp['second'],$tmp['month'],$tmp['day'],$tmp['year']));
            $html = "<input type='hidden' value=\"{$now}\" name=\"wpStarttime\" />\n";
            $html.= "<input type='hidden' value=\"{$edittime}\" name=\"wpEdittime\" />\n";
            $html.= "<input type='hidden' value=\"{$token}\" name=\"wpEditToken\" />\n";
            $html.= '<input name="wpAutoSummary" type="hidden" value="'.md5('').'" />'."\n";

            if (preg_match('/'.preg_quote('{{nobots}}','/').'/iS',$rv[0]['*'])) { return false; }        /* Honor the bots flags */
            //echo "No {{nobots}}...";

            if (preg_match('/'.preg_quote('{{bots|allow=none}}','/').'/iS',$rv[0]['*'])) { return false; }

            //echo "No {{bots|allow=none}}...";
            //
            if (preg_match('/'.preg_quote('{{bots|deny=all}}','/').'/iS',$rv[0]['*'])) { return false; }

            //echo "No {{bots|deny=all}}..";

            if (preg_match('/\{\{bots\|deny=(.*)\}\}/iS',$rv[0]['*'],$m)) { if (in_array($user,explode(',',$m[1]))) { return false; } } /* /Honor the bots flags */

            //echo "No {{bots|deny=SoxBot...";

            if (!preg_match('/'.preg_quote($user,'/').'/iS',$rv['currentuser'])) { return false; } /* We need to be logged in */

            //echo "Logged in...";

//            if (preg_match('/'.preg_quote('You have new messages','/').'/iS',$rv[0]['*'])) { return false; } /* Check talk page */
            //if (!preg_match('/(yes|enable|true)/iS',((isset($run))?$run:$wpq->getpage('User:'.$user.'/Run')))) { return false; } /* Check /Run page */
            //echo "Ok, I'm posting.";
            $x = $this->forcepost($page,$data,$summery,$minor,$html,$maxlag,$maxlagkeepgoing,$bot); /* Go ahead and post. */
            $this->lastpost = time();
            return $x;
        }

        function forcepost ($page,$data,$summery = '',$minor = false,$edithtml = null,$maxlag = null,$mlkg = null,$bot = true) {
            $post['wpSection'] = '';
            $post['wpScrolltop'] = '';
            if ($minor == true) { $post['wpMinoredit'] = 1; }
            $post['wpTextbox1'] = $data;
            $post['wpSummary'] = $summery;
            if ($edithtml == null) {
                $html = $this->http->get($this->indexurl.'?title='.urlencode($page).'&action=edit');
            } else {
                $html = $edithtml;
            }
            preg_match('|\<input type\=\\\'hidden\\\' value\=\"(.*)\" name\=\"wpStarttime\" /\>|U',$html,$m);
            $post['wpStarttime'] = $m[1];
            preg_match('|\<input type\=\\\'hidden\\\' value\=\"(.*)\" name\=\"wpEdittime\" /\>|U',$html,$m);
            $post['wpEdittime'] = $m[1];
            preg_match('|\<input type\=\\\'hidden\\\' value\=\"(.*)\" name\=\"wpEditToken\" /\>|U',$html,$m);
            $post['wpEditToken'] = $m[1];
            preg_match('|\<input name\=\"wpAutoSummary\" type\=\"hidden\" value\=\"(.*)\" /\>|U',$html,$m);
            $post['wpAutoSummary'] = $m[1];

            //print_r($post);

            if ($maxlag != null) {
                $x = $this->http->post($this->indexurl.'?title='.urlencode($page).'&action=submit&maxlag='.urlencode($maxlag).'&bot='.(($bot == true)?'1':'0'),$post);
                if (preg_match('/Waiting for ([^ ]*): ([0-9.-]+) seconds lagged/S',$x,$lagged)) {
                    global $irc;
                    if (is_resource($irc)) {
                        global $irctechchannel;
                        foreach(explode(',',$irctechchannel) as $y) {
                            fwrite($irc,'PRIVMSG '.$y.' :'.$lagged[1].' is lagged out by '.$lagged[2].' seconds. ('.$lagged[0].')'."\n");
                        }
                    }
                    sleep(10);
                    if ($mlkg != true) { return false; }
                    else { $x = $this->http->post($this->indexurl.'?title='.urlencode($page).'&action=submit&bot='.(($bot == true)?'1':'0'),$post); }
                }
                return $x;
            } else {
                return $this->http->post($this->indexurl.'?title='.urlencode($page).'&action=submit&bot='.(($bot == true)?'1':'0'),$post);
            }
        }

        function getMaxlag() {
            $maxlag = $this->http->get($this->indexurl.'?maxlag=-1');
            echo $maxlag;
            if( preg_match('/Waiting for (.*): (\d*) seconds lagged/',$maxlag,$lagged)) {
                return $lagged;
            }
            else {
                return false;
            }
        }

        function diff ($title,$oldid,$id,$wait = true) {
            $deleted = '';
            $added = '';

            $html = $this->http->get($this->indexurl.'?title='.urlencode($title).'&action=render&diff='.urlencode($id).'&oldid='.urlencode($oldid).'&diffonly=1');

            if (preg_match_all('/\&amp\;(oldid\=|undo=)(\d*)\'\>(Revision as of|undo)/USs', $html, $m, PREG_SET_ORDER)) {
                //print_r($m);
                if ((($oldid != $m[0][2]) and (is_numeric($oldid)))) {
                    if ($wait == true) {
                        sleep(1);
                        return $this->diff($title,$oldid,$id,false);
                    } else {
                        die('Revision error.'."\n");
                    }
                }
            }

            if (preg_match_all('/\<td class\=(\"|\')diff-addedline\1\>\<div\>(.*)\<\/div\>\<\/td\>/USs', $html, $m, PREG_SET_ORDER)) {
                //print_r($m);
                foreach ($m as $x) {
                    $added .= htmlspecialchars_decode(strip_tags($x[2]))."\n";
                }
            }

            if (preg_match_all('/\<td class\=(\"|\')diff-deletedline\1\>\<div\>(.*)\<\/div\>\<\/td\>/USs', $html, $m, PREG_SET_ORDER)) {
                //print_r($m);
                foreach ($m as $x) {
                    $deleted .= htmlspecialchars_decode(strip_tags($x[2]))."\n";
                }
            }

            //echo $added."\n".$deleted."\n";

            if (preg_match('/action\=rollback\&amp\;from\=.*\&amp\;token\=(.*)\"/US', $html, $m)) {
                $rbtoken = $m[1];
                $rbtoken = urldecode($rbtoken);
//                echo 'rbtoken: '.$rbtoken.' -- '; print_r($m); echo "\n\n";
                return array($added,$deleted,$rbtoken);
            }

            return array($added,$deleted);
        }

        function rollback ($title,$user,$reason = null,$token = null,$bot = true) {
            if (($token == null) or (!$token)) {
                $wpapi = new wikipediaapi; $wpapi->apiurl = str_replace('index.php','api.php',$this->indexurl);
                $token = $wpapi->revisions($title,1,'older',false,null,true,true);
                if ($token[0]['user'] == $user) {
//                    echo 'Token: '; print_r($token); echo "\n\n";
                    $token = $token[0]['rollbacktoken'];
                } else {
                    return false;
                }
            }
            $x = $this->http->get($this->indexurl.'?title='.urlencode($title).'&action=rollback&from='.urlencode($user).'&token='.urlencode($token).(($reason != null)?'&summary='.urlencode($reason):'').'&bot='.(($bot == true)?'1':'0'));
            global $logfd; if (!is_resource($logfd)) $logfd = fopen('php://stderr','w'); //fwrite($logfd,'Rollback return: '.$x."\n");
            if (!preg_match('/action complete/iS',$x)) return false;
            return $x;
        }

        function move ($old,$new,$reason) {
            $wpapi = new wikipediaapi; $wpapi->apiurl = str_replace('index.php','api.php',$this->indexurl);
            if ((!$this->edittoken) or ($this->edittoken == '')) $this->edittoken = $wpapi->getedittoken();

            $token = htmlspecialchars($this->edittoken);

            $post = array
                (
                    'wpOldTitle'    => $old,
                    'wpNewTitle'    => $new,
                    'wpReason'    => $reason,
                    'wpWatch'    => '0',
                    'wpMovetalk' => '1',
                    'wpLeaveRedirect' => '1',
                    'wpEditToken'    => $token,
                    'wpMove'    => 'Move page'
                );
            return $this->http->post($this->indexurl.'?title=Special:Movepage&bot=true&action=submit',$post);
        }

        function upload ($page,$file,$desc) {
            $post = array
                (
                    'wpUploadFile'        => '@'.$file,
                    'wpSourceType'        => 'file',
                    'wpDestFile'        => $page,
                    'wpUploadDescription'    => $desc,
                    'wpLicense'        => '',
                    'wpWatchthis'        => '0',
                    'wpIgnoreWarning'    => '1',
                    'wpUpload'        => 'Upload file'
                );
            return $this->http->post($this->indexurl.'?title=Special:Upload&action=submit',$post);
        }

        function hasemail ($user) {
            $tmp = $this->http->get($this->indexurl.'?title=Special:EmailUser&target='.urlencode($user));
            if (stripos($tmp,"No e-mail address") !== false) return false;
            return true;
        }

        function email ($user,$subject,$body) {
            $wpapi = new wikipediaapi; $wpapi->apiurl = str_replace('index.php','api.php',$this->indexurl);
            if ((!$this->edittoken) or ($this->edittoken == '')) $this->edittoken = $wpapi->getedittoken();

            $post = array
                (
                    'wpSubject'    => $subject,
                    'wpText'    => $body,
                    'wpCCMe'    => 0,
                    'wpSend'    => 'Send',
                    'wpEditToken'    => $this->edittoken
                );

            return $this->http->post($this->indexurl.'?title=Special:EmailUser&target='.urlencode($user).'&action=submit',$post);
        }
    }
?>
