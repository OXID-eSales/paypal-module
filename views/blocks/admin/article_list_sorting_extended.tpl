<td class="listheader first" height="15" width="30" align="center">
    <a href="Javascript:top.oxid.admin.setSorting( document.search, 'oxarticles', 'oxactive', 'asc');document.search.submit();" class="listheader">
        [{oxmultilang ident="GENERAL_ACTIVTITLE"}]
    </a>
</td>
<td class="listheader" height="15" width="30" align="center">
    [{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTIONS_SHORT"}]
</td>
<td class="listheader">
    <a href="Javascript:top.oxid.admin.setSorting( document.search, 'oxarticles', 'oxartnum', 'asc');document.search.submit();" class="listheader">
        [{oxmultilang ident="GENERAL_ARTNUM"}]
    </a>
</td>
<td class="listheader" height="15">
    &nbsp;
    <a href="Javascript:top.oxid.admin.setSorting( document.search, 'oxarticles', '[{$pwrsearchfld|oxlower}]', 'asc');document.search.submit();" class="listheader">
        [{assign var="ident" value="GENERAL_ARTICLE_"|cat:$pwrsearchfld}][{assign var="ident" value=$ident|oxupper}][{oxmultilang ident=$ident}]
    </a>
</td>
<td class="listheader" colspan="2">
    <a href="Javascript:top.oxid.admin.setSorting( document.search, 'oxarticles', 'oxshortdesc', 'asc');document.search.submit();" class="listheader">
        [{oxmultilang ident="GENERAL_SHORTDESC"}]
    </a>
</td>
