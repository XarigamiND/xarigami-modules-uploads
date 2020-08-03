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
 * Primarily used by Articles as a transform hook to turn "upload tags" into various display formats
 *
 * @param array $args ['extrainfo']
 * @return result
 */
function &uploads_userapi_transformhook ( $args )
{
    extract($args);

    if (is_array($extrainfo)) {
        if (isset($extrainfo['transform']) && is_array($extrainfo['transform'])) {
            foreach ($extrainfo['transform'] as $key) {
                if (isset($extrainfo[$key])) {
                    $extrainfo[$key] = uploads_userapi_transform($extrainfo[$key]);
                }
            }
            return $extrainfo;
        }
        foreach ($extrainfo as $key => $text) {
            $result[] = uploads_userapi_transform($text);
        }
    } else {
        $result = uploads_userapi_transform($extrainfo);
    }
    return $result;
}
/**
 * Transform the $body parameter
 * @param $body
 */
function uploads_userapi_transform ( $body )
{
    if (!is_string($body)) return; //jojo -we do not want to error here eg if a fileupload is now an upload in articles
    while(preg_match('/#(ulid|file|ulidd|ulfn|fileURL|fileIcon|fileName|fileLinkedIcon):([^#]+)#/i', $body, $matches)) {
        $replacement=NULL;
        array_shift($matches);
        list($type, $id) = $matches;
        switch ( $type )  {
            case 'ulid':
                // DEPRECATED
            case 'file':
                //$replacement = "index.php?module=uploads&func=download&fileId=$id";
                $list = xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileId' => $id));
                $replacement = xarTplModule('uploads', 'user', 'attachment-list',
                                             array('Attachments' => $list,
                                                   'style' => 'transform'));
                break;
            case 'ulidd':
                // DEPRECATED
                //$replacement = "index.php?module=uploads&func=download&fileId=$id";
                $replacement = xarModAPIFunc('uploads','user','showoutput',
                                             array('value' => $id));
                break;
            case 'ulfn': // ULFN is DEPRECATED
            case 'fileLinkedIcon':
                $list = xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileId' => $id));
                $replacement = xarTplModule('uploads', 'user', 'attachment-list',
                                             array('Attachments' => $list));
                break;
            case 'fileIcon':
                $file = xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileId' => $id));
                $file = end($file);
                $replacement = $file['mimeImage'];
                break;
            case 'fileURL':
                $file = xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileId' => $id));
                $file = end($file);
                $replacement = $file['fileDownload'];
                break;
            case 'fileName':
                $file = xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileId' => $id));
                $file = end($file);
                $replacement = $file['fileName'];
                break;
            default:
                $body = xarML("The text '#(1)' is not a valid replacement placeholder","#$type:$id#");
                return $body;
        }

        $body = preg_replace("/#$type:$id#/", $replacement, $body);
    }

    return $body;
}
?>
