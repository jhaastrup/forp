<?php

/***
 * This is the file that includes sendbox as a shipping method to woo-commerce
 * only edit this if you know what you are doing.
 * Author: Sendbox 
 * Contributor: Adejoke Haastrup 
 */
function wooss_shipping_method()
{
	if (!class_exists('Wooss_Shipping_Method')) {
		class Wooss_Shipping_Method extends WC_Shipping_Method
		{

			/**
			 * Class constructor
			 *
			 * @param  mixed $instance_id
			 *
			 * @return void
			 */
			public function __construct($instance_id = 0)
			{
				$this->id                   = 'wooss';
				$this->instance_id          = absint($instance_id);
				$this->method_title         = __('Sendbox Shipping', 'wooss');
				$this->method_description   = __('Sendbox Custom Shipping Method for Woocommerce', 'wooss');
				$this->single_rate          = 0;
				$this->supports             = array(
					'shipping-zones',
					'instance-settings',
					'settings',
					'instance-settings-modal',
				);
				$this->instance_form_fields = array(
					'enabled' => array(
						'title'   => __('Enable/Disable', 'wooss'),
						'type'    => 'checkbox',
						'label'   => __('Enable this shipping method'),
						'default' => 'yes',
					),
					'taxable' => array(
						'title'   => __('Taxable'),
						'type'    => 'select',
						'options' => array(
							'taxable' => 'Taxable',
							'none'    => 'None',
						),
						'default' => 'taxable',
					),
				);
				$this->version              = WOOSS_VERSION;
				$this->init_form_fields();
				$this->init_settings();
				$this->enabled          = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
				$this->title            = isset($this->settings['title']) ? $this->settings['title'] : __('Sendbox Shipping', 'wooss');
				$this->shipping_options = 'wooss_eee';
				if (null != $this->enabled) {
					update_option('wooss_option_enable', $this->enabled);
				}
				add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
				add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
			}

			/**
			 * Load required scripts and styles.
			 *
			 * @return void
			 */
			public function enqueue_scripts()
			{
				wp_enqueue_script('wooss_js_script', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), $this->version, false);
				wp_localize_script(
					'wooss_js_script',
					'wooss_ajax_object',
					[
						'wooss_ajax_url'      => admin_url('admin-ajax.php'),
						'wooss_ajax_security' => wp_create_nonce('wooss-ajax-security-nonce'),
					]
				);
				wp_enqueue_style('wooss_css_styles', plugin_dir_url(__FILE__) . 'assets/css/styles.css', array(), $this->version, 'all');
			}

			/**
			 * Init methods shipping forms.
			 *
			 * @return void
			 */
			function init_form_fields()
			{

				$this->form_fields = array(
					'enabled' => array(
						'title'   => __('Enable/Disable'),
						'type'    => 'checkbox',
						'label'   => __('Enable this shipping method'),
						'default' => 'yes',
					),
				);
				add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
			}

			/**
			 * Calculate  fees for the shipping method on the frontend.
			 *
			 * @param  mixed $package
			 *
			 * @return void
			 */
			public function calculate_shipping($package = array())
			{

				ob_start();
				if (is_cart() || is_checkout()) {
					$api_call         = new Wooss_Sendbox_Shipping_API();
					$fee              = 0;
					$quantity         = 0;
					$items_lists      = [];
					$wooss_extra_fees = esc_attr(get_option('wooss_extra_fees'));
					foreach ($package['contents'] as $item_id => $values) {
						if (!empty($values['data']->get_weight())) {
							$weight = $values['data']->get_weight();
						} else {
							$weight = 0;
						}
						$fee      += round($values['line_total']);
						$quantity += $values['quantity'];

						$outputs                    = new stdClass();
						$outputs->name              = $values['data']->get_name();
						$outputs->weight            = (int) $weight;
						$outputs->package_size_code = 'medium';
						$outputs->quantity          = $values['quantity'];
						$outputs->value             = round($values['line_total']);
						$outputs->amount_to_receive = round($values['line_total']);
						$outputs->item_type         = $values['data']->get_categories();

						array_push($items_lists, $outputs);
					}

					$auth_header = get_option('wooss_basic_auth');

					$origin_country = get_option('wooss_country');

					$origin_state = get_option('wooss_states_selected');


					$origin_street = get_option('wooss_store_address');

					$origin_city = get_option('wooss_city');

					$incoming_option_code = get_option('wooss_pickup_type');

					$profile_url                    = $api_call->get_sendbox_api_url('profile');
					$profile_args                   = array(
						'headers' => array(
							'Content-Type'  => 'application/json',
							'Authorization' => $auth_header,
						),
					);
					$response_code_from_profile_api = $api_call->get_api_response_code($profile_url, $profile_args, 'GET');
					$response_body_from_profile_api = $api_call->get_api_response_body($profile_url, $profile_args, 'GET');
					if (200 == $response_code_from_profile_api) {
						$origin_name  = $response_body_from_profile_api->name;
						$origin_phone = $response_body_from_profile_api->phone;
						$origin_email = $response_body_from_profile_api->email;
					}
					$date = new DateTime();
					$date->modify('+1 day');
					$pickup_date = $date->format(DateTime::ATOM);

					$destination_state_code = $package['destination']['country'];
					$destination_city       = $package['destination']['city'];
					$destination_street     = $package['destination']['address'];
					if (empty($destination_street)) {
						$destination_street = __('Customer street');
					}
					$destination_name  = __('Customer X', 'wooss');
					$destination_phone = __('00000000', 'wooss');

					$countries_obj       = new WC_Countries();
					$destination_states  = $countries_obj->get_states($package['destination']['country']);
					$destination_country = $countries_obj->get_shipping_countries($package['destination']['country']);

					if (empty($destination_states)) {
						$destination_states = array('');
					}

					if (empty($destination_country)) {
						$destination_country = array('');
					}

					foreach ($destination_country as $destination_country_code => $country_name) {
						if ($package['destination']['country'] == $destination_country_code) {
							$destination_country = $country_name;
							break;
						}
					}

					foreach ($destination_states as $states_code => $states_name) {
						if ($package['destination']['state'] == $states_code) {
							$destination_state = $states_name;
							break;
						}
					}


					if (preg_match('/\s\(\w+\)/', $destination_country) == true) {
						$destination_country = preg_replace('/\s\(\w+\)/', '', $destination_country);
					}

					/*  if(preg_match('/\s\(\w+\)/', $destination_state_code) == true){
						$destination_state_code = preg_replace('/\s\(\w+\)/', '', $destination_state_code);
					} */

					if (empty($destination_state)) {
						$destination_state = $package['destination']['state'];
					}


					if (empty($destination_city)) {
						$destination_city = $package['destination']['city'];
					}

					if (empty($destination_state)) {
						$destination_state = $destination_state;
					}

					$payload_data                         = new stdClass();
					$payload_data->destination_country    = $destination_country;
					//	$payload_data->destination_state_code =$destination_state_code;
					$payload_data->destination_state      = $destination_state;
					$payload_data->destination_city       = $destination_city;
					$payload_data->destination_street     = $destination_street;
					$payload_data->destination_name       = $destination_name;
					$payload_data->destination_phone      = $destination_phone;
					$payload_data->items                  = $items_lists;
					$payload_data->weight                 = (int) $weight;
					$payload_data->amount_to_receive      = (int) $fee;
					$payload_data->origin_country         = $origin_country;
					$payload_data->origin_state           = $origin_state;
					$payload_data->origin_name            = $origin_name;
					$payload_data->origin_phone           = $origin_phone;
					$payload_data->origin_street          = 'wooss_store_address';
					$payload_data->origin_city            = $origin_city;
					$payload_data->deliver_priority_code  = 'next_day';
					$payload_data->pickup_date            = $pickup_date;
					$payload_data->incoming_option_code   = $incoming_option_code;
					$payload_data->payment_option_code    = 'prepaid';
					$payload_data->deliver_type_code      = 'last_mile';



					$payload_data_json = json_encode($payload_data);

					$delivery_args     = array(
						'headers' => array(
							'Content-Type'  => 'application/json',
							'Authorization' => $auth_header,
						),
						'body'    => $payload_data_json,
					);

					$delivery_quotes_url = $api_call->get_sendbox_api_url('delivery_quote');
					// var_dump($payload_data);

					$delivery_quotes_details = $api_call->get_api_response_body($delivery_quotes_url, $delivery_args, 'POST');
					//var_dump(gettype($delivery_quotes_details) == "NULL"); 
					//so P this is the part i want to display the error message. if this max_quoted_fee is null
					//for now, when its null it prints 500 you get?

					$max_quoted_fee = (float) "500.001";

					if (gettype($delivery_quotes_details) != "NULL") {
						$max_quoted_fee = $delivery_quotes_details->max_quoted_fee;
					}
					$quoted_fee = $max_quoted_fee  + $wooss_extra_fees;


					// $quoted_fee = (float) $quoted_fee;
					// //var_dump($quoted_fee);
					//var_dump(gettype($quoted_fee)); 

					// $quoted_fee = ($delivery_quotes_details->max_quoted_fee) + $wooss_extra_fees;


					//var_dump($delivery_quotes_details);
					//die();

					//$delivery_quotes_details  = $api_call->post_on_api_by_curl($delivery_quotes_url, $delivery_args, $auth_header);
					//var_dump($delivery_quotes_details); 
					//die();
					//var_dump($quoted_fee);  
					// $this->single_rate = $quoted_fee;
					$new_rate = array(
						'id'      => $this->id,
						'label'   => $this->title,
						'cost'    => $quoted_fee,
						'package' => $package,
					);
					$this->add_rate($new_rate);

					echo ob_get_clean();
				}
			}
		}
	}
}
add_action('woocommerce_shipping_init', 'wooss_shipping_method');
/**
 * This function is responsible to display class in the settings.
 *
 * @param  mixed $methods
 *
 * @return void
 */
function add_wooss_shipping_method($methods)
{
	$methods['wooss'] = 'Wooss_Shipping_Method';
	return $methods;
}
add_filter('woocommerce_shipping_methods', 'add_wooss_shipping_method');



add_action('woocommerce_settings_tabs_shipping', 'wooss_form_fields', 100);

/**
 * This function handles the display of the forms only on sendbox shipping page.
 *
 * @return void
 */
function wooss_form_fields()
{
	$shipping_methods_enabled = get_option('wooss_option_enable');
	if (isset($_GET['tab']) && $_GET['tab'] == 'shipping' && $_GET['section'] == 'wooss'  && $shipping_methods_enabled == 'yes') {
		$api_call                   = new Wooss_Sendbox_Shipping_API();
		$auth_header                = esc_attr(get_option('wooss_basic_auth'));
		$args                       = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => $auth_header,
			),
		);
		$profile_api_url            = 'https://api.sendbox.ng/v1/merchant/profile';
		$profile_data_response_code = $api_call->get_api_response_code($profile_api_url, $args, 'GET');
		$profile_data_response_body = $api_call->get_api_response_body($profile_api_url, $args, 'GET');
		$wooss_username             = '';
		$wooss_email                = '';
		$wooss_tel                  = '';
		if (200 == $profile_data_response_code) {
			$wooss_username = $profile_data_response_body->name;
			$wooss_email    = $profile_data_response_body->email;
			$wooss_tel      = $profile_data_response_body->phone;
		}
		$wc_city             = get_option('woocommerce_store_city');
		$wc_store_address    = get_option('woocommerce_store_address');
		$wooss_city          = get_option('wooss_city');
		$wooss_store_address = get_option('wooss_store_address');
		$wooss_basic_auth    = get_option('wooss_basic_auth');
		$wc_extra_fees       = (int) get_option('wooss_extra_fees');
		if (null == $wooss_city) {
			$wooss_city = $wc_city;
		}
		if (null == $wooss_store_address) {
			$wooss_store_address = $wc_store_address;
		}
		$wooss_states_selected = get_option('wooss_states_selected');
		if (null == $wooss_states_selected) {
			$wooss_states_selected = '';
		}
		$wooss_country = get_option('wooss_country');
		if (null == $wooss_country) {
			$wooss_country = 'Nigeria';
		}
		$wooss_connection_status = get_option('wooss_basic_auth');
		$custom_styles           = '';
		if (null != $wooss_connection_status) {
			$custom_styles = 'display:none';
		}
		$wooss_display_fields = get_option('wooss_connexion_status');
		if (1 == $wooss_display_fields) {
			$display_fields = 'display : inline';
			$hide_button    = 'display : none';
		}
		$wooss_pickup_type = get_option('wooss_pickup_type');
		if (null == $wooss_pickup_type) {
			$wooss_pickup_type = 'pickup';
		}

		if (null == $wc_extra_fees) {
			$wc_extra_fees = 0;
		}

		$wooss_pickup_types = array('pickup', 'drop-off');
		$nigeria_states     = $api_call->get_nigeria_states();

		?>

		<div>

			<label for="wooss_basic_auth"><?php _e('API KEY :', 'wooss'); ?> </label><input type="text" placeholder="Basic X0000X0000000000AH" name="wooss_basic_auth" value="<?php _e($wooss_basic_auth, 'wooss'); ?>"> <br />
			<button type="submit" class="button-primary wooss-connect-sendbox wooss_fields" style="<?php echo $custom_styles; ?>"><?php _e('Connect to Sendbox', 'wooss'); ?></button><br />

			<div class="wooss_necessary_fields" style="
																																																																																																																																																																																																																																																																																											<?php
																																																																																																																																																																																																																																																																																											if (1 == $wooss_display_fields) {
																																																																																																																																																																																																																																																																																												$display_fields = 'display : inline';
																																																																																																																																																																																																																																																																																												echo $display_fields;
																																																																																																																																																																																																																																																																																											}
																																																																																																																																																																																																																																																																																											?>
																																																																																																																																																																																																																																																																																																						">
				<table style="width:100%">

					<tr>
						<td>
							<label for="wooss_username"><?php _e('Name ', 'wooss'); ?> </label>
						</td>
						<td>
							<input type="text" class="wooss_fields" placeholder="John Doe" name="wooss_username" id="wooss_username" value="<?php esc_attr_e($wooss_username, 'wooss'); ?>" required>
						</td>
					</tr>


					<tr>
						<td>
							<label for="wooss_tel"><?php _e('Phone Number ', 'wooss'); ?> </label>
						</td>
						<td>
							<input type="tel" class="wooss_fields" placeholder="+2340000000000" id="wooss_tel" name="wooss_tel" value="<?php esc_attr_e($wooss_tel, 'wooss'); ?>" required>
						</td>
					</tr>

					<tr>
						<td>
							<label for="wooss_email"><?php _e('Email ', 'wooss'); ?> </label>
						</td>
						<td>
							<input type="email" class="wooss_fields" placeholder="johndoe@gmail.com" id="wooss_email" name="wooss_email" value="<?php esc_attr_e($wooss_email, 'wooss'); ?>" required>
						</td>
					</tr>

					<tr>
						<td>
							<label for="wooss_country"><?php _e('Country ', 'wooss'); ?></label>
						</td>
						<td>
							<select class="wooss_country_select">
								<option value="<?php echo esc_attr_e($wooss_country, 'wooss'); ?>" selected><?php _e($wooss_country, 'wooss'); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<td>
							<label for="wooss_city"><?php esc_attr_e('City', 'wooss'); ?></label>
						</td>
						<td>
							<input type="text" class="wooss_fields" name="wooss_city" value="<?php echo ($wooss_city); ?>">
						</td>
					</tr>

					<tr>

						<td>
							<label for="wooss_state"><?php esc_attr_e('State ', 'wooss'); ?></label>
						</td>
						<td>
							<?php
							echo "<select class='wooss_state_dropdown wooss_fields' name='wooss_state_dropdown'>";
							foreach ($nigeria_states as $state) {
								$states_selected = (preg_match("/$wooss_states_selected/", $state) == true) ? 'selected="selected"' : '';
								echo "<option value='$state' $states_selected>$state</option>";
							}
							echo '</select>';
							?>
						</td>
					</tr>

					<tr>
						<td>
							<label for="wooss_state"><?php _e('Pickup types ', 'wooss'); ?></label>
						</td>
						<td>
							<?php
							echo "<select class='wooss_pickup_type wooss_fields' name='wooss_pickup_type'>";
							foreach ($wooss_pickup_types as $pickup_types) {
								$types_selected = (preg_match("/$wooss_pickup_type/", $pickup_types) == true) ? 'selected="selected"' : '';
								echo "<option value='$pickup_types' $types_selected>$pickup_types</option>";
							}
							echo '</select>';
							?>
						</td>
					</tr>

					<tr>
						<td>
							<label for="wooss_street"><?php _e('Street ', 'wooss'); ?></label>
						</td>
						<td>
							<input type="text" size="100" class="wooss_fields" name="wooss_street" value="<?php esc_attr_e($wc_store_address); ?>">
						</td>
					</tr>

					<tr>
						<td>
							<label for="wooss_extra_fees"><?php _e('Extra fees  ', 'wooss'); ?></label>
						</td>
						<td>
							<input class="wooss_fields" type="number" id="wooss_extra_fees" name="wooss_extra_fees" value="<?php esc_attr_e($wc_extra_fees); ?>">
						</td>
					</tr>
				</table>
			</div>
			<button type="submit" class="button-primary wooss_save_button"><?php esc_attr_e('Sync changes', 'wooss'); ?></button>

			<span class="wooss_errors_pages wooss_fields"></span>
		</div>
	<?php
	}
}

add_action('wp_ajax_connect_to_sendbox', 'connect_to_sendbox');

/**
 * AJAX function used to get status code from sendbox.
 *
 * @return void
 */
function connect_to_sendbox()
{
	if (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'wooss-ajax-security-nonce')) {
		wp_send_json_error('Invalid security token sent.');
	} elseif (isset($_POST['data'])) {
		$response_code          = 0;
		$data                   =  $_POST['data'];
		$wooss_basic_auth       = $data['wooss_basic_auth'];

		$api_call               = new Wooss_Sendbox_Shipping_API();
		$api_url                = $api_call->get_sendbox_api_url('profile');
		$args                   = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => $wooss_basic_auth,
			),
		);
		$response_code_from_api = $api_call->get_api_response_code($api_url, $args, 'GET');
		if (200 == $response_code_from_api) {
			$response_code = 1;
			update_option('wooss_connexion_status', $response_code);
			update_option('wooss_basic_auth', $wooss_basic_auth);
		}
		echo  $response_code;
	}
	wp_die();
}

add_action('wp_ajax_save_fields_by_ajax', 'save_fields_by_ajax');
/**
 * Function  for saving fields into db using ajax.
 *
 * @return mixed $string
 */
function save_fields_by_ajax()
{
	if (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'wooss-ajax-security-nonce')) {
		wp_send_json_error('Invalid security token sent.');
	} elseif (isset($_POST['data'])) {
		$operation_success = 0;
		$data              = $_POST['data'];
		$wooss_country     = sanitize_text_field($data['wooss_country']);
		$wooss_state       = sanitize_text_field($data['wooss_state_name']);
		$wooss_city        = sanitize_text_field($data['wooss_city']);
		$wooss_street      = sanitize_text_field($data['wooss_street']);
		$wooss_basic_auth  = sanitize_text_field($data['wooss_basic_auth']);
		$wooss_pickup_type = sanitize_text_field($data['wooss_pickup_type']);
		$wooss_extra_fees  = sanitize_text_field($data['wooss_extra_fees']);

		if (isset($wooss_city)) {
			update_option('wooss_city', $wooss_city);
			$operation_success = 1;
		}
		if (isset($wooss_extra_fees)) {
			update_option('wooss_extra_fees', $wooss_extra_fees);
			$operation_success = 1;
		}
		if (isset($wooss_country)) {
			update_option('wooss_country', $wooss_country);
			$operation_success = 1;
		}
		if (isset($wooss_state)) {
			update_option('wooss_states_selected', $wooss_state);
			$operation_success = 1;
		}
		if (isset($wooss_street)) {
			update_option('wooss_store_address', $wooss_street);
			$operation_success = 1;
		}
		if (isset($wooss_basic_auth)) {
			update_option('wooss_basic_auth', $wooss_basic_auth);
			$operation_success = 1;
		}
		if (isset($wooss_pickup_type)) {
			update_option('wooss_pickup_type', $wooss_pickup_type);
			$operation_success = 1;
		}
		update_option('wooss_display_fields', $operation_success);
		echo  $operation_success;
	}

	wp_die();
}
