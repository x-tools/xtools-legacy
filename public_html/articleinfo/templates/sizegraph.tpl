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
					{if $lastcount != ""}
						{if $count.size == 0}
							<value xid="{$count.xid}">{$lastcount}</value>
						{else}
							<value xid="{$count.xid}">{$count.size}</value>
							{assign var="lastcount" value=`$count.size`}
						{/if}
					{else}
						<value xid="{$count.xid}">{$count.size}</value>
						{assign var="lastcount" value=`$count.size`}
					{/if}
				{/foreach}
			{/foreach}
		</graph>
		<graph gid='1' title='{#cumulative#}'>
			{foreach from=$data key="year" item="item"}
				{foreach from=$item.months key="month" item="count"}
					
					<value xid="{$count.xid}">{$count.sizecumulative}</value>
				{/foreach}
			{/foreach}
		</graph>
	</graphs>
</chart>