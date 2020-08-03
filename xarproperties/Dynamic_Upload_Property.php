<?php
/**
 * Purpose of File
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Uploads Module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com
 */
/* Include parent class */
sys::import('modules.dynamicdata.class.properties');
/**
 * Class to handle file upload properties
 *
 * @package dynamicdata
 */
class Dynamic_Upload_Property extends Dynamic_Property
{
    public $size = 40;
    public $xv_max_file_size    = 1000000; //bytes maxsize
    public $xv_multiple    = TRUE;
    public $multiple    = TRUE; //backward compat
    public $xv_methods = array();// Holds GUI in checkbox list format

    public $methods = array('trusted'  => false,    //in format for backward compatibility of uploads
                            'external' => false,
                            'upload'   => false,
                            'stored'   => false
                              );
    public $xv_basedir  = null;
    public $xv_file_ext = 'gif,jpg,jpeg,png,bmp,pdf,doc,txt,zip,gzip';
    public $xv_importdir = null;
    public $xv_style    = 'icon';
    public $layout      = 'default';
    public $tplmodule   = 'uploads';
    public $filepath    = 'modules/uploads/xarproperties';
    public $xv_display          = false;
    public $xv_display_width    = 60; //width of a displayed image on input
    public $xv_allow_duplicates     = 2;    //overwrite old files
    public $xv_override          = false;
    // this is used by Dynamic_Property_Master::addProperty() to set the $object->upload flag
    public $upload = true;
       //image options
    public $xv_scale                = NULL; //100%
    public $xv_resize_width         = NULL;
    public $xv_resize_height        = NULL;
    public $xv_output_width         = '';
    public $xv_classname            = '';
    public $xv_output_height        = '';
    public $xv_output_units         = 'px';
    /**
     * Initiate the Upload property
     * Constructor
     */
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'uploads';
        $this->template = 'uploads';
        $this->filepath = 'modules/uploads/xarproperties';

        //set fall back defaults
        $basedir    = xarModGetVar('uploads', 'path.uploads-directory');
        $importdir  = xarModGetVar('uploads', 'path.imports-directory');
        if (!isset($this->xv_basedir) || empty($this->xv_basedir)) $this->xv_basedir =  $basedir;
        if (!isset($this->xv_importdir)) $this->xv_importdir = $importdir ;
        $this->xv_max_file_size  = isset($this->xv_max_file_size) ?$this->xv_max_file_size: xarModGetVar('uploads', 'file.maxsize');

        //set the methods - ensure $this->xv_methods in case modules is hooked later

        if (!isset($this->xv_methods) || empty($this->xv_methods))
        {
             $defaultmethods = array(
                'trusted'  => xarModGetVar('uploads', 'dd.fileupload.trusted')  ? TRUE : FALSE,
                'external' => xarModGetVar('uploads', 'dd.fileupload.external') ? TRUE : FALSE,
                'upload'   => xarModGetVar('uploads', 'dd.fileupload.upload')   ? TRUE : FALSE,
                'stored'   => xarModGetVar('uploads', 'dd.fileupload.stored')   ? TRUE : FALSE
            );
            foreach($defaultmethods as $k => $v) {
                if ($v === TRUE) {
                     $this->xv_methods[] =  $k;
                }
            }
        }
        foreach($this->xv_methods as $k) {
            if (in_array($k,$this->xv_methods)) {
             $this->methods[$k] =  TRUE;
            } else {
             $this->methods[$k] =  FALSE;
            }
         }
           $this->xv_file_ext = isset($this->xv_file_ext)?trim($this->xv_file_ext):'';
    }
    /**
     * Check the input into the uploads property
     *
     * This function will see if we have a value as input. If the value is set,
     *  it is parsed to the validate function
     * @param string name
     * @param mixed value
     */
    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }

        return $this->validateValue($value);
    }
    /**
     * Validate the value entered
     * @param mixed value
     */
    function validateValue($value = null)
    {
        //we cannot validate value for empty here as the value is not yet determined with uploads
       // if (!parent::validateValue($value)) return false;

        // convert old Upload values if necessary
        if (!isset($value)) {
            $value = $this->getNumValue();
        }
        if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_'.$this->id;
        }

        // retrieve new value for preview + new/modify combinations
        if (xarVarIsCached('DynamicData.Upload',$name)) {
            $this->value = xarVarGetCached('DynamicData.Upload',$name);
            return true;
        }

        $paths =  $this->getUploadDirInfo();
        $newbasedir = isset($paths['basedir']) ? $paths['basedir'] : '';
        $newbasepath = isset($paths['basepath']) ? $paths['basepath'] : '';
        $fullpath = $newbasepath.$newbasedir;

        // set override for the upload/import paths if necessary
        if (!empty( $newbasedir) || !empty($this->xv_importdir)) {
            $override = array();
            if (!empty( $newbasedir )) {
                $override['upload'] = array('path' =>  $newbasedir );
            } elseif (!empty( $newbasepath )) {
                 $override['upload'] = array('path' =>  $newbasepath );
            }
            if (!empty($this->xv_importdir)) {
                $override['import'] = array('path' => $this->xv_importdir);
            }
        } else {
            $override = null;
        }

        $display = $this->xv_display;
        $width= $this->xv_display_width;
        $extensions = $this->xv_file_ext;

        try {
            $return = xarModAPIFunc('uploads','admin','validatevalue',
                                array('id' => $name, // not $this->id
                                      'value' => $value,
                                      // pass the module id, item type and item id (if available) for associations
                                      'moduleid' => $this->_moduleid,
                                      'itemtype' => $this->_itemtype,
                                      'itemid'   => !empty($this->_itemid) ? $this->_itemid : null,
                                      'multiple' => $this->xv_multiple,
                                      'format'   => 'upload',
                                      'extensions'=> $extensions,
                                      'methods'  => $this->methods,
                                      'override' => !isset($override)?$this->xv_override:$override,
                                      'allow_duplicates' => !isset($allow_duplicates)?$this->xv_allow_duplicates:$allow_duplicates,
                                      'maxsize'  => $this->xv_max_file_size,
                                      'display'  => isset($display)?$display:$this->xv_display,
                                      'width'   => isset($width)?$width: $this->xv_display_width));

        } catch (Exception $e) {
            $this->invalid  =  $e->getMessage();
            $this->value = null;
            return false;

        }

        if (!isset($return) || !is_array($return) || count($return) < 2) {
            $this->value = null;
            return false;
        }
        if ($this->xv_allowempty != 1 && (!isset($return[1]) || empty($return[1]))) {
            $thename = !empty($this->label)?$this->label:$this->name;
            $this->invalid = xarML('#(1) cannot be empty', $thename);
            $this->value = null;
           return false;
        }
        if (empty($return[0])) {
            $this->value = null;
            $this->invalid = xarML('value');
            return false;
        } else {
            if (empty($return[1])) {
                $this->value = '';
            } else {
                $this->value = $return[1];
            }
            // save new value for preview + new/modify combinations
            xarVarSetCached('DynamicData.Upload',$name,$this->value);
            return true;
        }
    }

    /**
     * Show the input form
     * @param array args
     * @return mixed This function will show the input form via the uploads_admin_showinput function
     */
    function showInput(Array $data = array())
    {
        extract($data);

        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }

        // convert old Upload values if necessary
        if (!isset($value)) {
            $value = $this->getNumValue();
        }
        $data['display']   = isset($display) ?  $display: $this->xv_display;
        $data['width']  = isset($width) ?  $width: $this->xv_display_width;
        // inform anyone that we're showing a file upload field, and that they need to use
        // <form ... enctype="multipart/form-data" ... > in their input form
        xarCoreCache::setCached('Hooks.dynamicdata', 'withupload', 1);
        //first see if we have  passed in base dir or base path
        if (isset($data['basedir'])) $this->xv_basedir = $data['basedir'];
         if (isset($data['basepath'])) $this->xv_basepath = $data['basepath'];
        //ensure we are working with expanded vars in file names and normalised paths
        $paths = $this->getUploadDirInfo();
        $newbasedir = isset($paths['basedir']) ? $paths['basedir'] : '';
        $newbasepath = isset($paths['basepath']) ? $paths['basepath'] : '';
        $importpaths = $this->getUploadDirInfo('importdir');
        // set override for the upload/import paths if necessary
        if (!empty($newbasedir) || !empty($this->xv_importdir)) {
            $override = array();
            if (!empty($newbasedir)) {
                $override['upload'] = array('path' => $newbasedir);
            } elseif (!empty($newbasepath)) {
                  $override['upload'] = array('path' => $newbasepath);
            }
            if (!empty($this->xv_importdir)) {
                $override['import'] = array('path' => $this->xv_importdir);
            }
        } else {
            $override = null;
        }
        $data['value']  = $value;
        $data['id']     = $name;
        $data['multpiple']  = !isset($multple)?$this->xv_multiple: $multiple;
        $data['methods']    = $this->methods;
        $data['extensions'] = isset($extensions)? $extensions: $this->xv_file_ext;
        $data['override']   = !isset($override)?$this->xv_override: $override;
        $data['invalid']    = $this->invalid;
        $data['format']     = 'upload';
        $data['basedir']    = $newbasedir;
        $data['basepath']   = $newbasepath;
        return xarModAPIFunc('uploads','admin','showinput',$data);
        //return parent::showInput($data);
    }
    /**
     * Show the output: a link to the file
     */
    function showOutput(Array $data = array())
    {
        extract($data);
        // convert old Upload values if necessary
        if (!isset($value)) {
            $value = $this->getNumValue();
        }

        $paths = $this->getUploadDirInfo();
        $newbasedir = isset($paths['basedir']) ? $paths['basedir'] : '';
        $newbasepath = isset($paths['basepath']) ? $paths['basepath'] : '';
        $importpaths = $this->getUploadDirInfo('importdir');
        // set override for the upload/import paths if necessary
        if (!empty($newbasedir) || !empty($this->xv_importdir)) {
            $override = array();
            if (!empty($newbasedir)) {
                $override['upload'] = array('path' => $newbasedir);
            } elseif (!empty($newbasepath)) {
                  $override['upload'] = array('path' => $newbasepath);
            }
            if (!empty($this->xv_importdir)) {
                $override['import'] = array('path' => $this->xv_importdir);
            }
        } else {
            $override = null;
        }
        if (isset($data['style'])) $this->xv_style = $data['style'];
        $data['value'] = $value;
        $data['format'] = 'upload';
        $data['multiple'] = !isset($multiple)? $this->xv_multiple:$multiple;
        $data['style'] = !isset($style)? $this->xv_style:$style;
        $data['class'] = isset($class)? $class:$this->xv_classname;
        $data['output_width'] = isset($output_width)?$output_width: $this->xv_output_width;
        $data['output_height'] = isset($output_height)?$output_height: $this->xv_output_height;
        $data['output_units'] = isset($output_units)?$output_units: $this->xv_output_units;
      //  $data['template'] = isset($template)?$template:$this->template;
          return xarModAPIFunc('uploads','user','showoutput',$data);
        //return parent::showOutput($data);
    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     * (keep this for compatibility with old Uploads values)
     *
     * @return mixed the value for the property
     */
    function getNumValue()
    {
        $value = $this->value;

        if (empty($value)) {
            return $value;
        // For current values when DD stored the ULID
        } elseif ( is_numeric($value) ) {
            $ulid = ";$value";
        // For old values, pull the ULID from the URL that is stored
        } elseif (strstr($value, 'ulid=')) {
            preg_match('/ulid=([0-9]+)/',$value,$reg);
            $ulid = ";$reg[1]";
        // For new values when DD stores a ;-separated list
        } elseif (strstr($value, ';')) {
            $ulid = $value;
        }
        if (empty($ulid)) {
            $ulid = NULL;
        }
        return $ulid;
    }

    /**
     * Parse the listing of validation options
     * The function sets the options in return
     * @param string validation rules
     */
     /*
    function parseValidation($validation = '')
    {
        list($multiple, $methods, $basedir, $importdir, $display) = xarModAPIFunc('uploads', 'admin', 'dd_configure', $validation);

        $this->multiple = $multiple;
        $this->methods = $methods;
        $this->basedir = $basedir;
        $this->importdir = $importdir;
        $this->maxsize = xarModGetVar('uploads', 'file.maxsize');
        $this->display = $display;

    }
    */
  /* This function returns a serialized array of validation options specific for this property
     * The validation options will be combined with global validation options so only specific should be defined here
     * These validation options can be inherited  if necesary
     */
    public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {
            $parentvalidation = parent::getBaseValidationInfo();
            $methodargs = array( array('id'=>'trusted',  'name' =>'trusted'),
                                 array('id'=>'external', 'name' =>'external'),
                                 array('id'=>'upload',  'name' =>'upload'),
                                 array('id'=>'stored',  'name' =>'stored')
                               );
             $allowduppropargs = array('options' =>array(
                                                    array('id'=>0,  'name' =>xarML('Not allowed')),
                                                    array('id'=>1,  'name' =>xarML('Create a new version')),
                                                    array('id'=>2,  'name' =>xarML('Overwrite existing file'))
                                                      )
                                                      );

            $validations = array(
                                    'xv_basedir'       =>  array(  'label'=>xarML('Base directory'),
                                                                'description'=>xarML('A directory relative to index.php, containing select options for this field.'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>0,
                                                                'ctype'=>'definition',
                                                                'configinfo'    => xarML("{user} replaced by 'uname' of user uploading - var/uploads/{user} expands to var/uploads/myusername_123")
                                                                ),
                                    'xv_importdir'       =>  array(  'label'=>xarML('Import trusted directory'),
                                                                'description'=>xarML('A directory relative to index.php, for import of trusted file uploads.'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>0,
                                                                'ctype'=>'definition',
                                                               'configinfo'    => xarML("{user} replaced by 'uname' of user importing - var/imports/{user} expands to var/imports/myusername_123 ")
                                                                ),
                                   'xv_display' =>  array(  'label'=>xarML('Image on input?'),
                                                                'description'=>xarML('Display the image on input selection?'),
                                                                'propertyname'=>'checkbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'display'),
                                    'xv_allow_duplicates' => array(  'label'=>xarML('Allow duplicates?'),
                                                                'description'=>xarML('Overwrite, create a new version or disallow duplicate filenames uploaded.'),
                                                                'propertyname'=>'dropdown',
                                                                'propargs' => $allowduppropargs,
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'validation'),

                                    'xv_methods' =>  array(  'label'=>xarML('Allowed methods'),
                                                                'description'=>xarML('Allowed methods for file selection'),
                                                                'propertyname'=>'checkboxlist',
                                                                'propargs' => array('options'=>$methodargs),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'),
                                    'xv_multiple' =>  array(  'label'=>xarML('Allow multiple'),
                                                                'description'=>xarML('Allow multiple uploads'),
                                                                'propertyname'=>'dropdown',
                                                                'propargs' => array('options'=>array( //jojo - why use a dropdown? TODO: convert to checkbox?
                                                                                                array('id'=>0, 'name'=>xarML('No')),
                                                                                                 array('id'=>1, 'name'=>xarML('Yes')),
                                                                                                )
                                                                            ),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition'),
                                    'xv_file_ext'       =>  array('label'=>xarML('File extensions'),
                                                                'description'=>xarML('A list of allowable file extensions separated by commas'),
                                                                'propertyname'=>'textbox',
                                                                'ignore_empty'  =>1,
                                                                'ctype' =>'validation'),
                                     'xv_scale'   =>  array('label'=>xarML('Image scale'),
                                                                'description'=>xarML('Scale to this size and save on upload'),
                                                                'propertyname'=>'floatbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('% [Image upload only]'),
                                                                'ctype' =>'definition'),
                                    'xv_resize_width'   =>  array('label'=>xarML('Resize width'),
                                                                'description'=>xarML('Resize to this width and save on upload'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('pixels [Image upload only]'),
                                                                'ctype' =>'definition'),
                                    'xv_resize_height'   =>  array('label'=>xarML('Resize height'),
                                                                'description'=>xarML('Resize to this height and save on upload'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('pixels [Image upload only]'),
                                                                'ctype' =>'definition'),
                                    'xv_output_width'   =>  array('label'=>xarML('Display width'),
                                                                'description'=>xarML('Resize to this width on display'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('[Image upload only]'),
                                                                'ctype' =>'display'),
                                    'xv_output_height'   =>  array('label'=>xarML('Display height'),
                                                                'description'=>xarML('Resize to this height on display'),
                                                                'propertyname'=>'integerbox',
                                                                'propargs' => array('display_size'=>10),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('[Image upload only]'),
                                                                'ctype' =>'display'),
                                    'xv_output_units'   =>  array('label'=>xarML('Display units'),
                                                                'description'=>xarML('Resize to this height on display'),
                                                                'propertyname'=>'radio',
                                                                 'propargs' => array('options'=>array(  array('id' =>'px',  'name'=>xarML('px')),
                                                                                                        array('id' =>'%',   'name'=>xarML('%')),
                                                                                                        array('id' =>'em',  'name'=> xarML('em')),
                                                                                                    )
                                                                            ),
                                                                'ignore_empty'  =>1,
                                                                'configinfo'    => xarML('[Image upload only]'),
                                                                'ctype' =>'display'),
                                    'xv_style'       =>  array('label'=>xarML('Output type'),
                                                                'description'=>xarML('Mime download, image display (if available), or simple array'),
                                                                'propertyname'=>'radio',
                                                                 'propargs' => array('options'=>array(  array('id' =>'',        'name'=>xarML('Data array')),
                                                                                                        array('id' =>'icon',     'name'=>xarML('Download link')),
                                                                                                        array('id' =>'transform', 'name'=> xarML('Transform to image display')),
                                                                                                    )
                                                                            ),
                                                                'ignore_empty'  =>1,
                                                                'ctype' =>'display'),
                                    );


              $validationarray = array_merge($parentvalidation,$validations);
        }
         return $validationarray;
    }

    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
    function getBasePropertyInfo()
    {
        $args = array();
        $validation = $this->getBaseValidationInfo();
        $baseInfo = array(
                            'id'         => 105,
                            'name'       => 'uploads',
                            'label'      => 'Uploads',
                            'format'     => '105',
                            'validation' => serialize($validation),
                            'source'     => 'hook module',
                            'dependancies' => '',
                            'requiresmodule' => 'uploads',
                             'filepath'    => 'modules/uploads/xarproperties',
                            'aliases' => '',
                            'args'         =>  serialize($args),
                            // ...
                           );
        return $baseInfo;
    }

      public function getUploadDirInfo($dirtype = 'basedir')
    {

        if (!isset($dirtype)) $dirtype = 'basedir';

        if ($dirtype = 'basedir') {
            if (isset($this->xv_basedir))  {
               $prop_path = $this->xv_basedir;//often the bit below webroot but may be absolute
                // If the base dir supplied contains {var} then expand that
                // We discard anything that comes before '{var}' and anything up to the next '/' after it.
                // We expect {var} to be used like this: {var}/custom_path
                if (preg_match('/{var}/', $prop_path)) {
                  $prop_path = preg_replace('#{var}[^/]*/#', sys::varpath() . '/', $prop_path);
                  $this->xv_basedir = $prop_path ;
                }
            } else {
                 // No base directory supplied, so default to '{var}/uploads', with no basepath.
                $this->xv_basepath = '';
                $this->xv_basedir = 'var/uploads';
            }
            // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/default/images
            if (!empty($this->xv_basedir) && preg_match('/\{theme\}/',$this->xv_basedir)) {
                $curtheme = xarTplGetThemeDir();
                $this->xv_basedir = preg_replace('/\{theme\}/',$curtheme,$this->xv_basedir);
            }
            if (!empty($this->xv_basedir) && preg_match('/\{user\}/',$this->xv_basedir)) {

                $uname = xarUserGetVar('uname');
                $uid = xarUserGetVar('uid');
                $udir = $uname . '_' . $uid;

                $this->xv_basedir = preg_replace('/\{user\}/',$udir,$this->xv_basedir);
            }
            $paths = sys::getBaseDirs($this->xv_basedir) ;
        } elseif ($dirtype = 'importdir') {
            // This one for uploads-hooked operation only.
            if (!empty($this->xv_importdir) && preg_match('/\{user\}/',$this->xv_importdir)) {

                $uname = xarUserGetVar('uname');
                $uid = xarUserGetVar('uid');
                $udir = $uname . '_' . $uid;
                $this->xv_importdir = preg_replace('/\{user\}/',$udir,$this->xv_importdir);
            }
            $paths = sys::getBaseDirs($this->xv_importdir) ;
        }

        return $paths;

    }
}
?>
