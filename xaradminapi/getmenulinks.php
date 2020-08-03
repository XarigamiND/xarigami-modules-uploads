<?php
/**
 * Items for Admin Menu
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Uploads Module
 * @copyright (C) 2007,2008,2009 2skies.com 
 * @link http://xarigami.com
 */
/**
 * utility function pass individual menu items to the admin menu
 *
 * @return array containing the menulinks for the main menu items.
 */
function uploads_adminapi_getmenulinks()
{
    static $menulinks = array();
    if (isset($menulinks[0])) {
        return $menulinks;
    }
    if (xarSecurityCheck('EditUploads',0)) {
     $menulinks[] = Array('url'   => xarModURL('uploads','admin','view'),
                             'title' => xarML('View All Files'),
                             'label' => xarML('View Files'),
                             'active' => array('view',
                                              'main'),                             
                             );
                             
                             
        $menulinks[] = Array('url'   => xarModURL('uploads','admin', 'get_files'),
                             'title' => xarML('Add a File'),
                             'label' => xarML('Add File'),
                             'active' => array('get_files',
                                               'addfile-status',
                                               'purge_rejected'),                             
                             
                             );
         }
    if (xarSecurityCheck('AdminUploads',0)) {                             
        $menulinks[] = Array('url'   => xarModURL('uploads','admin','assoc'),
                             'title' => xarML('View All Known File Associations'),
                             'label' => xarML('View Associations'),
                             'active' => array('assoc'),                             
                             );
   

        $menulinks[] = Array('url'   => xarModURL('uploads','admin','modifyconfig'),
                             'active' => array('modifyconfig'),
                             'title' => xarML('Edit the Uploads Configuration'),
                             'label' => xarML('Modify Config')
                             );
        /*                     
        $menulinks[] = Array('url'   => xarModURL('uploads','admin','overview'),
                             'title' => xarML('Introduction on handling this module'),
                             'label' => xarML('Overview'),
                             'active' => array('overview')                            
                );
        */
    }
    return $menulinks;
}
?>
