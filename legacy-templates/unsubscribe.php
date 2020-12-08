
<?php
get_header();
// Template Name: Unsubscribe

if ( have_posts() ) :
          while ( have_posts() ) :
              the_post();
              the_content();
          endwhile;
      else: ?>
<?php endif; ?>
<div style="text-align:center">
    <?php
    $pbid = $_GET["pbid"];
    $reqId=$_GET["rid"];
    $PB_params;

    if(isset($pbid))
    {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'PBID',
                    'value' => $pbid,
                    'compare' => 'LIKE'
                )
            )
        );


        $u= get_users($args);

        $user_id=$u[0]->ID;

        update_user_meta($user_id,'optout','true');

        $PB_params = array (
                'id' => $pbid,
                'optout' => "true"
            );

        $r = FFDL_PB_Request::send_sf_message( $PB_params );

        $data = array(
                'sf_response'  => $r
                );

        echo "All Mailings";

    }
    else
    {
        $rid=$_GET["rid"];

        //Remove the request from PB
        $dbData=FFDL_Searches::search_FromRequestId($rid);

        if(isset($dbData))
            echo "Saved Search " . $dbData["name"];

            FFDL_PB_Request::query("services/apexrest/removerequest?id=" . $rid,array(),"DELETE");

        //HBG_ssearches::del_search( $dbData["id"] );
    }

    get_footer();
    ?>
</div>