<?php

/**
 *
 *
 * @package FFD_Integration_Analytic
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main  Analytic Class.
 *
 * @class FFD_Integration_Analytic
 */
class FFD_Integration_Analytic {

	
	

    /**
	 * The single instance of the class.
	 *
	 * @var FFD_Integration
	 * @since 2.1
	*/
	protected static $_instance = null;
	protected $crawler_detect;
	protected $cookie;
	public $visit_counter;
	public $visiter;
	public $options;
	public $defaults = array(
		'general'	 => array(
			'post_types_count'		 => array( 'listing', 'property' ),
			'counter_mode'			 => 'php',
			'post_views_column'		 => true,
			'time_between_counts'	 => array(
				'number' => 24,
				'type'	 => 'hours'
			),
			'reset_counts'			 => array(
				'number' => 30,
				'type'	 => 'days'
			),
			'flush_interval'		 => array(
				'number' => 0,
				'type'	 => 'minutes'
			),
			'exclude'				 => array(
				'groups' => array(),
				'roles'	 => array()
			),
			'exclude_ips'			 => array(),
			'strict_counts'			 => false,
			'restrict_edit_views'	 => false,
			'deactivation_delete'	 => false,
			'cron_run'				 => true,
			'cron_update'			 => true
		),
		'display'	 => array(
			'label'				 => 'Post Views:',
			'post_types_display' => array( 'post' ),
			'page_types_display' => array( 'singular' ),
			'restrict_display'	 => array(
				'groups' => array(),
				'roles'	 => array()
			),
			'position'			 => 'after',
			'display_style'		 => array(
				'icon'	 => true,
				'text'	 => true
			),
			'link_to_post'		 => true,
			'icon_class'		 => 'dashicons-chart-bar'
		),
		'version'	 => '1.3.1'
	);



    /**
	 * Main FFD_Integration_Analytic Instance.
	 *
	 * Ensures only one instance of FFD_Integration_Analytic is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see FFD()
	 * @return FFD_Integration_Analytic - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }




    /**
	 * FFD_Integration_Search Constructor.
	 */
	public function __construct() {
		
		$this->options = get_option('ffd_analytic', $this->defaults);
		$this->visit_counter = get_option('ffd_visit_counter', array());
		if( !is_array($this->visit_counter) ){
			$this->visit_counter = array();
		}

		
        $this->includes();
        $this->init_hooks();
       
    
    }

	public function includes(){

		include_once( FFD_PLUGIN_PATH . '/includes/ffd/ffd-crawler-detect.php' );
		$this->crawler_detect = new FFD_Crawler_Detect();
	}

     /**
	 * Hook into actions and filters.
	 *
	 * @since 1.0
	 */
    public function init_hooks(){

		$enabled = get_option('ffd_listing_view_limit_enabled');
			

		if( $enabled === 'yes' ){ 
			add_action( 'wp', array( $this, 'check_post_php' ) );
		}
    }


	/**
	 * Check whether to count visit via PHP request.
	 */
	public function check_post_php() {
		// do not count admin entries
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
			return;

		// do we use PHP as counter?
		if ( $this->options['general']['counter_mode'] != 'php' )
			return;

		$post_types = $this->options['general']['post_types_count'];
		$listing_posttype = ffd_get_listing_posttype();
		$post_types = array_merge($post_types, array($listing_posttype));

		// whether to count this post type
		if ( empty( $post_types ) || ! is_singular( $post_types ) )
			return;

		$this->check_post( get_the_ID() );
	}


	/**
	 * Check whether to count visit.
	 * 
	 * @param int $id
	 */
	public function check_post( $id = 0 ) {
		// force check post in SHORTINIT mode
		if ( defined( 'SHORTINIT' ) && SHORTINIT ){
			//$this->check_cookie();
		}

		// get post id
		$id = (int) ( empty( $id ) ? get_the_ID() : $id );

		// get user id, from current user or static var in rest api request
		$user_id = get_current_user_id();

		// get user IP address
		$user_ip = (string) $this->get_user_ip();

		// empty id?
		if ( empty( $id ) )
			return;

		$this->visiter = array('post_id'=>$id, 'user_id'=>$user_id, 'user_ip'=>$user_ip);

		do_action( 'ffd_analytic_before_check_visit', $id, $user_id, $user_ip );

		// get ips
		$ips = $this->options['general']['exclude_ips'];

		// whether to count this ip
		if ( ! empty( $ips ) && filter_var( preg_replace( '/[^0-9a-fA-F:., ]/', '', $user_ip ), FILTER_VALIDATE_IP ) ) {
			// check ips
			foreach ( $ips as $ip ) {
				if ( strpos( $ip, '*' ) !== false ) {
					if ( $this->ipv4_in_range( $user_ip, $ip ) )
						return;
				} else {
					if ( $user_ip === $ip )
						return;
				}
			}
		}

		// get groups to check them faster
		$groups = $this->options['general']['exclude']['groups'];

		// whether to count this user
		if ( ! empty( $user_id ) ) {
			// exclude logged in users?
			if ( in_array( 'users', $groups, true ) )
				return;
			// exclude specific roles?
			elseif ( in_array( 'roles', $groups, true ) && $this->is_user_role_excluded( $user_id, $this->options['general']['exclude']['roles'] ) )
				return;
		// exclude guests?
		} elseif ( in_array( 'guests', $groups, true ) )
			return;

		// whether to count robots
		if ( in_array( 'robots', $groups, true ) && $this->crawler_detect->is_crawler() )
			return;

		$current_time = current_time( 'timestamp', true );

		/* // cookie already existed?
		if ( $this->cookie['exists'] ) {
			// post already viewed but not expired?
			if ( in_array( $id, array_keys( $this->cookie['visited_posts'] ), true ) && $current_time < $this->cookie['visited_posts'][$id] ) {
				// update cookie but do not count visit
				$this->save_cookie( $id, $this->cookie, false );

				return;
			// update cookie
			} else
				$this->save_cookie( $id, $this->cookie );
		} else {
			// set new cookie
			$this->save_cookie( $id );
		} */

		$count_visit = (bool) apply_filters( 'ffd_analytic_count_visit', true, $id );

		// count visit
		if ( $count_visit ) {
			// strict counts?
			// if ( $this->options['general']['strict_counts'] )
			// 	$this->save_ip( $id );

			return $this->count_visit( $id );
		} else
			return;
	}


	/**
	 * Count visit function.
	 * 
	 * @global object $wpdb
	 * @param int $id
	 * @return int $id
	 */
	private function count_visit( $id ) {

		return $this->visit_counter();

		global $wpdb;

		$cache_key_names = array();
		$using_object_cache = $this->using_object_cache();
		$increment_amount = (int) apply_filters( 'ffd_analytic_views_increment_amount', 1, $id );

		// get day, week, month and year
		$date = explode( '-', date( 'W-d-m-Y', current_time( 'timestamp' ) ) );

		foreach ( array(
			0	 => $date[3] . $date[2] . $date[1], // day like 20140324
			1	 => $date[3] . $date[0], // week like 201439
			2	 => $date[3] . $date[2], // month like 201405
			3	 => $date[3], // year like 2014
			4	 => 'total'   // total views
		) as $type => $period ) {
			if ( $using_object_cache ) {
				$cache_key = $id . self::CACHE_KEY_SEPARATOR . $type . self::CACHE_KEY_SEPARATOR . $period;
				wp_cache_add( $cache_key, 0, self::GROUP );
				wp_cache_incr( $cache_key, $increment_amount, self::GROUP );
				$cache_key_names[] = $cache_key;
			} else {
				// hit the db directly
				// @TODO: investigate queueing these queries on the 'shutdown' hook instead of running them instantly?
				$this->db_insert( $id, $type, $period, $increment_amount );
			}
		}

		// update the list of cache keys to be flushed
		if ( $using_object_cache && ! empty( $cache_key_names ) )
			$this->update_cached_keys_list_if_needed( $cache_key_names );

		do_action( 'ffd_analytic_after_count_visit', $id );

		return $id;
	}


	/**
	 * Save cookie function.
	 * 
	 * @param int $id
	 * @param array $cookie
	 * @param bool $expired
	 */
	private function save_cookie( $id, $cookie = array(), $expired = true ) {
		$set_cookie = apply_filters( 'ffd_analytic_maybe_set_cookie', true );

		// Cookie Notice compatibility
		if ( function_exists( 'cn_cookies_accepted' ) && ! cn_cookies_accepted() )
			$set_cookie = false;

		if ( $set_cookie !== true )
			return $id;

		$expiration = DAY_IN_SECONDS * 30;

		// assign cookie name
		$cookie_name = 'ffd_analytic' . ( is_multisite() ? '_' . get_current_blog_id() : '' );

		// is this a new cookie?
		if ( empty( $cookie ) ) {
			// set cookie
			setcookie( $cookie_name . '[0]', $expiration . 'b' . $id, $expiration, COOKIEPATH, COOKIE_DOMAIN, (isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ? true : false ), true );
		} else {
			if ( $expired ) {
				// add new id or change expiration date if id already exists
				$cookie['visited_posts'][$id] = $expiration;
			}

			// create copy for better foreach performance
			$visited_posts_expirations = $cookie['visited_posts'];

			// get current gmt time
			$time = current_time( 'timestamp', true );

			// check whether viewed id has expired - no need to keep it in cookie (less size)
			foreach ( $visited_posts_expirations as $post_id => $post_expiration ) {
				if ( $time > $post_expiration )
					unset( $cookie['visited_posts'][$post_id] );
			}

			// set new last expiration date if needed
			$cookie['expiration'] = max( $cookie['visited_posts'] );

			$cookies = $imploded = array();

			// create pairs
			foreach ( $cookie['visited_posts'] as $id => $exp ) {
				$imploded[] = $exp . 'b' . $id;
			}

			// split cookie into chunks (4000 bytes to make sure it is safe for every browser)
			$chunks = str_split( implode( 'a', $imploded ), 4000 );

			// more then one chunk?
			if ( count( $chunks ) > 1 ) {
				$last_id = '';

				foreach ( $chunks as $chunk_id => $chunk ) {
					// new chunk
					$chunk_c = $last_id . $chunk;

					// is it full-length chunk?
					if ( strlen( $chunk ) === 4000 ) {
						// get last part
						$last_part = strrchr( $chunk_c, 'a' );

						// get last id
						$last_id = substr( $last_part, 1 );

						// add new full-lenght chunk
						$cookies[$chunk_id] = substr( $chunk_c, 0, strlen( $chunk_c ) - strlen( $last_part ) );
					} else {
						// add last chunk
						$cookies[$chunk_id] = $chunk_c;
					}
				}
			} else {
				// only one chunk
				$cookies[] = $chunks[0];
			}

			foreach ( $cookies as $key => $value ) {
				// set cookie
				setcookie( $cookie_name . '[' . $key . ']', $value, $cookie['expiration'], COOKIEPATH, COOKIE_DOMAIN, (isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ? true : false ), true );
			}
		}
	}



	/**
	 * Initialize cookie session.
	 */
	public function check_cookie() {
		// do not run in admin except for ajax requests
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
			return;

		// assign cookie name
		$cookie_name = 'ffd_analytic' . ( is_multisite() ? '_' . get_current_blog_id() : '' );

		// is cookie set?
		if ( isset( $_COOKIE[$cookie_name] ) && ! empty( $_COOKIE[$cookie_name] ) ) {
			$visited_posts = $expirations = array();

			foreach ( $_COOKIE[$cookie_name] as $content ) {
				// is cookie valid?
				if ( preg_match( '/^(([0-9]+b[0-9]+a?)+)$/', $content ) === 1 ) {
					// get single id with expiration
					$expiration_ids = explode( 'a', $content );

					// check every expiration => id pair
					foreach ( $expiration_ids as $pair ) {
						$pair = explode( 'b', $pair );
						$expirations[] = (int) $pair[0];
						$visited_posts[(int) $pair[1]] = (int) $pair[0];
					}
				}
			}

			$this->cookie = array(
				'exists'		 => true,
				'visited_posts'	 => $visited_posts,
				'expiration'	 => max( $expirations )
			);
		}
	}

	/**
	 * Single site activation.
	 * 
	 * @global array $wp_roles
	 */
	public function activate_single() {

		//todo need more efficiency. for using options table
		return;
		global $wpdb, $charset_collate;

		// required for dbdelta
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// create post views table
		dbDelta( '
			CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'ffd_analytic (
				id bigint unsigned NOT NULL,
				type tinyint(1) unsigned NOT NULL,
				period varchar(8) NOT NULL,
				count bigint unsigned NOT NULL,
				PRIMARY KEY  (type, period, id),
				UNIQUE INDEX id_type_period_count (id, type, period, count) USING BTREE,
				INDEX type_period_count (type, period, count) USING BTREE
			) ' . $charset_collate . ';'
		);

	

		// schedule cache flush
		$this->schedule_cache_flush();
	}


	/**
	 * Schedule cache flushing if it's not already scheduled.
	 * 
	 * @param bool $forced
	 */
	public function schedule_cache_flush( $forced = true ) {
		if ( $forced || ! wp_next_scheduled( 'ffd_integration_analytic' ) )
			wp_schedule_event( time(), 'ffd_analytic_flush_interval', 'ffd_integration_analytic' );
	}


	/**
	 * Add new cron interval from settings.
	 * 
	 * @param array $schedules
	 * @return array
	 */
	public function cron_time_intervals( $schedules ) {
		/* $schedules['ffd_analytic_interval'] = array(
			'interval'	 => 86400,
			'display'	 => __( 'Daily', 'post-views-counter' )
		); */

		return $schedules;
	}



	/**
	 * Get user real IP address.
	 * 
	 * @return string
	 */
	public function get_user_ip() {
		foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ) as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[$key] ) as $ip ) {
					// trim for safety measures
					$ip = trim( $ip );

					// attempt to validate IP
					if ( $this->validate_user_ip( $ip ) )
						return $ip;
				}
			}
		}

		

		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : $this->get_local_ip();
	}


	function get_local_ip(){

		
		if( function_exist('getHostName'))
			$ip = getHostByName(getHostName());
		else
			$ip = getHostByName(php_uname('n'));

		return $ip;

	}

	/**
	 * Ensure an ip address is both a valid IP and does not fall within a private network range.
	 * 
	 * @param $ip
	 * @return bool
	 */
	public function validate_user_ip( $ip ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false )
			return false;

		return true;
	}



	private function visit_counter($visiter=null){

		if( !$visiter )
			$visiter = $this->visiter;
		
		
		
		$post_id = $visiter['post_id'];
		$user_id = $visiter['user_id'];
		$user_ip = $visiter['user_ip'];

		if( $user_id ){
			$type  = $user_id;
		} else {
			$type = $user_ip;
		}

		$count = (int) ( isset( $this->visit_counter[$type][$post_id] ) ? $this->visit_counter[$type][$post_id] : 0 );

		if ( ! $count ) {
			$count = 1;
		}
		

		$this->visit_counter[$type][$post_id] = $count + 1;
		
		update_option('ffd_visit_counter', $this->visit_counter);

		if( isset($_GET['debug_visit_counter']) ){
			ffd_debug($this->visit_counter, true);
		}
	}
	/**
	 * Insert or update views count.
	 * 
	 * @global object $wpdb
	 * @param int $id
	 * @param string $type
	 * @param string $period
	 * @param int $count
	 * @return bool
	 */
	private function db_insert( $id, $type, $period, $count = 1 ) {

		

		

		global $wpdb;

		$count = (int) $count;

		if ( ! $count ) {
			$count = 1;
		}

		//@todo using options to save data
		return $this->options;

		return $wpdb->query(
			$wpdb->prepare( "
				INSERT INTO " . $wpdb->prefix . "ffd_analytic (id, type, period, count)
				VALUES (%d, %d, %s, %d)
				ON DUPLICATE KEY UPDATE count = count + %d", $id, $type, $period, $count, $count
			)
		);
	}


	/**
	 * Save user IP function.
	 * 
	 * @param int $id
	 */
	private function save_ip( $id ) {
		$set_cookie = apply_filters( 'ffd_analytic_maybe_set_cookie', true );

		// Cookie Notice compatibility
		if ( function_exists( 'cn_cookies_accepted' ) && ! cn_cookies_accepted() )
			$set_cookie = false;

		if ( $set_cookie !== true )
			return $id;

		// get IP cached visits
		$ip_cache = get_transient( 'ffd_analytic_views_counter_ip_cache' );

		if ( ! $ip_cache )
			$ip_cache = array();

		// get user IP address
		$user_ip = (string) $this->get_user_ip();

		// get current time
		$current_time = current_time( 'timestamp', true );

		// visit exists in transient?
		if ( isset( $ip_cache[$id][$user_ip] ) ) {
			if ( $current_time > $ip_cache[$id][$user_ip] + $this->get_timestamp( $this->options['general']['time_between_counts']['type'], $this->options['general']['time_between_counts']['number'], false ) )
				$ip_cache[$id][$user_ip] = $current_time;
			else
				return;
		} else
			$ip_cache[$id][$user_ip] = $current_time;

		// keep it light, only 10 records per post and maximum 100 post records (=> max. 1000 ip entries)
		// also, the data gets deleted after a week if there's no activity during this time...
		if ( count( $ip_cache[$id] ) > 10 )
			$ip_cache[$id] = array_slice( $ip_cache[$id], -10, 10, true );

		if ( count( $ip_cache ) > 100 )
			$ip_cache = array_slice( $ip_cache, -100, 100, true );

		set_transient( 'ffd_analytic_views_counter_ip_cache', $ip_cache, WEEK_IN_SECONDS );
	}


	/**
	 * Check whether user has excluded roles.
	 * 
	 * @param string $option
	 * @return bool
	 */
	public function is_user_role_excluded( $user_id, $option ) {
		$user = get_user_by( 'id', $user_id );
		$option = (array) $option;

		if ( empty( $user ) )
			return false;

		$roles = (array) $user->roles;

		if ( ! empty( $roles ) ) {
			foreach ( $roles as $role ) {
				if ( in_array( $role, $option, true ) )
					return true;
			}
		}

		return false;
	}


	/**
	 * Get post types avaiable for counting.
	 */
	public function load_post_types() {

		if ( ! is_admin() )
			return;

		$post_types = array();

		// built in public post types
		foreach ( get_post_types( array( '_builtin' => true, 'public' => true ), 'objects', 'and' ) as $key => $post_type ) {
			if ( $key !== 'attachment' )
				$post_types[$key] = $post_type->labels->name;
		}

		// public custom post types
		foreach ( get_post_types( array( '_builtin' => false, 'public' => true ), 'objects', 'and' ) as $key => $post_type ) {
			$post_types[$key] = $post_type->labels->name;
		}

		// sort post types alphabetically with their keys
		asort( $post_types, SORT_STRING );

		$this->post_types = $post_types;
	}



	/**
	 * Check if object cache is in use.
	 * 
	 * @param bool $using
	 * @return bool
	 */
	public function using_object_cache( $using = null ) {
		$using = wp_using_ext_object_cache( $using );

		if ( $using ) {
			// check if explicitly disabled by flush_interval setting/option <= 0
			$flush_interval_number = (int) $this->options['general']['flush_interval']['number'];
			$using = ( $flush_interval_number <= 0 ) ? false : true;
		}

		return $using;
	}


	/**
	 * Check if IPv4 is in range.
	 *
	 * @param string $ip IP address
	 * @param string $range IP range
	 * @return boolean Whether IP is in range
	 */
	public function ipv4_in_range( $ip, $range ) {
		$start = str_replace( '*', '0', $range );
		$end = str_replace( '*', '255', $range );
		$ip = (float) sprintf( "%u", ip2long( $ip ) );

		return ( $ip >= (float) sprintf( "%u", ip2long( $start ) ) && $ip <= (float) sprintf( "%u", ip2long( $end ) ) );
	}


	/**
	 * Update the single cache key which holds a list of all the cache keys
	 * that need to be flushed to the db.
	 *
	 * The value of that special cache key is a giant string containing key names separated with the `|` character.
	 * Each such key name then consists of 3 elements: $id, $type, $period (separated by a `.` character).
	 * Examples:
	 * 62053.0.20150327|62053.1.201513|62053.2.201503|62053.3.2015|62053.4.total|62180.0.20150327|62180.1.201513|62180.2.201503|62180.3.2015|62180.4.total
	 * A single key is `62053.0.20150327` and that key's data is: $id = 62053, $type = 0, $period = 20150327
	 *
	 * This data format proved more efficient (avoids the (un)serialization overhead completely + duplicates filtering is a string search now)
	 * 
	 * @param array $key_names
	 */
	private function update_cached_keys_list_if_needed( $key_names = array() ) {
		$existing_list = wp_cache_get( self::NAME_ALLKEYS, self::GROUP );
		if ( ! $existing_list )
			$existing_list = '';

		$list_modified = false;

		// modify the list contents if/when needed
		if ( empty( $existing_list ) ) {
			// the simpler case of an empty initial list where we just
			// transform the specified key names into a string
			$existing_list = implode( '|', $key_names );
			$list_modified = true;
		} else {
			// search each specified key name and append it if it's not found
			foreach ( $key_names as $key_name ) {
				if ( false === strpos( $existing_list, $key_name ) ) {
					$existing_list .= '|' . $key_name;
					$list_modified = true;
				}
			}
		}

		// save modified list back in cache
		if ( $list_modified ) {
			wp_cache_set( self::NAME_ALLKEYS, $existing_list, self::GROUP );
		}
	}
}


/**
 * Main instance of FFD_Integration_Analytic.
 *
 * Returns the main instance of FFD_Analytic to prevent the need to use globals.
 *
 * @since  1.0
 * @return FFD_Integration_Analytic
 */
function FFD_Analytic() {
	return FFD_Integration_Analytic::instance();
}
FFD_Analytic();
