<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    wooss
 * @subpackage wooss/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wooss
 * @subpackage Wooss/admin
 * @author     jastrup <jhaastrup21@gmail.com>
 */
class Wooss_Admin
{

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
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in wooss_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The wooss_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wooss-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in wooss_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The wooss_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wooss-admin.js', array('jquery'), $this->version, false);
		wp_localize_script(
			$this->plugin_name,
			'wooss_ajax_object',
			[
				'wooss_ajax_url'      => admin_url('admin-ajax.php'),
				'wooss_ajax_security' => wp_create_nonce('wooss-ajax-security-nonce'),
			]
		);
	}

	/**
	 * Function to check if woo-commerce is installed.
	 */
	public function check_wc()
	{
		if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			?>
		<div class=" notice notice-error">
			<p>
				<?php
				esc_html_e('For Test-sb-plugin to work, woocommerce is required ', 'wooss');
				?>
			</p>
		</div>
	<?php

	}
}


/**
 * Add ship with Sendbox as dropdown in admin.
 *
 * @param  mixed $actions
 *
 * @return void
 */
public function add_sendbox_shipping_dropdown($actions)
{
	$actions['sendbox_shipping_action'] = __('Ship With Sendbox', 'wooss');
	return $actions;
}

/**
 * This function displays custom data from Sendbox into each order page.
 *
 * @param  mixed $order
 *
 * @return void
 */
public function add_sendbox_shipping_modal($order)
{

	global $post_type;
	$order_id = $order->ID;
	if ('shop_order' == $post_type) {
		$_order = new WC_Order($order_id);
		$destination_name    = $_order->get_formatted_billing_full_name();
		$destination_phone   = $_order->get_billing_phone();
		$destination_street  = $_order->get_billing_address_1();
		$destination_city    = $_order->get_billing_city();
		$destination_state   = $_order->get_billing_state();
		$destination_country = $_order->get_billing_country();
		$destination_email   = $_order->get_billing_email();

		$countries_obj = new WC_Countries();

		$states = $countries_obj->get_states($destination_country);
		foreach ($states as $state_code => $state_name) {
			if ($destination_state == $state_code) {
				$destination_state = $state_name;
				break;
			}
		}

		$country = $countries_obj->get_countries();
		foreach ($country as $country_code => $country_name) {
			if ($destination_country == $country_code) {
				$destination_country = $country_name;
				break;
			}
		}

		$customer_products = $_order->get_items();
		$items_lists       = [];

		$fee      = 0;
		$quantity = 0;

		foreach ($customer_products as $products_data) {
			$product_data                  = $products_data->get_data();
			$product_id                    = $product_data['product_id'];
			$_product                      = wc_get_product($product_id);
			$items_data                    = new stdClass();
			$items_data->name              = $product_data['name'];
			$items_data->quantity          = $product_data['quantity'];
			$items_data->value             = $_product->get_price();
			$items_data->amount_to_receive = $_product->get_price();
			$items_data->package_size_code = 'medium';
			$items_data->item_type_code    = strip_tags(wc_get_product_category_list($product_id));

			$product_weight = $_product->get_weight();
			if (null != $product_weight) {
				$weight = $product_weight;
			} else {
				$weight = 0;
			}
			$items_data->weight = $weight;
			$fee               += round($_product->get_price());
			$quantity          += $product_data['quantity'];
			array_push($items_lists, $items_data);
		}

		// origin values
		$api_call                   = new Wooss_Sendbox_Shipping_API();
		$auth_header                = get_option('wooss_basic_auth');
		$args                       = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => $auth_header,
			),
		);
		$profile_api_url            = 'https://api.sendbox.ng/v1/merchant/profile';
		$profile_data_response_code = $api_call->get_api_response_code($profile_api_url, $args, 'GET');
		$profile_data_response_body = $api_call->get_api_response_body($profile_api_url, $args, 'GET');
		$wooss_origin_name          = '';
		$wooss_origin_email         = '';
		$wooss_origin_phone         = '';
		if (200 == $profile_data_response_code) {
			$wooss_origin_name  = $profile_data_response_body->name;
			$wooss_origin_email = $profile_data_response_body->email;
			$wooss_origin_phone = $profile_data_response_body->phone;
		}
		$wc_city             = get_option('woocommerce_store_city');
		$wc_store_address    = get_option('woocommerce_store_address');
		$wooss_origin_city   = get_option('wooss_origin_city');
		$wooss_origin_street = get_option('wooss_origin_street');
		if (null == $wooss_origin_city) {
			$wooss_origin_city = $wc_city;
		}
		if (null == $wooss_origin_street) {
			$wooss_origin_street = $wc_store_address;
		}
		$wooss_origin_states_selected = get_option('wooss_states_selected');
		if (null == $wooss_origin_states_selected) {
			$wooss_origin_states_selected = '';
		}
		$wooss_origin_country = get_option('wooss_origin_country');
		if (null == $wooss_origin_country) {
			$wooss_origin_country = 'Nigeria';
		}

		$wooss_pickup_type = get_option('wooss_pickup_type');
		if (null == $wooss_pickup_type) {
			$wooss_pickup_type = 'pickup';
		}

		$incoming_option_code = get_option('wooss_pickup_type');
		if (null == $incoming_option_code) {
			return;
		}

		$date = new DateTime();
		$date->modify('+1 day');
		$pickup_date = $date->format(DateTime::ATOM);

		?>
		<div id="wooss_shipments_data" style="display:none">

			<span><strong><?php _e('Origin Details : ', 'wooss'); ?></strong>
				<i>This represents your store details</i>
				<br />
				<label for="wooss_origin_name"><?php _e('Name : ', 'wooss'); ?></label>
				<input type="text" name="wooss_origin_name" id="wooss_origin_name" value="<?php echo ($wooss_origin_name); ?>" readonly>
				&nbsp
				<label for="wooss_origin_phone"><?php _e('Phone : ', 'wooss'); ?></label>
				<input type="text" name="wooss_origin_phone" id="wooss_origin_phone" value="<?php echo ($wooss_origin_phone); ?>" readonly>
				<br />&nbsp

				<br /><label for="wooss_origin_email"><?php _e('Email : ', 'wooss'); ?></label>
				<input type="text" name="wooss_origin_email" id="wooss_origin_email" value="<?php echo ($wooss_origin_email); ?>" readonly>
				&nbsp
				<label for="wooss_origin_street"><?php _e('Street : ', 'wooss'); ?></label>
				<input type="text" name="wooss_origin_street" id="wooss_origin_street" value="<?php echo ($wc_store_address); ?>" readonly>
				<br />&nbsp
				<br /><label for="wooss_origin_country"><?php _e('Country : ', 'wooss'); ?></label>
				<input type="text" name="wooss_origin_country" id="wooss_origin_country" value="<?php echo ($wooss_origin_country); ?>" readonly>
				&nbsp
				<label for="wooss_origin_state"><?php _e('States : ', 'wooss'); ?></label>
				<input type="text" name="wooss_origin_state" id="wooss_origin_state" value="<?php echo ($wooss_origin_states_selected); ?>" readonly>
				&nbsp

				<br />&nbsp


				<br /><label for="wooss_origin_city"><?php _e('City : ', 'wooss'); ?></label>
				<input type="text" name="wooss_origin_city" id="wooss_origin_city" value="<?php echo ($wooss_origin_city); ?>" readonly>
			</span>


			<br />
			<br />
			<span><strong><?php _e('Destination Details : ', 'wooss'); ?></strong>
				<i>This represents your customer details</i>
				<br />
				<label for="wooss_destination_name"><?php _e('Name : ', 'wooss'); ?></label>
				<input type="text" name="wooss_destination_name" id="wooss_destination_name" value="<?php _e($destination_name); ?>" readonly>
				&nbsp
				<label for="wooss_destination_phone"><?php _e('Phone : ', 'wooss'); ?></label>
				<input type="text" name="wooss_destination_phone" id="wooss_destination_phone" value="<?php _e($destination_phone); ?>" readonly>
				<br />&nbsp

				<br /><label for="wooss_destination_email"><?php _e('Email : ', 'wooss'); ?></label>
				<input type="text" name="wooss_destination_email" id="wooss_destination_email" value="<?php _e($destination_email); ?>" readonly>
				&nbsp
				<label for="wooss_destination_street"><?php _e('Street : ', 'wooss'); ?></label>
				<input type="text" name="wooss_destination_street" id="wooss_destination_street" value="<?php _e($destination_street); ?>" readonly>
				<br />&nbsp
				<br /><label for="wooss_destination_country"><?php _e('Country : ', 'wooss'); ?></label>
				<input type="text" name="wooss_destination_country" id="wooss_destination_country" value="<?php _e($destination_country); ?>" readonly>
				&nbsp
				<label for="wooss_destination_state"><?php _e('State : ', 'wooss'); ?></label>
				<input type="text" name="wooss_destination_state" id="wooss_destination_state" value="<?php _e($destination_state); ?>" readonly>
				<br />&nbsp
				<br /><label for="wooss_destination_city"><?php _e('City : ', 'wooss'); ?></label>
				<input type="text" name="wooss_destination_city" id="wooss_destination_city" value="<?php _e($destination_city); ?>" readonly>
			</span>

			<?php
			$product_content = '';
			foreach ($items_lists as $lists_id => $list_data) {
				$product_name              = $list_data->name;
				$product_quantity          = $list_data->quantity;
				$product_value             = $list_data->value;
				$product_amount            = $list_data->amount_to_receive;
				$product_package_size_code = $list_data->package_size_code;
				$product_item_type_code    = $list_data->item_type_code;
				$product_weights           = $list_data->weight;
				$product_content          .= '{"name" : "' . $product_name . '", "quantity" :"' . $product_quantity . '", "value" :"' . $product_value . '", "amount_to_receive" :"' . $product_amount . '", "package_size_code":"' . $product_package_size_code . '", "item_type":"' . $product_item_type_code . '"," weight" :"' . $product_weights . '"}';
			}

			// loading the payload

			$payload_data                         = new stdClass();
			$payload_data->wooss_origin_name      = $wooss_origin_name;
			$payload_data->destination_country    = $destination_country;
			$payload_data->destination_state_code = ' ';
			$payload_data->destination_state      = $destination_state;
			$payload_data->destination_city       = $destination_city;
			$payload_data->destination_street     = $destination_street;
			$payload_data->destination_name       = $destination_name;
			$payload_data->destination_phone      = $destination_phone;
			$payload_data->items                  = $items_lists;
			$payload_data->weight                 = (int) $weight;
			$payload_data->amount_to_receive      = (int) $fee;
			$payload_data->origin_country         = $wooss_origin_country;
			$payload_data->origin_state           = $wooss_origin_states_selected;

			$payload_data->wooss_origin_phone    = $wooss_origin_phone;
			$payload_data->origin_street         = 'wooss_store_address';
			$payload_data->wooss_origin_city     = $wooss_origin_city;
			$payload_data->deliver_priority_code = 'next_day';
			$payload_data->pickup_date           = $pickup_date;
			$payload_data->incoming_option_code  = $incoming_option_code;
			$payload_data->payment_option_code   = 'prepaid';
			$payload_data->deliver_type_code     = 'last_mile';

			$payload_data_json = json_encode($payload_data);
			$delivery_args     = array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => $auth_header,
				),
				'body'    => $payload_data_json,
			);

			?>

			<span>
				<br />
				<br />
				<span><strong><?php _e('Order Details : ', 'wooss'); ?></strong>
					<br />
					<!--<label?php _e('Items : ', 'wooss'); ?></label> --->
					<textarea cols="50" id="wooss_items_list" value="<?php print($product_content); ?>" data-id="<?php echo $order_id; ?>"><?php echo (trim($product_content)); ?></textarea>
					<br />
					<?php
					$quote_api_url = 'https://api.sendbox.ng/v1/merchant/shipments/delivery_quote';
					$quote_body    = $api_call->get_api_response_body($quote_api_url, $delivery_args, 'POST');

					$quotes_rates = $quote_body->rates;
					_e('<strong>Select Courier</strong>');
					?>
					<select id="wooss_selected_courier">
						<?php
						foreach ($quotes_rates as $rates_id => $rates_values) {
							$rates_names = $rates_values->name;
							$rates_fee   = $rates_values->fee;
							$rates_id   = $rates_values->id;

							?>
							<option data-courier-price="<?php _e($rates_fee, 'wooss'); ?> " value="<?php _e($rates_id, 'wooss'); ?>" id="<?php _e($rates_id, 'wooss'); ?>"><?php _e($rates_names) ?></option>
						<?php
						}

						?>
					</select>
					<button id="wooss_request_shipment" class="button-primary"><?php _e('Request Shipment'); ?></button>
				</span>
			</span>
		</div>

		<?php
		$tracking_code = preg_replace('/[a-zA-Z]/', '', get_post_meta($order_id, 'wooss_tracking_code'));
		$data = ['order_id' => $order_id, 'tracking_code' => $tracking_code];
		do_action('wooss_order_details', $data);
	}
}


/**
 * AJAX function to request final shipment to Sendbox.
 *
 * @return void
 */
public function request_shipments()
{

	if (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'wooss-ajax-security-nonce')) {
		wp_send_json_error('Invalid security token sent.');
	} elseif (isset($_POST['data'])) {
		$data              = $_POST['data'];
		$order_id          = sanitize_text_field($data['wooss_order_id']);
		$_order            = new WC_Order($order_id);
		$customer_products = $_order->get_items();
		$items_lists       = [];

		$fee      = 0;
		$quantity = 0;

		foreach ($customer_products as $products_data) {
			$product_data                  = $products_data->get_data();
			$product_id                    = $product_data['product_id'];
			$_product                      = wc_get_product($product_id);
			$items_data                    = new stdClass();
			$items_data->name              = $product_data['name'];
			$items_data->quantity          = $product_data['quantity'];
			$items_data->value             = $_product->get_price();
			$items_data->amount_to_receive = $_product->get_price();
			$items_data->package_size_code = 'medium';
			$items_data->item_type_code    = strip_tags(wc_get_product_category_list($product_id));

			$product_weight = $_product->get_weight();
			if (null != $product_weight) {
				$weight = $product_weight;
			} else {
				$weight = 0;
			}
			$items_data->weight = $weight;
			$fee               += round($_product->get_price());
			$quantity          += $product_data['quantity'];
			array_push($items_lists, $items_data);
		}


		$courier_selected = sanitize_text_field($data['wooss_selected_courier']);



		$destination_name    = sanitize_text_field($data['wooss_destination_name']);
		$destination_phone   = sanitize_text_field($data['wooss_destination_phone']);
		$destination_email   = sanitize_text_field($data['wooss_destination_email']);
		$destination_city    = sanitize_text_field($data['wooss_destination_city']);
		$destination_country = sanitize_text_field($data['wooss_destination_country']);
		$destination_state   = sanitize_text_field($data['wooss_destination_state']);
		$destination_street  = sanitize_text_field($data['wooss_destination_street']);

		$origin_name    = sanitize_text_field($data['wooss_origin_name']);
		$origin_phone   = sanitize_text_field($data['wooss_origin_phone']);
		$origin_email   = sanitize_text_field($data['wooss_origin_email']);
		$origin_city    = sanitize_text_field($data['wooss_origin_city']);
		$origin_state   = sanitize_text_field($data['wooss_origin_state']);
		$origin_street  = sanitize_text_field($data['wooss_origin_street']);
		$origin_country = sanitize_text_field($data['wooss_origin_country']);

		$webhook_url = get_site_url() . "/wp-json/wooss/v2/shipping";

		$payload_data = new stdClass();

		$payload_data->selected_courier_id = $courier_selected;
		//var_dump($courier_selected);

		$payload_data->destination_name    = $destination_name;
		$payload_data->destination_phone   = $destination_phone;
		$payload_data->destination_email   = $destination_email;
		$payload_data->destination_city    = $destination_city;
		$payload_data->destination_country = $destination_country;
		$payload_data->destination_state   = $destination_state;
		$payload_data->destination_street  = $destination_street;

		$payload_data->origin_name       = $origin_name;
		$payload_data->origin_phone      = $origin_phone;
		$payload_data->origin_email      = $origin_email;
		$payload_data->origin_city       = $origin_city;
		$payload_data->origin_state      = $origin_state;
		$payload_data->origin_street     = $origin_street;
		$payload_data->origin_country    = $origin_country;
		$payload_data->items             = $items_lists;
		$payload_data->reference_code     = trim(str_replace('#', '', $order_id));
		$payload_data->amount_to_receive = $_order->get_shipping_total();
		$payload_data->delivery_callback = $webhook_url;


		$date = new DateTime();
		$date->modify('+1 day');
		$pickup_date = $date->format('c');

		$payload_data->deliver_priority_code = 'next_day';
		$payload_data->pickup_date           = $pickup_date;

		$api_call    = new Wooss_Sendbox_Shipping_API();
		$auth_header = get_option('wooss_basic_auth');

		$payload_data_json = json_encode($payload_data);

		$shipments_args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => $auth_header,
			),
			'body'    => $payload_data_json,
		);

		$shipments_url = $api_call->get_sendbox_api_url('shipments');

		$successfull  = 0;
		//$shipments_details = post_on_api_by_curl($shipments_url, $payload_data, $auth_header);
		$shipments_details = $api_call->get_api_response_body($shipments_url, $shipments_args, 'POST');
		//$shipments_details = "jhdjhbdsu"; 

		if (isset($shipments_details)) {
			$tracking_code     = $shipments_details->code;
			$order = wc_get_order($order_id);


			if ($shipments_details->status_code == 'pending') {
				//$order->update_status("", ""); 
				update_post_meta($order_id, 'wooss_tracking_code', 'Your tracking code for this order is : ' . $tracking_code);
				$successfull = 1;
			} elseif ($shipments_details->status_code == 'drafted') {
				//update_post_meta( $order_id, 'wooss_tracking_code', 'Your tracking code for this order is : ' . $tracking_code. " ".'Login to your sendbox account and topup your wallet to complete shippment ' );
				$successfull = 2;
			}
		}
		echo $successfull;
	}


	wp_die();
}

/**This function creates the webhook url that allows sendbox
 * post data back to sendbox-shipping plugin.
 * 
 */
public function register_routes()
{
	register_rest_route('wooss/v2', '/shipping', array(
		'methods'  => 'GET',
		'callback' => array($this, 'get_data'),
	));

	register_rest_route('wooss/v2', '/shipping', array(
		'methods'  => 'POST',
		'callback' => array($this, 'post_data'),
	));
}

function get_data($data)
{
	//return array('name' => 'Adejoke');
}

/***This function updates order status after a post has been made to the webhook url */

function update_shipment_status($order_id, $status_code)
{
	// return ($order_id);
	$order = wc_get_order($order_id);
	if ($status_code === "accepted") {
		$order->update_status("processing", "");
	}

	if ($status_code === "delivered") {
		$order->update_status("completed", "");
	}
	if ($status_code === "rejected") {
		$order->update_status("failed", "");
	}

	if ($status_code === "cancelled") {
		$order->update_status("cancelled", "");
	}


	return $order;
}

/**This function process the data posted from sendbox to the plugin */
function post_data(WP_REST_Request $request)
{

	//$parameters = $request->get_json_params();
	$all = $request->get_json_params();


	$order_id = $all['package']['reference_code'];
	$status_code = $all['status_code'];
	$shipment = $this->update_shipment_status($order_id, $status_code);
	$payload_data_json = json_encode($shipment);

	return $payload_data_json;
	// return array($shipment);
}
}
