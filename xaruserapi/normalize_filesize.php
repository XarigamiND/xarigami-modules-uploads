<?php
/**
 * Purpose of File
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Uploads Module
 * @author Uploads Module Development Team
 */
function uploads_userapi_normalize_filesize( $args )
{

    if (is_array($args)) {
        extract($args);
    } elseif (is_numeric($args)) {
        $fileSize = $args;
    } else {
        return array('long' => 0, 'short' => 0);
    }

    $size = $fileSize;

    $range = array('', 'KB', 'MB', 'GB', 'TB', 'PB');

    for ($i = 0; $size >= 1024 && $i < count($range); $i++) {
        $size /= 1024;
    }

    $short = round($size, 2).' '.$range[$i];

    return array('long' => number_format($fileSize), 'short' => $short);
}
?>
