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
 *  Create an assocation between a (stored) file and a module/itemtype/item
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   integer fileId    The id of the file we are going to associate with an item
 *  @param   integer modid     The id of module this file is associated with
 *  @param   integer itemtype  The item type within the defined module
 *  @param   integer itemid    The id of the item types item
 *
 *  @return integer The id of the file that was associated, FALSE with exception on error
 */

function uploads_userapi_db_add_association( $args )
{
    extract($args);

    if (!isset($fileId)) {
        throw new EmptyParameterException('fileId');
    }

    if (!isset($modid)) {
        throw new EmptyParameterException('modid');
    }

    if (!isset($itemtype)) {
        $itemtype = 0;
    }

    if (!isset($itemid)) {
        $itemid = 0;
    }

    //add to uploads table
    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();

    // table and column definitions
    $file_assoc_table = $xartable['file_associations'];

    // insert value into table
    $sql = "INSERT INTO $file_assoc_table
                      (
                        xar_fileEntry_id,
                        xar_modid,
                        xar_itemtype,
                        xar_objectid
                      )
               VALUES
                      ( ?, ?, ?, ? )";

    $bindvars = array((int)$fileId,(int)$modid,(int)$itemtype,(int)$itemid);
    $result = $dbconn->Execute($sql, $bindvars);

    if (!$result) {
        return FALSE;
    } else {
        return $fileId  ;
    }
}

?>
