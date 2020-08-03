<?php
/**
 * Overview for Uploads
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Uploads Module
 * @copyright (C) 2007,2008,2009 2skies.com 
 * @link http://xarigami.com
 */

/**
 * Overview displays standard Overview page
 */
function uploads_admin_overview()
{
    $data=array();

    //common admin menu
    $data['menulinks'] = xarModAPIFunc('uploads','admin','getmenulinks');
    
    //just return to main function that displays the overview
    return xarTplModule('uploads', 'admin', 'main', $data, 'main');
}

?>