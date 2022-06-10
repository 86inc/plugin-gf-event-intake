<?php

/**
 * Define the event post type and related taxonomies.
 */

/**
 * Define the event post type and related taxonomies.
 *
 * Loads and defines the defines the event post type and related taxonomies.
 *
 * @since      1.0.0
 * @package    Gf_Event_Intake
 * @subpackage Gf_Event_Intake/includes
 * @author     Jonathan Bouganim <jb@86inc.co>
 */
class Gf_Event_Intake_Event_CPT {

    const EVENT_SLUG = 'event';
    const VENUE_SLUG = 'venue';

    static $cast_crew_mapping = array(
        'image' => 5,
        'title' => 2,
        'subtitle' => 3,
        'desc' => 4,
    );

    /**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
        add_action( 'admin_menu', [$this, 'add_pending_bubbles'] );
        add_action( 'before_delete_post', array(__CLASS__, 'delete_post_attachments') );
        add_filter( 'template_include', [$this, 'set_event_template'], 99 );

        // Sync post author box
		add_action( 'acf/save_post', array(__CLASS__, 'replace_author_box'), 20);
        add_action( 'save_post_'.self::EVENT_SLUG, array(__CLASS__, 'sync_custom_user_id'), 10, 2 );
        
        add_action( 'save_post_'.self::EVENT_SLUG, array( __CLASS__, 'update_cast_crew_gf_entry'), 20, 3 );
        //add_filter( 'acf/update_value/name=avatar_image', array( __CLASS__, 'update_cast_crew_gf_entry'), 20, 3 );
        //add_filter( 'save_post_'.self::EVENT_SLUG, array( __CLASS__, 'update_cast_crew_gf_entry'), 20, 3 );
        //add_filter( 'save_post_'.self::EVENT_SLUG, array( __CLASS__, 'update_cast_crew_gf_entry'), 20, 3 );
    }

	public static function update_cast_crew_gf_entry( $post_id, $post ) {

        if ( ! is_admin() ) {
            return;
        }

        $cast_crew_raw_entries = get_post_meta($post_id, 'cast_crew_raw_data', true);
        if (!empty($cast_crew_raw_entries)) {
            $cast_crew_entries = explode(",", $cast_crew_raw_entries);
            foreach($cast_crew_entries as $index => $entry_id) {
                $entry = GFAPI::get_entry( $entry_id );
                if (is_wp_error($entry)) {
                    error_log( $entry->get_error_message() );
                    continue;
                }
                $keys = array_keys( self::$cast_crew_mapping );
                foreach($keys as $key) {
                    $meta_key = "cast_crew_{$index}_{$key}";
                    $value = get_post_meta( $post_id, $meta_key, true );
                    if ($key === 'image') {
                        $value = wp_get_attachment_url($value);
                    }
                    $entry[ self::$cast_crew_mapping[ $key ] ] = $value;
                }
                $success = GFAPI::update_entry( $entry, $entry_id );
            }
        }
	}

    public function set_event_template( $template ) {
        if ( is_singular( [ self::EVENT_SLUG ] )  ) {
            $template = GF_EVENT_INTAKE_PLUGIN_PATH . "public/templates/event/single-event.php";
        }
        return $template;
    }

    /**
	 * When updating the post author from the back-end, sync it to the post author.
	 *
	 * @param [WP_Post] $post_id
	 * @return void
	 */
	public static function replace_author_box( $post_id ) {
		if (isset($_POST['post_type']) && ($_POST['post_type'] == self::EVENT_SLUG)) {
			// get new value
			$user_id = Gf_Event_Intake_Admin::get_acf_post_option('author', $post_id);
			if( $user_id ) {
				wp_update_post( array( 'ID'=>$post_id, 'post_author' => $user_id ) ); 
			}
		}	
	}

	/**
	 * Sync the current post author with the custom author meta if it doesn't exist.
	 *
	 * @param [int] $post_id
	 * @param WP_Post $post
	 * @return void
	 */
	public static function sync_custom_user_id( $post_id, $post ) {
		$custom_user_id = Gf_Event_Intake_Admin::get_acf_post_option('author', $post_id);
		if (!empty($post->post_author) && empty($custom_user_id)) {
			update_post_meta($post_id, 'author', $post->post_author);
		}
	}

    public static function get_create_event_page_link($fallback = false) {
        $create_event_page_id = get_option('woocommerce_createevents_page_id');
        if (!empty($create_event_page_id)) {
            $create_event_page = get_permalink($create_event_page_id);
        } else {
            $create_event_page = !empty($fallback) ? $fallback : apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) );
        }
        return $create_event_page;
    }

    public static function get_edit_event_page_link( $id = 0, $fallback = false) {
        $edit_event_page_id = get_option('woocommerce_editevent_page_id');
        if (!empty($edit_event_page_id)) {
            $edit_event_page = get_permalink($edit_event_page_id);
            if (!empty($id)) {
                $edit_event_page = add_query_arg( [ 'post_id'=> $id ], $edit_event_page );
            }
        } else {
            $edit_event_page = !empty($fallback) ? $fallback : apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) );
        }
        return $edit_event_page;
    }

    public static function get_my_events_page_link( $fallback = false ) {
        $events_link = !empty($fallback) ? $fallback : apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) );
        $account_url = get_permalink( wc_get_page_id( 'myaccount' ) );
        if (!empty($account_url)) {
            $events_link = rtrim($account_url , "/" ) . "/"  . Gf_Event_Intake_Product::MY_EVENTS_REWRITE;
        }
        return $events_link;
    }

    public static function get_user_events( $user_id = 0, $status = 'any' ) {
        $args = [
            'post_type' => self::EVENT_SLUG,
            'post_status'  => $status,
            'posts_per_page' => -1,
            'author' => $user_id,
            'orderby' => 'post_date',
            'order' => 'ASC',
        ];

        return get_posts($args);
    } 

    /**
     * Action hook to delete all associated media with an imported post.
     *
     * @param int $id
     * @return void
     */
    public static function delete_post_attachments( $id ) {    
        // The attachment could be used by more than one post i.e. deleting one language, in which case, don't delete    
        if ( wp_attachment_is_image( $id ) ) {
			$thumbnail_query = new WP_Query( array(
				'meta_key'       => '_thumbnail_id',
				'meta_value'     => $id,
				'post_type'      => 'any',	
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'posts_per_page' => -1,
			) );

			if ($thumbnail_query->posts > 1) {
                return;
            }
		}

		$attachments = get_attached_media( '', $id );
		foreach ($attachments as $attachment) {
			wp_delete_attachment( $attachment->ID, 'true' );
		}
	}
    
    /**
     * Add the pending posts count in the admin menu as a bubble/badge for easy moderation.
     *
     * @return void
     */
    public function add_pending_bubbles() {
		global $menu;
        $post_types = [ self::EVENT_SLUG, self::VENUE_SLUG ];
        foreach($post_types as $post_type) :
            $custom_post_count = wp_count_posts($post_type);
            $custom_post_pending_count = $custom_post_count->pending;
            if ( $custom_post_pending_count ) {
                foreach ( $menu as $key => $value ) {
                    if ( $menu[$key][2] == 'edit.php?post_type=' . $post_type ) {
                        $menu[$key][0] .= "<span class=\"update-plugins count-{$custom_post_pending_count}\"><span class=\"plugin-count\">{$custom_post_pending_count}</span></span>";
                    }
                }
            }
        endforeach;    
	}

	/**
	 * Load the event post types.
	 *
	 * @since    1.0.0
	 */
	public function register_cpt() {
        $this->register_event_cpt();
        $this->register_venue_cpt();
    }
    
    public function register_event_cpt() {
        $labels = array(
            'name'                  => _x( 'Events', 'Event General Name', 'gf-event-intake' ),
            'singular_name'         => _x( 'Event', 'Event Singular Name', 'gf-event-intake' ),
            'menu_name'             => __( 'Events', 'gf-event-intake' ),
            'name_admin_bar'        => __( 'Event', 'gf-event-intake' ),
            'archives'              => __( 'Event Archives', 'gf-event-intake' ),
            'attributes'            => __( 'Event Attributes', 'gf-event-intake' ),
            'parent_item_colon'     => __( 'Parent Event:', 'gf-event-intake' ),
            'all_items'             => __( 'All Events', 'gf-event-intake' ),
            'add_new_item'          => __( 'Add New Event', 'gf-event-intake' ),
            'add_new'               => __( 'Add New', 'gf-event-intake' ),
            'new_item'              => __( 'New Event', 'gf-event-intake' ),
            'edit_item'             => __( 'Edit Event', 'gf-event-intake' ),
            'update_item'           => __( 'Update Event', 'gf-event-intake' ),
            'view_item'             => __( 'View Event', 'gf-event-intake' ),
            'view_items'            => __( 'View Events', 'gf-event-intake' ),
            'search_items'          => __( 'Search Event', 'gf-event-intake' ),
            'not_found'             => __( 'Not found', 'gf-event-intake' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'gf-event-intake' ),
            'featured_image'        => __( 'Featured Image', 'gf-event-intake' ),
            'set_featured_image'    => __( 'Set featured image', 'gf-event-intake' ),
            'remove_featured_image' => __( 'Remove featured image', 'gf-event-intake' ),
            'use_featured_image'    => __( 'Use as featured image', 'gf-event-intake' ),
            'insert_into_item'      => __( 'Insert into item', 'gf-event-intake' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'gf-event-intake' ),
            'items_list'            => __( 'Events list', 'gf-event-intake' ),
            'items_list_navigation' => __( 'Events list navigation', 'gf-event-intake' ),
            'filter_items_list'     => __( 'Filter items list', 'gf-event-intake' ),
        );
        $args = array(
            'label'                 => __( 'Event', 'gf-event-intake' ),
            'description'           => __( 'Events', 'gf-event-intake' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
            'taxonomies'            => array( 'category', 'post_tag' ),
            'hierarchical'          => true,
            'public'                => true,
            'rewrite'               => array( 'slug' => self::EVENT_SLUG ),
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_rest'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-calendar',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => 'events',
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'event',
        );
        register_post_type( self::EVENT_SLUG, $args );

        $tax_labels = array(
            'name'                       => _x( 'Event Type', 'Taxonomy General Name', 'gf-event-intake' ),
            'singular_name'              => _x( 'Event Type', 'Taxonomy Singular Name', 'gf-event-intake' ),
            'menu_name'                  => __( 'Event Types', 'gf-event-intake' ),
            'all_items'                  => __( 'All Event Types', 'gf-event-intake' ),
            'parent_item'                => __( 'Parent Event Type', 'gf-event-intake' ),
            'parent_item_colon'          => __( 'Parent Event Type:', 'gf-event-intake' ),
            'new_item_name'              => __( 'New Event Type Name', 'gf-event-intake' ),
            'add_new_item'               => __( 'Add New Event Type', 'gf-event-intake' ),
            'edit_item'                  => __( 'Edit Event Type', 'gf-event-intake' ),
            'update_item'                => __( 'Update Event Type', 'gf-event-intake' ),
            'view_item'                  => __( 'View Event Type', 'gf-event-intake' ),
            'separate_items_with_commas' => __( 'Separate items with commas', 'gf-event-intake' ),
            'add_or_remove_items'        => __( 'Add or remove items', 'gf-event-intake' ),
            'choose_from_most_used'      => __( 'Choose from the most used', 'gf-event-intake' ),
            'popular_items'              => __( 'Popular Event Types', 'gf-event-intake' ),
            'search_items'               => __( 'Search Event Types', 'gf-event-intake' ),
            'not_found'                  => __( 'Not Found', 'gf-event-intake' ),
            'no_terms'                   => __( 'No items', 'gf-event-intake' ),
            'items_list'                 => __( 'Event Types list', 'gf-event-intake' ),
            'items_list_navigation'      => __( 'Event Types list navigation', 'gf-event-intake' ),
        );
        $tax_args = array(
            'labels'                     => $tax_labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
        );
        register_taxonomy( 'event_type', array( self::EVENT_SLUG, 'product' ), $tax_args );

    }


    public function register_venue_cpt() {
        $labels = array(
            'name'                  => _x( 'Venues', 'Venue General Name', 'gf-event-intake' ),
            'singular_name'         => _x( 'Venue', 'Venue Singular Name', 'gf-event-intake' ),
            'menu_name'             => __( 'Venues', 'gf-event-intake' ),
            'name_admin_bar'        => __( 'Venue', 'gf-event-intake' ),
            'archives'              => __( 'Venue Archives', 'gf-event-intake' ),
            'attributes'            => __( 'Venue Attributes', 'gf-event-intake' ),
            'parent_item_colon'     => __( 'Parent Venue:', 'gf-event-intake' ),
            'all_items'             => __( 'All Venues', 'gf-event-intake' ),
            'add_new_item'          => __( 'Add New Venue', 'gf-event-intake' ),
            'add_new'               => __( 'Add New', 'gf-event-intake' ),
            'new_item'              => __( 'New Venue', 'gf-event-intake' ),
            'edit_item'             => __( 'Edit Venue', 'gf-event-intake' ),
            'update_item'           => __( 'Update Venue', 'gf-event-intake' ),
            'view_item'             => __( 'View Venue', 'gf-event-intake' ),
            'view_items'            => __( 'View Venues', 'gf-event-intake' ),
            'search_items'          => __( 'Search Venue', 'gf-event-intake' ),
            'not_found'             => __( 'Not found', 'gf-event-intake' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'gf-event-intake' ),
            'featured_image'        => __( 'Featured Image', 'gf-event-intake' ),
            'set_featured_image'    => __( 'Set featured image', 'gf-event-intake' ),
            'remove_featured_image' => __( 'Remove featured image', 'gf-event-intake' ),
            'use_featured_image'    => __( 'Use as featured image', 'gf-event-intake' ),
            'insert_into_item'      => __( 'Insert into item', 'gf-event-intake' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'gf-event-intake' ),
            'items_list'            => __( 'Venues list', 'gf-event-intake' ),
            'items_list_navigation' => __( 'Venues list navigation', 'gf-event-intake' ),
            'filter_items_list'     => __( 'Filter items list', 'gf-event-intake' ),
        );
        $args = array(
            'label'                 => __( 'Venue', 'gf-event-intake' ),
            'description'           => __( 'Event Venue', 'gf-event-intake' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'author', 'excerpt', 'custom-fields' ),
            'taxonomies'            => array(),
            'hierarchical'          => true,
            'public'                => true,
            'rewrite'               => array( 'slug' => self::VENUE_SLUG ),
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_rest'          => true,
            'menu_position'         => 6,
            'menu_icon'             => 'dashicons-location',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => 'venues',
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
        );
        register_post_type( self::VENUE_SLUG, $args );
    }
}