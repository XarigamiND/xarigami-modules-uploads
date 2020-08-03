<?php
/**
 * Purpose of File
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Uploads Module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com
 */
/**
 * Show a page with various ways to add files to the system
 */
// load defined constants
xarModAPILoad('uploads', 'user');

function uploads_admin_get_files()
{

    if (!xarSecurityCheck('AddUploads')) return;
    $data = array();

    //common admin menu
    $data['menulinks'] = xarModAPIFunc('uploads','admin','getmenulinks');

    if (!xarVarFetch('msg', 'str', $data['msg'], '',  XARVAR_NOT_REQUIRED)) return;

    $actionList[] = _UPLOADS_GET_UPLOAD;
    $actionList[] = _UPLOADS_GET_EXTERNAL;
    $actionList[] = _UPLOADS_GET_LOCAL;
    $actionList[] = _UPLOADS_GET_REFRESH_LOCAL;
    $actionList = 'enum:' . implode(':', $actionList);

    if (!xarVarFetch('action',        $actionList, $action, '', XARVAR_NOT_REQUIRED)) return;

    // StoreType can -only- be one of FSDB or DB_FULL
    $storeTypes = _UPLOADS_STORE_FSDB . ':' . _UPLOADS_STORE_DB_FULL;
    if (!xarVarFetch('storeType',     "enum:$storeTypes", $storeType, '', XARVAR_NOT_REQUIRED)) return;


    // now make sure someone hasn't tried to change our maxsize on us ;-)
    $file_maxsize = xarModGetVar('uploads', 'file.maxsize');

    if (!isset($action)) {
        $action = NULL;
    }
    $args['action']    = $action;

    switch ($action) {
        case _UPLOADS_GET_UPLOAD:
            if (!xarVarFetch('MAX_FILE_SIZE', "int::$file_maxsize", $maxsize)) return;
            if (!xarVarValidate('array:1:', $_FILES['upload'])) return;
            $upload = $_FILES['upload'];
            $args['upload'] = $upload;
            if ($upload['error'] > 0) {
                if ($upload['error'] == 4) {
                    $msg = xarML('No file was selected for upload. Please choose a file for upload and then click on Upload.');
                } else {
                    $msg = $upload['error'];
                }
                xarTplSetMessage($msg,'error');
                xarResponseRedirect(xarModURL('uploads', 'admin', 'get_files'));
            }
            break;
        case _UPLOADS_GET_EXTERNAL:
            // minimum external import link must be: ftp://a.ws  <-- 10 characters total
            if (!xarVarFetch('import', 'regexp:/^([a-z]*).\/\/(.{7,})/', $import, '', XARVAR_NOT_REQUIRED)) return;
            $args['import'] = $import;

            if (is_null($import) || empty($import)) {
                $msg= xarML('You chose to import an external file but no file was selected for import');
                xarTplSetMessage($msg,'error');
                 xarResponseRedirect(xarModURL('uploads', 'admin', 'get_files'));
            }
            break;
        case _UPLOADS_GET_LOCAL:
            if (!xarVarFetch('fileList', 'list:regexp:/(?<!\.{2,2}\/)[\w\d]*/', $fileList, array(), XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('file_all', 'checkbox', $file_all, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('addbutton', 'str:1', $addbutton, '',  XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('delbutton', 'str:1', $delbutton, '',  XARVAR_NOT_REQUIRED)) return;

            if (empty($fileList)) {
                $msg= xarML('You chose to import local files but no files were selected for import');
                 xarTplSetMessage($msg,'error');
                 xarResponseRedirect(xarModURL('uploads', 'admin', 'get_files'));
            }
            if (empty($addbutton) && empty($delbutton)) {
                $msg = xarML('Unsure how to proceed - missing button action!');
                throw new EmptyParameterException('addbutton',$msg);
            } else {
                $args['bAction'] = (!empty($addbutton)) ? $addbutton : $delbutton;
            }

            $cwd = xarModGetUserVar('uploads', 'path.imports-cwd');
            if (!empty($fileList)) {
                foreach ($fileList as $file) {
                    $args['fileList']["$cwd/$file"] = xarModAPIFunc('uploads', 'user', 'file_get_metadata',
                                                                     array('fileLocation' => "$cwd/$file"));
                }
            }
            $args['getAll'] = $file_all;

            break;
        default:
        case _UPLOADS_GET_REFRESH_LOCAL:
            if (!xarVarFetch('inode', 'regexp:/(?<!\.{2,2}\/)[\w\d]*/', $inode, '', XARVAR_NOT_REQUIRED)) return;

            $cwd = xarModAPIFunc('uploads', 'user', 'import_chdir', array('dirName' => isset($inode) ? $inode : NULL));
            if (!is_dir($cwd)) {
                $msg = xarML('Please set a valid upload directory in Modify Configuration before continuing.');
                xarTplSetMessage($msg,'alert');
                xarResponseRedirect(xarModURL('uploads','admin','modifyconfig'));
                return;
            }
            $data['storeType']['DB_FULL']     = _UPLOADS_STORE_DB_FULL;
            $data['storeType']['FSDB']        = _UPLOADS_STORE_FSDB;
            $data['inodeType']['DIRECTORY']   = _INODE_TYPE_DIRECTORY;
            $data['inodeType']['FILE']        = _INODE_TYPE_FILE;
            $data['getAction']['LOCAL']       = _UPLOADS_GET_LOCAL;
            $data['getAction']['EXTERNAL']    = _UPLOADS_GET_EXTERNAL;
            $data['getAction']['UPLOAD']      = _UPLOADS_GET_UPLOAD;
            $data['getAction']['REFRESH']     = _UPLOADS_GET_REFRESH_LOCAL;
            $data['local_add_button']         = xarML('Import selected local files');
            $data['local_del_button']         = xarML('Delete selected local files');
            $data['local_import_post_url']    = xarModURL('uploads', 'admin', 'get_files');
            $data['external_import_post_url'] = xarModURL('uploads', 'admin', 'get_files');
            $data['upload_post_url'] = xarModURL('uploads', 'admin', 'get_files');
            $data['fileList'] = xarModAPIFunc('uploads', 'user', 'import_get_filelist',
                                               array('fileLocation' => $cwd, 'onlyNew' => TRUE));

            $data['curDir'] = str_replace(xarModGetVar('uploads', 'path.imports-directory'), '', $cwd);
            $data['noPrevDir'] = (xarModGetVar('uploads', 'path.imports-directory') == $cwd) ? TRUE : FALSE;
            // reset the CWD for the local import
            // then only display the: 'check for new imports' button
            $data['authid'] = xarSecGenAuthKey();
            $data['file_maxsize'] = $file_maxsize;
            return $data;
            break;
    }
    if (isset($storeType)) {
        $args['storeType'] = $storeType;
    }

    $list = xarModAPIFunc('uploads','user','process_files', $args);

    if (is_array($list) && count($list)) {
        return xarTplModule('uploads','admin', 'addfile-status', array('fileList' => $list), NULL);
    } else {
        xarResponseRedirect(xarModURL('uploads', 'admin', 'get_files', $data));                return;
    }

    return $data;
}
?>
