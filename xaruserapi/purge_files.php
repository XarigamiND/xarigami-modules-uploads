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
/**
 * Takes a list of files and deletes them

 *
 * @author  Carl P. Corliss
 * @access  public
 * @param   array   fileList    List of files to delete containing complete fileName => fileInfo arrays
 * @return boolean             true if successful, false otherwise
 */

function uploads_userapi_purge_files( $args )
{

    extract ( $args );

    if (!isset($fileList)) {
        throw new EmptyParameterException('filelist');
    }

    foreach ($fileList as $fileName => $fileInfo) {

        if ($fileInfo['storeType'] & _UPLOADS_STORE_FILESYSTEM) {
            xarModAPIFunc('uploads', 'user', 'file_delete', array('fileName' => $fileInfo['fileLocation']));
        }

        if ($fileInfo['storeType'] & _UPLOADS_STORE_DB_DATA) {
            xarModAPIFunc('uploads', 'user', 'db_delete_file_data', array('fileId' => $fileInfo['fileId']));
        }

        // go ahead and delete the file from the database.
        xarModAPIFunc('uploads', 'user', 'db_delete_file', array('fileId' => $fileInfo['fileId']));

    }

    return TRUE;
}

?>