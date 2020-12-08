<?php


require __DIR__ . '/libraries/php-liquid/vendor/autoload.php';


use Liquid\Liquid;
use Liquid\Template;


Liquid::set('INCLUDE_SUFFIX', '');
Liquid::set('INCLUDE_PREFIX', '');
//Liquid::Template.error_mode = :strict;


/* 
class LiquidTagGetPost extends AbstractBlock
{
    public function render(Context $context)
    {
        ffd_debug($this);
        return '';
    }
} */

final class FFD_Liquid {

    /**
	 * The single instance of the class.
	 *
	 * @var FFD_Liquid
	 * @since 2.1
	 */
    protected static $_instance = null;
    
    /**
	 * Liquid Template
	 *
	 * @var FFD_Liquid
	 * @since 2.1
	 */
	public $Template = null;
    
    


    /**
	 * Main FFD_Liquid Instance.
	 *
	 * Ensures only one instance of FFD_Liquid is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see FFD()
	 * @return FFD_Liquid - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }


    /**
	* FFD_Liquid Constructor.
	*/
	public function __construct() {
        
        try {
            $this->Template =  new Template();
            $this->custom_registers($this->Template);
        } catch (\Exception $ex) {
            
        }

    }

    public function tokenize($content){

        $tokens = null;
        try {
            
            $tokens = $this->Template->tokenize($content);
            
        } catch (Exception $ex) {
            
        }

        return $tokens;
    
    }

    public function parse($content){

        try {
            $this->Template->parse($content);
        } catch (Exception $ex) {
            
        }

        return null;
    
    }


    public function render($content, $data){

       
        try {
           
            $this->Template->parse($content);
            $content = $this->Template->render($data);
        } catch (Exception $ex) {
            
            return $ex->getMessage();
        }

        return $content;
    
    }


    public function render_content($content, $data){

       

        try {
            
            $liquid =  new Template();
            $liquid->parse($content);
        
            $content = $liquid->render($data);

        } catch (Exception $ex) {
           
        }

        return $content;
    
    }


    function custom_registers($template){

        

        include_once FFD_PLUGIN_PATH . '/includes/liquid-register/TagGetPost.php';

        $template->registerTag('getpost', 'TagGetPost');

        $template->registerFilter('money', function ($input, $decimals=0) {
            $input = (string) $input;
            $format = '%0.'.$decimals.'f';
            $input =  sprintf($format,$input);
            $input = preg_replace('/(\d)(?=(\d{3})+(?!\d))/', '\1,', $input);
            return $input;
        });

        $template->registerFilter('number', function($n){
            return preg_replace('/\D/', '', $n);
        });

        $template->registerFilter('number_with_decimal', function($n){
            return preg_replace('/[^0-9\.]/', '', $n);
        });

        

        $template->registerFilter('phone', function($input){
            $input = preg_replace('/\D/', '', $input);
            return preg_replace('~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '($1) $2-$3', $input);
        });

        

        $template->registerFilter('getpost', function ($input) {

            $post = array();

            $atts = shortcode_parse_atts($input);

            if( !empty($atts) ){
                $atts['numberposts'] = 1;
                $posts = get_posts($atts);

               

                if( !empty($posts) ){
                    $post = $posts[0];
                    $post = (array) $post;

                    $featured_img_url = get_the_post_thumbnail_url($post['ID'], 'full');
                    $featured_img_medium = get_the_post_thumbnail_url($post['ID'], 'medium');
                    
                    

                    $post['image'] = $featured_img_url;
                    $post['image_medium'] = $featured_img_medium;

                    $media = ffd_get_field('ffd_media', $post['ID']);
                    if( !empty($media) ){
                        $media = current( (array) $media);
                        if ($media){
                            $post['ffd_image'] = $media;
                        }
                    }

                    $meta = get_post_meta($post['ID']);
                    foreach($meta as $key => $value){
                       
                        $value = ffd_get_field($key, $post['ID']);
                        if( !empty($value) ){
                            $post['meta'][$key] = ffd_get_field($key, $post['ID']);
                        }
                        
                    }


                    if(isset($_GET['debug_getpost_filter']) ){
                        ffd_debug($post, true);
                    }
                    
                }
            }

            
            return $post;
        });


        $template->registerFilter('getfield', function ($input) {

            $atts = shortcode_parse_atts($input);
            if(empty($atts['ID']) ){
                global $post;
                $atts['ID'] = $post->ID;
            }
            $field = (string) ffd_get_field($atts['field'], $atts['ID']);
            return $field;
        });

        $template->registerFilter('usermeta', function ($input) {

            $atts = shortcode_parse_atts($input);
            if(empty($atts['ID']) ){
                
                $atts['ID'] = get_current_user_id();
            }
            $field = (string) ffd_get_field($atts['field'], $atts['ID']);
            return $field;
        });


        $template->registerFilter('getuser', function ($field=null, $value=null) {

            $user = null;
            $user_data = array();

           if(empty($field) ){
               $user = wp_get_current_user();
           } else {
                $user = get_user_by($field, $value);
           }

           if( $user && !is_wp_error($user) ){
                $user_data['ID'] = $user->ID;
                $user_data['email'] = $user->user_email;
                $user_data['login'] = $user->user_login;
                $user_data['first_name'] = $user->first_name;
                $user_data['last_name'] = $user->last_name;
                $user_data['display_name'] = $user->display_name;
           }

           return $user_data;
        });

        $template->registerFilter('format_number', function ($input, $decimals=0, $decimal_sep='.', $thousand_sep=',') {
            $input = (float) $input;
            return number_format($input, $decimals, $decimal_sep, $thousand_sep);
        });

        $template->registerFilter('script_tag', function ($input) {
            
            $js = "<!-- FFD Integration Script Tag -->\n<script type=\"text/javascript\" src=\"$input\"></script>\n";
            return $js;
        });


        $template->registerFilter('append_if', function ($input, $string) {
            if( empty($input) )
                return '';

            return $input . $string;
            
        });


        $template->registerFilter('preppend_if', function ($input, $string) {
            if( empty($input) )
                return '';
                
            return $string . $input;
            
        });

        $template->registerFilter('split_if', function ($input, $pattern){
            $value = explode($pattern, $input);
            return ( count($value) > 0  ? $value : null );
        });


        $template->registerFilter('appendattr', function ($input, $charlimit=50) {

            if( empty($input) )
                return '';
            
            $charlimit = intval($charlimit);
            if( !$charlimit || $charlimit < 1 ){
                $charlimit = 50;
            }

            $atts = '';
            if( !empty($input) && is_array($input)  ){
                foreach($input as $key => $value ){
                    if( strlen($value) <= $charlimit ){
                        $atts .= ' data-'.$key.'="'.$value.'" ';
                    }
                }
            }
            
            $atts .=' data-listing_keys="' . implode("|", array_keys($input)) . '"';
            $atts .=' data-listing_data="' . implode("|", $input) . '"';

            return $atts;
            
        });


        $template->registerFilter('wp_is', function ($input, $value=null) {

            if( 'logged_in' == $input && is_user_logged_in() ) return 1;
            if( 'page' == $input && is_page() ) return 1;
            if( 'post' == $input && is_post() ) return 1;
            if( 'single' == $input && is_single() ) return 1;
            if( 'singular' == $input && is_singular() ) return 1;

            
            
            return '';
        });


        

    }



}


/**
 * Main instance of FFD_Liquid.
 *
 * Returns the main instance of FFD_Liquid to prevent the need to use globals.
 *
 * @since  1.0
 * @return FFD_Integration
 */
function FFD_Liquid() {
	return FFD_Liquid::instance();
}
