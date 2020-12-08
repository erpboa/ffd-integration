<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FFDL_PB_Request {
	public static $base_url;
	//public static $token;
	public static $tokenWeb2Prospect;
	public static $nocache = false;
	public static $cache_hits = 0;
	public static $cache_misses = 0;
	public static $cache_requests = 0;
	public static $cache_expiration = 21600;/* 6 hours */
	public static $ignored_request_params = array(

	);
	protected static $expirations_stack = array();
	protected static $last_response = null;

	protected static $domain = null;
	protected static $subdomain = null;

	public static function start() {
		static $started = false;

		if ( ! $started ) {
			$started = true;
		}
	}

	public static function strip_ignored_request_params( $data ) {
		if ( ! empty( $data ) ) {
			// Allow certain fields to be excluded from cache generation
			foreach ( self::$ignored_request_params as $key ) {
				unset( $data[ $key ] );
			}
		}

		return $data;
	}
	
	public static function get_last_response() {
		return self::$last_response;
	}
    public static function send_sf_message($body, $sfa_id = null) {
        $user_id=null;

        if(is_user_logged_in())
            $user_id=get_current_user_id();

		//echo json_encode($body);
        //die();

        //allowed fields in the $body array: name, email, phone, message
		// optional fields for different purposes: community_name, favorite_ids, property

		self::$tokenWeb2Prospect = apply_filters('ffdl_webtoprospect_token', get_option('ffd_webtoprospect_token'));
		self::$domain  = apply_filters('ffdl_webtoprospect_domain', get_option('ffd_webtoprospect_domain'));
		self::$subdomain  = apply_filters('ffdl_webtoprospect_site', get_option('ffd_webtoprospect_site'));
		
		$query = array (
			'prospect' => array(
				//'wrongFIeld' => 'junk for debug purposes',
				'token' => self::$tokenWeb2Prospect,
				'contact' => array (
					'LeadSource' => self::$subdomain . '.com',
					'Email'      => $body['email'],
					//'Id'         => '0033600000uPctSAAS',
				),
				'contactFields' => array(
					'Email',
					'LastName',
					'Phone'
				),
			)
		);
//$_GET['testing'] = 1;
		if ( ! empty( $body['first_name'] ) ) {
			$query ['prospect']['contact']['FirstName'] =  $body['first_name'];
		}

        if ( ! empty( $body['optout'] ) ) {
            $query['prospect']['contact']['No_Email__c']=$body['optout'];
        }

		if ( ! empty( $body['frontage'] ) ) {
            $query['prospect']['request']['frontage__c'] = implode(";",$body['frontage']);
        }

		if ( ! empty( $body['view'] ) ) {
            $query['prospect']['request']['frontage__c'] = implode(";",$body['view']);
        }

		if ( ! empty( $body['last_name'] ) ) {
			$query ['prospect']['contact']['LastName'] =  $body['last_name'];
		}

		if ( ! empty( $body['name'] ) ) {
			$query ['prospect']['contact']['FirstName'] =  $body['name'];
		}

		if ( ! empty( $body['phone'] ) ) {
			$query ['prospect']['contact']['Phone'] =  $body['phone'];
		}

        if ( ! empty( $body['id'] ) ) {
			$query ['prospect']['contact']['Id'] =  $body['id'];
		}

		if ( ! empty( $body['last_login'] ) ) {
			$query ['prospect']['contact']['Last_Site_Login__c'] =  $body['last_login'];
		}

		//Assign all Leads to user
		/*$query ['prospect']['contact']['OwnerID']="005f4000000pw7E";*/

		if ( ! empty( $sfa_id ) ) {
			$query ['prospect']['contact']['OwnerID'] = $sfa_id;
		}

		if ( ! empty( $body['favorite_ids'] ) ) {
			$query ['prospect']['favoriteListings'] = $body['favorite_ids'];
		}

		if ( isset( $body['property'] ) ) {
			//$query ['prospect']['requestFromListing'] = $body['property'];
			if ( ! empty( $body['property'] ) ) {
				$query ['prospect']['favoriteListings'][] = $body['property'];
			}
		}

        if ( ! empty( $body['ssearch-name'] ) ) {
			$query['prospect']['request']['Search_Name__c'] = $body['ssearch-name'];
        }

		if ( ! empty( $body['interest'] ) ) {
            $query['prospect']['request']['Interested_In__c'] = $body['interest'];
        }

        if ( ! empty( $body['search-frequency'] ) ) {
			$query['prospect']['request']['Email_Frequency__c'] = $body['search-frequency'];
        }

        if ( ! empty( $body['daysm'] ) && strtolower($body['daysm'])!="--please select--" ) {
			$days = str_replace(" day or less","",str_replace(" days or less","",$body["daysm"]));
            $query['prospect']['request']['Days_on_Market__c'] = $days;
        }

        if ( ! empty( $body['requestId'] ) ) {
            $query['prospect']['request']['Id'] = $body['requestId'];
        }

        if ( ! empty( $body['changed'] ) && strtolower($body['changed'])!="--please select--" ) {
			$days = str_replace(" day or less","",str_replace(" days or less","",$body["daysm"]));
            $query['prospect']['request']['Price_Changed_Days_Max__c'] = $days;
        }

        if ( ! empty( $body['brokerage'] ) ) {
            $query['prospect']['request'][self::$subdomain . '_Only__c'] = "true";
        }

        if ( ! empty( $body['pool'] ) ) {
            $query['prospect']['request']['Pool__c'] = $body['pool'];
        }

        if ( ! empty( $body['minprice'] ) ) {
            $query['prospect']['request']['pba__ListingPrice_pb_min__c'] = $body['minprice'];
        }

        if ( ! empty( $body['maxprice'] ) ) {
            $query['prospect']['request']['pba__ListingPrice_pb_max__c'] = $body['maxprice'];
        }

        if ( ! empty( $body['baths'] ) ) {
            $query['prospect']['request']['pba__FullBathrooms_pb_min__c'] = $body['baths'];
        }

        if ( ! empty( $body['tenure'] ) ) {
            $query['prospect']['request']['Tenure__c'] = $body['tenure'];
        }

        if ( ! empty( $body['minsq'] ) ) {
            $query['prospect']['request']['pba__TotalArea_pb_min__c'] = $body['minsq'];
        }

        if ( ! empty( $body['maxsq'] ) ) {
            $query['prospect']['request']['pba__TotalArea_pb_max__c'] = $body['maxsq'];
        }

        if ( ! empty( $body['minacr'] ) ) {
            $query['prospect']['request']['pba__LotSize_pb_min__c'] = $body['minacr'];
        }

        if ( ! empty( $body['maxacr'] ) ) {
            $query['prospect']['request']['pba__LotSize_pb_max__c'] = $body['maxacr'];
        }

        if ( ! empty( $body['proptype'] ) && strtolower($body['proptype'])!="select" ) {
            $query['prospect']['request']['pba__PropertyType__c'] = implode(";",$body['proptype']);
        }

		if ( ! empty( $body['property-type'] ) && $body['property-type']!="Select" ) {
            $query['prospect']['request']['pba__PropertyType__c'] = $body['property-type'];
        }

        if ( ! empty( $body['propset'] ) ) {
            $query['prospect']['request']['Property_Settings__c'] = implode(";",$body['propset']);
        }

        if ( ! empty( $body['district'] ) && $body['district']!="Select" ) {
            $query['prospect']['request']['District__c'] = $body['district'];
        }

        if ( ! empty( $body['status'] ) ) {
            $query['prospect']['request']['Statuses__c'] = implode(";",$body['status']);
        }

        if ( ! empty( $body['beds'] ) ) {
            $query['prospect']['request']['pba__Bedrooms_pb_min__c'] = $body['beds'];
     	}

		if ( ! empty( $body['community_name'] ) ) {
			$query['prospect']['request']['Community__c'] = $body['community_name'];
		}

		if ( ! empty( $body['postalcode'] ) ) {
			$query['prospect']['request']['pba__PostalCode_pb__c'] = $body['postalcode'];
		}

		if ( ! empty( $body['featured'] ) ) {
			$query['prospect']['request'][self::$subdomain . '_Listings'] = true;
		}

		if ( ! empty( $body['message'] )) {
			if ( ! empty( $body['message'] ) ) {
				$query['prospect']['request']['pba__Comments__c'] = $body['message'];
			}
		}

		$query = apply_filters('ffdl_webtoprospect_query', $query);
		
		//ffd_debug($query, true);

		$webtoprospect_path = apply_filters('ffdl_webtoprospect_path', 'services/apexrest/pba/webtoprospect/v1/');

		$result = self::query( $webtoprospect_path, $query, 'POST', false, true );
		
		if ( !is_wp_error($result) && is_string( $result ) ) {
			$result = json_decode( $result, true );
		}

		

        //Save contact id
        if(  !is_wp_error($result) && isset($user_id)){
			update_user_meta($user_id,'PBID',$result["contact"]["Id"]);
		}	
		do_action('ffd_logger', array('ffdl_webtoprospect' => $result));

		return $result;
	}
	public static function query( $path, $data = array(), $method = 'GET', $parse_arrays = true, $json = false ) {

		if ( $parse_arrays ) {
			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					$compare = ! empty( $value['compare'] ) && 'RANGE' == $value['compare'] ? 'RANGE' : 'IN';
					unset( $value['compare'] );
					if ( 'RANGE' == $compare ) {
						$value = array_slice( $value, 0, 2 );
						$data[ $key ] = '[' . implode( ';', $value ) . ']';
					} else {
						$data[ $key ] = 'IN(' . implode( ';', array_unique( $value ) ) . ')';
					}
				}
			}
		}

		self::$base_url = apply_filters('ffdl_webtoprospect_base_url', get_option('ffd_webtoprospect_base_url'));

		$webtoprospect_url = apply_filters('ffdl_webtoprospect_url', self::$base_url . $path);
		$request_url = add_query_arg( $data, $webtoprospect_url );

        $result = false;
		

		if ( false === $result ) {
			if ( 'POST' == $method || 'DELETE' == $method) {
				$post_request = array(
					'method'      => $method,
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(),
					'body'        => $data,
					'cookies'     => array()
				);

				if ($json) {
					$post_request['headers']['Content-Type'] = 'application/json';
					$post_request['body'] = json_encode( $post_request['body'] );
				}

				

                $result = wp_remote_post( $webtoprospect_url, $post_request );
				//$result = wp_remote_get( $request_url );
			} else {
                $result = wp_remote_get( $request_url );
			}
			

			self::$last_response = $result;

			if ( is_wp_error( $result ) ) {
				return $result;
			} elseif ( 200 != wp_remote_retrieve_response_code( $result ) ) {
				$response_body = wp_remote_retrieve_body( $result );
				$response = json_decode( $response_body );

				if ( is_array($response) && isset($response[0]) && isset($response[0]->errorCode)){
          
					$error_code = $response[0]->errorCode;
					$error_message = isset($response[0]->message) ? $response[0]->message : 'No error message found for this error.';
					$error_data = array('url'=>$webtoprospect_url, 'args' => $post_request, 'response' => $response);
			
					return new WP_Error( $error_code, $error_message, $error_data);
			
				}
	
				$result = new WP_Error( 'response_code_not_200', 'The response code for this request is not 200 OK.', $post_request );

				return $result;
			}

			$result = $result['body'];
		}

		return $result;
	}
}
FFDL_PB_Request::start();