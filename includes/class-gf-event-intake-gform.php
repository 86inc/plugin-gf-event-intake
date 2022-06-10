<?php
/**
 * Setup our hooks related to gravity forms.
 *
 * Loads and defines the defines the event post type and related taxonomies.
 *
 * @since      1.0.0
 * @package    Gf_Event_Intake_GForm
 * @subpackage Gf_Event_Intake_GForm/includes
 * @author     Jonathan Bouganim <jb@86inc.co>
 */
class Gf_Event_Intake_GForm {

	const AS_DISABLE_PRODUCT_HOOK = 'gf_event_intake_disable_product';

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

	private $intake_form_id = '1';
	private $register_form_id = '2';
	private $edit_form_id = '6';
	private $performance_form_id = '4';
	private $seating_crew_form_id = '5';
	private $cast_crew_form_id = '3';

	private $create_events_page;

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
		$this->setup_hooks();
		
		$this->intake_form_id = Gf_Event_Intake_Admin::get_acf_option('create_event_intake_form_id');
		$this->register_form_id = Gf_Event_Intake_Admin::get_acf_option('register_user_form_id');
		$this->edit_form_id = Gf_Event_Intake_Admin::get_acf_option('edit_event_intake_form_id');
		$this->cast_crew_form_id = Gf_Event_Intake_Admin::get_acf_option('cast_crew_intake_form_id');
		$this->performance_form_id = Gf_Event_Intake_Admin::get_acf_option('performance_intake_form_id');
		$this->seating_crew_form_id = Gf_Event_Intake_Admin::get_acf_option('seating_intake_form_id');
	}
	
	public function setup_hooks() {
		// Dynamically load the event types for the select dropdown.
		add_filter( "gform_pre_render_{$this->intake_form_id}", [$this, 'load_event_types_options'] );
		add_filter( "gform_pre_validation_{$this->intake_form_id}", [$this, 'load_event_types_options'] );
		add_filter( "gform_pre_submission_filter_{$this->intake_form_id}", [$this, 'load_event_types_options'] );
		add_filter( "gform_admin_pre_render_{$this->intake_form_id}", [$this, 'load_event_types_options'] );

		// Dynamically load the venue options for the select dropdown.
		add_filter( "gform_pre_render_{$this->intake_form_id}", [$this, 'load_venue_options'] );
		add_filter( "gform_pre_validation_{$this->intake_form_id}", [$this, 'load_venue_options'] );
		add_filter( "gform_pre_submission_filter_{$this->intake_form_id}", [$this, 'load_venue_options'] );
		add_filter( "gform_admin_pre_render_{$this->intake_form_id}", [$this, 'load_venue_options'] );

		// styles
		add_filter( "gform_progressbar_start_at_zero", '__return_true' );
		add_filter( 'gpnf_enable_feed_processing_setting', '__return_true' );
		add_filter( 'gpnf_should_process_feed', '__return_true' );
		
		// Disable Spam Filters
		add_filter( "gform_entry_is_spam_{$this->intake_form_id}", '__return_false' );
		add_filter( "gform_entry_is_spam_{$this->edit_form_id}", '__return_false' );
		add_filter( "gform_entry_is_spam_{$this->performance_form_id}", '__return_false' );
		add_filter( "gform_entry_is_spam_{$this->seating_crew_form_id}", '__return_false' );
		add_filter( "gform_entry_is_spam_{$this->cast_crew_form_id}", '__return_false' );

		// Hide the default gform anchor.
		add_filter( "gform_confirmation_anchor_{$this->intake_form_id}", '__return_false' );

		// Create an scheduled event to disable all variations at the scheduled time cutoff.
		add_filter('acf/update_value/name=sales_cut_off_datetime', [$this,'schedule_product_cutoff_action'], 10, 2);
		add_action( self::AS_DISABLE_PRODUCT_HOOK, [$this, 'disable_product_variations'], 10, 1 );

		// Add Gravity Form merge tags to insert.
		add_action( 'gform_admin_pre_render', [$this, 'add_gform_merge_tags'] );
		add_filter( 'gform_replace_merge_tags', [$this,'replace_merge_tags'], 10, 7 );

		// Redirect non-logged in users to register before they can create an event.
		add_filter( "gform_pre_render_{$this->intake_form_id}", [$this,'redirect_to_register'], 10, 3 );
		
		// Redirect to my account if they are already logged in.
		add_filter( "gform_pre_render_{$this->register_form_id}", [$this,'redirect_to_myaccount'], 10, 3 );
		// After registering, redirect users to the redirect_to param to continue where they left off.
		add_action( "gform_confirmation_{$this->register_form_id}", [$this, 'custom_confirmation'], 10, 4 );

		// Apply to all post object fields. Add links to easily navigate to the associated products.
		add_action('acf/render_field/type=post_object', [__CLASS__,'append_post_links']);	

		// Disable the default WP new user registration email as we have a custom confirmation email setup.
		remove_action( 'register_new_user', 'wp_send_new_user_notifications' );

		// Edit filters.
		// Add image preview of file uploads to edit page.
		add_filter( "gform_field_content_{$this->edit_form_id}", [$this, 'update_image_content_fields_featured_image'], 10, 5 );
		add_filter( "gform_field_content_{$this->cast_crew_form_id}", [$this, 'update_image_content_fields_cast_crew'], 10, 5 );

		// Delete events/products after edit form submissions.
		// add_action( "gform_after_submission_{$this->edit_form_id}", [$this, 'cleanup_events'], 10, 2 );

		// Assign the cast/performance/seating meta to products.
		add_action( "gform_advancedpostcreation_post_after_creation", [$this, 'set_event_data'], 10, 4 );

		// For the event edit page, provide the correct entry ID related to that event.
		add_filter( "gpep_source_entry_id", [$this,'replace_source_entry_id'], 10, 2 );

		//add_filter( "gform_addon_pre_process_feeds_{$this->edit_form_id}", [$this,'preprocess_feeds'], 10, 3 );	

		add_action( "gform_after_submission_{$this->edit_form_id}", [$this, 'map_edit_form_fields'], 10, 2 );
		add_action( "gform_advancedpostedit_post_after_edit", [$this, 'set_edit_data'], 10, 4 );

		// Not used but sets the values for existing forms based on our data.
		//add_filter( "gform_pre_render_{$this->edit_form_id}", [$this, 'populate_html'] );
	}

	public function map_edit_form_fields( $entry, $form ) {
		//getting post
		//$post = get_post( $entry['post_id'] );

		$create_events_page = Gf_Event_Intake_Event_CPT::get_create_event_page_link();
		// Ensure we have an event to edit.
		$event_id = ! empty( $_REQUEST['post_id'] ) ? preg_replace('#[^\d]#i', '', $_REQUEST['post_id']) : false;
		if (empty( $event_id )) {
			wp_redirect( $create_events_page );
		}
		
		// Ensure it's an event, and the author is the logged in user.
		$event_post = get_post($event_id);
		if (empty($event_post) || ($event_post->post_type !== Gf_Event_Intake_Event_CPT::EVENT_SLUG) || (intval($event_post->post_author) !== intval(get_current_user_id() ) ) ) {
			wp_redirect( $create_events_page );
		}
	

		$event_post->post_title = rgar( $entry, '24' );
		$event_post->post_content = rgar( $entry, '23' );
		$event_post->total_duration = rgar( $entry, '16' );
		$event_post->intermission_duration = rgar( $entry, '17' );
		$event_post->sales_cut_off = rgar( $entry, '30' );

		wp_update_post( $event_post );

// 24 => string 'Street Care Named Desire2' (length=25)
//   23 => string 'EVENT DESC' (length=10)
//   21 => string 'Play' (length=4)
//   22 => string '' (length=0)
//   16 => string '120' (length=3) // show length in min
//   17 => string '10' (length=2) // intermission minutes
//   30 => string '1' (length=1) // cutoff time.
//   20 => string '137' (length=3)
//   32 => string '138' (length=3)
//   34 => string '139' (length=3)
//   '9.1' => string '' (length=0)
//   '9.2' => string '' (length=0)
//   '9.3' => string '' (length=0)
//   '9.4' => string '' (length=0)
//   '9.5' => string '' (length=0)
//   '9.6' => string '' (length=0)
//   4 => string '' (length=0)
//   11 => string '' (length=0)
//   27 => string '' (length=0)

		// //changing post content
		// $post->post_content = 'Blender Version:' . rgar( $entry, '7' ) . "<br/> <img src='" . rgar( $entry, '8' ) . "'> <br/> <br/> " . rgar( $entry, '13' ) . " <br/> <img src='" . rgar( $entry, '5' ) . "'>";
	 
		// //updating post
		// wp_update_post( $post );
	}

	public function preprocess_feeds($feeds, $entry, $form) {
		error_log("processing feed");
		return $feeds;
	}

	public function update_image_content_fields_featured_image( $content, $field, $value, $lead_id, $form_id ) {
		$event_id = ! empty( $_REQUEST['post_id'] ) ? preg_replace('#[^\d]#i', '', $_REQUEST['post_id']) : false;
		if (empty( $event_id )) {
			return $content;
		}

		if ( $field->id == 22 ) {
			$image_url = get_the_post_thumbnail_url($event_id, 'thumbnail');
			if (!empty($image_url)) {
				ob_start();
				?>
				<div class="ginput_preview">
				<img alt="Delete file" class="gform_delete" src="/wp-content/plugins/gravityforms/images/delete.png" onclick="gformDeleteUploadedFileEventIntake(<?php echo $form_id ?>, <?php echo $field->id ?>, this);" onkeypress="gformDeleteUploadedFileEventIntake(<?php echo $form_id ?>, <?php echo $field->id ?>, this);"> 
				<strong><?php echo basename($image_url); ?></strong>
				</div>
				<div class="image-preview">
					<img src="<?php echo esc_url( $image_url ) ?>" style="width:150px;">
				</div>
				<?php
				$content .= ob_get_clean();
			}
			
		}

		return $content;
	}

	public function update_image_content_fields_cast_crew( $content, $field, $value, $lead_id, $form_id ) {
		if ( $field->id == 5 && !empty($value) ) {
			ob_start();
			?>
			<div class="image-preview">
				<img src="<?php echo esc_url( $value ) ?>" style="width:150px;">
			</div>
			<?php
			$content .= ob_get_clean();
		}

		return $content;
	}

	public function replace_source_entry_id( $source_entry_id = 0, $form_id = 0 ) {
		// Ensure they are logged in.
		$create_events_page = Gf_Event_Intake_Event_CPT::get_create_event_page_link();
		if ( ! is_user_logged_in() ) {
			wp_redirect( $create_events_page );
		}
	
		// Ensure we have an event to edit.
		$event_id = ! empty( $_REQUEST['post_id'] ) ? preg_replace('#[^\d]#i', '', $_REQUEST['post_id']) : false;
		if (empty( $event_id )) {
			wp_redirect( $create_events_page );
		}

		// Ensure it's an event, and the author is the logged in user.
		$event_post = get_post($event_id);
		if (empty($event_post) || ($event_post->post_type !== Gf_Event_Intake_Event_CPT::EVENT_SLUG) || (intval($event_post->post_author) !== intval(get_current_user_id() ) ) ) {
			wp_redirect( $create_events_page );
		}

		$entry_id = get_post_meta($event_id, 'entry_id', true);
		if (empty($entry_id)) {
			wp_redirect( $create_events_page );
		}

		$source_entry_id = $entry_id;
		return $source_entry_id;
	}

	
	public function populate_html( $form ) {
		// Ensure they are logged in.
		$create_events_page = Gf_Event_Intake_Event_CPT::get_create_event_page_link();
		if ( ! is_user_logged_in() ) {
			wp_redirect( $create_events_page );
		}

		// Ensure we have an event to edit.
		$event_id = ! empty( $_REQUEST['post_id'] ) ? preg_replace('#[^\d]#i', '', $_REQUEST['post_id']) : false;
		if (empty( $event_id )) {
			wp_redirect( $create_events_page );
		}

		// Ensure it's an event, and the author is the logged in user.
		$event_post = get_post($event_id);
		if (empty($event_post) || ($event_post->post_type !== Gf_Event_Intake_Event_CPT::EVENT_SLUG) || (intval($event_post->post_author) !== intval(get_current_user_id() ) ) ) {
			wp_redirect( $create_events_page );
		}

		foreach ( $form['fields'] as &$field ) {
			if (empty($field->inputName)) {
				continue;
			}

			if ( ! preg_match('#(event|venue)+\.(.+)#i', $field->inputName, $matches) ) {
				continue;
			}

			$post_type = $matches[1];
			$field_name = $matches[2];
			if ($field_name === 'cast') {
				$field_value = '92';
			}
			else if ($post_type == Gf_Event_Intake_Event_CPT::EVENT_SLUG) {
				$field_value = $event_post->$field_name;
			}
			//$field_default = get_post_meta($event_id, $field->inputName, true);
			if (empty($field_value)) {
				continue;
			}
			$field->defaultValue = $field_value;
		}	

		//var_dump($form['fields'][2]);
		//$form['fields'][2]->defaultValue = '648';
		return $form;
		//this is a 2-page form with the data from page one being displayed in an html field on page 2
		$current_page = GFFormDisplay::get_current_page( $form['id'] );
		$html_content = "The information you have submitted is as follows:<br/><ul>";
		if ( $current_page == 2 ) {
			foreach ( $form['fields'] as &$field ) {
				//gather form data to save into html field (id 6 on my form), exclude page break
				if ( $field->id != 6 && $field->type != 'page' ) {
					//see if this is a complex field (will have inputs)
					if ( is_array( $field->inputs ) ) {
						//this is a complex fieldset (name, adress, etc.) - get individual field info
						//get field's label and put individual input information in a comma-delimited list
						$html_content .= '<li>' .$field->label . ' - ';
						$num_in_array = count( $field->inputs );
						$counter = 0;
						foreach ( $field->inputs as $input ) {
							$counter++;
							//get name of individual field, replace period with underscore when pulling from post
							$input_name = 'input_' . str_replace( '.', '_', $input['id'] );
							$value = rgpost( $input_name );
							$html_content .= $input['label'] . ': ' . $value;
							if ( $counter < $num_in_array ) {
								$html_content .= ', ';
							}
						}
						$html_content .= "</li>";
					} else {
						//this can be changed to be a switch statement if you need to handle each field type differently
						//get the filename of file uploaded or post image uploaded
						if ( $field->type == 'fileupload' || $field->type == 'post_image' ) {
							$input_name = 'input_' . $field->id;
							//before final submission, the image is stored in a temporary directory
							//if displaying image in the html, point the img tag to the temporary location
							$temp_filename = RGFormsModel::get_temp_filename( $form['id'], $input_name );
							$uploaded_name = $temp_filename['uploaded_filename'];
							$temp_location = RGFormsModel::get_upload_url( $form['id'] ) . '/tmp/' . $temp_filename['temp_filename'];
							if ( !empty( $uploaded_name ) ) {
								$html_content .= '<li>' . $field->label . ': ' . $uploaded_name . "<img src='" . $temp_location . "' height='200' width='200'></img></li>";
							}
						} else {
							//get the label and then get the posted data for the field (this works for simple fields only - not the field groups like name and address)
							$field_data = rgpost('input_' . $field->id );
							if ( is_array( $field_data ) ){
								//if data is an array, get individual input info
								$html_content .= '<li>' . $field->label . ': ';
								$num_in_array = count( $field_data );
								$counter = 0;
								foreach ( $field_data as $data ) {
									$counter++;
									$html_content .= print_r( $data, true );
									if ( $counter < $num_in_array ) {
										$html_content .= ', ';
									}
								}
								$html_content .= '</li>';
							}
							else {
								$html_content .= '<li>' . $field->label . ': ' . $field_data . '</li>';
							}
						}
					}
				}
			}
			$html_content .= '</ul>';
			//loop back through form fields to get html field (id 6 on my form) that we are populating with the data gathered above
			foreach( $form['fields'] as &$field ) {
				//get html field
				if ( $field->id == 6 ) {
					//set the field content to the html
					$field->content = $html_content;
				}
			}
		}
		//return altered form so changes are displayed
		return $form;
	}

	public static function append_post_links( $field ) {
		if (!empty($field['value'])) {
			$values = is_string($field['value']) ? array( $field['value'] ) : $field['value'];
			printf('</br><div style="margin-top: 15px;" class="acf-label"><label>%s:</label></div>', __('Links', 'gf-event-intake'));
			foreach($values as $post_id) {
				$edit_link = get_edit_post_link($post_id);
				$view_link = get_permalink($post_id);
				$post = get_post($post_id);
				if (!empty($edit_link)) {
					printf('<div style="margin-bottom: 5px;">%s <strong>(%s)</strong><a style="padding: 0 8px 0 8px;" target="_blank" href="%s">%s</a><a style="padding: 0 8px 0 8px;" target="_blank" href="%s">%s</a></div>', $post->post_title, $post->post_status, $edit_link, __('Edit', 'gf-event-intake'), $view_link, __('View', 'gf-event-intake') );
				}
			}
		}
	}

	public function redirect_to_register( $form, $ajax, $field_values ) {

		if ( rgar( $form, 'requireLogin' ) ) {
			if ( ! is_user_logged_in() ) {
				$create_event_page_id = get_option('woocommerce_createevents_page_id');
				$slug = 'create-event';
        		if (!empty($create_event_page_id)) {
					$create_event_page = get_post($create_event_page_id);
					$slug = $create_event_page->post_name;
        		}
				$register_url = get_site_url(null, 'register?redirect-to='.$slug);
				wp_redirect( $register_url );
			}
		}
		return $form;
	}

	public function redirect_to_myaccount( $form, $ajax, $field_values ) {
		if ( is_user_logged_in() ) {
			$url = get_permalink( wc_get_page_id( 'myaccount' ) );
			wp_redirect( $url );
		}
		return $form;
	}

	public function custom_confirmation( $confirmation, $form, $entry, $ajax ) {
		if (!empty($_GET['redirect-to'])) {
			$redirect_path = preg_replace('#[^a-zA-Z0-9\-\_]#i', '', $_GET['redirect-to'] );
			$redirect_url = get_site_url(null, $redirect_path);
			$confirmation = array( 'redirect' => $redirect_url );
		}
		return $confirmation;
	}

	public function disable_product_variations( $variable_product_id = false ) {
		$product = wc_get_product( $variable_product_id );
		if ($product instanceof WC_Product_Variable) {
			$available_variations = $product->get_available_variations();
			if (!empty($available_variations)) {
				foreach($available_variations as $variation_data) {
					$variation_id = $variation_data['variation_id'];
					$variation = new WC_Product_Variation($variation_id);
					$variation->set_status('private');
					$variation->save();
				}
			}
		}
	}

	public function schedule_product_cutoff_action( $value, $post_id ) {
		if( !empty($value) ) {
			$args = [ $post_id ];
			$cutoff_time = strtotime("{$value}") - (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ); // it's minus because we are converting it back to GMT timestamp for insertion.
			// Only set if it's in the future.
			if ($cutoff_time > current_time('timestamp')) {
				as_unschedule_all_actions( self::AS_DISABLE_PRODUCT_HOOK, $args );
				as_schedule_single_action( $cutoff_time, self::AS_DISABLE_PRODUCT_HOOK, $args );
			}
		}
		return $value;
	}

	public function cleanup_events( $entry, $form ) {
		// Ensure we have an event to edit.
		$entry_id = $entry['id'];
		$event_id = ! empty( $_REQUEST['post_id'] ) ? preg_replace('#[^\d]#i', '', $_REQUEST['post_id']) : false;
		if (empty( $event_id )) {
			error_log("Could not delete previous old event - {$entry_id}");
			return;
		}

		// Ensure it's an event, and the author is the logged in user.
		$event_post = get_post($event_id);
		if (empty($event_post) || ($event_post->post_type !== Gf_Event_Intake_Event_CPT::EVENT_SLUG) || (intval($event_post->post_author) !== intval(get_current_user_id() ) ) ) {
			error_log("Could not delete previous old event - wrong post type or user - {$entry_id}");
			return;
		}

		// First we delete the associated products, this will delete the associated meta and images.
		$related_product_ids = get_post_meta($event_id, "related_products", true );
		if (!empty($related_product_ids)) {
			foreach($related_product_ids as $related_product_id) {
				wp_delete_post($related_product_id, false);
			}
		}

		// Next we delete the event.
		wp_delete_post($event_id, false);
		return;
	}
	
	public function set_event_data( $post_id, $feed, $entry, $form ) {
		//$update = false;
		$entry_id = $entry['id'];
		if ( ( (int) $form['id'] ) !== ( (int) $this->intake_form_id ) && ( (int) $form['id'] ) !== ( (int) $this->edit_form_id ) ) {
			return;
		}

		// If we are updating.
		// if (( ( (int) $form['id'] ) === ( (int) $this->edit_form_id ) )) {
		// 	$update = true;
		// 	$event_id = ! empty( $_REQUEST['post_id'] ) ? (int) preg_replace('#[^\d]#i', '', $_REQUEST['post_id']) : false;
		// 	if (empty( $event_id )) {
		// 		error_log("Could not update new event - {$entry_id}");
		// 	}
		// }
		
		//getting post and thumbnail image
		$post = get_post( $post_id );
		$entry_id = $entry['id'];
		update_post_meta($post_id, 'entry_id', $entry_id);
		if ($post->post_type === Gf_Event_Intake_Event_CPT::VENUE_SLUG) {
			gform_update_meta( $entry_id, 'venue_post_id', $post_id );
		} 
		else if ($post->post_type === Gf_Event_Intake_Event_CPT::EVENT_SLUG) {
			// Connect the event and venue data.
			gform_update_meta( $entry_id, 'event_post_id', $post_id );
			$created_venue_post_id = gform_get_meta($entry_id, 'venue_post_id');
			if (!empty($created_venue_post_id)) {
				update_post_meta($post_id, 'venue', (int) $created_venue_post_id);
			}

			// Insert the Cast&Crew data.
			$cast_crew_entries_string = get_post_meta($post_id, 'cast_crew_raw_data', true);
			if (!empty($cast_crew_entries_string)) {
				$cast_crew_entries = explode(",", $cast_crew_entries_string);
				$count = 0;
				foreach($cast_crew_entries as $index => $cast_entry_id) {
					$cast_entry = GFAPI::get_entry( $cast_entry_id );
					if (empty($cast_entry)) {
						continue;
					}

					$thumbnail_entry_key = Gf_Event_Intake_Event_CPT::$cast_crew_mapping['image'];
					if (!empty($cast_entry[ $thumbnail_entry_key ])) {
						$attachment_id = attachment_url_to_postid($cast_entry[ $thumbnail_entry_key ]);
						if (!empty($attachment_id)) {
							update_post_meta($post_id, "cast_crew_{$index}_image", $attachment_id );
							//gform_update_meta($cast_entry_id, 'attachment_id', $attachment_id);
							$attachment_data = array(
								'ID' => $attachment_id,
								'post_parent' => $post_id,
							);
							wp_update_post($attachment_data);
						}
					}
					update_post_meta($post_id, "cast_crew_{$index}_title", $cast_entry[ Gf_Event_Intake_Event_CPT::$cast_crew_mapping['title'] ] );
					update_post_meta($post_id, "cast_crew_{$index}_subtitle", $cast_entry[ Gf_Event_Intake_Event_CPT::$cast_crew_mapping['subtitle'] ] );
					update_post_meta($post_id, "cast_crew_{$index}_desc", $cast_entry[ Gf_Event_Intake_Event_CPT::$cast_crew_mapping['desc'] ] );
					$count++;
				}
				update_post_meta($post_id, "cast_crew", $count );
			}

			// Prepare the seating data.
			$seating_attrs = [];
			$seating_entries_string = get_post_meta($post_id, 'seating_raw_data', true);
			if (!empty($seating_entries_string)) {
				$seating_entries = explode(",", $seating_entries_string);				
				foreach($seating_entries as $index => $seating_entry_id) {
					$seating_entry = GFAPI::get_entry( $seating_entry_id );
					if (empty($seating_entry)) {
						continue;
					}

					$seating_type = !empty($seating_entry[1]) ? $seating_entry[1] : '';
					$max_qty = !empty($seating_entry[2]) ? $seating_entry[2] : '';
					$price = !empty($seating_entry[3]) ? $seating_entry[3] : '';

					if (!empty($seating_type)) {
						gform_update_meta( $seating_entry_id, 'seating_type', $seating_type );
						$seating_attrs[$seating_type] = [
							'name'		=> $seating_type,
							'quantity' => (int) $max_qty,
							'price'	=> $price,
						];
					}
				}
			}

			// Create the product data.
			$dates_entries_string = get_post_meta($post_id, 'dates_raw_data', true);
			$product_api = new Gf_Event_Intake_Product();
			$related_product_ids = [];
			if (!empty($dates_entries_string)) {
				// Create a product for each date
				$content = sprintf('%s </br></br>Event: <a href="%s" target="_blank">%s</a>', $post->post_content, get_permalink($post_id), $post->post_title);
				$product_data = [
					//'name'                      => $post->post_title,
					'description'               => $content,
					'short_description'         => $content,
					'sku'                       => $post->post_name,
					'status'					=> 'pending',
				];
				// Set the product featured image.
				$thumbnail_id = get_post_thumbnail_id($post_id);
				if (!empty($thumbnail_id)) {
					$product_data['image_id'] = $thumbnail_id;
				}
				$date_entries = explode(",", $dates_entries_string);
				foreach($date_entries as $index => $date_entry_id) :
					$date_entry = GFAPI::get_entry( $date_entry_id );
					if (empty($date_entry)) {
						continue;
					}

					$start_date = !empty($date_entry[1]) ? $date_entry[1] : '';
					$start_time = !empty($date_entry[4]) ? $date_entry[4] : '';
					$end_date = !empty($date_entry[3]) ? $date_entry[3] : '';
					$end_time = !empty($date_entry[2]) ? $date_entry[2] : '';

					$performance_date = "{$start_date} {$start_time}";
					$performance_end_date = "{$end_date} {$end_time}";
					$performance_datetime_gmt = strtotime("{$performance_date}") - (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
					$performance_date_string = wp_date('YmdHi', $performance_datetime_gmt);
					
					$product_data['name'] = "{$post->post_title} - {$performance_date}";
					$product_data['attributes'] = $seating_attrs;
					
					$product_data['sku']	 = "{$post_id}-{$performance_date_string}";
					$variable_product = $product_api->create_variable_product( $product_data );
					if (is_wp_error($variable_product)) {
						$msg = $variable_product->get_error_message();
						error_log("GF-IN - Could not create product - {$msg}");
					} else {
						$variable_product_id = $variable_product->get_id();
						gform_update_meta( $date_entry_id, 'product_id', $variable_product_id );
						$related_product_ids[] = $variable_product_id;
						update_post_meta($variable_product_id, 'performance_date', $performance_date);
						update_post_meta($variable_product_id, 'performance_end_date', $performance_end_date);
						update_post_meta($variable_product_id, 'event_post_id', $post_id);
						update_post_meta($variable_product_id, '_ticket', 'yes');
						if (!empty($thumbnail_id)) {
							update_post_meta($variable_product_id, '_thumbnail_id', $thumbnail_id);
						}
						// Set the event terms.
						$event_types = get_the_terms($post_id, 'event_type');
						if (!empty($event_types)) {
							$event_terms = wp_list_pluck($event_types, 'term_id');
							wp_set_object_terms($variable_product_id, $event_terms, 'event_type');
						}

						$cutoff_hours = get_post_meta($post_id, 'sales_cut_off', true);
						if (!empty($cutoff_hours)) {
							// // it's minus because we are converting it back to GMT timestamp for insertion.
							$cutoff_time = $performance_datetime_gmt - (((int) $cutoff_hours) * HOUR_IN_SECONDS);
							$cutoff_datetime = wp_date('Y-m-d H:i:s', $cutoff_time);
							update_post_meta($variable_product_id, 'sales_cut_off_datetime', $cutoff_datetime);
							$this->schedule_product_cutoff_action($cutoff_datetime, $variable_product_id);	
						}
					}
				endforeach;
				// Set related products.
				if (!empty($related_product_ids)) {
					update_post_meta($post_id, "related_products", $related_product_ids );
				}
			}

		} // end create event post
	}

	public function set_edit_data( $post_id, $feed, $entry, $form ) {
		$update = false;
		$entry_id = $entry['id'];
		if ( ( (int) $form['id'] ) !== ( (int) $this->edit_form_id ) ) {
			return;
		}

		// If we are updating.
		// if (( ( (int) $form['id'] ) === ( (int) $this->edit_form_id ) )) {
		// 	$update = true;
		// 	$event_id = ! empty( $_REQUEST['post_id'] ) ? preg_replace('#[^\d]#i', '', $_REQUEST['post_id']) : false;
		// 	if (empty( $event_id )) {
		// 		error_log("Could not update new event - {$entry_id}");
		// 	}
		// }
		
		//getting post and thumbnail image
		$post = get_post( $post_id );
		$entry_id = $entry['id'];
		error_log("After POST UPDATE for {$post_id} post type {$post->post_type}");
		//update_post_meta($post_id, 'entry_id', $entry_id);
		if ($post->post_type === Gf_Event_Intake_Event_CPT::VENUE_SLUG) {
			gform_update_meta( $entry_id, 'venue_post_id', $post_id );
		} 
		else if ($post->post_type === Gf_Event_Intake_Event_CPT::EVENT_SLUG) {
			// Connect the event and venue data.
			// gform_update_meta( $entry_id, 'event_post_id', $post_id );
			// $created_venue_post_id = gform_get_meta($entry_id, 'venue_post_id');
			// if (!empty($created_venue_post_id)) {
			// 	update_post_meta($post_id, 'venue', (int) $created_venue_post_id);
			// }

			// Insert the Cast&Crew data.
			$cast_crew_entries_string = get_post_meta($post_id, 'cast_crew_raw_data', true);
			if (!empty($cast_crew_entries_string)) {
				$cast_crew_entries = explode(",", $cast_crew_entries_string);
				$count = 0;
				foreach($cast_crew_entries as $index => $cast_entry_id) {
					$cast_entry = GFAPI::get_entry( $cast_entry_id );
					if (empty($cast_entry)) {
						continue;
					}

					if (!empty($cast_entry[5])) {
						$thumbnail_entry_key = Gf_Event_Intake_Event_CPT::$cast_crew_mapping['image'];
						$attachment_id = attachment_url_to_postid($cast_entry[ $thumbnail_entry_key ]);
						if (!empty($attachment_id)) {
							update_post_meta($post_id, "cast_crew_{$index}_image", $attachment_id );
							//gform_update_meta($cast_entry_id, 'attachment_id', $attachment_id);
							$attachment_data = array(
								'ID' => $attachment_id,
								'post_parent' => $post_id,
							);
							wp_update_post($attachment_data);
						}
					}
					update_post_meta($post_id, "cast_crew_{$index}_title", $cast_entry[ Gf_Event_Intake_Event_CPT::$cast_crew_mapping['title'] ] );
					update_post_meta($post_id, "cast_crew_{$index}_subtitle", $cast_entry[ Gf_Event_Intake_Event_CPT::$cast_crew_mapping['subtitle'] ] );
					update_post_meta($post_id, "cast_crew_{$index}_desc", $cast_entry[ Gf_Event_Intake_Event_CPT::$cast_crew_mapping['desc'] ] );
					$count++;
				}
				update_post_meta($post_id, "cast_crew", $count );
			}

			// Prepare the seating data.
			$seating_attrs = [];
			$seating_entries_string = get_post_meta($post_id, 'seating_raw_data', true);
			if (!empty($seating_entries_string)) {
				$seating_entries = explode(",", $seating_entries_string);				
				foreach($seating_entries as $index => $seating_entry_id) {
					$seating_entry = GFAPI::get_entry( $seating_entry_id );
					if (empty($seating_entry)) {
						continue;
					}

					$seating_type = !empty($seating_entry[1]) ? $seating_entry[1] : '';
					$max_qty = !empty($seating_entry[2]) ? $seating_entry[2] : '';
					$price = !empty($seating_entry[3]) ? $seating_entry[3] : '';

					if (!empty($seating_type)) {
						gform_update_meta( $seating_entry_id, 'seating_type', $seating_type );
						$seating_attrs[$seating_type] = [
							'name'		=> $seating_type,
							'quantity' => (int) $max_qty,
							'price'	=> $price,
						];

						// $wc_attribute = new WC_Product_Attribute();
						// $wc_attribute->set_id( wc_attribute_taxonomy_id_by_name( self::COLOUR_TAX ) );
						// $wc_attribute->set_name( self::COLOUR_TAX );
						// $wc_attribute->set_visible( 1 );
						// $wc_attribute->set_variation( 1 );
						// $wc_attribute->set_position( 0 );
					}
				}
			}

			// Create the product data.
			$dates_entries_string = get_post_meta($post_id, 'dates_raw_data', true);
			$product_api = new Gf_Event_Intake_Product();
			$update = false;
			$related_product_ids = [];
			if (!empty($dates_entries_string)) {
				// Create a product for each date
				$content = sprintf('%s </br></br>Event: <a href="%s" target="_blank">%s</a>', $post->post_content, get_permalink($post_id), $post->post_title);
				$product_data = [
					//'name'                      => $post->post_title,
					'description'               => $content,
					'short_description'         => $content,
					'sku'                       => $post->post_name,
					'status'					=> 'pending',
				];
				// Set the product featured image.
				$thumbnail_id = get_post_thumbnail_id($post_id);
				if (!empty($thumbnail_id)) {
					$product_data['image_id'] = $thumbnail_id;
				}
				$date_entries = explode(",", $dates_entries_string);
				foreach($date_entries as $index => $date_entry_id) :
					$date_entry = GFAPI::get_entry( $date_entry_id );
					if (empty($date_entry)) {
						continue;
					}

					$pid = gform_get_meta($date_entry_id, 'product_id');
					if (!empty( $pid )) {
						$variable_product_id = (int) $pid;
						$variable_product = wc_get_product($variable_product_id);
						if (empty($variable_product)) {
							continue;
						}
						$update = true;
					}
					
					$start_date = !empty($date_entry[1]) ? $date_entry[1] : '';
					$start_time = !empty($date_entry[4]) ? $date_entry[4] : '';
					$end_date = !empty($date_entry[3]) ? $date_entry[3] : '';
					$end_time = !empty($date_entry[2]) ? $date_entry[2] : '';

					$performance_date = "{$start_date} {$start_time}";
					$performance_end_date = "{$end_date} {$end_time}";
					$performance_datetime_gmt = strtotime("{$performance_date}") - (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
					$performance_date_string = wp_date('YmdHi', $performance_datetime_gmt);
					
					if ($update) {
						// $variable_product->set_description( $content );
						// $variable_product->set_image_id( $thumbnail_id );
						// $variable_product->set_short_description( $content );
						// $variable_product->set_name( "{$post->post_title} - {$performance_date}" );
						// $variable_product->set_sku( "{$post_id}-{$performance_date_string}" );
						// $variable_product->set_attributes($seating_attrs);
						unset($product_data['status']);
						$product_data['name'] = "{$post->post_title} - {$performance_date}";
						$product_data['attributes'] = $seating_attrs;
						$product_data['sku']	 = "{$post_id}-{$performance_date_string}";
						$variable_product->set_props($product_data);
						$variable_product->save();
					} else {
						$product_data['name'] = "{$post->post_title} - {$performance_date}";
						$product_data['attributes'] = $seating_attrs;
						$product_data['sku']	 = "{$post_id}-{$performance_date_string}";
						$variable_product = $product_api->create_variable_product( $product_data );
					}
					
					if (is_wp_error($variable_product)) {
						$msg = $variable_product->get_error_message();
						error_log("GF-IN - Could not create/update product - {$msg}");
					} else {
						$variable_product_id = $variable_product->get_id();
						$related_product_ids[] = $variable_product_id;
						if (!$update) {
							gform_update_meta( $date_entry_id, 'product_id', $variable_product_id );
						}
						update_post_meta($variable_product_id, 'performance_date', $performance_date);
						update_post_meta($variable_product_id, 'performance_end_date', $performance_end_date);
						update_post_meta($variable_product_id, 'event_post_id', $post_id);
						update_post_meta($variable_product_id, '_ticket', 'yes');
						if (!empty($thumbnail_id)) {
							update_post_meta($variable_product_id, '_thumbnail_id', $thumbnail_id);
						}
						// Set the event terms.
						$event_types = get_the_terms($post_id, 'event_type');
						if (!empty($event_types)) {
							$event_terms = wp_list_pluck($event_types, 'term_id');
							wp_set_object_terms($variable_product_id, $event_terms, 'event_type');
						}

						$cutoff_hours = get_post_meta($post_id, 'sales_cut_off', true);
						if (!empty($cutoff_hours)) {
							// // it's minus because we are converting it back to GMT timestamp for insertion.
							$cutoff_time = $performance_datetime_gmt - (((int) $cutoff_hours) * HOUR_IN_SECONDS);
							$cutoff_datetime = wp_date('Y-m-d H:i:s', $cutoff_time);
							update_post_meta($variable_product_id, 'sales_cut_off_datetime', $cutoff_datetime);
							$this->schedule_product_cutoff_action($cutoff_datetime, $variable_product_id);	
						}
					}
				endforeach;
				// Set related products.
				if (!empty($related_product_ids)) {
					update_post_meta($post_id, "related_products", $related_product_ids );
				}
			}

		} // end create event post
	}
	
	public function load_event_types_options($form) {
		
		foreach ( $form['fields'] as &$field ) {
 
			if ( strpos( $field->cssClass, 'populate-event-types' ) === false ) {
				continue;
			}
	 
			// you can add additional parameters here to alter the posts that are retrieved
			// more info: http://codex.wordpress.org/Template_Tags/get_posts
			$args  = [
				'taxonomy' => 'event_type',
				'hide_empty' => false,
			];
			
			$types = get_terms( $args );
	 
			$choices = array();
	 
			foreach ( $types as $type ) {
				$choices[] = array( 'text' => $type->name, 'value' => $type->name );
			}
	 
			// update 'Select a Post' to whatever you'd like the instructive option to be
			//$field->placeholder = 'Select a Venue';
			$field->choices = $choices;
	 
		}
	 
		return $form;
	}

	public function load_venue_options($form) {
		foreach ( $form['fields'] as &$field ) {
 
			if ( strpos( $field->cssClass, 'populate-venues' ) === false ) {
				continue;
			}
			
			// you can add additional parameters here to alter the posts that are retrieved
			// more info: http://codex.wordpress.org/Template_Tags/get_posts
			$args  = [
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'post_type' => Gf_Event_Intake_Event_CPT::VENUE_SLUG,
				'orderby' => 'title',
				'order' => 'ASC',
			];
			$posts = get_posts( $args );
	 
			$choices = array();

			$choices[] = array( 'text' => '-- Add a New Venue', 'value' => '-1' );
	 
			foreach ( $posts as $post ) {
				$text = !empty ($post->post_content) ? $post->post_title . " - " . strip_tags( $post->post_content ) : $post->post_title;
				$choices[] = array( 'text' => $text, 'value' => $post->ID );
			}
	 
			// update 'Select a Post' to whatever you'd like the instructive option to be
			$field->placeholder = 'Select a Venue';
			$field->choices = $choices;
	 
		}
	 
		return $form;
	}

	public function add_gform_merge_tags( $form ) {
		if ( GFCommon::is_entry_detail() ) {
			return $form;
		}
	 
		?>
		<script type="text/javascript">
			gform.addFilter('gform_merge_tags', 'add_merge_tags');
	 
			function add_merge_tags(mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option) {
				mergeTags["custom"].tags.push({tag: '{my_events_page}', label: 'My Events page'});
				mergeTags["custom"].tags.push({tag: '{create_event_page}', label: 'Create Event page'}); 
				return mergeTags;
			}
		</script>
		<?php
		//return the form object from the php hook
		return $form;
	}

	public function replace_merge_tags( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
		$my_events_tag = '{my_events_page}';
		if ( strpos( $text, $my_events_tag ) !== false ) {
			$my_events_page = Gf_Event_Intake_Event_CPT::get_my_events_page_link();
			$text = str_ireplace( $my_events_tag, $my_events_page, $text );
		}

		$create_event_tag = '{create_event_page}';
		if ( strpos( $text, $create_event_tag ) !== false ) {
			$create_events_page = Gf_Event_Intake_Event_CPT::get_create_event_page_link();
			$text = str_ireplace( $create_event_tag, $create_events_page, $text );
		}
		return $text;
	}
}