<?php

/*
Soxred93's Edit Counter
Copyright (C) 2010 Soxred93

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <//www.gnu.org/licenses/>.
*/

class HTTP {
    private $ch;

    private $uid;

    public $postfollowredirs;

    public $getfollowredirs;

    private $baseurl;



    function __construct ($baseurl = '') {

        $this->baseurl = $baseurl;

        $this->ch = curl_init();

        $this->uid = dechex(rand(0,99999999));

        curl_setopt($this->ch,CURLOPT_COOKIEJAR,'/tmp/cookies.'.$this->uid.'.dat');

        curl_setopt($this->ch,CURLOPT_COOKIEFILE,'/tmp/cookies.'.$this->uid.'.dat');

        curl_setopt($this->ch,CURLOPT_MAXCONNECTS,100);

        curl_setopt($this->ch,CURLOPT_CLOSEPOLICY,CURLCLOSEPOLICY_LEAST_RECENTLY_USED);
        curl_setopt($this->ch,CURLOPT_USERAGENT,"Soxred93's Edit Counters");

        $this->postfollowredirs = 0;

        $this->getfollowredirs = 1;

    }



    function get ($url) {

        $time = microtime(1);

        curl_setopt($this->ch,CURLOPT_URL,$url);

        curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,$this->getfollowredirs);

        curl_setopt($this->ch,CURLOPT_MAXREDIRS,10);

        curl_setopt($this->ch,CURLOPT_HEADER,0);

        curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);

        curl_setopt($this->ch,CURLOPT_TIMEOUT,30);

        curl_setopt($this->ch,CURLOPT_CONNECTTIMEOUT,10);

        curl_setopt($this->ch,CURLOPT_HTTPGET,1);

        $data = curl_exec($this->ch);

        //echo 'GET: '.$url.' ('.(microtime(1) - $time).' s) ('.strlen($data)." b)\n";

        return $data;

    }
    
    function getcode( $url ) {
        $co = $this->get( $url );
        if(!curl_errno($this->ch)) {
            $info = curl_getinfo($this->ch);
            return $info['http_code'];
        }
    }



    function getpage( $title, $bool = false ) {

        $url = $this->baseurl . "api.php?action=query&prop=revisions&titles=".urlencode($title)."&rvprop=content&limit=1&format=php";

        $x = unserialize($this->get( $url ));


        if( !is_array( $x['query']['pages'] ) ) { die("ERROR WHEN GETTING $title!!!"); }



        foreach( $x['query']['pages'] as $key => $page ) {

            if( $key == "-1" ) return false;

            if( $bool == true ) return true;

            return $page['revisions']['0']['*'];

        }

    }
    
    function getnamespaces() {
        global $phptemp;
        
        $x = unserialize( $this->get( $this->baseurl . 'api.php?action=query&meta=siteinfo&siprop=namespaces&format=php' ) );
        //echo $this->baseurl;
        
        unset( $x['query']['namespaces'][-2] );
        unset( $x['query']['namespaces'][-1] );
        
        $res = array( 'ids' => array(), 'names' => array() );
        
        foreach( $x['query']['namespaces'] as $id => $ns ) {
            $res['ids'][$ns['*']] = $id;
            $res['names'][$id] = $ns['*'];
        }
        
        if( isset( $phptemp ) ) $res['ids'][''] = $res['names'][0] = $phptemp->getConf( 'mainspace' );
        
        return $res;

    }
    
    function isOptedOut( $user ) {
        $x = unserialize( $this->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterOptOut.js&rvprop=content&format=php' ) );

        foreach( $x['query']['pages'] as $page ) {
            if( !isset( $page['revisions'] ) ) {
                
                $x = unserialize( $this->get( '//meta.wikimedia.org/w/api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterGlobalOptOut.js&rvprop=content&format=php' ) );
                foreach( $x['query']['pages'] as $page ) {
                    if( !isset( $page['revisions'] ) ) {
                        $x = unserialize( $this->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/Editcounter&rvprop=content&format=php' ) );
                        foreach( $x['query']['pages'] as $page ) {
                            if( !isset( $page['revisions'] ) ) {
                                return false;
                            }
                            elseif( strpos( $page['revisions'][0]['*'], "Month-Graph:no" ) !== FALSE ) {
                                return true;
                            }
                        }
                    }
                    elseif( $page['revisions'][0]['*'] != "" ) {
                        return true;
                    }
                }
            }
            elseif( $page['revisions'][0]['*'] != "" ) {
                return true;
            }
        }
    }
    
    function isOptedIn( $user ) {
        $x = unserialize( $this->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterOptIn.js&rvprop=content&format=php' ) );

        foreach( $x['query']['pages'] as $page ) {
            if( !isset( $page['revisions'] ) ) {
                
                $x = unserialize( $this->get( 'http://meta.wikimedia.org/w/api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterGlobalOptIn.js&rvprop=content&format=php' ) );
                foreach( $x['query']['pages'] as $page ) {
                    if( !isset( $page['revisions'] ) ) {
                        $x = unserialize( $this->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/Editcounter&rvprop=content&format=php' ) );
                        foreach( $x['query']['pages'] as $page ) {
                            if( !isset( $page['revisions'] ) ) {
                                return false;
                            }
                            elseif( strpos( $page['revisions'][0]['*'], "Month-Graph:yes" ) !== FALSE ) {
                                return true;
                            }
                        }
                    }
                    elseif( $page['revisions'][0]['*'] != "" ) {
                        return true;
                    }
                }
            }
            elseif( $page['revisions'][0]['*'] != "" ) {
                return true;
            }
        }
        
        return false;
    }
    
    function getWhichOptIn( $user ) {
        $x = unserialize( $this->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterOptIn.js&rvprop=content&format=php' ) );

        foreach( $x['query']['pages'] as $page ) {
            if( !isset( $page['revisions'] ) ) {
                
                $x = unserialize( $this->get( 'http://meta.wikimedia.org/w/api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/EditCounterGlobalOptIn.js&rvprop=content&format=php' ) );
                foreach( $x['query']['pages'] as $page ) {
                    if( !isset( $page['revisions'] ) ) {
                        $x = unserialize( $this->get( $this->baseurl . 'api.php?action=query&prop=revisions&titles=User:'.urlencode($user).'/Editcounter&rvprop=content&format=php' ) );
                        foreach( $x['query']['pages'] as $page ) {
                            if( !isset( $page['revisions'] ) ) {
                                return "false";
                            }
                            elseif( strpos( $page['revisions'][0]['*'], "Month-Graph:yes" ) !== FALSE ) {
                                return "interiot";
                            }
                        }
                    }
                    elseif( $page['revisions'][0]['*'] != "" ) {
                        return "globally";
                    }
                }
            }
            elseif( $page['revisions'][0]['*'] != "" ) {
                return "locally";
            }
        }
        
        return "false";
    }
    
    function __destruct () {

        curl_close($this->ch);

        @unlink('cookies.'.$this->uid.'.dat');

    }

}