{if $error != ""}<br /><h2 class="alert">{$error}</h2>{/if}
{if $form != ""}<br />{$form}{/if}

{if $showstats != ""}
<b>{#cidr#}:</b> {$cidr}<br />
<b>{#ip_start#}:</b> {$ip_start}<br />
<b>{#ip_end#}:</b> {$ip_end}<br />
<b>{#ip_number#}:</b> {$ip_number}<br />
{/if}

{if $list != ""}
{$list}
{/if}
