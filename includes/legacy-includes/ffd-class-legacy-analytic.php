<?php
class FFDAnalytics
{   
    protected static $retry_on_db_error = true;

    static function activation(){

       

        if( !wp_next_scheduled('SyncAnalytics') ){
            wp_schedule_event(time(),'hourly','SyncAnalytics');
        }


        //Setup database
        self::setupdb();

    }
    static function deactivation(){
        wp_clear_scheduled_hook('SyncAnalytics');
    }
    private static function setupdb(){
        global $wpdb;
       
        $charset_collate = $wpdb->get_charset_collate();

        //Setup analytics table
        $analyticsTableName= $wpdb->prefix . "FFDAnalytics";

        $sql = "CREATE TABLE $analyticsTableName (
                id int NOT NULL AUTO_INCREMENT,
                listingId nvarchar(255) NOT NULL,
                userId int,
                createddate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                sfId nvarchar(255),
                recorded bit,
                PRIMARY KEY (id),
                INDEX listing_mlsid (listingId)
                ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $result = dbDelta( $sql );


    }
    public static function recordAnalytics($listingId){
        global $wpdb;
                
        $userid=get_current_user_id();
        //$cur_date = date('Y-m-d G:i:s'); 

        $sql = '';

        if(is_user_logged_in())
        {
            $sql = "insert into {$wpdb->prefix}FFDAnalytics (listingId,userId,sfId, createddate) " .
            "values(%s,%d,%s, %s)";
    
            $sfid=get_user_meta($userid,"PBID",true);

            $date_utc = new DateTime('now', new DateTimeZone("UTC"));
            $utc_time = $date_utc->format("Y-m-d\TG:i:s");

            $sql=$wpdb->prepare($sql,$listingId,$userid,$sfid, $utc_time);
            
        }    
        else
        {
            $sql = "insert into {$wpdb->prefix}FFDAnalytics (listingId)" .
            "values(%s)";
    
            $sql=$wpdb->prepare($sql,$listingId);
        }

        $result = $wpdb->query($sql);
        
        if($wpdb->last_error)
        {   
            if( self::$retry_on_db_error== true){

                self::$retry_on_db_error = false;

                //retry creating table;
                self::setupdb();
                return self::recordAnalytics($listingId);
            }
            $err=$wpdb->last_error;
            do_action('ffd_logger', array('recordAnalytics DB Error: ' => $err));

        }
    }

    public static function SyncAnalytics()
    {
      
        global $wpdb;
        $hits=array();

        $sql="select id,listingid,createddate,sfid from {$wpdb->prefix}FFDAnalytics where recorded is null limit 200";

        $results = $wpdb->get_results($sql);
     

        if($wpdb->last_error || empty($results) )
        {   
            $error = $wpdb->last_error;
            $log_data = array('result_empty' => empty($results), 'error' => $error);
            //do_action('ffd_logger', array('recordAnalytics Sync SQL: ' => $log_data ) );
        }
        $index=1;
        foreach ($results as $result) {
            //Save a request
            $hit=array();

            $atts=array();
            $atts["type"]="analytic__c";
            $atts["referenceId"]="ref" . $index;
            
            $hit["attributes"]=$atts;
            $hit["Listing__c"]=$result->listingid;

            

            $hit["Date__c"]=date("c",strtotime($result->createddate));

            if(isset($result->sfid) && $result->sfid!="")
                $hit["Contact__c"]=$result->sfid;

            array_push($hits,$hit);

            $sql="update {$wpdb->prefix}FFDAnalytics set recorded=true where id=" . $result->id;
            $wpdb->query($sql);

            $index+=1;
        }
        
        if(count($hits)>0)
        {
            $args=array();
            
            $args['headers']['Content-Type'] = 'application/json; charset=utf-8';
            $args['body']= json_encode(array("records" => $hits));
            $args['method'] = 'POST';
            $args['data_format'] = 'body';

                
            $json= FFD_PropertyBase_API::propertybase_api_post_analytics($args);
            
            

            if( is_wp_error( $json ) ) {
            
                do_action('ffd_logger', array('recordAnalytics Sync Error: ' => $json));

            } else if( false !== $json )  {
    
            } else {
               
            }

        } else {
            
        }

    }
}

add_action('SyncAnalytics', array('FFDAnalytics', 'SyncAnalytics'));
