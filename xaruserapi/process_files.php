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
 *  Processes incoming files (uploads / imports)
 *
 *  @author  Carl P. Corliss (aka Rabbitt)
 *  @access  public
 *  @param   string     importFrom  The complete path to a (local) directory to import files from
 *  @param   array      override    Array containing override values for import/upload path/obfuscate
 *  @param   string     override.upload.path        Override the upload path with the specified value
 *  @param   string     override.upload.obfuscate   Override the upload filename obfuscation
 *  @param   integer    action        The action that is happening ;-)
 *  @return array      list of files the files that were requested to be stored. If they had errors,
 *                      they will have 'error' index defined and will -not- have been added. Otherwise,
 *                      they will have a fileId associated with them if they were added to the DB.
 */
// Load the defines
xarModAPILoad('uploads', 'user');

function uploads_userapi_process_files( $args )
{
    extract($args);

    $storeList = array();

    if (!isset($action)) {
        throw new EmptyParamaterException('action');
    }
    //backward compatibility - could be removed at some later date. Make consistent variable name with file uploads property
    if (isset($allow_duplicate) && !isset($allow_duplicates)) $allow_duplicates = $allow_duplicate;

    // If not store type defined, default to DB ENTRY AND FILESYSTEM STORE
    if (!isset($storeType)) {
        // this is the same as _UPLOADS_STORE_DB_ENTRY OR'd with _UPLOADS_STORE_FILESYSTEM
        $storeType = _UPLOADS_STORE_FSDB;
    }
    // If there is an override['upload']['path'], try to use that
    if (!empty($override['upload']['path'])) {
        $upload_directory = $override['upload']['path'];
        if (!file_exists($upload_directory)) {
            // Note: the parent directory must already exist
            $result = @mkdir($upload_directory);
            if ($result) {
                // create dummy index.html in case it's web-accessible
                @touch($upload_directory . '/index.html');
            } else {
            // CHECKME: fall back to common uploads directory, or fail ?
                $upload_directory = xarModGetVar('uploads','path.uploads-directory');
            }
        }
    } else {
        $upload_directory = xarModGetVar('uploads','path.uploads-directory');
    }
    if (!isset($allow_duplicates) )  $allow_duplicates = xarModGetVar('uploads', 'file.allow-duplicate-upload');

    // Check for override of upload obfuscation and set accordingly
    if (isset($override['upload']['obfuscate']) && $override['upload']['obfuscate']) {
        $upload_obfuscate = TRUE;
    } else {
        $upload_obfuscate = FALSE;
    }

    switch ($action) {

        case _UPLOADS_GET_UPLOAD:
            if (!isset($upload) || empty($upload)) {
                throw new EmptyParamaterException('upload');
            }
            if (!isset($allow_duplicates) || empty($allow_duplicates)) {
                $allow_duplicates = 0;
            }

            if (isset($upload['name']) && !empty($upload['name'])) {
                // make sure we look in the right directory :-)
                if ($storeType & _UPLOADS_STORE_FILESYSTEM) {
                   //remove trailing string $upload_directory
                   $upload_directory = rtrim($upload_directory ,'/');
                    $dirfilter = $upload_directory . '/%';

                } else {
                    $dirfilter = null;
                }

                // Note: we don't check on fileSize here (it wasn't taken into account before)
                $fileTest = xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileName' => $upload['name'],
                                                                                  // make sure we look in the right directory :-)
                                                                                  'fileLocation' => $dirfilter));

                if (count($fileTest)>0) {
                    $file = end($fileTest);
                    // if we don't allow duplicates
                    if (empty($allow_duplicates)) {
                        // specify the error message
                        $file['errors'] = array();
                        $file['errors'][] = array('errorMesg' => xarML('Filename already exists, overwrite not permitted.'),
                                                  'errorId'   => _UPLOADS_ERROR_BAD_FORMAT);
                        // set the fileId to null for templates etc.
                        $file['fileId'] = null;
                        // add the existing file to the list and break off
                        $fileList[0] = $file;
                        break;

                    // if we want to replace duplicate files
                    } elseif ($allow_duplicates == 2) {
                        // pass original fileId and fileLocation to $upload,
                        // and do something special in prepare_uploads / file_store ?
                        $upload['fileId'] = $file['fileId'];
                        $upload['fileLocation'] = $file['fileLocation'];
                        $upload['isDuplicate'] = 2;

                    } else {
                        // new version for duplicate files - continue as usual
                        $upload['isDuplicate'] = 1;
                    }
                }
                if (isset($extensions) && trim($extensions) != '') {
                    $filename = $upload['name'];
                    $filename = rtrim($filename,'.');
                    $pos = strrchr($filename, '.');
                    $testlist = array();
                    $testregex = '';
                    $check = TRUE;
                    if ($pos !== FALSE) {
                        $extension = ltrim($pos,'.');
                    }
                    // example: array('gif', 'jpg', 'jpeg', ...)
                    if (is_array($extensions)) {
                        $testlist = $extensions;
                    // example: gif,jpg,jpeg,png,bmp,txt,htm,html
                    } elseif (strpos($extensions, ',') !== false) {
                        $testlist = explode(',', $extensions);

                    // example: gif|jpe?g|png|bmp|txt|html?
                    } else {
                        $testregex = $extensions;
                    }

                    if (!empty($testlist) &&
                        !in_array($extension, $testlist)) {
                         $check = FALSE;
                    }
                    if (!empty($testregex) &&
                        !preg_match('/^' .$testregex . '$/', $extension)) {
                          $check = FALSE;
                    }
                    if ($check === FALSE) {
                        $file['errors'][] = array('errorMesg' => xarML('File extension must be one of #(1)', $extensions),
                                                  'errorId'   => _UPLOADS_ERROR_BAD_FORMAT);
                        // set the fileId to null for templates etc.
                        $file['fileId'] = null;
                         $fileList[0] = $file;
                        break;
                    }

                }
            }

            $fileList = xarModAPIFunc('uploads','user','prepare_uploads',
                                       array('savePath'  => $upload_directory,
                                             'obfuscate' => $upload_obfuscate,
                                             'fileInfo'  => $upload,
                                             'scale'    =>  isset($scale)?$scale: NULL,
                                             'resize_width' =>  isset($resize_width)?$resize_width: NULL,
                                             'resize_height' =>isset($resize_height)?$resize_height: NULL
                                             ));
            break;
        case _UPLOADS_GET_LOCAL:

            $storeType = _UPLOADS_STORE_DB_ENTRY;

            if (isset($getAll) && !empty($getAll)) {
                // current working directory for the user, set by import_chdir() when using the get_files() GUI
                $cwd = xarModGetUserVar('uploads', 'path.imports-cwd');

                $fileList = xarModAPIFunc('uploads', 'user', 'import_get_filelist', array('fileLocation' => $cwd, 'descend' => TRUE));

            } else {
                $list = array();
                // file list coming from validatevalue() or the get_files() GUI
                foreach ($fileList as $location => $fileInfo) {
                    if ($fileInfo['inodeType'] == _INODE_TYPE_DIRECTORY) {
                        $list += xarModAPIFunc('uploads', 'user', 'import_get_filelist',
                                                array('fileLocation' => $location, 'descend' => TRUE));
                        unset($fileList[$location]);
                    }
                }

                $fileList += $list;

                // files in the trusted directory are automatically approved
                foreach ($fileList as $key => $fileInfo) {
                    $fileList[$key]['fileStatus'] = _UPLOADS_STATUS_APPROVED;
                }
                unset($list);
            }
            break;
        case _UPLOADS_GET_EXTERNAL:

            if (!isset($import)) {
                throw new EmptyParamaterException('import');
            }

            // Setup the uri structure so we have defaults if parse_url() doesn't create them
            $uri = parse_url($import);

            if (!isset($uri['scheme']) || empty($uri['scheme'])) {
                $uri['scheme'] = xarML('unknown');
            }

            switch ($uri['scheme']) {
                case 'ftp':
                    $fileList = xarModAPIFunc('uploads', 'user', 'import_external_ftp',
                                              array('savePath'  => $upload_directory,
                                                    'obfuscate' => $upload_obfuscate,
                                                    'uri'       => $uri));
                    break;
                case 'https':
                case 'http':
                    $fileList = xarModAPIFunc('uploads', 'user', 'import_external_http',
                                              array('savePath'  => $upload_directory,
                                                    'obfuscate' => $upload_obfuscate,
                                                    'uri'       => $uri));
                    break;
                case 'file':
                    // If we'ere using the file scheme then just store a db entry only
                    // as there is really no sense in moving the file around
                    $storeType = _UPLOADS_STORE_DB_ENTRY;
                    $fileList = xarModAPIFunc('uploads', 'user', 'import_external_file',
                                              array('uri'       => $uri));
                    break;
                case 'gopher':
                case 'wais':
                case 'news':
                case 'nntp':
                case 'prospero':
                default:
                    // ERROR
                    return xarTplModule('uploads','user','errors',array('errortype' => 'not_supported','var1'=>$uri['scheme']));
            }
            break;
        default:
            throw new BadParamaterException('action');

    }

    foreach ($fileList as $fileInfo) {

        // If the file has errors, add the file to the storeList (with it's errors intact),
        // and continue to the next file in the list. Note: it's up to the calling function
        // to deal with the error (or not) - however, we won't be adding the file with errors :-)
        if (isset($fileInfo['errors'])) {
            $storeList[] = $fileInfo;
            continue;
        }
        $storeList[] = xarModAPIFunc('uploads', 'user', 'file_store',
                                      array('fileInfo'  => $fileInfo,
                                            'storeType' => $storeType));
    }

    return $storeList;
}

?>
