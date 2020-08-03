<?php
/**
 * Upload input field
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Uploads Module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com
 */
/**
 * show output for uploads module (used in DD properties)
 *
 * @param string $args['value']    Optional, the current value(s), default is Null
 * @param string $args['format']   Optional, specifying 'fileupload', 'textupload' or
                                   'upload' as output format, default is fileupload
 * @param bool   $args['multiple'] Optional, Deliver several files, default is false
 * @param string $args['style']    Optional, specifing 'icon' gives templated
 *                                 output, default is Null
 *
 * @return array or string containing the uploads output, default is array
 */
function uploads_userapi_showoutput($args)
{

    extract($args);
    if (empty($value)) {
        $value = null;
    }
    if (empty($format)) {
        $format = 'fileupload';
    }
    if (empty($multiple)) {
        $multiple = false;
    }
    $data = array();

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
    // The explode will create an empty indice,
    // so we get rid of it with array_filter :-)
    $value = array_filter(explode(';', $value));
    if (!$multiple) {
        $value = array(current($value));
    }
    // make sure to remove any indices which are empty
    $value = array_filter($value);
    $return = '';
    if (!is_array($value) && empty($value)) {
        //jojo - eh? what's going on, commenting out array return
        // $return = array();
        return '';
    }

    // FIXME: Quick Fix - Forcing return of raw array of fileId's with their metadata for now
    if (is_array($value) && empty($value)) return array();

    if (isset($style)) {
        $data['style'] = $style;
    }

    //image display only
    if (isset($output_width)) $data['output_width'] = $output_width;
     if (isset($output_height)) $data['output_height'] = $output_height;
    if (isset($output_units)) $data['output_units'] = $output_units;
    $data['class'] = isset($class)?$class:'';
    if (isset($style) && ($style == 'icon' || $style=='transform')) {
        if (is_array($value) && count($value)) {
            $data['Attachments'] = xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileId' => $value));
        } else {
            $data['Attachments'] = '';
        }

        $data['format'] = $format;
        $info= xarTplModule('uploads', 'user', 'attachment-list', $data, NULL);
        $return = $info;
    } else {
        // return a raw array for now

        $data = xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileId' => $value));
        $return = $data;
    }
    return $return;
}

?>
