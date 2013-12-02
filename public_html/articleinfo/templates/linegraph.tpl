<?xml version="1.0" encoding="UTF-8"?>
<chart>
	<series>
		{foreach from=$data key="year" item="item"}
			{foreach from=$item.months key="month" item="count"}
				<value xid="{$count.xid}">{$month}/{$year}</value>
			{/foreach}
		{/foreach}
	</series>
	<graphs>
		<graph gid='0' title='{#monthly#}'>
			{foreach from=$data key="year" item="item"}
				{foreach from=$item.months key="month" item="count"}
					<value xid="{$count.xid}">{$count.all}</value>
				{/foreach}
			{/foreach}
		</graph>
		<graph gid='1' title='{#cumulative#}'>
			{foreach from=$data key="year" item="item"}
				{foreach from=$item.months key="month" item="count"}
					{assign var="datestamp" value=`$month``$year`}
					<value xid="{$count.xid}" {if $eventdata[$datestamp] != ""}description="{$eventdata[$datestamp]}" bullet="round" bullet_color="#009900" bullet_size="7"{/if}>{$count.cumulative}</value>
				{/foreach}
			{/foreach}
		</graph>
		<graph gid='2' title='{#ips#}'>
			{foreach from=$data key="year" item="item"}
				{foreach from=$item.months key="month" item="count"}
					<value xid="{$count.xid}">{$count.anon}</value>
				{/foreach}
			{/foreach}
		</graph>
		<graph gid='3' title='{#minor#}'>
			{foreach from=$data key="year" item="item"}
				{foreach from=$item.months key="month" item="count"}
					<value xid="{$count.xid}">{$count.minor}</value>
				{/foreach}
			{/foreach}
		</graph>
	</graphs>
</chart>