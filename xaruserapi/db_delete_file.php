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
 *  Remove a file entry from the database. This just removes any metadata about a file
 *  that we might have in store. The actual DATA (contents) of the file (ie., the file
 *  itself) are removed via either file_delete() or db_delete_fileData() depending on
 *  how the DATA is stored.
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   integer file_id    The id of the file we are deleting
 *
 *  @return integer The number of affected rows on success, or FALSE on error
 */

function uploads_userapi_db_delete_file( $args )
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
    $fileEntry_table   = $xartable['file_entry'];

    // insert value into table
    $sql = "DELETE FROM $fileEntry_table
                  WHERE xar_fileEntry_id = $fileId";


    $result = $dbconn->Execute($sql);

    if (!$result) {
        return FALSE;
    }

    // Pass the arguments to the hook modules too
    $args['module'] = 'uploads';
    $args['itemtype'] = 1; // Files
    xarModCallHooks('item', 'delete', $fileId, $args);

    return TRUE;
}

?>
