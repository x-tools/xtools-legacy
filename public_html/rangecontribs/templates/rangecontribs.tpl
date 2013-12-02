{&isset: error &}<br /><h2 class="alert">{$error$}</h2>{&endisset&}
{&isset: replag &}<br /><h2 class="alert">{$replag$}</h2>{&endisset&}
{&isset: form &}<br />{$form$}{&endisset&}

{&isset: showstats &}
<b>{#cidr#}:</b> {$cidr$}<br />
<b>{#ip_start#}:</b> {$ip_start$}<br />
<b>{#ip_end#}:</b> {$ip_end$}<br />
<b>{#ip_number#}:</b> {$ip_number$}<br />
{&endisset&}

{&isset: nocontribs &}{#nocontribs#}{&endisset&}

{&isset: list &}
<ul>
{$list$}
</ul>
{&endisset&}

{&isset: nextlist &}{$nextlist$}{&endisset&}