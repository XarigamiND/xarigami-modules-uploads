<?php
/**
 * Purpose of File
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Uploads Module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com
 */
/**
 *  Move a file from one location to another. Can (or will eventually be able to) grab a file from
 *  a remote site via ftp/http/etc and save it locally as well. Note: isUpload=TRUE implies isLocal=True
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   string  fileSrc    Complete path to source file
 *  @param   string  fileDest   Complete path to destination
 *  @param   boolean isUpload   Whether or not this file was uploaded (uses special checks on uploaded files)
 *  @param   boolean isLocal    Whether or not the file is a Local file or not (think: grabbing a web page)
 *
 *  @return boolean TRUE on success, FALSE otherwise
 */

function uploads_userapi_file_move( $args )
{

    extract ($args);

    if (!isset($force)) {
        $force = TRUE;
    }

    // if it wasn't specified, assume TRUE
    if (!isset($isUpload)) {
        $isUpload = FALSE;
    }

    if (!isset($fileSrc)) {
        throw new EmptyParameterException('fileSrc');
    }

    if (!isset($fileDest)) {
         throw new EmptyParameterException('fileDest');
    }

    if (!is_readable($fileSrc)) {
         throw new FileNotFoundException($fileSrc);
    }

    if (!file_exists($fileSrc)) {
        throw new FileNotFoundException($fileSrc);
    }

    $dirDest = realpath(dirname($fileDest));

    if (!file_exists($dirDest))  {
        throw new DirectoryNotFoundException($dirDest);
    }

    if (!is_writable($dirDest)) {
       return xarTplModule('base','user','errors',array('errortype' => 'not_writeable','var1'=>$dirDest));
    }
    $freespace = @disk_free_space($dirDest);
    if (!empty($freespace) && $freespace <= filesize($fileSrc)) {
        $msg = xarML('Unable to move file - Destination drive does not have enough free space!');
        throw new BadParameterException(null,$msg);
    }
    if (file_exists($fileDest) && $force != TRUE) {
        $msg = xarML('Unable to move file - Destination file already exists!');
         throw new BadParameterException(null,$msg);
    }

    if ($isUpload) {
        if (isset($resizeFile) && !empty($resizeFile)) {

            if (!class_exists('xarImage')) {
               sys::import('modules.base.xarclass.xarImage');
            }
            if ($resizeFile->saveImage($fileDest)) {
            $msg = xarML('Unable to move file [#(1)] to destination [#(2)].',$sizedimage->image, $fileDest);
             throw new BadParameterException(null,$msg);
             }
        }elseif (!move_uploaded_file($fileSrc, $fileDest)) {
            $msg = xarML('Unable to move file [#(1)] to destination [#(2)].',$fileSrc, $fileDest);
             throw new BadParameterException(null,$msg);
        }
    } else {
        if (!copy($fileSrc, $fileDest)) {
            $msg = xarML('Unable to move file [#(1)] to destination [#(2)].',$fileSrc, $fileDest);
             throw new BadParameterException(null,$msg);
        }
        // Now remove the file :-)
        @unlink($fileSrc);
    }

    return TRUE;
}

?>
