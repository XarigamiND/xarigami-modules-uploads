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
 *  Adds a file's  contents to the database. This only takes 4K (4096 bytes) blocks.
 *  So a file's data could potentially be contained amongst many records. This is done to
 *  ensure that we are able to actually save the whole file in the db.
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   integer fileId     The ID of the file this data belongs to
 *  @param   string  fileData   A line of data from the file to be stored (no greater than 65535 bytes)
 *
 *  @return integer The id of the fileData that was added, or FALSE on error
 */

function uploads_userapi_db_add_file_data( $args )
{

    extract($args);

    if (!isset($fileId)) {
         throw new EmptyParameterException('fileId');
    }

    if (!isset($fileData)) {
         throw new EmptyParameterException('fileData');
    }

    if (sizeof($fileData) >= (1024 * 64)) {
        $msg = xarML('#(1) exceeds maximum storage limit of 64KB per data chunk.', 'fileData');
         throw new BadParameterException(null,$msg);
    }

    $fileData = base64_encode($fileData);

    //add to uploads table
    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();


    // table and column definitions
    $fileData_table = $xartable['file_data'];
    $fileDataID    = $dbconn->GenID($fileData_table);

    // insert value into table
    $sql = "INSERT INTO $fileData_table
                      (
                        xar_fileEntry_id,
                        xar_fileData_id,
                        xar_fileData
                      )
               VALUES
                      (
                        $fileId,
                        $fileDataID,
                        '$fileData'
                      )";
    $result = $dbconn->Execute($sql);

    if (!$result) {
        return FALSE;
    } else {
        $id = $dbconn->PO_Insert_ID($xartable['file_data'], 'xar_cid');
        return $id;
    }
}

?>