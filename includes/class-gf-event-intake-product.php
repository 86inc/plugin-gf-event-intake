<?php
/**
 * Define the event/product relationship.
 *
 * creates WC products
 *
 * @since      1.0.0
 * @package    Gf_Event_Intake_Product
 * @subpackage Gf_Event_Intake/includes
 * @author     Jonathan Bouganim <jb@86inc.co>
 */
class Gf_Event_Intake_Product {

	const SEATING_ATTR = 'pa_seating';
	const MY_EVENTS_REWRITE = 'my-events';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {

	}

	public static function setup_hooks() {
		add_filter ( 'woocommerce_account_menu_items', [__CLASS__, 'add_account_events_endpoint'], 40 );
		add_action( 'init', [__CLASS__, 'add_account_events_endpoint_rewrite'] );
		add_action( 'woocommerce_account_'.self::MY_EVENTS_REWRITE.'_endpoint', [__CLASS__, 'my_events_endpoint_content'] );
		add_filter( 'genesis_post_title_text', array( __CLASS__, 'change_endpoint_title' ), 11, 1 );
		add_filter( 'woocommerce_settings_pages', array( __CLASS__, 'add_events_settings_page' ), 10, 1 );
	}

	public static function add_events_settings_page($pages = []) {
		//var_dump($pages);
		//exit;
		$create_new_page = array(
			'title'    => __( 'Create Event', 'woocommerce' ),
			/* Translators: %s Page contents. */
			'desc'     => sprintf( __( 'Page contents: [%s]', 'woocommerce' ), apply_filters( 'woocommerce_my_account_shortcode_tag', 'woocommerce_my_account' ) ),
			'id'       => 'woocommerce_createevents_page_id',
			'type'     => 'single_select_page',
			'default'  => '',
			'class'    => 'wc-enhanced-select-nostd',
			'css'      => 'min-width:300px;',
			'args'     => array(
				'exclude' =>
					array(
						wc_get_page_id( 'cart' ),
						wc_get_page_id( 'checkout' ),
					),
			),
			'desc_tip' => true,
			'autoload' => false,
        );
        
        $edit_new_page = array(
			'title'    => __( 'Edit Event', 'woocommerce' ),
			/* Translators: %s Page contents. */
			'desc'     => sprintf( __( 'Page contents: [%s]', 'woocommerce' ), apply_filters( 'woocommerce_my_account_shortcode_tag', 'woocommerce_my_account' ) ),
			'id'       => 'woocommerce_editevent_page_id',
			'type'     => 'single_select_page',
			'default'  => '',
			'class'    => 'wc-enhanced-select-nostd',
			'css'      => 'min-width:300px;',
			'args'     => array(
				'exclude' =>
					array(
						wc_get_page_id( 'cart' ),
						wc_get_page_id( 'checkout' ),
					),
			),
			'desc_tip' => true,
			'autoload' => false,
		);

		$new_pages = array_merge( array_slice( $pages, 0, 3, true ), array( $create_new_page, $edit_new_page ), array_slice( $pages, 3, NULL, true )  );
		return $new_pages;
	}

	/**
	 * Changes page title on my events page.
	 *
	 * @param  string $title original title
	 * @return string        changed title
	 */
	public static function change_endpoint_title( $title ) {
		global $wp;
		
		if ( isset($wp->query_vars[ self::MY_EVENTS_REWRITE ]) ) {
			$title = __('My Events', 'gf-event-intake');
			//remove_filter( 'the_title', array( __CLASS__, 'change_endpoint_title' ), 11, 1 );
		}
		return $title;
	}

	public static function add_account_events_endpoint_rewrite() { 
		add_rewrite_endpoint( self::MY_EVENTS_REWRITE, EP_PAGES );
	}

	public static function add_account_events_endpoint( $menu_links ){
		$menu_links = array_slice( $menu_links, 0, 3, true ) 
		+ array( self::MY_EVENTS_REWRITE => __('My Events', 'gf-event-intake') )
		+ array_slice( $menu_links, 3, NULL, true );
		return $menu_links;
	 
	}

	public static function my_events_endpoint_content() {
		wc_get_template( 'myaccount/my-events.php', array(), 'gf-event-intake', GF_EVENT_INTAKE_PLUGIN_PATH . 'public/templates/' );
	}

	public function create_variable_product( $product_data = array() ) {
        // Before insert
        //$product_data = apply_filters('es_wc_api_before_variable_product_insert', $product_data );
        //do_action('es_wc_api_debug', "Creating a new variable product");
        //Create main product
        $variable_product = new WC_Product_Variable();
        $product_name = self::get_val($product_data, 'name', "");
        $variable_product_data = [
            'name'                      => $product_name,
            'description'               => self::get_val($product_data, 'description', ""),
            'short_description'         => self::get_val($product_data, 'short_description', ""),
            'sku'                       => self::get_val($product_data, 'sku', ""),
            'status'                    => self::get_val($product_data, 'status', "pending"),
            //'tax_status'                => 'taxable',
            //'attributes'                => !empty($attributes) ? $attributes : [], // Array of WC_Product_Attribute objects.
            //'reviews_allowed'           => true,
            //'category_ids'              => array(), // Array of terms IDs. [int]
            //'tag_ids'                   => array(), // Array of terms IDs. [int]
            //'gallery_image_ids'         => array(), // Array of image ids. [int]
            //'image_id'                  => 0, // Product image id. (int)
            //'width'                     => 2.4, // Total width. (float)
            //'height'                    => '', //  Total height. (float)
            //'length'                    => '', // Total length. (float)
            //'weight'                    => self::get_val($product_data, 'weight', ''), // Total weight. (float)
            //'regular_price'             => apply_filters( 'es_wc_api_before_product_price', self::get_val($product_data, 'price', '') ), // Price. (float)
            //'sale_price'                => '', // Price. (float)
            //'price'                     => '', // Price. (float)
            //'manage_stock'              => true, // Boolean,
            //'stock_quantity'            => 0, //  int.
        ];
        $variable_product->set_props( $variable_product_data );
        $variable_product_id = $variable_product->save();

        // If we can't create the product.
        if (empty($variable_product_id)) {
            return new WP_Error('500', __('Cannot create the variable product') );
		}

		// Creates the attributes for the seatings.
		$this->set_product_attributes_seating($variable_product_id, $product_data, $variable_product);

        // First loop through all the attributes so we can insert them in the product now.
        if ( !empty($product_data['attributes']) ) :
            $attr_count = 0;
            foreach( $product_data['attributes'] as $variation_data ) :
                $attr_count++;
				
				$seating_name = !empty( $variation_data['name'] ) ? $variation_data['name'] : '';
                $attribute = $this->attribute_exists( $seating_name );

                // Create it.
                if ( empty($attribute) ) {
                    $attribute = $this->create_attribute($seating_name, self::SEATING_ATTR);
                }
                
                if ( empty($attribute->name) ) {
                    continue;
                }

				$variation_attributes = [
					self::SEATING_ATTR => $attribute->slug,
                ];
                
                if (!empty( $variation_data['sku'] )) {
                    $variation_sku = $variation_data;
                } else {
                    $variation_sku = !empty( $product_data['sku'] ) ? $product_data['sku'] . "-{$attribute->slug}" : "{$variable_product_id}-{$attr_count}"; 
                }

                // Create our product variations.
                // we know the attribute exists and we want to create a product variation now.
                $variation = new WC_Product_Variation();
                $variation_product_data = [
                    'parent_id'                 => $variable_product_id, // for now.
                    'sku'                       => $variation_sku,
                    //'tax_class'                 => self::get_val($variation_data, 'name', false),
                    //'description'               => self::get_val($variation_data, 'description', ""),
                    'attributes'                => $variation_attributes, // Set attributes requires a key/value containing tax and term slug
                    //'image_id'                  => reset($variation_image_ids), // Product image id. (int)
                    //'width'                     => self::get_val($variation_data, 'width', ""), // Total width. (float)
                    //'height'                    => self::get_val($variation_data, 'height', ""), //  Total height. (float)
                    //'length'                    => self::get_val($variation_data, 'length', ""), // Total length. (float)
                    //'weight'                    => self::get_val($variation_data, 'weight', ""), // Total weight. (float)
                    'regular_price'             => self::get_val($variation_data, 'price', 0), // Price. (float)
                    //'sale_price'                => '', // Price. (float)
                    'manage_stock'              => true, // Boolean,
                    'stock_quantity'            => self::get_val($variation_data, 'quantity', 0), //  int.
                ];
                //Save variation, returns variation id
                $variation->set_props( $variation_product_data );
                $variation_id = $variation->save();

            endforeach; 
        endif;        
        $variable_product->save();
        return $variable_product;
	}

	public function set_product_attributes_seating( $product_id, $product_data, $product ) {
        if (!empty($product_data['attributes'])) {

            $wc_attribute = new WC_Product_Attribute();
            $wc_attribute->set_id( wc_attribute_taxonomy_id_by_name( self::SEATING_ATTR ) );
            $wc_attribute->set_name( self::SEATING_ATTR );
            $wc_attribute->set_visible( 1 );
            $wc_attribute->set_variation( 1 );
            $wc_attribute->set_position( 0 );
            
            $options = [];
            foreach($product_data['attributes'] as $seating_name => $seating_attr) {
                $attribute = $this->attribute_exists( $seating_name, self::SEATING_ATTR );

                // Create it.
                if ( empty($attribute) ) {
                    $attribute = $this->create_attribute($seating_name, self::SEATING_ATTR);
                }
                
                if ( empty($attribute->name) ) {
                    continue;
                }

                $options[] = $attribute->name;
            }

            if (!empty($options)) {
                //Set terms slugs
                $wc_attribute->set_options( $options );
                $current_attr = $product->get_attributes();
                $new_attr = array_values($current_attr);
                $new_attr[] = $wc_attribute;
                $product->set_attributes( $new_attr );
                $product->save();
            }   
        }
    }

	/**
     * Check if term attribute exists
     *
     * @param string $term_name
     * @param string $taxonomy
     * @return object
     */
    public function attribute_exists( $term_name = '', $taxonomy = self::SEATING_ATTR ) {
        $term = get_term_by('name', $term_name, $taxonomy );
        return $term;
    }

    /**
     * Create the term attribute.
     *
     * @param string $term_name
     * @param string $taxonomy
     * @param string $color
     * @return boolean|int
     */
    public function create_attribute( $term_name = '', $taxonomy = self::SEATING_ATTR, $color = false ) {        
        $updated = false;
        $inserted = wp_insert_term(
            $term_name, // the term 
            $taxonomy, // the taxonomy
            array(
            'description'=> $term_name,
            //'slug' => 'ash',
            'parent'=> 0,
            ) 
        );

        if ( ! is_wp_error($inserted) ) {
            $inserted_id = (int) $inserted['term_id'];
            $updated = get_term($inserted_id, $taxonomy);
        }
        
        return $updated;
    }
	
	/**
     * Helper to get the index value or property of an array if it exists, default otherwise.
     *
     * @param array|object $map
     * @param string $index
     * @param string $default
     * @return string
     */
    public static function get_val( $map, $index, $default = '' ) {
        if ( is_array($map) ) {
            if ( isset($map[ $index ]) ) {
                return $map[ $index ];
            } else {
                return $default;
            }
        } else if ( is_object($map) ) {
            if ( isset($map->$index) ) {
                return $map->$index;
            } else {
                return $default;
            }
        }
        return $default;
    }

}