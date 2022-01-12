[{if $pagenavi}]
    [{assign var="where" value=$oView->getListFilter()}]
    [{assign var="whereparam" value="&amp;"}]
    [{foreach from=$where item=aField key=sTable}]
        [{foreach from=$aField item=sFilter key=sField}]
            [{assign var="whereparam" value=$whereparam|cat:"where["|cat:$sTable|cat:"]["|cat:$sField|cat:"]="|cat:$sFilter|cat:"&amp;"}]
        [{/foreach}]
    [{/foreach}]
    [{assign var="viewListSize" value=$oView->getViewListSize()}]
    [{assign var="whereparam" value=$whereparam|cat:"viewListSize="|cat:$viewListSize}]
    <nav>
        <ul class="pagination">
            <li class="page-item">
                <a class="page-link" href="[{$oViewConf->getSelfLink()}]&cl=[{$oViewConf->getActiveClassName()}]&amp;oxid=[{$oxid}]&amp;jumppage=1&amp;[{$linkSort}]&amp;actedit=[{$actedit}]&amp;language=[{$actlang}]&amp;editlanguage=[{$actlang}][{$whereparam}]&amp;folder=[{$folder}]&amp;pwrsearchfld=[{$pwrsearchfld}]&amp;art_category=[{$art_category}]">
                    &#171;
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="[{$oViewConf->getSelfLink()}]&cl=[{$oViewConf->getActiveClassName()}]&amp;oxid=[{$oxid}]&amp;jumppage=[{if $pagenavi->actpage-1 > 0}][{$pagenavi->actpage-1 > 0}][{else}]1[{/if}]&amp;[{$linkSort}]&amp;actedit=[{$actedit}]&amp;language=[{$actlang}]&amp;editlanguage=[{$actlang}][{$whereparam}]&amp;folder=[{$folder}]&amp;pwrsearchfld=[{$pwrsearchfld}]&amp;art_category=[{$art_category}]">
                    &#8249;
                </a>
            </li>
            [{foreach key=iPage from=$pagenavi->changePage item=page}]
                <li class="page-item [{if $iPage == $pagenavi->actpage}]active[{/if}]">
                    <a class="page-link" href="[{$oViewConf->getSelfLink()}]&cl=[{$oViewConf->getActiveClassName()}]&amp;oxid=[{$oxid}]&amp;jumppage=[{$iPage}]&amp;[{$linkSort}]&amp;actedit=[{$actedit}]&amp;language=[{$actlang}]&amp;editlanguage=[{$actlang}][{$whereparam}]&amp;folder=[{$folder}]&amp;pwrsearchfld=[{$pwrsearchfld}]&amp;art_category=[{$art_category}]">
                        [{$iPage}]
                    </a>
                </li>
            [{/foreach}]
            <li class="page-item">
                <a class="page-link" href="[{$oViewConf->getSelfLink()}]&cl=[{$oViewConf->getActiveClassName()}]&amp;oxid=[{$oxid}]&amp;jumppage=[{if $pagenavi->actpage+1 > $pagenavi->pages}][{$pagenavi->actpage}][{else}][{$pagenavi->actpage+1}][{/if}]&amp;[{$linkSort}]&amp;actedit=[{$actedit}]&amp;language=[{$actlang}]&amp;editlanguage=[{$actlang}][{$whereparam}]&amp;folder=[{$folder}]&amp;pwrsearchfld=[{$pwrsearchfld}]&amp;art_category=[{$art_category}]">
                    &#8250;
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="[{$oViewConf->getSelfLink()}]&cl=[{$oViewConf->getActiveClassName()}]&amp;oxid=[{$oxid}]&amp;jumppage=[{$pagenavi->pages}]&amp;[{$linkSort}]&amp;actedit=[{$actedit}]&amp;language=[{$actlang}]&amp;editlanguage=[{$actlang}][{$whereparam}]&amp;folder=[{$folder}]&amp;pwrsearchfld=[{$pwrsearchfld}]&amp;art_category=[{$art_category}]">
                    &#187;
                </a>
            </li>
        </ul>
    </nav>
[{/if}]