<?php
/**
 * Upload input field
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Uploads Module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com
 */
/**
 * show input fields for uploads module (used in DD properties)
 *
 * @param  $args ['id'] string id of the upload field(s)
 * @param  $args ['value'] string the current value(s)
 * @param  $args ['format'] string format specifying 'fileupload', 'textupload' or 'upload' (future ?)
 * @param  $args ['multiple'] boolean allow multiple uploads or not
 * @param  $args ['methods'] array of allowed methods 'upload', 'stored', 'trusted', and/or 'external'
 * @param  $args ['override'] array optional override values for import/upload path/obfuscate (cfr. process_files)
 * @param  $args ['invalid'] string invalid error message
 * @return string
 * @return string containing the input fields
 */
function uploads_adminapi_showinput($args)
{
    extract($args);

    if (empty($id)) {
        $id = null;
    }
    if (empty($value)) {
        $value = null;
    }
    if (empty($multiple)) {
        $multiple = false;
    } else {
        $multiple = true;
    }
    if (empty($format)) {
        $format = 'fileupload';
    }
    if (empty($methods)) {
        $methods = null;
    }
    if (!isset($extensions)) $extensions = '';

    // Check to see if an old value is present. Old values just file names
    // and do not start with a semicolon (our delimiter)
    if (xarModAPIFunc('uploads', 'admin', 'dd_value_needs_conversion', $value)) {
        $newValue = xarModAPIFunc('uploads', 'admin', 'dd_convert_value', array('value' =>$value));

        // if we were unable to convert the value, then go ahead and and return
        // an empty string instead of processing the value and bombing out
        if ($newValue == $value) {
            $value = null;
            unset($newValue);
        } else {
            $value = $newValue;
            unset($newValue);
        }
    }

    $data = array();


    xarModAPILoad('uploads','user');

    $data['display'] = isset($display) ? $display: FALSE;

    if (isset($methods) && count($methods) ==4) {
        $data['methods'] = array(
            'trusted'  => isset($methods['trusted'] ) && ($methods['trusted'] ==1) ? TRUE : FALSE,
            'external' => isset($methods['external'] ) && $methods['external'] ==1? TRUE : FALSE,
            'upload'   => isset($methods['upload'] ) && $methods['upload']   ==1? TRUE : FALSE,
            'stored'   => isset($methods['stored'] ) && $methods['stored']  ==1 ? TRUE : FALSE
        );
    }elseif  (isset($methods) && count($methods) >0) {
         $data['methods'] = array(
            'trusted'  => in_array('trusted',$methods) ? TRUE : FALSE,
            'external' => in_array('external',$methods)? TRUE : FALSE,
            'upload'   => in_array('upload',$methods)? TRUE : FALSE,
            'stored'   => in_array('stored',$methods) ? TRUE : FALSE
        );
    } else {
        $data['methods'] = array(
            'trusted'  => xarModGetVar('uploads', 'dd.fileupload.trusted')  ? TRUE : FALSE,
            'external' => xarModGetVar('uploads', 'dd.fileupload.external') ? TRUE : FALSE,
            'upload'   => xarModGetVar('uploads', 'dd.fileupload.upload')   ? TRUE : FALSE,
            'stored'   => xarModGetVar('uploads', 'dd.fileupload.stored')   ? TRUE : FALSE
        );
    }

    $descend = TRUE;

    $data['getAction']['LOCAL']       = _UPLOADS_GET_LOCAL;
    $data['getAction']['EXTERNAL']    = _UPLOADS_GET_EXTERNAL;
    $data['getAction']['UPLOAD']      = _UPLOADS_GET_UPLOAD;
    $data['getAction']['STORED']      = _UPLOADS_GET_STORED;
    $data['getAction']['REFRESH']     = _UPLOADS_GET_REFRESH_LOCAL;
    $data['id']                       = $id;
    $data['file_maxsize'] = xarModGetVar('uploads', 'file.maxsize');
    if ($data['methods']['trusted']) {
        // if there is an override['import']['path'], try to use that
        if (!empty($override['import']['path'])) {
            $trusted_dir = $override['import']['path'];
            if (!file_exists($trusted_dir)) {
            // CHECKME: fall back to common trusted directory, or fail here ?
                $trusted_dir = xarModGetVar('uploads', 'path.imports-directory');
            //  return xarML('Unable to find trusted directory #(1)', $trusted_dir);
            }
        } else {
            $trusted_dir = xarModGetVar('uploads', 'path.imports-directory');
        }
        $cacheExpire = xarModGetVar('uploads','file.cache-expire');

    // CHECKME: use 'imports' name like in db_get_file() ?
        // Note: for relativePath, the (main) import directory is replaced by /trusted in file_get_metadata()
        $data['fileList']     = xarModAPIFunc('uploads', 'user', 'import_get_filelist',
                                              array('fileLocation' => $trusted_dir,
                                                    'descend'      => $descend,
                                                    // no need to analyze the mime type here
                                                    'analyze'      => FALSE,
                                                    // cache the results if configured
                                                    'cacheExpire'  => $cacheExpire));
    } else {
        $data['fileList']     = array();
    }

    if ($data['methods']['stored']) {
        // if there is an override['upload']['path'], try to use that
        if (!empty($override['upload']['path'])) {
            $upload_directory = $override['upload']['path'];
            if (file_exists($upload_directory)) {
                $data['storedList']   = xarModAPIFunc('uploads', 'user', 'db_get_file',
                    // find all files located under that upload directory
                    array('fileLocation' => str_replace('//','/',$upload_directory . '/%')));
            } else {
                // Note: the parent directory must already exist
                $result = @mkdir($upload_directory);
                if ($result) {
                    // create dummy index.html in case it's web-accessible
                    @touch($upload_directory . '/index.html');
                    // the upload directory is still empty for the moment
                    $data['storedList']   = array();
                } else {
                // CHECKME: fall back to common uploads directory, or fail here ?
                //  $data['storedList']   = xarModAPIFunc('uploads', 'user', 'db_getall_files');
                    return xarML('Unable to create upload directory #(1)', $upload_directory);
                }
            }
        } else {
            $data['storedList']   = xarModAPIFunc('uploads', 'user', 'db_getall_files');
        }
    } else {
        $data['storedList']   = array();
    }

    // used to allow selection of multiple files
    $data['multiple_' . $id] = $multiple;

    if (!empty($value)) {
        // We use array_filter to remove any values from
        // the array that are empty, null, or false
        $aList = array_filter(explode(';', $value));

        if (is_array($aList) && count($aList)) {
            $data['inodeType']['DIRECTORY']   = _INODE_TYPE_DIRECTORY;
            $data['inodeType']['FILE']        = _INODE_TYPE_FILE;
            $data['Attachments'] = xarModAPIFunc('uploads', 'user', 'db_get_file',
                                                  array('fileId' => $aList));
            $list = xarModAPIFunc('uploads','user','showoutput',
                                  array('value' => $value, 'style' => 'icon', 'multiple' => $multiple));

            foreach ($aList as $fileId) {
                if (!empty($data['storedList'][$fileId])) {
                    $data['storedList'][$fileId]['selected'] = TRUE;
                } elseif (!empty($data['Attachments'][$fileId])) {
                    // add it to the list (e.g. from another user's upload directory - we need this when editing)
                    $data['storedList'][$fileId] = $data['Attachments'][$fileId];
                    $data['storedList'][$fileId]['selected'] = TRUE;
                } else {
                    // missing data for $fileId
                }
            }
        }
    }
    //simple url for display on input for DD prop
    $src = '';
    if (isset($value) && isset($display)) {
        $srcid = trim(str_replace(';','',$value));
        if ($srcid > 0) {
            $src = xarModURL('uploads', 'user', 'download', array('fileId' => $srcid));
        }
    }
    $data['src'] = $src;

  //fix max file size for display on input of info
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    $upload_mb = min($max_upload, $max_post, $memory_limit);
    $maxsize =  $data['file_maxsize']; //bytes
    $maxmb = (int) ($maxsize/(1024 * 1024)); //bytes to Mb
    $data['phpmaxerror'] = '';
    if ($maxmb>$upload_mb) {
       $maxsize = $upload_mb * 1024 * 1024; //in bytes
        $data['phpmaxerror'] = xarML('The max upload size set of #(1) Mb for uploads is greater than the php maximum allowed of #(2) Mb',$maxmb, $upload_mb);
    }
    $data['maxsize'] = $maxsize;

    if ($data['maxsize']  >= (1024 * 1024)) {
        $data['filemax'] = number_format(($data['maxsize'] /(1024*1024))) . ' '.xarMl('Mb');
    } else {
         $data['filemax'] = $data['maxsize']. ' '.xarMl('Bytes');
    }
    $data['extensions'] = $extensions;

    if (!empty($invalid)) {
       $data['invalid'] = $invalid;
    }

// TODO: different formats ?
    return (isset($list) ? $list : '') . xarTplModule('uploads', 'user', 'attach_files', $data, NULL);
}

?>
