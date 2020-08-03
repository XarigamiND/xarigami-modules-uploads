<?php
/**
 * Purpose of File
 *
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Uploads Module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com
 */
/**
 *
 * The "media-types" directory contains a subdirectory for each content
 * type and each of those directories contains a file for each content
 * subtype.
 *
 *                                |-application-
 *                                |-audio-------
 *                                |-image-------
 *                  |-media-types-|-message-----
 *                                |-model-------
 *                                |-multipart---
 *                                |-text--------
 *                                |-video-------
 *
 *    URL = ftp://ftp.isi.edu/in-notes/iana/assignments/media-types
 */

$modversion['name']         = 'uploads';
$modversion['directory']    = 'uploads';
$modversion['id']           = '666';
$modversion['version']      = '1.1.4'; // long jump to current uploads_guimods version
$modversion['displayname']  = 'Uploads';
$modversion['description']  = 'Upload/Download File Handler';
$modversion['help']         = 'docs/installation.txt';
$modversion['changelog']    = 'docs/changes.txt';
$modversion['official']     = 1;
$modversion['author']       = 'Xaraya and Xarigami module developers';
$modversion['homepage']     = 'http://xarigami.com';
$modversion['admin']        = 1;
$modversion['user']         = 0;
$modversion['class']        = 'Complete';
$modversion['category']     = 'Content';
$modversion['dependency']   = array(999); // Mime module
$modversion['dependencyinfo']   = array(
                                    0 => array(
                                            'name' => 'core',
                                            'version_ge' => '1.4.0'
                                         ),
                                    999 => array(
                                            'name' => 'mime',
                                            'version_ge' => '1.2.0'
                                         )
                                  );
if (false) {
    xarML('Uploads');
    xarML('Upload/Download File Handler');
}
?>
