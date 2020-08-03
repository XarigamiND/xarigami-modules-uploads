<?php
/**
 * Purpose of File
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
 * The main administration function
 * This function redirects the user to the view function
 * @return bool true
 */
function uploads_admin_main()
{
    // Security Check
    if (!xarSecurityCheck('EditUploads')) return;
      xarResponseRedirect(xarModURL('uploads', 'admin', 'view'));
    // success
    return true;
}

?>