    <ul id="Home" title="{#tool#}" selected="true">
        <li><a href="#General">{#generalinfo#}</a></li>
        <li><a href="#NSTable">{#namespacetotals#}</a></li>
        <li><a href="#NSGraph">{#graphalt#}</a></li>
        <li><a href="#Monthcounts">{#monthcounts#}</a></li>
        <li><a href="#Topedited">{#topedited#}</a></li>
        <li><a onclick="window.location.href='http://tools.wmflabs.org/xtools/pcount/index.php?name={$usernameurl$}&nophone{$loadwiki$}'">Full Edit Counter</a></li>
        <h6>&copy;2010 <a onclick="window.location.href='http://enwp.org/User:X!'">X!</a></h6>
    </ul>
    <ul id="General" title="{#generalinfo#}" parentName="Home">
        {&isset: username &}
        <li class="semilink">{#username#}: <a onclick="window.location.href='http://{$url$}/wiki/User:{$usernameurl$}'">{$username$}</a></li>
        {&endisset&}
        
        {&isset: groups &}
        <li class="nolink">{#groups#}: {$groups$}</li>
        {&endisset&}

        {&isset: lastedit &}
        <li class="nolink">{#lastedit#}: {$lastedit$}</li>
        {&endisset&}
        
        {&isset: unique &}
        <li class="nolink">{#unique#}: {$unique$}</li>
        {&endisset&}

        {&isset: average &}
        <li class="nolink">{#average#}: {$average$}</li>
        {&endisset&}

        {&isset: live &}
        <li class="nolink">{#live#}: {$live$}</li>
        {&endisset&}

        {&isset: deleted &}
        <li class="nolink">{#deleted#}: {$deleted$}</li>
        {&endisset&}

        {&isset: total &}
        <li class="nolink">{#total#}: {$total$}</li>
        {&endisset&}


    </ul>
    <ul id="NSTable" title="{#namespacetotals#}" parentName="Home">
        {&isset: namespacetotals &}
        {$namespacetotals$}
        {&endisset&}
    </ul>
    <ul id="NSGraph" title="{#namespacetotals#}" parentName="Home">
        {&isset: graph &}
        <p>{$graph$}</p>
        
        {&endisset&}
    </ul>
    <ul id="Monthcounts" title="{#monthcounts#}" parentName="Home">
        {&isset: monthcounts &}
        {$monthcounts$}
        {&endisset&}
        
        {&isset: nograph &}
        {$nograph$}
        {&endisset&}
    </ul>
    <ul id="Topedited" title="{#topedited#}" parentName="Home">
        {&isset: topedited &}
        {$topedited$}
        {&endisset&}
        
        {&isset: nograph &}
        {$nograph$}
        {&endisset&}
    </ul>