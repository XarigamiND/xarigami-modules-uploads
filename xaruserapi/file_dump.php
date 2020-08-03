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
 *  Dump a files contents into the database.
 *
 *  @author  Carl P. corliss
 *  @access  public
 *  @param   string  fileSrc   The location of the file whose contents we want to dump into the database
 *  @param   integer fileId    The file entry id of the file's meta data in the database
 *  returns  integer           The total bytes stored or boolean FALSE on error
 */

function uploads_userapi_file_dump( $args )
{

    extract($args);

    if (!isset($unlink)) {
        $unlink = TRUE;
    }
    if (!isset($fileSrc)) {
         throw new EmptyParameterException('fileSrc');
    }

    if (!isset($fileId)) {
         throw new EmptyParameterException('fileId');
    }

    if (!file_exists($fileSrc)) {
         throw new FileNotFoundException($fileSrc);
    }

    if (!is_readable($fileSrc) || !is_writable($fileSrc)) {
        return xarTplModule('base','user','errors',array('errortype' => 'not_writeable','var1'=>$fileSrc));

    }

    $fileInfo = xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileId' => $fileId));
    $fileInfo = end($fileInfo);

    if (!count($fileInfo) || empty($fileInfo)) {
        throw new DataNotFoundException($fileInfo);
    } else {
        $dataBlocks = xarModAPIFunc('uploads', 'user', 'db_count_data', array('fileId' => $fileId));

        if ($dataBlocks > 0) {
            // we don't support non-truncated overwrites nor appends
            // so truncate the file and then save it
            if (!xarModAPIFunc('uploads', 'user', 'db_delete_file_data', array('fileId' => $fileId))) {
                $msg = xarML('Unable to truncate file [#(1)] in database.', $fileInfo['fileName']);
                 throw new DataNotFoundException(null,$msg);
            }
        }

        // Now we copy the contents of the file into the database
        if (($srcId = fopen($fileSrc, 'rb')) !== FALSE) {

            do {
                // Read 16K in at a time
                $data = fread($srcId, (64 * 1024));
                if (0 == strlen($data)) {
                    fclose($srcId);
                    break;
                }
                if (!xarModAPIFunc('uploads', 'user', 'db_add_file_data', array('fileId' => $fileId, 'fileData' => $data))) {
                    // there was an error, so close the input file and delete any blocks
                    // we may have written, unlink the file (if specified), and return an exception
                    fclose($srcId);
                    if ($unlink) {
                        @unlink($fileSrc); // fail silently
                    }
                    xarModAPIFunc('uploads', 'user', 'db_delete_file_data', array('fileId' => $fileId));
                    $msg = xarML('Unable to save file contents to database.');
                    throw new BadParameterException(null,$msg);
                }
            } while (TRUE);
       } else {
            return xarTplModule('base','user','errors',array('errortype' => 'not_writeable','var1'=>$fileSrc));
       }
    }

    if ($unlink) {
        @unlink($fileSrc);
    }
    return TRUE;
}

?>