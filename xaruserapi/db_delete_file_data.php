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
 *  Remove a file's data contents from the database. This just removes any data (contents)
 *  that we might have in store for this file. The actual metadata (FILE ENTRY) for the file
 *  itself is removed via db_delete_file() .
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   integer fileId    The id of the file who's contents we are removing
 *
 *  @return integer The number of affected rows on success, or FALSE on error
 */

function uploads_userapi_db_delete_file_data( $args )
{
    extract($args);

    if (!isset($fileId)) {
         throw new EmptyParameterException('fileId');
    }

    //add to uploads table
    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();

    // table and column definitions
    $fileData_table   = $xartable['file_data'];

    // insert value into table
    $sql = "DELETE
              FROM $fileData_table
             WHERE xar_fileEntry_id = $fileId";


    $result = $dbconn->Execute($sql);

    if (!$result) {
        return FALSE;
    } else {
        return $dbconn->Affected_Rows();
    }

}

?>