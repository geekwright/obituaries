<{if count($obituaries_users) > 0}>
    <{$breadcrumb}>
    <br>
    <br>
    <p align="center">
        <a href="<{$xoops_url}>/modules/obituaries/index.php"><img
                    src="<{$xoops_url}>/modules/obituaries/assets/images/logoModule.png" alt=""></a>
    </p>
    <br>
    <br>
    <br>
    <br>
    <ul>
        <{foreach item=obituaries_user from=$obituaries_users}>
            <li><a href="<{$smarty.const.OBITUARIES_URL}>user.php?obituaries_id=<{$obituaries_user.obituaries_id}>"
                   title="<{$obituaries_user.obituaries_href_title}>"><{$obituaries_user.obituaries_fullname}></a> <{$obituaries_user.obituaries_formated_date}>
            </li>
        <{/foreach}>
    </ul>
<{else}>
    <h3><{$smarty.const._AM_OBITUARIES_ERROR3}></h3>
<{/if}>

<{if isset($pagenav)}>
    <div align="center"><{$pagenav}></div>
<{/if}>
