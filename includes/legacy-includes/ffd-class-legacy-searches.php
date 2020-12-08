<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FFDL_Searches {
	protected static $DB_VERSION = 11;
	protected static $user_id = null;

	public static function install() {
		if ( version_compare( get_option( 'ffdl_searches_db_version' ), self::$DB_VERSION, '<' ) ) {
			self::create_table();
		}
	}

	protected static function create_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( self::get_table_sql() );
		update_option( 'ffdl_searches_db_version', self::$DB_VERSION );
	}

	protected static function get_table_sql() {
		global $wpdb;

		return "CREATE TABLE " . $wpdb->prefix . "ffdl_listings (
			ID char(32) NOT NULL,
			changed__c datetime NULL,
			md5 char(32),
			details text,
			created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (ID)
		)
		COLLATE=" . $wpdb->get_charset_collate() . "
		ENGINE=InnoDB
		;

		CREATE TABLE " . $wpdb->prefix . "ffdl_searches (
			ID bigint(10) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(10) unsigned NULL DEFAULT NULL,
			name varchar(32) NULL,
            requestId varchar(50) NULL,
			sync TINYINT(1) UNSIGNED NULL,
			search_options text NOT NULL,
			created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (ID),
			KEY user_id (user_id),
			UNIQUE KEY name (name)
		)
		COLLATE=" . $wpdb->get_charset_collate() . "
		ENGINE=InnoDB
		;

		CREATE TABLE " . $wpdb->prefix . "ffdl_searches_listings (
			listing_id char(32) NOT NULL,
			search_id bigint(10) unsigned NULL DEFAULT NULL,
			KEY fk_ffdl_listings (listing_id),
			KEY fk_ffdl_searches (search_id),
			CONSTRAINT fk_ffdl_listings FOREIGN KEY (listing_id) REFERENCES " . $wpdb->prefix . "ffdl_listings (ID) ON DELETE CASCADE,
			CONSTRAINT fk_ffdl_searches FOREIGN KEY (search_id) REFERENCES " . $wpdb->prefix . "ffdl_searches (ID) ON DELETE CASCADE
		)
		COLLATE=" . $wpdb->get_charset_collate() . "
		ENGINE=InnoDB
		;
		";
	}

	public static function start() {
		self::$user_id = get_current_user_id();
		self::install();

		global $wpdb;

		$wpdb->ffdl_listings = $wpdb->prefix . 'ffdl_listings';
		$wpdb->ffdl_searches = $wpdb->prefix . 'ffdl_searches';
		$wpdb->ffdl_searches_listings = $wpdb->prefix . 'ffdl_searches_listings';
	}

	public static function array_to_sqlin( $values ) {
		global $wpdb;

		if ( ! is_array ( $values ) ) {
			$values = array( $values );
		}

		$values = array_map( function( $val ) use ( $wpdb ) {
			return $wpdb->prepare( '%s', $val );
		}, $values );

		return implode( ',', $values );
	}
    public static function search_PBRequestId($name){
        global $wpdb;
		$user_id = self::$user_id;

        return $wpdb->get_row($wpdb->prepare(
				"SELECT requestId FROM `{$wpdb->ffdl_searches}`
				WHERE `name`=%s and `user_id`=%d",$name,$user_id), ARRAY_A );
    }
    public static function search_PBRequestIdFromId($id){
        global $wpdb;
		$user_id = self::$user_id;

        return $wpdb->get_row($wpdb->prepare(
				"SELECT requestId FROM `{$wpdb->ffdl_searches}`
				WHERE `id`=%d",$id), ARRAY_A );
    }
    public static function search_FromRequestId($id){
        global $wpdb;
		$user_id = self::$user_id;

        return $wpdb->get_row($wpdb->prepare(
				"SELECT id,name FROM `{$wpdb->ffdl_searches}`
				WHERE `requestId`=%d",$id), ARRAY_A );
    }
	public static function save_search( $name, $searchparams,$requestId, $user_id = null )  {
		global $wpdb;
		$user_id = ( null === $user_id) ? self::$user_id : $user_id;

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$wpdb->ffdl_searches}`
				(`user_id`, `name`, `search_options`,`requestId`)
				VALUES (%d, %s, %s,%s)
				ON DUPLICATE KEY UPDATE `name`=%s,`requestId`=%s",
				array( $user_id, $name, $searchparams,$requestId, $name,$requestId ) )
		);
		
	}

	public static function save_property( $prop_id, $listing ) {
		global $wpdb;

		$changed = (isset ($listing['Changed__c']) ) ? $listing['Changed__c'] : null;
		$listing_encoded = json_encode( $listing );
		$md5 = md5( $listing_encoded );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$wpdb->ffdl_listings}`
				(`ID`, `Changed__c`, `md5`, `details`)
				VALUES (%s, %s, %s, %s)
				ON DUPLICATE KEY UPDATE `Changed__c`=%s, `md5`=%s, `details`=%s;",
				array( trim( $prop_id ), $changed, $md5, $listing_encoded , $changed, $md5, $listing_encoded )
			)
		);

	}

	public static function update_properties_from_searches() {

	}

	public static function get_search( $search_ids) {
		global $wpdb;
		$search_ids = self::array_to_sqlin( $search_ids );

		return $wpdb->get_results(
				"SELECT * FROM `{$wpdb->ffdl_searches}`
				WHERE `ID` IN (" . $search_ids . ")
				ORDER BY created DESC"
		, ARRAY_A );
	}

	public static function get_searches_for_user( $user_id = null ) {
		global $wpdb;
		$user_id = ( null === $user_id ) ? self::$user_id : $user_id;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->ffdl_searches}`
				WHERE `user_id` = %d
				ORDER BY created DESC"
			, $user_id)
		, ARRAY_A );
	}

	public static function get_property ( $prop_ids ) {
		global $wpdb;
		$prop_ids = self::array_to_sqlin( $prop_ids );

		return $wpdb->get_results(
				"SELECT * FROM `{$wpdb->ffdl_listings}`
				WHERE `ID` IN (" . $prop_ids . ")"
		, ARRAY_A );
	}

	public static function get_property_ids_for_search ( $search_ids ) {
		global $wpdb;

		$search_ids = self::array_to_sqlin( $search_ids );

		return $wpdb->get_results(
			"SELECT * FROM `{$wpdb->ffdl_searches_listings}`
			WHERE `search_id` IN (" . $search_ids . ")"
		, ARRAY_A);
	}

	public static function del_search ( $search_ids ) {
		global $wpdb;

		$search_ids = self::array_to_sqlin( $search_ids );
		$wpdb->query(
			"DELETE FROM `{$wpdb->ffdl_searches}`
			WHERE `ID` IN (" . $search_ids . ")"
		);
	}

	public static function del_property( $prop_ids ) {
		global $wpdb;

		$prop_ids = self::array_to_sqlin( $prop_ids );
		$wpdb->query(
			"DELETE FROM `{$wpdb->ffdl_listings}`
			WHERE `ID` IN (" . $prop_ids . ")"
		);
	}

}

add_action( 'init', array( 'FFDL_Searches', 'start' ), 10 );