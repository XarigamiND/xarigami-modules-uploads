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
 *  Retrieve the filename for a particular file based on the file id
 *
 * @author Carl P. Corliss
 * @access public
 * @param  integer  fileId     (Optional) grab file with the specified file id
 *
 * @return array   All of the metadata stored for the particular file
 */

function uploads_userapi_db_get_filename( $args )
{

    extract($args);

    if (!isset($fileId)) {
         throw new EmptyParameterException('fileId');
    }

    if (isset($fileId)) {
        if (is_array($fileId)) {
            $where = 'xar_fileEntry_id IN (' . implode(',', $fileId) . ')';
        } elseif (!empty($fileId)) {
            $where = "xar_fileEntry_id = $fileId";
        }
    }

    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();

        // table and column definitions
    $fileEntry_table = $xartable['file_entry'];

    $sql = "SELECT xar_filename
              FROM $fileEntry_table
             WHERE $where";

    $result = $dbconn->Execute($sql);

    if (!$result)  {
        return;
    }

    // if no record found, return an empty array
    if ($result->EOF) {
        return '';
    }

    while (!$result->EOF) {
        $row = $result->GetRowAssoc(false);
        $fileName = $row['xar_filename'];
        $result->MoveNext();
    }
    return $fileName;
}

?>