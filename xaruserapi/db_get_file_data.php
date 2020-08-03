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
 *  Retrieve the DATA (contents) stored for a particular file based on
 *  the file id. This returns an array not unlike the php function
 *  'file()' whereby the contents of the file are in an ordered array.
 *  The contents can be put back together by doing: implode('',
 *
 * @author Carl P. Corliss
 * @author Micheal Cortez
 * @access public
 * @param  integer  fileId     The ID of the file we are are retrieving
 *
 * @return array   All the (4K) blocks stored for this file
 * @throws BAD_PARAM
 */

function uploads_userapi_db_get_file_data( $args )
{
    extract($args);

    if (!isset($fileId)) {
        throw new EmptyParameterException('fileId');
    }

    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();

    // table definition
    $fileData_table = $xartable['file_data'];

    $sql = "SELECT xar_fileEntry_id,
                   xar_fileData_id,
                   xar_fileData
              FROM $fileData_table
             WHERE xar_fileEntry_id = $fileId
          ORDER BY xar_fileData_id ASC";

    $result = $dbconn->Execute($sql);

    if (!$result) {
        return;
    }

    // if no record found, return an empty array
    if ($result->EOF) {
        return array();
    }

    while (!$result->EOF) {
        $row = $result->GetRowAssoc(false);
        $fileData[$row['xar_filedata_id']] = base64_decode($row['xar_filedata']);
        $result->MoveNext();
    }
    $result->Close();

   return $fileData;
}
?>