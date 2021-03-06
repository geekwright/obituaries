<?php
/**
 * ****************************************************************************
 * Obituaries - MODULE FOR XOOPS
 * Copyright (c) Hervé Thouzard of Instant Zero (http://www.instant-zero.com)
 * Created on 10 juil. 08 at 11:38:52
 * Version :
 * ****************************************************************************
 */

use XoopsModules\Obituaries;

require_once __DIR__ . '/admin_header.php';
//require_once  dirname(dirname(dirname(__DIR__))) . '/include/cp_header.php';
require_once dirname(__DIR__) . '/include/common.php';
require_once XOOPS_ROOT_PATH . '/class/pagenav.php';
require_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

$adminObject = \Xmf\Module\Admin::getInstance();

$op = 'default';
if (\Xmf\Request::hasVar('op', 'POST')) {
    $op = $_POST['op'];
} elseif (\Xmf\Request::hasVar('op', 'GET')) {
    $op = $_GET['op'];
}

// Lecture de certains paramètres de l'application ********************************************************************
$limit         = Obituaries\ObituariesUtils::getModuleOption('perpage');    // Nombre maximum d'éléments à afficher
$baseurl       = OBITUARIES_URL . 'admin/' . basename(__FILE__);    // URL de ce script
$conf_msg      = Obituaries\ObituariesUtils::javascriptLinkConfirm(_AM_OBITUARIES_CONF_DELITEM);
$images_width  = Obituaries\ObituariesUtils::getModuleOption('images_width');
$images_height = Obituaries\ObituariesUtils::getModuleOption('images_height');
$destname      = '';

$cacheFolder = XOOPS_UPLOAD_PATH . '/' . OBITUARIES_DIRNAME;
if (!is_dir($cacheFolder)) {
    mkdir($cacheFolder, 0777);
    file_put_contents($cacheFolder . '/index.html', '<script>history.go(-1);</script>');
}

switch ($op) {
    // ****************************************************************************************************************
    case 'default':    // List obituariess and show form to add a someone
        // ****************************************************************************************************************
        xoops_cp_header();
        // echo '<h1>'.ObituariesUtils::getModuleName().'</h1>';
        $adminObject->displayNavigation(basename(__FILE__));
        $start = \Xmf\Request::getInt('start', 0, 'GET');
        /** @var \UsersHandler $usersHandler */
        $itemsCount = $usersHandler->getCount();
        if ($itemsCount > $limit) {
            $pagenav = new \XoopsPageNav($itemsCount, $limit, $start, 'start');
        }
        if (isset($pagenav) && is_object($pagenav)) {
            echo "<div align='right'>" . $pagenav->renderNav() . '</div>';
        }
        if ($itemsCount > 0) {
            $class = '';
            //          $items = $usersHandler->getItems($start, $limit, 'obituaries_lastname');

            $tblItems = [];
            //$critere = new \Criteria($this->keyName, 0 ,'<>');
            $critere = new \Criteria('obituaries_id', 0, '<>');
            $critere->setLimit($limit);
            $critere->setStart($start);
            $critere->setSort('obituaries_lastname');
            //                  $critere->setOrder($order);
            //                  $tblItems = $this->getObjects($critere, $idAsKey);
            $items = $usersHandler->getAllUsers($start, $limit, 'obituaries_lastname');

            echo "<table width='100%' cellspacing='1' cellpadding='3' border='0' class='outer'>";
            echo "<tr><th align='center'>" . _AM_OBITUARIES_DATE . "</th><th align='center'>" . _AM_OBITUARIES_USERNAME . "</th><th align='center'>" . _AM_OBITUARIES_LASTNAME . ',  ' . _AM_OBITUARIES_FIRSTNAME . "</th><th align='center'>" . _AM_OBITUARIES_ACTION . '</th></tr>';
            foreach ($items as $item) {
                $class = ('even' === $class) ? 'odd' : 'even';
                $id    = $item->getVar('obituaries_id');
                $user  = null;
                $user  = $item->getXoopsUser();
                $uname = '';
                if (is_object($user)) {
                    $uname = $user->getVar('uname');
                }
                $action_edit   = "<a href='$baseurl?op=edit&id=" . $id . "' title='" . _EDIT . "'>" . $birdthday_icones['edit'] . '</a>';
                $action_delete = "<a href='$baseurl?op=delete&id=" . $id . "' title='" . _DELETE . "'" . $conf_msg . '>' . $birdthday_icones['delete'] . '</a>';

                echo "<tr class='" . $class . "'>\n";
                echo "<td align='center'>" . Obituaries\ObituariesUtils::SQLDateToHuman($item->getVar('obituaries_date')) . '</td>';
                echo "<td align='center'>" . $uname . '</td>';
                echo "<td align='center'>" . $item->getFullName() . '</td>';
                echo "<td align='center'>" . $action_edit . ' ' . $action_delete . '</td>';
                echo "</tr>\n";
            }
            echo "</table>\n";
            if (isset($pagenav) && is_object($pagenav)) {
                echo "<div align='left'>" . $pagenav->renderNav() . '</div>';
            }
            echo "<br><br>\n";
        }
        $item = $usersHandler->create(true);
        $form = $usersHandler->getForm($item, $baseurl);
        $form->display();
        break;
    // ****************************************************************************************************************
    case 'maintain':    // Maintenance des tables et du cache
        // ****************************************************************************************************************
        xoops_cp_header();
        $adminObject->displayNavigation(basename(__FILE__));
        require_once dirname(__DIR__) . '/xoops_version.php';
        $tables = [];
        foreach ($modversion['tables'] as $table) {
            $tables[] = $xoopsDB->prefix($table);
        }
        if (count($tables) > 0) {
            $list = implode(',', $tables);
            $xoopsDB->queryF('CHECK TABLE ' . $list);
            $xoopsDB->queryF('ANALYZE TABLE ' . $list);
            $xoopsDB->queryF('OPTIMIZE TABLE ' . $list);
        }
        Obituaries\ObituariesUtils::updateCache();
        $usersHandler->forceCacheClean();
        Obituaries\ObituariesUtils::redirect(_AM_OBITUARIES_SAVE_OK, $baseurl, 2);
        break;
    // ****************************************************************************************************************
    case 'edit':    // Edition d'un utilisateur existant
        // ****************************************************************************************************************
        xoops_cp_header();
        $adminObject->displayNavigation(basename(__FILE__));
        $id = \Xmf\Request::getInt('id', 0, 'GET');
        if (empty($id)) {
            Obituaries\ObituariesUtils::redirect(_AM_OBITUARIES_ERROR_1, $baseurl, 5);
        }
        // Item exits ?
        $item = null;
        $item = $usersHandler->get($id);
        if (!is_object($item)) {
            Obituaries\ObituariesUtils::redirect(_AM_OBITUARIES_NOT_FOUND, $baseurl, 5);
        }
        $form = $usersHandler->getForm($item, $baseurl);
        $form->display();
        break;
    // ****************************************************************************************************************
    case 'saveedit':    // Enregistrement des modifications
        // ****************************************************************************************************************
        xoops_cp_header();
        $adminObject->displayNavigation(basename(__FILE__));
        $result = $usersHandler->saveUser();
        if ($result) {
            Obituaries\ObituariesUtils::redirect(_AM_OBITUARIES_SAVE_OK, $baseurl, 1);
        } else {
            Obituaries\ObituariesUtils::redirect(_AM_OBITUARIES_SAVE_PB, $baseurl, 3);
        }
        break;
    // ****************************************************************************************************************
    case 'delete':    // Suppression d'un utilisateur
        // ****************************************************************************************************************
        $id = \Xmf\Request::getInt('id', 0, 'GET');
        if (empty($id)) {
            Obituaries\ObituariesUtils::redirect(_AM_OBITUARIES_ERROR_1, $baseurl, 5);
        }
        // Item exits ?
        $item = null;
        $item = $usersHandler->get($id);
        if (!is_object($item)) {
            Obituaries\ObituariesUtils::redirect(_AM_OBITUARIES_NOT_FOUND, $baseurl, 5);
        }
        $result = $usersHandler->deleteUser($item);
        if ($result) {
            Obituaries\ObituariesUtils::redirect(_AM_OBITUARIES_SAVE_OK, $baseurl, 1);
        } else {
            Obituaries\ObituariesUtils::redirect(_AM_OBITUARIES_SAVE_PB, $baseurl, 3);
        }
}
xoops_cp_footer();
