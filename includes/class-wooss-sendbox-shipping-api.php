<?php

/**
 * This class makes a call to sendbox API
 */
class Wooss_Sendbox_Shipping_API
{


	/**
	 * This function allows to connect to any api.
	 *
	 * @param  mixed $auth Authentification
	 * @param  mixed $api_url API URL
	 * @param  mixed $content Body content
	 * @param  mixed $method_type GET|POST
	 *
	 * @return int $response
	 */
	public function connect_to_api($api_url, $args, $method_type)
	{
		if ('GET' == $method_type) {
			$response = wp_remote_get(esc_url_raw($api_url), $args);
		} elseif ('POST' == $method_type) {
			$response = wp_remote_post(esc_url_raw($api_url), $args);
		}

		/* if (is_wp_error($response)) {
			$error = $response->get_error_message();
			echo $error;
			// put alert  here 
		} */

		return $response;
	}

	/**
	 * This function allow to get response code from  api
	 */
	public function get_api_response_code($api_url, $args, $method)
	{
		$api_call      = $this->connect_to_api($api_url, $args, $method);
		$response_code = wp_remote_retrieve_response_code($api_call);
		return $response_code;
	}

	/**
	 * This function gets body content from  api.
	 */
	public function get_api_response_body($api_url, $args, $method)
	{
		$api_call      = $this->connect_to_api($api_url, $args, $method);
		$response_body = json_decode(wp_remote_retrieve_body($api_call));
		return $response_body;
	}

	/**
	 * This function returns the necessary url that needs Sendbox.
	 */
	public function get_sendbox_api_url($url_type)
	{
		if ('delivery_quote' == $url_type) {
			$url = 'https://api.sendbox.ng/v1/merchant/shipments/delivery_quote';
		} elseif ('countries' == $url_type) {
			$url = 'https://cloud.sendbox.ng/api/v1/countries?page_by={' . '"per_page"' . ':264}';
		} elseif ('states' == $url_type) {
			$url = 'https://cloud.sendbox.ng/api/v1/states?page_by={' . '"per_page"' . ':1023}';
		} elseif ('shipments' == $url_type) {
			$url = 'https://api.sendbox.ng/v1/merchant/shipments';
		} elseif ('item_type' == $url_type) {
			$url = 'https://api.sendbox.ng/v1/item_types';
		} elseif ('profile' == $url_type) {
			$url = 'https://api.sendbox.ng/v1/merchant/profile';
		} else {
			$url = '';
		}
		return $url;
	}

	/**
	 * This function  gets response from any api by using curl.
	 */
	public function get_api_response_by_curl($url)
	{
		$handle = curl_init();
		curl_setopt($handle, CURLOPT_URL, $url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($handle);
		curl_close($handle);
		return $output;
	}

	/**
	 * Static function for getting nigeria states.
	 *
	 * @return void
	 */
	public function get_nigeria_states()
	{
		$state = array('Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Benue', 'Borno', 'Bayelsa', 'Cross River', 'Delta', 'Ebonyi ', 'Edo', 'Ekiti', 'Enugu ', 'Federal Capital Territory', 'Gombe ', 'Jigawa ', ' Imo ', ' Kaduna', 'Kebbi ', 'Kano', ' Kogi', ' Lagos', 'Katsina', 'Kwara', 'Nasarawa', 'Niger ', 'Ogun', 'Ondo ', 'Rivers', 'Oyo', 'Osun', 'Sokoto', 'Plateau', 'Taraba', 'Yobe', 'Zamfara');
		return $state;
	}

	/**
	 * Function used for making curl post .
	 *
	 * @param  mixed $url
	 * @param  mixed $data
	 * @param  mixed $api_key
	 *
	 * @return void
	 */
	public function post_on_api_by_curl($url, $data, $api_key)
	{
		$ch = curl_init($url);
		// Setup request to send json via POST.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:' . $api_key));
		// Return response instead of printing.
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// Send request.
		$result = curl_exec($ch);
		curl_close($ch);
		// Print response.
		return $result;
	}
}
