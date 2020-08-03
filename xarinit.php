<?php
/**
 * Initialisation functions for Uploads
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
 * initialise the module
 */
function uploads_init()
{

    // load the predefined constants
    xarModAPILoad('uploads', 'user');

    if(xarServerGetVar('SCRIPT_FILENAME')) {
        $base_directory = dirname(realpath(xarServerGetVar('SCRIPT_FILENAME')));
    } else {
        $base_directory = './';
    }
    xarModSetVar('uploads', 'path.uploads-directory',   'Change me to something outside the webroot');
    xarModSetVar('uploads', 'path.imports-directory',   'Change me to something outside the webroot');
    xarModSetVar('uploads', 'file.maxsize',            '10000000');
    xarModSetVar('uploads', 'file.delete-confirmation', TRUE);
    xarModSetVar('uploads', 'file.auto-purge',          FALSE);
    xarModSetVar('uploads', 'file.obfuscate-on-import', FALSE);
    xarModSetVar('uploads', 'file.obfuscate-on-upload', TRUE);
    xarModSetVar('uploads', 'path.imports-cwd', xarModGetVar('uploads', 'path.imports-directory'));
    xarModSetVar('uploads', 'dd.fileupload.stored',   TRUE);
    xarModSetVar('uploads', 'dd.fileupload.external', TRUE);
    xarModSetVar('uploads', 'dd.fileupload.upload',   TRUE);
    xarModSetVar('uploads', 'dd.fileupload.trusted',  TRUE);
    xarModSetVar('uploads', 'file.auto-approve', _UPLOADS_APPROVE_ADMIN);

    //TODO The filter options should not be set on init but dynamically on runtime
    $data['filters']['inverse']                     = FALSE;
    $data['filters']['mimetypes'][0]['typeId']      = 0;
    $data['filters']['mimetypes'][0]['typeName']    = xarML('All');
    $data['filters']['subtypes'][0]['subtypeId']    = 0;
    $data['filters']['subtypes'][0]['subtypeName']  = xarML('All');
    $data['filters']['status'][0]['statusId']       = 0;
    $data['filters']['status'][0]['statusName']     = xarML('All');
    $data['filters']['status'][_UPLOADS_STATUS_SUBMITTED]['statusId']    = _UPLOADS_STATUS_SUBMITTED;
    $data['filters']['status'][_UPLOADS_STATUS_SUBMITTED]['statusName']  = xarML('Submitted');
    $data['filters']['status'][_UPLOADS_STATUS_APPROVED]['statusId']     = _UPLOADS_STATUS_APPROVED;
    $data['filters']['status'][_UPLOADS_STATUS_APPROVED]['statusName']   = xarML('Approved');
    $data['filters']['status'][_UPLOADS_STATUS_REJECTED]['statusId']     = _UPLOADS_STATUS_REJECTED;
    $data['filters']['status'][_UPLOADS_STATUS_REJECTED]['statusName']   = xarML('Rejected');
    $filter['fileType']     = '%';
    $filter['fileStatus']   = '';

    $mimetypes =& $data['filters']['mimetypes'];
    $mimetypes += xarModAPIFunc('mime','user','getall_types');

    xarModSetVar('uploads','view.filter', serialize(array('data' => $data,'filter' => $filter)));
    unset($mimetypes);

    xarModSetVar('uploads', 'view.itemsperpage', 200);
    xarModSetVar('uploads', 'file.cache-expire', 0);
    xarModSetVar('uploads', 'file.allow-duplicate-upload', 0);

    // Get datbase setup
    $dbconn = xarDBGetConn();

    $xartable = xarDBGetTables();

    $file_entry_table = $xartable['file_entry'];
    $file_data_table  = $xartable['file_data'];
    $file_assoc_table = $xartable['file_associations'];

    xarDBLoadTableMaintenanceAPI();

    $file_entry_fields = array(
        'xar_fileEntry_id' => array('type'=>'integer', 'size'=>'big', 'null'=>FALSE,  'increment'=>TRUE,'primary_key'=>TRUE),
        'xar_user_id'      => array('type'=>'integer', 'size'=>'big', 'null'=>FALSE),
        'xar_filename'     => array('type'=>'varchar', 'size'=>128,   'null'=>FALSE),
        'xar_location'     => array('type'=>'varchar', 'size'=>255,   'null'=>FALSE),
        'xar_status'       => array('type'=>'integer', 'size'=>'tiny','null'=>FALSE,  'default'=>'0'),
        'xar_filesize'     => array('type'=>'integer', 'size'=>'big',    'null'=>FALSE),
        'xar_store_type'   => array('type'=>'integer', 'size'=>'tiny',     'null'=>FALSE),
        'xar_mime_type'    => array('type'=>'varchar', 'size' =>128,  'null'=>FALSE,  'default' => 'application/octet-stream'),
        'xar_extrainfo'    => array('type'=>'text')
    );


    // Create the Table - the function will return the SQL if it is successful or
    // raise an exception if it fails, in this case $sql is empty
    $query   =  xarDBCreateTable($file_entry_table, $file_entry_fields);
    $result  = $dbconn->Execute($query);

    $file_data_fields = array(
        'xar_fileData_id'  => array('type'=>'integer','size'=>'big','null'=>FALSE,'increment'=>TRUE, 'primary_key'=>TRUE),
        'xar_fileEntry_id' => array('type'=>'integer','size'=>'big','null'=>FALSE),
        'xar_fileData'     => array('type'=>'blob','size'=>'medium','null'=>FALSE)
    );

    $query  =  xarDBCreateTable($file_data_table, $file_data_fields);
    $result = $dbconn->Execute($query);

    $file_assoc_fields = array(
        'xar_fileEntry_id' => array('type'=>'integer', 'size'=>'big', 'null'=>FALSE),
        'xar_modid'        => array('type'=>'integer', 'size'=>'big', 'null'=>FALSE),
        'xar_itemtype'     => array('type'=>'integer', 'size'=>'big', 'null'=>FALSE, 'default'=>'0'),
        'xar_objectid'       => array('type'=>'integer', 'size'=>'big', 'null'=>FALSE, 'default'=>'0'),
    );

    $query   =  xarDBCreateTable($file_assoc_table, $file_assoc_fields);
    $result  = $dbconn->Execute($query);

    $instances[0]['header'] = 'external';
    $instances[0]['query']  = xarModURL('uploads', 'admin', 'privileges');
    $instances[0]['limit']  = 0;

    xarDefineInstance('uploads', 'File', $instances);

    xarRegisterMask('ViewUploads',  'All','uploads','File','All:All:All:All','ACCESS_READ');
    xarRegisterMask('AddUploads',   'All','uploads','File','All:All:All:All','ACCESS_ADD');
    xarRegisterMask('EditUploads',  'All','uploads','File','All:All:All:All','ACCESS_EDIT');
    xarRegisterMask('DeleteUploads','All','uploads','File','All:All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminUploads', 'All','uploads','File','All:All:All:All','ACCESS_ADMIN');

    /**
     * Register hooks
     */
    if (!xarModRegisterHook('item', 'transform', 'API', 'uploads', 'user', 'transformhook')) {
         $msg = xarML('Could not register hook');
         throw new DataNotFoundException(null,$msg);
    }
     xarLogMessage('UPLOADS: successfully created tables and masks, going to upgrade routine');
    //we have all the information to upgrade now and assuming people are at a minimum upgrade version 1.0.0
    return uploads_upgrade('1.0.0');
}

/**
 * upgrade the uploads module from an old version
 */
function uploads_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch($oldversion) {

        case '1.0.0':

            if (!xarModRegisterHook('item', 'waitingcontent', 'GUI',
                                   'uploads', 'admin', 'waitingcontent')) {
                return false;
            }
        case '1.1.0' :
            xarRegisterMask('CommentUploads','All','uploads','File','All:All:All:All','ACCESS_COMMENT');
       case '1.1.1' :
        case '1.1.2' : //current version for core 1.4.0
        case '1.1.3' : //upload directory and image display adjustments
       case '1.1.4' : //fix allow_duplicates variable and double slash on file directory
        default:
            return true;
    }

    return true;
}

/**
 * delete the uploads module
 */
function uploads_delete()
{
    xarModDelAllVars('uploads');
    xarRemoveMasks('uploads');
    xarRemoveInstances('uploads');

    /* Unregister each of the hooks that have been created */
    xarModUnregisterHook('item', 'transform', 'API', 'uploads', 'user', 'transformhook');

    if (!xarModUnregisterHook('item', 'waitingcontent', 'GUI',
                                   'uploads', 'admin', 'waitingcontent')) {
        return false;
    }

    // Get database information

    $dbconn = xarDBGetConn();
    $xartables      = xarDBGetTables();

    //Load Table Maintainance API
    xarDBLoadTableMaintenanceAPI();

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartables['file_entry']);
    if (empty($query))
        return; // throw back

    // Drop the table and send exception if returns false.
    $result = $dbconn->Execute($query);

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartables['file_data']);
    if (empty($query))
        return; // throw back

    // Drop the table and send exception if returns false.
    $result = $dbconn->Execute($query);

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartables['file_associations']);
    if (empty($query))
        return; // throw back

    // Drop the table and send exception if returns false.
    $result = $dbconn->Execute($query);
    return true;
}

?>