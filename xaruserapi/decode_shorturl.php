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
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 *
 * @author the Example module development team
 * @param $params array containing the different elements of the virtual path
 * @return array
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function uploads_userapi_decode_shorturl($params)
{
    // Initialise the argument list we will return
    $args = array();

    // Analyse the different parts of the virtual path
    // $params[1] contains the first part after index.php/example

    // In general, you should be strict in encoding URLs, but as liberal
    // as possible in trying to decode them...
    if (empty($params[1])) {
        // nothing specified -> we'll go to the main function
        return array('download', $args);

    } elseif (preg_match('/^(\d+)\.(.*)/', $params[1], $matches)) {

        // something that starts with a number must be for the display function
        // Note : make sure your encoding/decoding is consistent ! :-)
        $fileId = $matches[1];
        $fileExists = xarModAPIFunc('uploads', 'user', 'db_count', array('fileId' => $fileId));

        if (!$fileExists) {
             throw new FileNotFoundException($params[1]);
        } else {
            $args['fileId'] = $fileId;
            return array('download', $args);
        }
    }

}

?>