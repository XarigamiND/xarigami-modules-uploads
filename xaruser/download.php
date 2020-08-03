<?php
/**
 * Purpose of File
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
  *
 * @subpackage Xarigami Uploads Module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com
 */
function uploads_user_download()
{
    if (!xarSecurityCheck('ViewUploads')) return;

    if (!xarVarFetch('fileId', 'int:1:', $fileId)) return;

    $fileInfo = xarModAPIFunc('uploads','user','db_get_file', array('fileId' => $fileId));

    if (empty($fileInfo) || !count($fileInfo)) {
        xarResponseRedirect('modules/uploads/xarimages/notapproved.gif');
        return TRUE;
    }

    // the file should be the first indice in the array
    $fileInfo = end($fileInfo);

    $instance[0] = $fileInfo['fileTypeInfo']['typeId'];
    $instance[1] = $fileInfo['fileTypeInfo']['subtypeId'];
    $instance[2] = xarSessionGetVar('uid');
    $instance[3] = $fileId;

    $instance = implode(':', $instance);

    // If you are an administrator OR the file is approved, continue
    if ($fileInfo['fileStatus'] != _UPLOADS_STATUS_APPROVED && !xarSecurityCheck('EditUploads', 0, 'File', $instance)) {
        return xarResponseForbidden($msg);
    }

    if (xarSecurityCheck('ViewUploads', 1, 'File', $instance)) {
        if ($fileInfo['storeType'] & _UPLOADS_STORE_FILESYSTEM || ($fileInfo['storeType'] == _UPLOADS_STORE_DB_ENTRY)) {
            if (!file_exists($fileInfo['fileLocation'])) {
                $msg = xarML('You requested a file that could not be located.');
                return xarResponseNotFound($msg);
            }
        } elseif ($fileInfo['storeType'] & _UPLOADS_STORE_DB_FULL) {
            if (!xarModAPIFunc('uploads', 'user', 'db_count_data', array('fileId' => $fileInfo['fileId']))) {
                 throw new DataNotFoundException($fileInfo['storeType']);
            }
        }

        try {
            $result = xarModAPIFunc('uploads', 'user', 'file_push', $fileInfo);
        } catch (Exception $e) {
            return false;
        }


        // Let any hooked modules know that we've just pushed a file
        // the hitcount module in particular needs to know to save the fact
        // that we just pushed a file and not display the count
        xarVarSetCached('Hooks.hitcount','save', 1);

        // Note: we're ignoring the output from the display hooks here
        xarModCallHooks('item', 'display', $fileId,
                         array('module'    => 'uploads',
                               'itemtype'  => 1, // Files
                               'returnurl' => xarModURL('uploads', 'user', 'download', array('fileId' => $fileId))));

        // File has been pushed to the client, now shut down.
        exit();

    } else {
        return FALSE;
    }
}
?>
