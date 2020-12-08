<?php

//ini_set('display_errors', 1); error_reporting(E_ALL);

/**
 * FFD Integration Admin
 *
 * @class    FFD_Listings_Sync
 * @author   FrozenFish
 * @category Admin
 * @package  FFD Integration/Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * FFD_Listings_Sync class.
 */
class FFD_Listings_Sync extends FFD_PropertyBase_API {

    

   
    
    
    protected static $methodUrl=""; //pb request api url
    protected static $query=''; //pb query
    protected static $where=''; // pb query where condition
    protected static $order=''; // pb query order statement
    protected static $last_id=null; // pb id of last record created
    protected static $current_run=null; // current run date
    protected static $last_run=null; // last run date
    protected static $synced_ids_total=0; //already synced listings count
    protected static $synced_ids=array(); //already synced listings ( contains salesforce id => post_id )

    protected static $prevent_timeouts = false;

    //listing fields mapped
    protected static $fields=array(); // meta
    protected static $post_fields=array(); // post table fields
    protected static $listing_posttype='listing'; // post table fields

    
    //contains default mapping for record data
    protected static $default_mapped=array();

    //listings records from api
    protected static $listings=array();

    // count container during sync
    protected static $sync_index  = 0; // total number of records added
    protected static $ignored_records   = 0; // number of existing records ignored
    protected static $updated_records   = 0; // number of existing records updated
    protected static $new_records   = 0; // number of new records
    protected static $deleted_records   = 0; // number of existing record deleted using custom delete query
    protected static $expired_records   = 0; // number of existing record expired
    protected static $empty_image_records   = 0; // number of existing record expired
    protected static $trashed_records   = 0; // number of existing record expired
    protected static $total_records   = 0; // number of new records

    protected static $sync_type=''; // last run date
    protected static $sync_status   = '';
    protected static $sync_done  = false;
    protected static $did_timeout  = false;

    //data container for current listing  being added/updated
    protected static $record=null;
    protected static $media=null;
    protected static $post_id=null;
    protected static $mls_id=null;
    protected static $salesforce_id=null;
    protected static $is_deleted=false; //if listing is deleted
    protected static $is_update=false;
    protected static $taxonomies=array();
    
    private static $debug_apirequest = array();
    private static $debugger=false;
    private static $test_api=false;
    private static $debugger_logs=array();


    /**
	 * Constructor.
	 */
	public static function run(){

        add_filter( 'cron_schedules', array(__CLASS__, 'sync_intervals'));
        add_filter('ffd_main_sync_interval', array(__CLASS__, 'set_sync_interval'), 5, 1);
        add_action('ffd_salesforce_rest_credentials', array(__CLASS__, 'credentials_updated'), 10, 1);

        $pbtowp = get_option('ffd_propertybasetowp_sync', 'no');
        $wptopb = get_option('ffd_wptopropertybase_sync', 'no');

       
        if( 'yes' === $pbtowp &&  $wptopb !== 'yes' ){
           
            add_action('ffd_main_sync', array(__CLASS__, 'run_main_sync'));
            add_action('ffd_timeout_sync', array(__CLASS__, 'run_timeout_sync'));

            //check syn run time for a site every 6 hour.
            //if more then 24 hours send email
            add_action('ffd_propertybasetowp_sync_status', array(__CLASS__, 'check_sync_status'));
                        
        }

    
    

        add_action('init', array(__CLASS__, 'init'), 101);
       
        
    }

    
    public static function set_sync_interval($interval){

        $sync_interval = get_option('ffd_propertybase_sync_interval', 'hourly');
        if( !empty($sync_interval) ){
            $interval = $sync_interval;
        }

        return $interval;
    }

    public static function test(){

            ini_set('display_errors', 1);
            error_reporting(E_ALL);

            $records = self::test_propertybase_query();
            $ids = self::get_sync_ids();
            echo 'in pb:' . $records . ' | ';
            echo 'in db:' . count($ids);

            ffd_debug(self::query(), false);

            ffd_debug($ids, true);

            exit;
        
    }


    public static function check_sync_status(){

        $notification = get_option('ffd_sync_notification');

        if( 'yes' === $notification && self::$is_sync_enabled ){
           
            $last_run = get_option('ffd_sync_last_run'); //Y-m-d\TH:i:s
            if( !empty($last_run) ){
                $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s', $last_run);
                if( $datetime !== false ) {
                    $lastrun_time = $datetime->getTimestamp();
                    $difference = (time() - $lastrun_time);
                    if( $difference >= DAY_IN_SECONDS ){
                        $site_name = get_bloginfo('name');
                        $to = get_option('ffd_sync_notification_email');
                        if( !empty($to) ){
                            $subject = $site_name . ' Sync Not Ran in last 24 hours';
                            $message = 'Alert: Sync Not Ran in last 24 hours for ' . $site_name;
                            wp_mail( $to, $subject, $message );

                        }


                    }
                }
            }

            
        }

    }

    /* 
    * When credentials are updated in 
    * wp to propertybase api calls ( specificaly tokens )
    */
    public function credentials_updated($data){

        if( is_object($data) )
            $data = (array) $data;

        self::$api_vars =  self::propertybase_api_vars();
        foreach(self::$api_vars as $api_var){
            if( isset( self::$api_settings[$api_var]) && isset($data[$api_var]) ){
                self::$api_settings[$api_var] = $data[$api_var];
            }
        }

        do_action('ffd_logger', array('propertybasetowp_credentials_set' => 'by wptopropertybase'));

    }

  


    public static function activated(){

        

        //unschedule main and timeout sync hook
        self::clear_main_sync();
        self::clear_timeout_sync();

        wp_schedule_event(time(), 'twicedaily', 'ffd_propertybasetowp_sync_status');

        //schedule main sync 
        $sync_interval = apply_filters( 'ffd_main_sync_interval', 'hourly');
        $scheduled = wp_schedule_event(time(), $sync_interval, 'ffd_main_sync');
        
    }

    public static function de_activated(){

        
      
        //unschedule main and timeout sync hook
        self::unschedule_main_sync();
        self::unschedule_timeout_sync();

        $timestamp = wp_next_scheduled( 'ffd_propertybasetowp_sync_status');
        if( $timestamp ){
            wp_unschedule_event( $timestamp, 'ffd_propertybasetowp_sync_status');
        }

        //reset sync settings, so next time we can start fresh.
        update_option('ffd_synced_listings_total', '');
        update_option('ffd_prune_listings', true);
        update_option('ffd_sync_last_id', '');
        update_option('ffd_sync_last_run', '');
        update_option('ffd_sync_stats', '');
        update_option('ffd_sync_current_run', '');
        update_option('ffd_propertybase_sync_ids', '');
        update_option('ffd_propertybase_sync_status', 'idle');
        update_option('ffd_propertybase_sync_index', '0');

    }

    
    public static function init(){

        if( isset($_GET['ffd_sync_test']) ){
            self::test();
            return;
        }

        if( isset($_GET['ffd_sync_debug']) ){
            self::$debugger = true;
            self::$sync_type = 'debug_sync';
            self::debug_init();
            self::start_sync();
            return;
        }

    }
   
    public static function run_main_sync(){

        $pbtowp = get_option('ffd_propertybasetowp_sync', 'no');
        $wptopb = get_option('ffd_wptopropertybase_sync', 'no');
        
        if( 'yes' === $pbtowp &&  $wptopb !== 'yes' ){
            self::$sync_type = 'main_sync';
            self::start_sync();
        }
    }

    public static function run_timeout_sync(){

        self::$sync_type = 'timeout_sync';
        self::start_sync();
    }

    public static function sync_intervals( $schedules ) {

        $schedules['ffd_5min'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => __( 'Once every 5 minutes' )
        );

        $schedules['ffd_10min'] = array(
            'interval' => 10 * MINUTE_IN_SECONDS,
            'display' => __( 'Once every 10 minutes' )
        );

        $schedules['ffd_15min'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __( 'Once every 15 minutes' )
        );

        $schedules['ffd_30min'] = array(
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display' => __( 'Once every 30 minutes' )
        );

        $schedules['ffd_hourly'] = array(
            'interval' => HOUR_IN_SECONDS,
            'display' => __( 'Once every hour' )
        );

        $schedules['ffd_2hour'] = array(
            'interval' => 2 * HOUR_IN_SECONDS,
            'display' => __( 'Once every 2 hours' )
        );

        $schedules['ffd_4hour'] = array(
            'interval' => 4 * HOUR_IN_SECONDS,
            'display' => __( 'Once every 4 hours' )
        );

        $schedules['ffd_6hour'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __( 'Once every 6 hours' )
        );

        $schedules['ffd_12hour'] = array(
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __( 'Once every 12 hours' )
        );

        $schedules['ffd_daily'] = array(
            'interval' => DAY_IN_SECONDS,
            'display' => __( 'Once every day' )
        );

        return $schedules;
    }


    public static function clear_main_sync(){

        //$this->unschedule_main_sync(); // clear schedule hook already does that.
        wp_clear_scheduled_hook('ffd_main_sync');

    } 

    

    public static function reschedule_main_sync(){

        //if already scheduled clear that.
        self::clear_main_sync();
        self::clear_timeout_sync();

        $sync_interval = apply_filters( 'ffd_main_sync_interval', 'hourly');
        $interval_time = 5 * MINUTE_IN_SECONDS;
        $scheduled = wp_schedule_event(time() + $interval_time, $sync_interval, 'ffd_main_sync');

        return $scheduled;
    }

	private static function unschedule_main_sync(){
        $timestamp = wp_next_scheduled( 'ffd_main_sync');
        if( $timestamp ){
            wp_unschedule_event( $timestamp, 'ffd_main_sync');
        }
    }

    private static function clear_timeout_sync(){

        wp_clear_scheduled_hook('ffd_timeout_sync');

    } 

    public static function unschedule_timeout_sync(){
       
            $timestamp = wp_next_scheduled( 'ffd_timeout_sync');
            if( $timestamp ){
                wp_unschedule_event( $timestamp, 'ffd_timeout_sync');
            }
        
    }
    
    
    public static function start_sync(){

        self::propertybase_set_api_vars();
        

        if( !self::$is_sync_enabled ){
            return;
        }

        $post_type = apply_filters('ffd_listing_posttype', 'listing');
        if( !$post_type || !post_type_exists($post_type) ){
            
            do_action('ffd_logger', array('[FFD Sync]' => 'Post Type '.$post_type.' does not exists.'));
            return;
        }

        do_action('ffd_logger', array('[FFD Sync]' => self::$sync_type . ' started'));

        //setup fields and api query
        self::sync_init();

        //total number of listings in sf/pb
        $listings_total = get_option('ffd_synced_listings_total', '');

        self::$synced_ids = array();
        if( ! self::$debugger  ){
            $listings_total = !empty($listings_total) ? (int) $listings_total : 0;
            self::$synced_ids = self::get_sync_ids();
            $sync_ids_total = is_array( self::$synced_ids ) ? count( self::$synced_ids ) : 0;
            self::$synced_ids_total = $sync_ids_total;
        }

        //remove expired/deleted listings.
        $prune_listings = apply_filters('ffd_pre_prune_listings', get_option('ffd_prune_listings', true));
        if( !empty( self::$fields ) ){


            //use for avoiding running delete query if we don't have listings
            $count_posts = wp_count_posts(self::$listing_posttype); 
            if ( $count_posts ) {
                $published_posts = $count_posts->publish;
            }
        

            if( $published_posts && $prune_listings && self::$sync_type == 'main_sync'  ){

                self::prune_listings();
    
            } 

           

           

            self::import_listings();
            self::update_schedule_after_import();
        }

            
    }

    public static function get_delete_listings_query(){

        $query = '';
        $end=date("Y-m-d\TH:i:s", strtotime("+2 days"));
        $start=date("Y-m-d\TH:i:s", strtotime("-29 days"));
        $lastRun=date("Y-m-d\TH:i:s", strtotime("January 1 1900")); 

        if ( self::$last_run && !empty(self::$last_run) ) {
            $lastRun=self::$last_run;
        }

        if( self::$debugger !== false  ){
            $lastRun=date("Y-m-d\TH:i:s", strtotime("January 1 1900")); 
        }


        $where =" where LastModifiedDate>" . $lastRun . "Z "; 
        $prune_listings = get_option('ffd_sync_prune_listings_condition', '');
        $and = apply_filters( 'ffd_sync_prune_listings_condition', $prune_listings, self::$platform);
        
        if( !empty($and) ){
            $where .= " " . $and;
            $query = "select id,pba__property__c from pba__listing__c" . $where;
        }

        return $query;


    }
    public static function prune_listings(){

        //remove listings deleted in sf/pb
        self::remove_expired();


        do_action('ffd_logger', array('[FFD Sync]' => self::$sync_type . ' deleting (delete query)'));

        
        $query = self::get_delete_listings_query();

        if( !empty($query) ){
          
        

            
            self::$methodUrl = "/services/data/v29.0/query/?q=" . urlencode($query);

            $done=false;
            
            $current=0;
            $current_deleted=0;
            while (!$done) {

                $results = self::propertybase_get_listings();

                if (isset($results->totalSize) && $results->totalSize>0) {
                    $done=$results->done;

                    if (!$done) {
                        self::$methodUrl = $results->nextRecordsUrl;
                    }

                    foreach ($results->records as $listing) {
                        $current+=1;

                        $status = "Processing Deleted Listings " . $current . " of " . $results->totalSize;
                        self::update_sync_status($status, $current);
                        $del_id = isset($listing->Id) ? $listing->Id : $listing->id;
                        $deleted = self::delete_listing($del_id, true);


                        if( $deleted  ){
                            self::$deleted_records+=1;
                            $current_deleted+=1;
                            
                            if( isset( self::$synced_ids[$del_id]) ){
                                unset( self::$synced_ids[$del_id] );
                                self::update_sync_ids( self::$synced_ids );
                            }
                        }
                    }
                } else {
                    $done=true;
                }
            }

            do_action('ffd_logger', 
            array('[FFD Sync]' => 'remove Deleted: Found=' . $current . ' Deleted:' . $current_deleted )
            );
          
            self::update_sync_status('idle', '0');

        } else {
            do_action('ffd_logger', array('[FFD Sync]' => 'there is no delete query, skipping delete'));
        }
        
        //this make delete listing skip ( one time ) the next time sync run
        update_option('ffd_prune_listings', false);
    }


    public static function get_expired_listings_query(){

        $end=date("Y-m-d\TH:i:s", strtotime("+2 days"));
        $start=date("Y-m-d\TH:i:s", strtotime("-29 days"));
        $lastRun=date("Y-m-d\TH:i:s", strtotime("January 1 1900"));

        if ( self::$last_run && !empty(self::$last_run) ) {
            $lastRun=strtotime("-2 days", strtotime(self::$last_run));
        }

        $query="start=" . urlencode($start . "Z") . "&end=" . urlencode($end . "Z");

        return $query;
    }

    public static function remove_expired(){

        do_action('ffd_logger', array('[FFD Sync]' => self::$sync_type . ' deleting expired'));

        $query = self::get_expired_listings_query();
        self::$methodUrl ="/services/data/v29.0/sobjects/pba__listing__c/deleted/?" . $query;
        $current=0;
        $current_deleted=0;
        $results = self::propertybase_get_listings();
       
        if( !empty($results) ){
            $totalDeleted = count($results->deletedRecords);
            do_action('ffd_logger', 
                array('[FFD Sync]' => 'Expired Total:' . $totalDeleted )
            );

            foreach ($results->deletedRecords as $listing) {
                try {
                    $current+=1;

                    $status = "Processing Expired Listings " . $current . " of " . $totalDeleted;
                    self::update_sync_status($status, $current);
                    $del_id = isset($listing->Id) ? $listing->Id : $listing->id;
                    $deleted = self::delete_listing($del_id, true);
                    if( $deleted  ){

                        self::$deleted_records+=1;
                        $current_deleted+=1;
                        
                        if( isset( self::$synced_ids[$del_id]) ){
                            unset( self::$synced_ids[$del_id] );
                            self::update_sync_ids( self::$synced_ids );
                        }
                    }
                    
                } catch (Exception $ex) {
                    $err=$ex;
                }
            }
        }

        do_action('ffd_logger', 
            array('[FFD Sync]' => 'Remove expired :' . $current_deleted )
        );
        self::update_sync_status('idle', '0');
    }

    public static function import_listings(){

        do_action('ffd_logger', array('[FFD Sync]' => self::$sync_type . ' importing'));

        self::$methodUrl = "/services/data/v29.0/query/?q=" . self::$query;
       
        
        $status_array = self::get_sync_status();

        self::$start_time = time();
        self::$sync_index = $status_array['index'];
        self::$new_records = 0;
        self::$ignored_records = 0;
        self::$deleted_records = 0;
        self::$expired_records = 0;
        self::$empty_image_records = 0;
        self::$trashed_records = 0;
        self::$updated_records = 0;

        self::$sync_done = false;
        self::$did_timeout = false; // did sync completed properly?

        //start importing listings
        self::$did_timeout = self::start_importing();
    
        return self::$did_timeout;
    }

    public static function update_schedule_after_import(){

        $listings_total =  isset(self::$listings->totalSize) ? self::$listings->totalSize : 0;
        update_option('ffd_synced_listings_total', $listings_total);

        if( self::$did_timeout ){

            self::unschedule_main_sync();
           

            $timestamp = wp_next_scheduled( 'ffd_timeout_sync' );
            if( !$timestamp ){
                wp_schedule_event(time() + ( MINUTE_IN_SECONDS * 5 ), 'ffd_5min', 'ffd_timeout_sync');
                $timestamp = wp_next_scheduled( 'ffd_timeout_sync' );
            }
           

            update_option('ffd_sync_method_url', self::$methodUrl);
            $sync_status = 'In progress ' . ( !empty($timestamp) ? ', next run: after ' . human_time_diff($timestamp) : '');
            self::update_sync_status($sync_status, self::$sync_index);
            
    
            do_action('ffd_sync_progress', array('total_synced' => self::$sync_index, 'updated_records'=>self::$updated_records, 'new_records' => self::$new_records ));
            

        } else {

            
            self::unschedule_timeout_sync();
        
            $timestamp = wp_next_scheduled( 'ffd_main_sync' );
            if( !$timestamp ){
                $sync_interval = apply_filters( 'ffd_sync_interval', 'hourly');
                wp_schedule_event(time() + HOUR_IN_SECONDS, $sync_interval, 'ffd_main_sync');
            }

            update_option('ffd_prune_listings', true);
            update_option('ffd_sync_method_url', '');
            update_option('ffd_sync_current_run', '');

            if( self::$total_records  !== null ){
                update_option('ffd_sync_last_run', self::$current_run);
            }

            self::update_sync_status();

            do_action('ffd_sync_finished', array('total_synced' => self::$sync_index, 'updated_records'=>self::$updated_records, 'new_records' => self::$new_records ));

        }

        $log_data = array(
            'total_records'   => self::$total_records, 
            'ignored_records'   => self::$ignored_records, 
            'updated_records'   => self::$updated_records, 
            'new_records'       => self::$new_records, 
            'expired_records'   => self::$expired_records, 
            'deleted_records'   => self::$deleted_records, 
            'deleted_no_images_records' => self::$empty_image_records, 
            'trashed_records' => self::$trashed_records, 
            'sync_time' => self::$current_run,
            'timeout' => (int) self::$did_timeout
            );

        update_option('ffd_sync_stats', $log_data);

        if( self::$debugger !== false || isset($_GET['ffd_debug_query'])){

            ffd_debug(self::$api_settings, $exit=false);
            ffd_debug(self::$debugger_logs, $exit=false);
            ffd_debug(self::$debug_apirequest, $exit=false);
            ffd_debug($log_data, $exit=false);
            ffd_debug('--------done--------', true);

        }

        do_action('ffd_logger', array('[FFD Sync]' => self::$sync_type . ' finished ( timeout: '. ( (int) self::$did_timeout ).' )', 'Data' => $log_data));

    }

    


    public static function start_importing(){

        while (!self::$sync_done && !self::$did_timeout ) {
            
            do_action('ffd_sync_started');

            self::$listings = self::propertybase_get_listings();
            self::$total_records = isset(self::$listings->totalSize) ? self::$listings->totalSize : null;

            
            if( self::$debugger ){
                self::$debug_apirequest['request_count'] = isset( self::$debug_apirequest['request_count']) ?  self::$debug_apirequest['request_count'] + 1 : 1;
                self::$debug_apirequest['request'][self::$debug_apirequest['request_count']]['info'] = array('totalSize' => self::$listings->totalSize, 'done' => self::$listings->done); 
            }

            if (isset(self::$listings->totalSize) && self::$listings->totalSize>0 ) {
                    
                self::$sync_done = self::$listings->done;
                
                if (!self::$sync_done) {
                    self::$methodUrl = self::$listings->nextRecordsUrl;
                }

                self::upsert_listings();

              

            } else {

                self::$sync_done=true;

                if( !self::$did_timeout ){
                    do_action('ffd_sync_done');
                }
            }

            if( self::$did_timeout ){
                break;
            }
        } 

        do_action('ffd_logger', array('[FFD Sync]' => self::$sync_type . ' Done', 'did_timeout' => self::$did_timeout, 'Total Listing Size' => self::$listings->totalSize));

        return self::$did_timeout;
    }



    public static function upsert_listings(){

        global $ffd_pb_record;
        $ffd_pb_record = null;
        
        $looped = 0;
        foreach (self::$listings->records as $property) {

            try {


                if ( $property && isset($property->pba__Listings__r) ) {

                    
                    foreach ($property->pba__Listings__r->records as $record) {

                        if( self::$debugger ){
                            self::$debug_apirequest['request'][self::$debug_apirequest['request_count']]['looped'] = ++$looped;
                        }
                        //Create the listing
                        self::$record = $record;
                        
                        $ffd_pb_record = $record;

                        self::$media = isset($property->pba__PropertyMedia__r) ? $property->pba__PropertyMedia__r : array();

                        $sync_status = "Syncing Property: " . self::$sync_index     . " of " . self::$listings->totalSize;
                        self::update_sync_status($sync_status, self::$sync_index);
                        self::create_listing();
                        self::$sync_index +=1;

                        if (self::check_timeout()) {

                            do_action('ffd_sync_timeout_done');
                            self::$did_timeout = true;
                            break;
                        }

                       
                    }

                }

            } catch (Exception $ex) {

                error_log($ex->getMessage());
                do_action('ffd_logger', array('[FFD Sync]' => self::$sync_type . ' Error: ' . $ex->getMessage()));
            }


            if( self::$did_timeout){
                break;
            } 

        }

        $ffd_pb_record = null;

        if( self::$debugger ){
            self::$debug_apirequest['request'][self::$debug_apirequest['request_count']]['progress'] = array(
                'total_records'   => self::$total_records, 
                'ignored_records'   => self::$ignored_records, 
                'updated_records'   => self::$updated_records, 
                'new_records'       => self::$new_records, 
                'expired_records'   => self::$expired_records, 
                'deleted_records'   => self::$deleted_records, 
                'deleted_no_images_records' => self::$empty_image_records, 
                'trashed_records' => self::$trashed_records, 
                );
        }

    }

    private static function current_record_mls_id(){

            $mls_id =  isset(self::$record->MLS_ID__c) ? self::$record->MLS_ID__c : null;
        if( empty($mls_id) )
            $mls_id =  isset(self::$record->pba__MlsId__c) ? self::$record->pba__MlsId__c : null;
        
        if( empty($mls_id) )
            $mls_id =  null;

        return $mls_id;
    }


    private static function current_record_salesforce_id(){

        $salesforce_id = isset(self::$record->id) ? self::$record->id : ( isset(self::$record->Id) ? self::$record->Id : null );
        if( empty($salesforce_id) )
            $salesforce_id =  null;
    
        return $salesforce_id;
    }


    private static function is_current_record_deleted(){

        if( isset(self::$record->IsDeleted) && self::$record->IsDeleted != "" ){
            return true;
        } else {
            return false;
        }

    }

    public static function create_listing(){

        
        self::$salesforce_id = self::current_record_salesforce_id();
        self::$mls_id = self::current_record_mls_id();

        $post_title = isset(self::$record->{self::$default_mapped["post_title"]}) ? self::$record->{self::$default_mapped["post_title"]} : '';
        $post_name = isset(self::$record->{self::$default_mapped["post_name"]}) ? self::$record->{self::$default_mapped["post_name"]} : '';
        $post_content = isset(self::$record->{self::$default_mapped["post_content"]}) ? self::$record->{self::$default_mapped["post_content"]} : '';

        
        if( self::$salesforce_id === null){
            do_action('ffd_logger', array('[FFD Sync]' => 'empty salesforce id'));
            return;
        }
        
        if(  self::$mls_id !== null ){
            //get post by meta key from postmeta
            self::$post_id = self::get_post_id_by_mls_id(self::$mls_id);
        } 

        if( !self::$post_id) {
             //get post by meta key from postmeta
            self::$post_id = self::get_post_id_by_salesforce_id(self::$salesforce_id);
        }

        //check if $post_id actually exist in posts table
       /* if( self::$post_id ){
            $_postid = self::$post_id;
            $_post = get_post( self::$post_id, ARRAY_A );
            if ( is_null( $_post ) ) {
                self::$post_id  = 0;
            }
        }   */
        
 


        self::$is_update = false;
        self::$is_deleted = self::is_current_record_deleted();
        $synced_ids_total = self::$synced_ids_total;

        
        if( ! self::ignore_processed_record(false) ){

            if( !self::$post_id &&  self::$is_deleted ){

                //ignored deleted recored that are not added yet.
                self::$ignored_records+=1;
                update_option('ffd_sync_last_id', self::$salesforce_id);
                return;

            } else if( 
                self::$post_id  // record already in db
                && $synced_ids_total // total records in db
                && isset( self::$synced_ids[self::$salesforce_id] ) // check listing exists using sf id
                && self::$listings->totalSize > $synced_ids_total // api listings total is greater then db listings
            ){
                //ignore already processed items so we can add new items first
                self::$ignored_records+=1;
                update_option('ffd_sync_last_id', self::$salesforce_id);
                return;
            } 
        }

        

        $images = self::get_record_images();

        if( self::$post_id && self::ignore_empty_media_record(false, $images) ){
            return;
        }

       

        $args = array(
            'post_title'=> $post_title,
            'post_name'=> $post_name,
            'post_content'=> !empty($post_content) ? $post_content : $post_title,
        );


        if( self::$post_id ){

            $args['ID'] = self::$post_id;
            $args['post_type']  = apply_filters('ffd_listing_posttype', 'listing');
            self::$is_update = true;

        } else {
            $args['comment_status'] = 'closed';
            $args['ping_status'] = 'closed';
            $args['post_author'] = 1;
            $args['post_status'] = 'publish';
            $args['post_type']  = apply_filters('ffd_listing_posttype', 'listing');
        }

        if( self::$is_deleted ){
            self::$trashed_records+=1;
            $args['post_status'] = 'trash';
        }


        //fill in values for post fields.
        foreach( self::$post_fields as $pb_field => $post_field){
            if( isset(self::$record->$pb_field) && '' !== self::$record->$pb_field ){
                $args[$post_field] = self::$record->$pb_field;
            }
        }

        $_args = apply_filters( 'ffd_sync_create_listing_args', $args, self::$record);

        if( $_args && !empty($_args) )
            $args = $_args;

        if( empty($args) ){

            do_action('ffd_logger', array('[FFD Sync]' => self::$sync_type . ' Error: empty args'));

        } else {
           

            if( self::$is_update ){
                $_postid = wp_update_post($args, true);
            } else {
                $_postid = wp_insert_post($args, true);
            }

            if( is_wp_error($_postid) ){

                do_action('ffd_logger', array('[FFD Sync]' => self::$sync_type . ' Error ('.self::$salesforce_id.'): ' . $_postid->get_error_message(), 'args' => $args, 'is_update' => self::$is_update ));

            } else {

                  self::$post_id = $_postid;

                    if( self::$is_deleted ){ 

                        self::$expired_records+=1;
                        do_action('ffd_sync_listing_deleted', self::$post_id);

                    } else {

                        

                        self::add_listing_data();
                        self::add_listing_images($images);

                        if( self::$is_update ){

                            self::$updated_records+=1;
                            do_action('ffd_sync_listing_updated', self::$post_id, self::$record);
    
                        } else {
    
                            self::$new_records += 1;
                            do_action('ffd_sync_listing_added', self::$post_id, self::$record);
                        } 

                        do_action('ffd_sync_listing_created', self::$post_id, self::$record);
                    }
                    
                   
                    
                    //update last sync id
                    update_option('ffd_sync_last_id', self::$salesforce_id);
                    
                    //must use meta
                    update_post_meta(self::$post_id, 'ffd_listing_title', $args['post_title']);
                    update_post_meta(self::$post_id, 'ffd_mls_id', self::$mls_id);
                    update_post_meta(self::$post_id, 'ffd_salesforce_id', self::$salesforce_id);
                    $record_is_deleted = isset(self::$record->IsDeleted) ? self::$record->IsDeleted : '-1';
                    update_post_meta(self::$post_id, 'ffd_record_is_deleted',  $record_is_deleted);
                    
                   
                    self::$synced_ids[self::$salesforce_id] = self::$post_id;
                    self::update_sync_ids(self::$synced_ids);
            }

        }

      

       if( self::$debugger !== false ){
            
            ffd_debug(self::$record, false);
            ffd_debug(self::$post_fields);
            ffd_debug($args, true);

            

            /* ffd_debug(self::$api_settings['instance_url'], $exit=false);
            ffd_debug(self::$query, $exit=false);
            ffd_debug(self::$record, $exit=false);
            ffd_debug(self::$media->records, $exit=false);
            ffd_debug(get_post_meta(self::$post_id, 'ffd_media', true), $exit=true); */
            // ffd_debug(self::$api_settings, $exit=false);
            // ffd_debug(self::$debugger_logs, $exit=true); *
        }  

        //reset listing data container
        self::$record=null;
        self::$media=null;
        self::$post_id=null;
        self::$mls_id=null;
        self::$salesforce_id=null;
        self::$is_update=false;

       


    }



    private static function ignore_empty_media_record($ignore=false, $images){

        if( $ignore == true && empty($images) ){

            // delete listing from db if we don't have images
            // because it either expired or else not of use.
            self::delete_listing_by_post_id(self::$post_id);

            self::$empty_image_records+=1;
            //update last sync id
            update_option('ffd_sync_last_id', self::$salesforce_id);
            self::$synced_ids[self::$salesforce_id] = self::$post_id;
            self::update_sync_ids(self::$synced_ids);
            

            $ignore = true;

        } else {
            $ignore = false;
        }

        return apply_filters('ffd_ignore_empty_media_record', $ignore, self::$salesforce_id);
    }

    /* 
    * use for temporirly ignore already process listings
    */
    private static function ignore_processed_record($ignore=false){

        return apply_filters('ffd_ignore_processed_record', $ignore, self::$salesforce_id);

    }


    public static function add_listing_data(){

        $pre = apply_filters( "pre_ffd_sync_add_listing_data", true, self::$post_id, self::$fields);   

        if( $pre === false )
            return;

        self::$taxonomies = array();

       // ffd_debug(self::$record, false);
        foreach(self::$fields as $pb_field => $field_key ){
            
            //for debugging
            self::$debugger_logs['pb_field'] = $pb_field;

            //echo $pb_field . ' #' . self::$record->$pb_field . '# ' . $meta_key . '<br>';

            if( isset(self::$record->$pb_field) && self::$record->$pb_field !== "" ){

                $field_value = apply_filters('ffd_sync_field_value_before_insert', self::$record->$pb_field, $field_key, $pb_field);
                
                if( empty($field_value) ){
                    continue;
                }

                if( in_array($field_key, self::$post_fields) ){
                    continue;
                }

               
               
                if( strpos($field_key, '|') !== false ){
                    $original_key = $field_key;
                    $original_value = $field_value;

                    $multi_meta = explode('|', $field_key);
                    $multi_meta = array_map('trim', $multi_meta);

                    foreach ($multi_meta as  $field_key) {
                        if( !empty($field_key) ){

                            $field_format = self::get_field_format($field_key, $field_value);
                            $field_format['value'] = self::values_formatter($field_format['value'], $field_format['format']);
                            self::add_single_field_data($field_format);
                        }
                        
                    }

                   

                } else {

                    $field_format = self::get_field_format($field_key, $field_value);
                    self::add_single_field_data($field_format);
                
                }
            }
        }

        
        ffd_debug(self::$taxonomies);
        if( !empty( self::$taxonomies) ){
            self::add_taxonomies_data(self::$post_id,  self::$taxonomies);
        }    

    }


    public static function add_single_field_data($field_format){

        if( 'meta' ===  $field_format['type']){
                
            $field_format['value'] = self::values_formatter($field_format['value'], $field_format['format']);
            self::debugger_log(array($field_format['key'] => $field_format['value']));
            update_post_meta(self::$post_id, $field_format['key'], $field_format['value']);
        
        } else {

            //$field_format['value'] = self::values_formatter($field_format['value'], $field_format['format']);
            self::alt_add_listing_data(self::$post_id, $field_format);
        }

    }
    
   //metakey=RecordKey1+RecordKey2;
    //metakey=somestaticvalue;
    //metakey=somestaticvalue;


    //type:key:format  ( value is get from themapped field )
    //type => meta, cat or tag
    //key => meta key, taxonomy
    //form text, array

    public static function get_field_format($field_key, $field_value='', $default_key=''){

        if( strpos($field_key, '=') !== false ){

            $multi_field = explode('=', $field_key);
            $multi_field = array_map('trim', $multi_field);
            
            

            $multi_field_key = isset($multi_field[0]) && !empty($multi_field[0]) ? $multi_field[0] : $default_key;

            if( !empty($multi_field_key) ){

                $multi_field_value = '';
                if( isset($multi_field[1]) && !empty($multi_field[1])){

                    $multi_field_exp = explode('+', $multi_field[1]);
                    //$pb_fields = array_keys(self::$fields);
                    
                    foreach($multi_field_exp as $key => $exp_field ){
                        if( $exp_field !== " "){

                            $preserve_spaces = $exp_field;
                            $exp_field = trim($exp_field);
                            $preserve_spaces = str_replace($exp_field, '::FIELD::', $preserve_spaces);
                            
                            $exp_field_value = self::find_record_value_by_field(self::$record, $exp_field);
                            
                            if( $exp_field_value )
                                $exp_field_value = str_replace('::FIELD::', $exp_field_value, $preserve_spaces);

                            if( $exp_field_value !== false ){

                                $multi_field_value .= $exp_field_value;
                            
                            } else {
                                $multi_field_value .= $exp_field;
                            }

                        } else {
                            $multi_field_value .= $exp_field;
                        }

                    }

                    return self::get_field_format($multi_field_key, $multi_field_value);
                }

            } else {

                $field_key = str_replace('=', '', $field_key);
                return self::get_field_format($field_key, $field_value);
            }

        } else {

            if( strpos($field_key, ':') !== false ){

                $field_format = explode(':', $field_key);
                $field_format = array_map('trim', $field_format);

                $type =     isset($field_format[0]) ? $field_format[0] : 'meta';
                $key =      isset($field_format[1]) ? $field_format[1] : $field_key;
                $format =   isset($field_format[2]) ? $field_format[2] : 'text';

            } else {

                $type = 'meta';
                $key = trim($field_key);
                $format = 'text';

            }
          
            
        }

        $return = array(
                    'type' => $type, 
                    'key' => $key, 
                    'format' => $format, 
                    'value' => $field_value
        );

        return $return;

    }


    public static function find_record_value_by_field($record, $var){

        /* this search value case insensitive */
        if( !empty($record) ){
            foreach($record as $key => $value) {
                if(strtolower($var) == strtolower($key)) {
                    return $value;
                    break;
                }
            }
        }

        return false;

    }


    public static function get_media_field_format($field_key){

        if( strpos($field_key, ':') !== false ){

            $media_format = explode(':', $field_key);

            $type = isset($media_format[0]) ? $media_format[0] : 'url'; //attachment or url or featured
            $key = isset($media_format[1]) ? $media_format[1] : $field_key;
            $format = isset($media_format[2]) ? $media_format[2] : 'array'; //comma_seperated or array

        } else {

            $type = 'url'; //attachment, or url
            $key = $field_key;
            $format = 'array'; //comma_seperated or array

        }
       
        $return = array(
            'type'  => $type, 
            'key'   => $key, 
            'format' => $format
        );

        return $return;

    }



    public static function get_record_images(){

        $images=array();

        //Save images
        if ( isset(self::$media) && isset(self::$media->records) ){
            foreach (self::$media->records as $media) {
                array_push($images, $media->pba__URL__c);
            }
        } else {
            return false;
        }

        return $images;
    }

    public static function add_listing_images($images){



        $pre = apply_filters( "pre_ffd_sync_add_listing_images", true, self::$post_id, $images);   

        //for debugging
        self::$debugger_logs['pb_field'] = 'media_records';

        if( $pre === false )
            return;

        if( empty($images) ){
         
            self::debugger_log(array('ffd_media' => 'NO images for ' . self::$record->Id));
            return;
        }

        $images = array_unique($images);
        $ffd_featured_image = reset($images);

        $media_field_key = get_option('ffd_propertybase_media_field', 'ffd_media');

        if( 'ffd_media' === $media_field_key ){
           
            self::debugger_log(array($media_field_key => $images));
            update_post_meta(self::$post_id, $media_field_key, $images);

        } else {

            update_post_meta(self::$post_id, 'ffd_media', $images);
            self::alt_add_listing_images(self::$post_id, $media_field_key, $images);
            
        }

        update_post_meta(self::$post_id, 'ffd_featured_image', $ffd_featured_image);

    }

    /* 
    * add field value as tag or category
    */
    public static function alt_add_listing_data($post_id, $field_format){

       

        if( !empty($field_format['key']) ){

           

            $field_format['value'] = self::values_formatter($field_format['value'], $field_format['format']);

            $key = $field_format['key'];
            $value = $field_format['value'];
            $type = $field_format['type'];

            if( !isset(self::$taxonomies[$key]) ) {
                self::$taxonomies[$key] = array();
                self::$taxonomies[$key]['type'] = $type;
               
            }
            self::$taxonomies[$key]['values'][] = $value; 
            
        }

    }


    public static function add_taxonomies_data($post_id, $taxonomies){

            foreach($taxonomies as $taxonomy => $data ){

                $value = implode(';', $data['values']);

                if( $data['type'] === 'category' || $data['type'] === 'cat' ){

                    $categories = self::parse_category_field($taxonomy, $value);
                    self::update_taxonomy_terms($post_id, $taxonomy, $categories);

                } else if( $data['type'] === 'tag' ){

                    $tags = self::parse_tag_field($taxonomy, $value);
                    self::update_taxonomy_terms($post_id, $taxonomy, $tags);

                } 
            }


    }

    public static function parse_category_field($taxonomy, $value, $parent_sep='>'){


        

            if ( empty( $value ) || empty($taxonomy) ) {
                return array();
            }

            $row_terms  = self::explode_values( $value, ';');
            $row_terms = array_map( 'trim', $row_terms);
            $term_ids = array();

           
            foreach ( $row_terms as $row_term ) {
                $parent = null;
                $_terms = array_map( 'trim', explode( $parent_sep , $row_term ) );
                $total  = count( $_terms );

              
                foreach ( $_terms as $index => $_term ) {
                    // Check if category exists. Parent must be empty string or null if doesn't exists.
                    $term = term_exists( $_term, $taxonomy, $parent );
                   
                    if ( is_array( $term ) ) {
                        $term_id = $term['term_id'];
                        
                    } else {
                    
                        $term = wp_insert_term( $_term, $taxonomy, array( 'parent' => intval( $parent ) ) );
                       
                        if ( is_wp_error( $term ) ) {

                            if( self::$debugger !== false  ){
                                ffd_debug(array('taxonomy' => $taxonomy, 'term' => $_term));
                                ffd_debug($term, false);
                                $taxonomies = get_taxonomies();
                                ffd_debug($taxonomies, true);
                            }

                            break; // We cannot continue if the term cannot be inserted.
                        }

                        $term_id = $term['term_id'];
                    }

                    
                    // Only requires assign the last category.
                    if ( ( 1 + $index ) === $total ) {
                        $term_ids[] = (int) $term_id;
                    } else {
                        // Store parent to be able to insert or query categories based in parent ID.
                        $parent = (int) $term_id;
                        $term_ids[] = (int) $parent;
                    }

                    do_action('ffd_added_taxonomy_term', $term_id, $taxonomy);

                }

            }
            
           $term_ids = array_unique($term_ids);
            
            return $term_ids;
        

    }



    public static function parse_tag_field( $taxonomy, $term ) {

        if ( empty( $term ) || empty($taxonomy) ) {
            return array();
        }

        $names = self::explode_values( $term, ';');
        $names = array_map( 'trim', $names);
        $tags_ids  = array();

        foreach ( $names as $name ) {
            $term = get_term_by( 'name', $name, $taxonomy );

            if ( ! $term || is_wp_error( $term ) ) {
                $term = (object) wp_insert_term( $name, $taxonomy );
            }

            if ( ! is_wp_error( $term ) ) {
                $tags_ids[] = $term->term_id;

                do_action('ffd_added_taxonomy_term', $term->term_id, $taxonomy);

            }
        }

        return $tags_ids;
    }


    protected static function update_taxonomy_terms($post_id, $taxonomy, $terms, $force = false ) {

        self::debugger_log(array('taxonomy' => $taxonomy, 'value' => $terms));

        $term_ids = wp_set_post_terms( $post_id, $terms, $taxonomy, false );

        if( !is_wp_error($term_ids) && $term_ids){
            do_action('ffd_listing_updated_terms', $term_ids, $taxonomy, $post_id);
        }

    }

    protected static function explode_values( $value , $delimeter=',') {
        
        $values = explode( $delimeter, $value );
        
        

        return $values;
    }

    protected static function values_formatter( $value, $format='') {
        
        


        if( 'comma_seperated' === $format && is_array($value) ){
            $value = implode(',', $value);
        } else if( 'array' === $format && !is_array($value) && is_string($value) ){
            $value = explode(',', $value);
        } else if( strpos($format, 'split') !== false && !is_array($value) && is_string($value)){
            
            $original = $value;
            $peices =   explode('_', $format);
            $sep    =   isset($peices[1]) ? $peices[1] : 'comma';
            $index  =   isset($peices[2]) ? $peices[2] : '';

            /* $delimeters = array(
                'comma' => ',',
                'space' => ' ',
                'line' => '/\r\n|\r|\n/',
            );

            if( !is_array($value) ){
                $value = implode($delimeters[$sep]);
            } */

            switch ($sep) {
                case 'space':
                    $value = explode(' ', $value);
                    break;
                case 'line':
                    $value = preg_split('/\r\n|\r|\n/', $value);
                    break;
                
                case 'comma':
                        $value = explode(',', $value);
                    break;
                default:
                    $value = $original;
                    break;
            }

            if( $index != '' ){
                $value = isset($value[$index]) ? trim($value[$index]) : $original;
            }

        } else if( 'boolean' === $format ){

            $value = ( !empty($value) ) ? 1 : 0;

        } else if( 'number' === $format ){

            $value = preg_replace('/\D/', '', $value);

        } else if( 'currency' === $format ){

            $value = preg_replace('#[^0-9\.,]#', '', $value);

        } else if( strpos($format, 'is_selected') !== false && !is_array($value) ){

           
            $selected = str_replace('is_selected', '', $format);
            $selected = trim($selected);

            
            
            if( !empty($selected) && ( $value == 1 || $value === true )){
                $value = $selected;
            } else {
                $value = "";
            }

        }  else if( strpos($format, 'children_of') !== false && !is_array($value) && is_string($value)){

            $_parent = str_replace('children_of', '', $format);
            $_parent = trim($_parent);
            if( !empty($_parent) ){
                $peices = explode(';', $value);
               
                $values = array();
                foreach($peices as $peice ){
                    $values[]= $_parent . '>' . $peice;
                }
                $value = implode(';', $values);
            }
               

        } else if( strpos($format, 'child_of') !== false && !is_array($value) && is_string($value)){

        

            $format = trim($format);
            $_parent = explode(' ', $format);
            $_parent = isset($_parent[1]) ? $_parent[1] : '';

            $_parent = trim($_parent);
        
            
            if( !empty($_parent) && isset(self::$record->$_parent)){
                $_parent = self::$record->$_parent;
                $value = $_parent .'>' . $value;
            }

            
            
        }
        

        

        

        return $value;
    }



    /* 
    * Add listing media/images using field key format i.e as wp attachment
    */
    public static function alt_add_listing_images($post_id, $field_key, $images){

        
        $media_format = self::get_media_field_format($field_key);
        
        if(self::$debugger !== false && isset($_GET['ffd_debug_query']) ){
            ffd_debug($media_format, false);
        }

        if( 'featured' === $media_format['type'] ){

            $data = array();
            $data['raw_image_id'] = isset($images[0]) ? $images[0] : null;

            if(self::$debugger !== false && isset($_GET['ffd_debug_query']) ){
                ffd_debug($data, false);
            }
            
            self::set_image_data($post_id, $data, $media_format);

        } else if( !empty($media_format['key']) && 'attachment' === $media_format['type'] ){
            
            $data = array();
            $data['raw_image_id'] = isset($images[0]) ? $images[0] : null;
            $data['raw_gallery_image_ids'] = $images;

            self::set_image_data($post_id, $data, $media_format);

        } else {

            $images = self::values_formatter($images, $media_format['format']);
            self::debugger_log(array($media_format['key'] => $images));
            update_post_meta( $post_id, $media_format['key'], $images);
        }
    }


	public static function set_image_data( $post_id, $data, $media_format) {
		// Image URLs need converting to IDs before inserting.
		if (  isset( $data['raw_image_id'] )) {

            
            if( 'attachment' === $media_format['type'] || 'featured' === $media_format['type'] ){
                $image_id = self::get_attachment_id_from_url( $data['raw_image_id'], $post_id);
                if( 0 !== $image_id ){
                    self::set_image_id( $post_id, $image_id, $media_format);
                }
            } 
		} 

		
		if ( isset( $data['raw_gallery_image_ids'] ) ) {

            
            
            
            if( 'attachment' === $media_format['type'] ){

                $gallery_image_ids = array();

                // Gallery image URLs need converting to IDs before inserting.
                foreach ( $data['raw_gallery_image_ids'] as $image_id ) {
                    $gallery_image_id = self::get_attachment_id_from_url( $image_id, $post_id );
                    if( !is_wp_error($gallery_image_id) && !empty($gallery_image_id) ){
                        $gallery_image_ids[] = $gallery_image_id;
                    } else {

                        if( self::$debugger !== false && 0 !== $gallery_image_id ){
                            ffd_debug($gallery_image_id, true);
                        } 
                       
                    }

                    if ( self::check_timeout() ) {

                        do_action('ffd_sync_timeout_done');
                       
                        self::$did_timeout = true;
                        break;
                    }
                }
                
                self::set_gallery_image_ids( $post_id, $gallery_image_ids, $media_format);
            }
		}
    }
    

    /**
	 * Set main image ID.
	 *
	 * @since 3.0.0
	 * @param int|string $image_id Listing image id.
	 */
	public static function set_image_id( $post_id, $image_id = '', $media_format=array()) {
        
        self::debugger_log(array('featured_image' => $image_id));
        set_post_thumbnail($post_id, $image_id);

		//update_post_meta( $post_id, $meta_key, $image_id );
	}
    

    /**
	 * Set gallery attachment ids.
	 *
	 * @since 3.0.0
	 * @param array $image_ids List of image ids.
	 */
	public static function set_gallery_image_ids( $post_id, $image_ids, $media_format=array()) {


       

		$image_ids = wp_parse_id_list( $image_ids );
		$image_ids = array_filter( $image_ids, 'wp_attachment_is_image' );
        
       
        $image_ids = self::values_formatter($image_ids, $media_format['format']);
        self::debugger_log(array($media_format['key'] => $image_ids));
		update_post_meta( $post_id, $media_format['key'], $image_ids);
	}


    /**
	 * Get attachment ID.
	 *
	 * @param  string $url        Attachment URL.
	 * @param  int    $listing_id Listing ID.
	 * @return int
	 * @throws Exception If attachment cannot be loaded.
	 */
	public static function get_attachment_id_from_url( $url, $listing_id ) {

       

		if ( empty( $url ) ) {

			return 0;
        }
        
        $file_name  = basename( current( explode( '?', $url ) ) );
        $filetype = wp_check_filetype( $file_name, self::ffd_rest_allowed_image_mime_types() );
        if( !$filetype['type'] ){

            $attachment_id = self::insert_attachment_from_url($url, $listing_id);
            
            
            
            if( !$attachment_id  ){
                return 0;
            } else {
                return $attachment_id;
            }
        }

		$id         = 0;
		$upload_dir = wp_upload_dir( null, false );
		$base_url   = $upload_dir['baseurl'] . '/';

		// Check first if attachment is inside the WordPress uploads directory, or we're given a filename only.
		if ( false !== strpos( $url, $base_url ) || false === strpos( $url, '://' ) ) {
			// Search for yyyy/mm/slug.extension or slug.extension - remove the base URL.
			$file = str_replace( $base_url, '', $url );
			$args = array(
				'post_type'   => 'attachment',
				'post_status' => 'any',
				'fields'      => 'ids',
				'meta_query'  => array( // @codingStandardsIgnoreLine.
					'relation' => 'OR',
					array(
						'key'     => '_wp_attached_file',
						'value'   => '^' . $file,
						'compare' => 'REGEXP',
					),
					array(
						'key'     => '_wp_attached_file',
						'value'   => '/' . $file,
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_ffd_attachment_source',
						'value'   => '/' . $file,
						'compare' => 'LIKE',
					),
				),
			);
		} else {
			// This is an external URL, so compare to source.
			$args = array(
				'post_type'   => 'attachment',
				'post_status' => 'any',
				'fields'      => 'ids',
				'meta_query'  => array( // @codingStandardsIgnoreLine.
					array(
						'value' => $url,
						'key'   => '_ffd_attachment_source',
					),
				),
			);
		}

		$ids = get_posts( $args ); // @codingStandardsIgnoreLine.

		if ( $ids ) {
			$id = current( $ids );
		}

		// Upload if attachment does not exists.
		if ( ! $id && stristr( $url, '://' ) ) {
			$upload = self::ffd_rest_upload_image_from_url( $url );

			if ( is_wp_error( $upload ) ) {

                $upload_error = new WP_Error( 'ffd_rest_upload_failed',
                                /* translators: %s: image URL */
                                sprintf( __( 'Error uploading remote image %s.', 'ffd' ), $url ) . ' '
                                /* translators: %s: error message */
                                . sprintf( __( 'Error: %s.', 'ffd' ), $upload->get_error_message() ), array( 'status' => 400 )
                            );

                if( self::$debugger !== false ){
                    ffd_debug($upload_error, true);
                }     
                
				return '';
			}

			$id = self::ffd_rest_set_uploaded_image_as_attachment( $upload, $listing_id );

			if ( ! wp_attachment_is_image( $id ) ) {

                $attach_error = new WP_Error('ffd_sync_unable_to_attach_image', sprintf( __( 'Not able to attach "%s".', 'ffd-integration' ), $url ), array('status'=>400));
                
                
                if( self::$debugger !== false ){
                    ffd_debug($attach_error, true);
                }  

                return $attach_error;
			}

			// Save attachment source for future reference.
			update_post_meta( $id, '_ffd_attachment_source', $url );
		}

		if ( ! $id ) {
            
            $use_error = new WP_Error('ffd_sync_unable_to_use_image', sprintf( __( 'Unable to use image "%s".', 'ffd-integration' ), $url ), array('status'=>400));
                
                
            if( self::$debugger !== false ){
                ffd_debug($use_error, true);
            }  
            return $use_error;
		}

		return $id;
    }
    

    /**
     * Upload image from URL.
     *
     * @since 2.6.0
     * @param string $image_url Image URL.
     * @return array|WP_Error Attachment data or error message.
     */
    public static function ffd_rest_upload_image_from_url( $image_url ) {
        $file_name  = basename( current( explode( '?', $image_url ) ) );
        $parsed_url = wp_parse_url( $image_url );

        // Check parsed URL.
        if ( ! $parsed_url || ! is_array( $parsed_url ) ) {

            $invalid_url = new WP_Error( 'ffd_rest_invalid_image_url', sprintf( __( 'Invalid URL %s.', 'ffd' ), $image_url ), array( 'status' => 400 ) );

            if( self::$debugger !== false ){
                ffd_debug($invalid_url, true);
            }     

            /* translators: %s: image URL */
            return $invalid_url;
        }

        // Ensure url is valid.
        $image_url = esc_url_raw( $image_url );

        // Get the file.
        $response = wp_safe_remote_get(
            $image_url, array(
                'timeout' => 10,
            )
        );

        if ( is_wp_error( $response ) ) {

            $invalid_url = new WP_Error( 'ffd_rest_invalid_remote_image_url',
                                /* translators: %s: image URL */
                                sprintf( __( 'Error getting remote image %s.', 'ffd' ), $image_url ) . ' '
                                /* translators: %s: error message */
                                . sprintf( __( 'Error: %s.', 'ffd' ), $response->get_error_message() ), array( 'status' => 400 )
                            );

            if( self::$debugger !== false ){
                ffd_debug($invalid_url, true);
            }     
                       
            return $invalid_url;
        } elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
            /* translators: %s: image URL */

            $remote_error = new WP_Error( 'ffd_rest_invalid_remote_image_url', sprintf( __( 'Error getting remote image %s.', 'ffd' ), $image_url ), array( 'status' => 400 ) );
            if( self::$debugger !== false ){
                ffd_debug($remote_error, true);
            }

            return $remote_error;
        }


        // Ensure we have a file name and type.
        $wp_filetype = wp_check_filetype( $file_name, self::ffd_rest_allowed_image_mime_types() );

        if ( ! $wp_filetype['type'] ) {
            $headers = wp_remote_retrieve_headers( $response );
            if ( isset( $headers['content-disposition'] ) && strstr( $headers['content-disposition'], 'filename=' ) ) {
                $content     = explode( 'filename=', $headers['content-disposition'] );
                $disposition = end( $content );
                $disposition = sanitize_file_name( $disposition );
                $file_name   = $disposition;
            } elseif ( isset( $headers['content-type'] ) && strstr( $headers['content-type'], 'image/' ) ) {
                $file_name = 'image.' . str_replace( 'image/', '', $headers['content-type'] );
            }
            unset( $headers );

            // Recheck filetype.
            $wp_filetype = wp_check_filetype( $file_name, self::ffd_rest_allowed_image_mime_types() );

            if ( !$wp_filetype['type'] ) {

                $file_type_error = new WP_Error( 'ffd_rest_invalid_image_type', __( 'Invalid image type. (' . $file_name . ')', 'ffd' ), array( 'status' => 400 ) );

                if( self::$debugger !== false ){
                    ffd_debug($file_type_error, false);
                }

                return $file_type_error;
            }
        }

        // Upload the file.
        $upload = wp_upload_bits( $file_name, '', wp_remote_retrieve_body( $response ) );

        if ( $upload['error'] ) {

            $upload_error = new WP_Error( 'ffd_rest_image_upload_error', $upload['error'], array( 'status' => 400 ) );
            
            if( self::$debugger !== false ){
                ffd_debug($upload_error, true);
            }

            return $upload_error;
        }

        // Get filesize.
        $filesize = filesize( $upload['file'] );

        if ( ! $filesize ) {
            @unlink( $upload['file'] ); // @codingStandardsIgnoreLine
            unset( $upload );

            $filesize_error = new WP_Error( 'ffd_rest_image_upload_file_error', __( 'Zero size file downloaded.', 'ffd' ), array( 'status' => 400 ) );

            if( self::$debugger !== false ){
                ffd_debug($filesize_error, true);
            }

            return $filesize_error;
        }

        do_action( 'ffd_rest_api_uploaded_image_from_url', $upload, $image_url );

        return $upload;
    }


    /**
     * Set uploaded image as attachment.
     *
     * @since 2.6.0
     * @param array $upload Upload information from wp_upload_bits.
     * @param int   $id Post ID. Default to 0.
     * @return int Attachment ID
     */
    public static function ffd_rest_set_uploaded_image_as_attachment( $upload, $id = 0 ) {
        $info    = wp_check_filetype( $upload['file'] );
        $title   = '';
        $content = '';

        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            include_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $image_meta = wp_read_image_metadata( $upload['file'] );
        if ( $image_meta ) {
            if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
                $title = ffd_clean( $image_meta['title'] );
            }
            if ( trim( $image_meta['caption'] ) ) {
                $content = ffd_clean( $image_meta['caption'] );
            }
        }

        $attachment = array(
            'post_mime_type' => $info['type'],
            'guid'           => $upload['url'],
            'post_parent'    => $id,
            'post_title'     => $title ? $title : basename( $upload['file'] ),
            'post_content'   => $content,
        );

        $attachment_id = wp_insert_attachment( $attachment, $upload['file'], $id );
        if ( ! is_wp_error( $attachment_id ) ) {
            wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
        } else {

            if( self::$debugger !== false ){
                ffd_debug($attachment_id, true);
            }
        }

        return $attachment_id;
    }



    /**
     * Returns image mime types users are allowed to upload via the API.
     *
     * @since  2.6.4
     * @return array
     */
    public static function ffd_rest_allowed_image_mime_types() {
        return apply_filters(
            'ffd_rest_allowed_image_mime_types', array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'gif'          => 'image/gif',
                'png'          => 'image/png',
                'bmp'          => 'image/bmp',
                'tiff|tif'     => 'image/tiff',
                'ico'          => 'image/x-icon',
                'text'          => 'text/plain',
                /* 'pdf'          => 'application/pdf' */
            )
        );
    }


    private static function insert_attachment_from_url($image_url, $listing_id = null) {
        try
        {
            // Get the file.
            $response = wp_safe_remote_get(
                $image_url, array(
                    'timeout' => 10,
                )
            );

            if ( is_wp_error( $response ) ) {

                $invalid_url = new WP_Error( 'ffd_rest_invalid_remote_image_url',
                                    /* translators: %s: image URL */
                                    sprintf( __( 'Error getting remote image %s.', 'ffd' ), $image_url ) . ' '
                                    /* translators: %s: error message */
                                    . sprintf( __( 'Error: %s.', 'ffd' ), $response->get_error_message() ), array( 'status' => 400 )
                                );

                if( self::$debugger !== false ){
                    ffd_debug($invalid_url, true);
                }     
                        
                return $invalid_url;
            } elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
                /* translators: %s: image URL */

                $remote_error = new WP_Error( 'ffd_rest_invalid_remote_image_url', sprintf( __( 'Error getting remote image %s.', 'ffd' ), $image_url ), array( 'status' => 400 ) );
                if( self::$debugger !== false ){
                    ffd_debug($remote_error, true);
                }

                return $remote_error;
            }


            // Upload the file.
            $upload = wp_upload_bits( basename($image_url, '.jpg') . '.jpg' , '', wp_remote_retrieve_body( $response ) );

            if ( $upload['error'] ) {

                $upload_error = new WP_Error( 'ffd_rest_image_upload_error', $upload['error'], array( 'status' => 400 ) );
                
                if( self::$debugger !== false ){
                    ffd_debug($upload_error, true);
                }

                return $upload_error;
            }

            // Get filesize.
            $filesize = filesize( $upload['file'] );

            if ( ! $filesize ) {
                @unlink( $upload['file'] ); // @codingStandardsIgnoreLine
                unset( $upload );

                $filesize_error = new WP_Error( 'ffd_rest_image_upload_file_error', __( 'Zero size file downloaded.', 'ffd' ), array( 'status' => 400 ) );

                if( self::$debugger !== false ){
                    ffd_debug($filesize_error, true);
                }

                return $filesize_error;
            }

            $id = self::ffd_rest_set_uploaded_image_as_attachment( $upload, $listing_id );
        
            return $id;
        }
        catch(Exception $e)
        {
            if( self::$debugger !== false  ){
                ffd_debug($e->getMessage());
            }
        }
    }

    


    protected static function get_post_id_by_mls_id($mls_id=null){

       

        if( empty($mls_id) )
            return 0;
        
        global $wpdb;
        $posttype = self::$listing_posttype;
        
        //$post_id = $wpdb->get_var("SELECT post_id FROM `".$wpdb->postmeta."` WHERE meta_key='ffd_mls_id' AND meta_value='".$mls_id."';");
        $post_id = $wpdb->get_var("SELECT a.ID as post_id FROM $wpdb->posts a LEFT JOIN $wpdb->postmeta b ON a.ID=b.post_id WHERE a.post_type='$posttype' AND b.meta_key='ffd_mls_id' AND b.meta_value='".$mls_id."';");
        if( !is_null($post_id) )
            return $post_id;

        return 0;
    }


    protected static function get_post_id_by_salesforce_id($salesforce_id=null){

        

        if( empty($salesforce_id) )
            return 0;
        
        global $wpdb;
        $posttype = self::$listing_posttype;

        //$post_id = $wpdb->get_var("SELECT post_id FROM `".$wpdb->postmeta."` WHERE meta_key='ffd_salesforce_id' AND meta_value='".$salesforce_id."';");
        $post_id = $wpdb->get_var("SELECT a.ID as post_id FROM $wpdb->posts a LEFT JOIN $wpdb->postmeta b ON a.ID=b.post_id WHERE a.post_type='$posttype' AND b.meta_key='ffd_salesforce_id' AND b.meta_value='".$salesforce_id."';");
        if( !is_null($post_id) )
            return $post_id;

        return 0;
    }



    public static function debug_init(){

        self::$timeout_limit = 600; //timeout in seconds

        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        //self::update_sync_ids();
        update_option('ffd_sync_last_id', '');
        update_option('ffd_'.self::$platform.'_sync_status', 'idle');
        update_option('ffd_'.self::$platform.'_sync_index', '0');

    }

    public static function sync_init(){

        self::$listing_posttype = ffd_get_listing_posttype();
        self::$last_id = get_option('ffd_sync_last_id');
        self::$last_run = get_option('ffd_sync_last_run', '');
        self::$current_run = get_option('ffd_sync_current_run');
        
        self::$methodUrl = get_option('ffd_sync_method_url');

        if( empty(self::$current_run) ){
            self::$current_run = date("Y-m-d\TH:i:s");
            update_option('ffd_sync_current_run', self::$current_run);
        }

        //this return only posts table fields mapping
        self::$post_fields = self::fields(null, 'post_fields');

        self::$fields = self::fields(null, 'sync');
        self::$where = self::where();
        self::$order = self::order();
        self::$query = self::query();

        if( self::$debugger !== false &&  isset($_GET['ffd_debug_query']) ){
            echo  self::$query ;
        }

       
        
    }


    public static function fields($platform=null, $context='view'){
        
        if( !$platform )
            $platform = self::$platform;
            
        $fields_mapping = get_option( $platform . '_fields_mapping', array());
        $propertybase_default_fields = self::propertybase_api_default_fields();
       
       

        $fields = array();
        $post_fields = array();
        $post_table = array(
            'ID',
            'post_author',
            'post_date', 
            'post_date_gmt', 
            'post_content', 
            'post_title', 
            'post_excerpt', 
            'post_status',
            'post_password', 
            'post_name',
            'post_modified', 
            'post_modified_gmt',
            'post_parent', 
            'guid', 
            'menu_order',
        );

        if ( !empty($fields_mapping) ){

            foreach($fields_mapping as $field_name => $field_settings ){
                if( $field_settings['enabled'] == 1){
                    $key = trim($field_settings['key']);
                    $key_pieces = explode(':', $key);
                    if( in_array($key_pieces[0], $post_table) ){
                        $post_fields[$field_name] = $key_pieces[0];
                    } 
                    
                    $fields[$field_name] = $key;
                    
                  
                }

            }

        } 

        
        if( $context == 'post_fields' ){
            return $post_fields;
        }
 

        if( empty($fields) ){
            foreach($fields_mapping as $field_name => $field_settings ){
                if( isset($propertybase_default_fields[strtolower($field_name)]) ){
                    $fields[$field_name] = 'ffd_' . strtolower(trim(str_replace(array('pba__', '__c'), '', $field_name), '_'));
                }
            }
        }
        

        if( $context == 'meta' ){
            $fields['ffd_media'] = 'ffd_media';
            $fields['ffd_salesforce_id'] = 'ffd_salesforce_id';
            $fields['ffd_mls_id'] = 'ffd_mls_id';
            $fields['ffd_system_source'] = 'ffd_system_source';
        }


        

        
        return $fields;

    }



    public static function defaults_fields(){

        return  $defaults_fields = apply_filters('ffd_sync_default_fields', array(
                            'Id', 
                            'Name',
                            /* 'MLS_ID__c', */
                            'pba__Description_pb__c', 
                            'LastModifiedDate', 
                            'CreatedDate',
                            'isDeleted',
                        ));
    }
    public static function query(){

       $defaults_fields = self::defaults_fields();
        
        self::$default_mapped = apply_filters('ffd_sync_default_mapped', array(
            'post_title'          => 'Name',
            'post_name'           => 'Name',
            'post_content'        => 'pba__Description_pb__c',
            'post_status'         => 'isDeleted',
            'PBmeta_Image_url'    => 'Image_URL__c',
            'PBmeta_Tax_Key'      => 'Code__c',
            'PBmeta_Excerpt'      => 'Excerpt__c',
            'meta_Image_url'      => 'Image_URL__c',
            'meta_Tax_Key'        => 'Tax_Key__c',
            'meta_Excerpt'        => 'Excerpt__c',
        ));

        

        $q = "SELECT Id, (";
            $q .= "SELECT Id, pba__ExternalLink__c,pba__url__c,pba__Title__c FROM pba__PropertyMedia__r WHERE pba__IsOnWebsite__c=true AND isDeleted=false ORDER BY pba__SortOnWebsite__c";
        $q .= "), ";
        $q .="( SELECT ".implode(', ', $defaults_fields).", ";

        
            if ( !empty(self::$fields) ){

                foreach(self::$fields as $field_name => $field_key ){
                    if( !in_array($field_name, $defaults_fields)){
                        $q.= $field_name . ", ";
                    }
                }
                $q = rtrim($q, ", ");

            }

        $q .= " FROM pba__listings__r" . self::$where . ")";

        $q .= " FROM pba__property__c WHERE id IN (select pba__property__c from pba__listing__c" . self::$where . ")";
        
        //skip already processed records 
        //if( isset(self::$last_id) && !empty(self::$last_id) ){
            //$q .=" AND Id>'".self::$last_id."' ";
        //}

        //$q .= self::$order;

        

        return $q;
        

    }

    public static function where(){

        $lastRun=date("Y-m-d\TH:i:s", strtotime("January 1 1900")); 
        
        if ( ! self::ignore_processed_record(false) && self::$last_run && !empty(self::$last_run) ) {
            $lastRun=self::$last_run;
        }

        if( self::$debugger !== false || self::$test_api !== false  ){
            $lastRun=date("Y-m-d\TH:i:s", strtotime("January 1 1900")); 
        }

        $where=" WHERE LastModifiedDate>" . $lastRun . "Z ";
        $and = get_option("ffd_sync_where_condition", "");

        //debug a listing by mls id or pb id
        $debug_where = '';
        if( self::$debugger !== false && isset($_GET['pbid'])){
            $debug_where =" AND Id='".$_GET['pbid']."' ";
        } else if(self::$debugger !== false && isset($_GET['mlsid'])){
            $debug_where =" AND pba__MlsId__c='".$_GET['mlsid']."' ";
        }

        $and = apply_filters( 'ffd_sync_where_condition', $and, self::$platform);


        return $where . " " . $and . $debug_where;
    }

    public static function deleted(){

        $where = '';
        $and = " AND isDeleted = TRUE ";

        

       return $where . $and;
    }


    public static function order(){

        $order = " order by Id DESC ";
        return $order;
    }


    protected static function propertybase_get_listings(){

        //do_action('ffd_logger', array('[FFD Sync]' => 'Error: pb get_listings manually disabled.'));

        //return;

        $url = self::$api_settings['instance_url'] . self::$methodUrl;
        //url = urlencode($url);
        $args = array();
        $args['headers'] = array(
            'Authorization' => 'OAuth ' . self::$api_settings['access_token'],
        );

        $json = self::propertybase_api_request($url, $args);

        
        
        if( is_wp_error($json) || false === $json ){

            if( self::$debugger !== false  ){
                ffd_debug($url, false);
                ffd_debug(self::query(), false);
                ffd_debug($json, true); 
            }

            if( is_wp_error( $json ) ) {
                $error_message = $json->get_error_message();
            }

            do_action('ffd_logger', array('[FFD Sync]' => 'Error: get_listings', 'Error' => $error_message));

            if( self::$test_api ){
                return $json;
            }

            return false;
        }
        
        if( self::$debugger !== false &&  isset($_GET['ffd_debug_query']) ){
            ffd_debug($json, false); 
        }

        $total_listings = null;
        if( !is_wp_error( $json ) ){
            $total_listings = isset($json->totalSize) ? $json->totalSize : 'null';
        }if( is_wp_error( $json ) ) {
            $error_message = $json->get_error_message();
        }
        //do_action('ffd_logger', array('[FFD Sync]' => 'get_listings', 'total: ' . $total_listings, 'args' => $args, 'url' => $url));

        return $json;
    }


    protected static function update_sync_status($status='idle', $index=0){
        self::$sync_status = $status;
        update_option('ffd_'.self::$platform.'_sync_status', $status);
        update_option('ffd_'.self::$platform.'_sync_index', $index);
        
    }


    protected static function get_sync_status(){
        
        $status = get_option('ffd_'.self::$platform.'_sync_status', 'idle');
        $index = get_option('ffd_'.self::$platform.'_sync_index', 0);
        if( $index == '' )
            $index = 0;
        
        return array('status'=> $status, 'index'=>$index);
    }
    

    protected static function get_sync_index(){

        $status = self::get_sync_status();

        return $status['index'];
    }

    

    public static function test_propertybase_query(){

        self::$test_api = true;
        

        self::propertybase_set_api_vars();
        self::sync_init();
        
        if( isset($_GET['ffd_sync_test']) == 'delete' ){
            $query = self::get_delete_listings_query();
            self::$methodUrl = "/services/data/v29.0/query/?q=" . urlencode($query);
        } else if( isset($_GET['ffd_sync_test']) == 'expired' ){

            $query = self::get_expired_listings_query();
            self::$methodUrl ="/services/data/v29.0/sobjects/pba__listing__c/deleted/?" . $query;

        } else {

            self::$methodUrl = "/services/data/v29.0/query/?q=" . self::$query;
           

        }

        $json = self::propertybase_get_listings();
        
        $response = null;
        if( !is_wp_error( $json ) ){
            $response = isset($json->totalSize) ? $json->totalSize : '0';
        }if( is_wp_error( $json ) ) {
            $response = $json->get_error_message();
        }

       

        return '<br> Result: <br> ' .  $response . '<br> Query: <br> ' . self::$query;
        
    

    }


    protected static function update_sync_ids($ids=array()){

        update_option('ffd_'.self::$platform.'_sync_ids', $ids);

        return $ids;
    }

    protected static function get_sync_ids(){

        if( self::$debugger !== false )
            return array();

        $synced_ids = get_option('ffd_'.self::$platform.'_sync_ids', array());
        if( empty($synced_ids) ){
            $synced_ids = array();
        }

        return $synced_ids;
    }


    private static function debugger_log($value){

        if( self::$debugger !== false ){

            $key = isset(self::$debugger_logs['pb_field']) ? self::$debugger_logs['pb_field'] : '__';

            if( isset(self::$debugger_logs['data'][$key]) )
                self::$debugger_logs['data'][$key][] = $value;
            else
                self::$debugger_logs['data'][$key] = $value;
        }

    }


    protected static function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}
		return intval( $memory_limit ) * 1024 * 1024;
    }
    
    protected static function memory_exceeded() {
		$memory_limit   = self::get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;
		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}
		return apply_filters( 'ffd_propertybase_api_memory_exceeded', $return );
	}

    protected static function time_exceeded() {
        if( self::$timeout_limit === -1 || self::$timeout_limit  === 0 )
            return false;

		$finish = self::$start_time + apply_filters( 'ffd_propertybase_api_default_time_limit', self::$timeout_limit ); 
        $return = false;
		if ( time() >= $finish ) {
			$return = true;
		}
		return apply_filters( 'ffd_propertybase_api_time_exceeded', $return );
    }
    
    protected static function check_timeout() {

        self::$prevent_timeouts = apply_filters( 'ffd_sync_prevent_timeouts', self::$prevent_timeouts);

        if( !self::$prevent_timeouts )
            return false;

        return self::$prevent_timeouts && ( self::time_exceeded() || self::memory_exceeded() ) ;
    }


    private static function delete_listing_by_post_id($post_id, $delete_attachments=false){
      
        $deleted = false;
        if ($post_id!=0) {
            if( $delete_attachments ){
                self::delete_post_attachments($post_id);
            }
            $deleted = wp_delete_post($post_id, true);
            if( $deleted ){
                do_action('ffd_sync_listing_deleted', $post_id);
            }
        }

        return ( $deleted !== false ) ? true : false;
    }

    private static function delete_listing($sf_id, $delete_attachments=false)
    {
        $post_id=self::get_post_id_by_salesforce_id($sf_id);
        $deleted = false;
        if ($post_id!=0) {
            if( $delete_attachments ){
                self::delete_post_attachments($post_id);
            }
            $deleted = wp_delete_post($post_id, true);
            if( $deleted ){
                do_action('ffd_sync_listing_deleted', $post_id);
            }
        }

        return ( $deleted !== false ) ? true : false;
    }

    /* *
    * @ $delete_upload  // if false it will only delete db refrence  of attachments for the post.
    */
    private static function delete_post_attachments($post_id, $delete_upload=false)
    {
        global $wpdb;
    
        $args = array(
            'post_type'         => 'attachment',
            'post_status'       => 'any',
            'posts_per_page'    => -1,
            'post_parent'       => $post_id
        );
        $attachments = new WP_Query($args);
        $attachment_ids = array();
        if ($attachments->have_posts()) : while ($attachments->have_posts()) : $attachments->the_post();
            $attachmentid = get_the_id();

            if( $delete_upload ){
                wp_delete_attachment($attachmentid); 
            } else {
                $attachment_ids[] = $attachmentid;
            }

            
        endwhile;
        endif;
        wp_reset_postdata();
        
        if( !$delete_upload ){
            
            if (!empty($attachment_ids)) :
            $delete_attachments_query = $wpdb->prepare('DELETE FROM %1$s WHERE %1$s.ID IN (%2$s)', $wpdb->posts, join(',', $attachment_ids));
            $wpdb->query($delete_attachments_query);
            endif;
        }
    }

    public function is_blank($value) {
        return empty($value) && !is_numeric($value);
    }


    public static function render_content($content, $data=array()){

        if( is_object($data) ){
            $data = self::object_array($data);
        }
        $default_data = array(
            'curr_datetime'     => date("Y-m-d\TH:i:s"),
            'curr_date'         => date("Y-m-d"),
            'curr_time'         => date("h:i:s"),
            'curr_year'         => date("Y"),
            'curr_month'        => date("m"),
            'curr_day'          => date("d"),

            'lasty_datetime'    => date("Y-m-d\TH:i:s",strtotime("-1 year")),
            'last2y_datetime'    => date("Y-m-d\TH:i:s",strtotime("-2 year")),
            'lasty_date'        => date("Y-m-d",strtotime("-1 year")),
            'lasty_time'        => date("h:i:s",strtotime("-1 year")),
            'lasty_year'        => date("Y",strtotime("-1 year")),
            'lasty_month'       => date("m",strtotime("-1 year")),
            'lasty_day'         => date("d"),
        );
        
        $data = array_merge($default_data, $data);

        if (preg_match_all("/{{(.*?)}}/", $content, $m)) {
            foreach ($m[1] as $i => $varname) {
                if( isset($data[$varname]) ){
                    $content = str_replace($m[0][$i], sprintf('%s', $data[$varname]), $content);
                }

            }
        }

        return $content;
    }

}

FFD_Listings_Sync::run();