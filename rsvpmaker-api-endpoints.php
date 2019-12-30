<?php
/*
RSVPMaker API Endpoints
*/

class RSVPMaker_Listing_Controller extends WP_REST_Controller {
  public function register_routes() {
    $namespace = 'rsvpmaker/v1';
    $path = 'future';

	 register_rest_route( $namespace, '/' . $path, [
      array(
        'methods'             => 'GET',
        'callback'            => array( $this, 'get_items' ),
        'permission_callback' => array( $this, 'get_items_permissions_check' )
            ),

        ]);     
    }

  public function get_items_permissions_check($request) {
    return true;
  }

public function get_items($request) {

    $events = get_future_events();

    if (empty($events)) {

            return new WP_Error( 'empty_category', 'no future events listed', array( 'status' => 404 ) );
    }
    return new WP_REST_Response($events, 200);
  }

	//other functions to override
	//create_item(), update_item(), delete_item() and get_item()

}

class RSVPMaker_Types_Controller extends WP_REST_Controller {
  public function register_routes() {
    $namespace = 'rsvpmaker/v1';
    $path = 'types';

    register_rest_route( $namespace, '/' . $path, [
      array(
        'methods'             => 'GET',
        'callback'            => array( $this, 'get_items' ),
        'permission_callback' => array( $this, 'get_items_permissions_check' )
            ),

        ]);     
    }

  public function get_items_permissions_check($request) {
    return true;
  }

public function get_items($request) {

    $types = get_terms('rsvpmaker-type');
    return new WP_REST_Response($types, 200);
  }

	//other functions to override
	//create_item(), update_item(), delete_item() and get_item()

}

class RSVPMaker_By_Type_Controller extends WP_REST_Controller {
  public function register_routes() {
    $namespace = 'rsvpmaker/v1';
    $path = 'type/(?P<type>[A-Za-z_\-]+)';	  
	  
    register_rest_route( $namespace, '/' . $path, [
      array(
        'methods'             => 'GET',
        'callback'            => array( $this, 'get_items' )
            ),
        ]);     
    }

  public function get_items_permissions_check($request) {
    return true;
  }

public function get_items($request) {
//$posts = rsvpmaker_upcoming_data($atts);

add_filter('posts_join', 'rsvpmaker_join' );
add_filter('posts_groupby', 'rsvpmaker_groupby' );
add_filter('posts_distinct', 'rsvpmaker_distinct' );
add_filter('posts_fields', 'rsvpmaker_select' );
add_filter('posts_where', 'rsvpmaker_where' );
add_filter('posts_orderby', 'rsvpmaker_orderby',99 );
	
	$querystring = "post_type=rsvpmaker&post_status=publish&rsvpmaker-type=".$request['type'];
	$wp_query = new WP_Query($querystring);
  $posts = $wp_query->get_posts();
	
  remove_filter('posts_join', 'rsvpmaker_join' );
  remove_filter('posts_groupby', 'rsvpmaker_groupby' );
  remove_filter('posts_distinct', 'rsvpmaker_distinct' );
  remove_filter('posts_fields', 'rsvpmaker_select' );
  remove_filter('posts_where', 'rsvpmaker_where' );
  remove_filter('posts_orderby', 'rsvpmaker_orderby',99 );  

    if (empty($posts)) {
            return new WP_Error( 'empty_category', 'there is no post in this category '.$querystring, array( 'status' => 404 ) );
    }
    return new WP_REST_Response($posts, 200);
  }

	//other functions to override
	//create_item(), update_item(), delete_item() and get_item()

}

class RSVPMaker_GuestList_Controller extends WP_REST_Controller {
  public function register_routes() {
    $namespace = 'rsvpmaker/v1';
    $path = 'guestlist/(?P<post_id>[0-9]+)';///(?P<nonce>.+)

    register_rest_route( $namespace, '/' . $path, [
      array(
        'methods'             => 'GET',
        'callback'            => array( $this, 'get_items' ),
        'permission_callback' => array( $this, 'get_items_permissions_check' )
            ),
        ]);     
    }

  public function get_items_permissions_check($request) {
    global $rsvp_options;
    $meta = get_post_meta($request['post_id'],"_rsvp_show_attendees",true);
    if($meta)
      return true;
    elseif(($meta == '') && $rsvp_options['show_attendees'])
      return true; //if not explicitly set for event, default is positive value 
    return false;
  }

public function get_items($request) {
    global $wpdb;
    $event = $request['post_id'];
    $sql = "SELECT first,last,note FROM ".$wpdb->prefix."rsvpmaker WHERE event=$event AND yesno=1 ORDER BY id DESC";
    $attendees = $wpdb->get_results($sql); 
    return new WP_REST_Response($attendees, 200);
  }
}

class RSVPMaker_ClearDateCache extends WP_REST_Controller {
  public function register_routes() {
    $namespace = 'rsvpmaker/v1';
    $path = 'clearcache/(?P<post_id>[0-9]+)';

    register_rest_route( $namespace, '/' . $path, [
      array(
        'methods'             => 'GET',
        'callback'            => array( $this, 'get_items' ),
        'permission_callback' => array( $this, 'get_items_permissions_check' )
            ),
        ]);     
    }

  public function get_items_permissions_check($request) {
    return true;
  }

public function get_items($request) {
  delete_transient('rsvpmakerdates');
  return new WP_REST_Response((object) 'deleted rsvpmakerdates transient', 200);
  }
}

class RSVPMaker_Sked_Controller extends WP_REST_Controller {
  public function register_routes() {
    $namespace = 'rsvpmaker/v1';
    $path = 'sked/(?P<post_id>[0-9]+)';///(?P<nonce>.+)

    register_rest_route( $namespace, '/' . $path, [
      array(
        'methods'             => 'GET',
        'callback'            => array( $this, 'get_items' ),
        'permission_callback' => array( $this, 'get_items_permissions_check' )
            ),
        ]);     
    }

  public function get_items_permissions_check($request) {
    return true;
  }

public function get_items($request) {
    $sked = get_post_meta($request['post_id'],'_sked',true);
    return new WP_REST_Response($sked, 200);
  }
}


add_action('rest_api_init', function () {
  $rsvpmaker_sked_controller = new RSVPMaker_Sked_Controller();
  $rsvpmaker_sked_controller->register_routes();
   $rsvpmaker_by_type_controller = new RSVPMaker_By_Type_Controller();
    $rsvpmaker_by_type_controller->register_routes();
     $rsvpmaker_listing_controller = new RSVPMaker_Listing_Controller();
    $rsvpmaker_listing_controller->register_routes();
     $rsvpmaker_types_controller = new RSVPMaker_Types_Controller();
    $rsvpmaker_types_controller->register_routes();
    $rsvpmaker_guestlist_controller = new RSVPMaker_GuestList_Controller();
    $rsvpmaker_guestlist_controller->register_routes();
    $rsvpmaker_meta_controller = new RSVPMaker_ClearDateCache();
    $rsvpmaker_meta_controller->register_routes();
} );

?>