<?php

/**
 *
 *
 * @package FFD_Integration_Helpers
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main  UI Class.
 *
 * @class FFD_Integration_Helpers
 */
class FFD_Integration_Helpers {

    /**
	 * The single instance of the class.
	 *
	 * @var FFD_Integration_Helpers
	 * @since 2.1
	*/
    protected static $_instance = null;


    /**
	 * Main FFD_Integration_Helpers Instance.
	 *
	 * Ensures only one instance of FFD_Integration_Helpers is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see FFD()
	 * @return FFD_Integration_Helpers - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }




    /**
	 * FFD_Integration_Helpers Constructor.
	 */
	public function __construct() {
        
    
    }


	public function format_bytes($bytes, $precision = 2) { 
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    
        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 
    
        // Uncomment one of the following alternatives
        // $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow)); 
    
        return round($bytes, $precision) . ' ' . $units[$pow]; 
    } 


     # Simple loop copy
    public  function array_object($array) {
        $object = new stdClass();
        if( !empty($array)){
            foreach ($array as $key => $value) {
                $object->$key = $value;
            }
        }
        return $object;
    }


    public  function object_array($object) {
        $array = array();
        if( !empty($object)){
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    # leveraging json_decode + json_encode
    public  function json_object($array) {
       
        $object = json_decode(json_encode($array), FALSE);

        return $object;
    }
	

	


}


/**
 * Main instance of FFD_Integration_Helpers.
 *
 * Returns the main instance of FFD_UI to prevent the need to use globals.
 *
 * @since  1.0
 * @return FFD_Integration_Helpers
 */
function FFD_Helpers() {
	return FFD_Integration_Helpers::instance();
}
FFD_Helpers();
