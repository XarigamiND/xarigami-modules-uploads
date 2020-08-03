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
 *  Delete a file from the filesystem
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   string fileName    The complete path to the file being deleted
 *
 *  @return TRUE on success, FALSE on error
 */

function uploads_userapi_file_delete( $args )
{

    extract ($args);

    if (!isset($fileName)) {
         throw new EmptyParameterException('fileName');
    }

    if (!file_exists($fileName)) {
        // if the file doesn't exist, then we don't need
        // to worry about deleting it - so return true :)
        return TRUE;
    }

    if (!unlink($fileName)) {
        $msg = xarML('Unable to remove file: [#(1)].', $fileName);
        throw new BadParameterException(null,$msg);
    }

    return TRUE;
}

?>