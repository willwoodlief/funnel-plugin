<?php
require_once realpath( dirname( __FILE__ ) ) . '/../vendor/autoload.php';
require_once realpath( dirname( __FILE__ ) ) . '/../includes/JsonHelpers.php';
class EcomhubFiScrapeEmail
{
	var $email = null;
	var $full_name = null;
	var $first_name = null;
	var $last_name = null;
	var $street = null;
	var $city = null;
	var $state = null;
	var $country = null;
	var $postal = null;
	var $phone = null;
	var $full_address = null;
	var $user_referal_token = null;
	var $product_ids = [];
	var $stripe_customer_token = null;

	/**
	 * EcomhubFiScrapeEmail constructor.
	 *
	 * @param string $email_body
	 *
	 * @throws Exception
	 */
	public function __construct($email_body) {
		$html = str_get_html($email_body);
		$email_ele = $html->find('div.email');
		if (sizeof($email_ele) > 0) {
			$this->email = $email_ele[0]->plaintext;
		}

		$phone_ele = $html->find('div.phone');
		if (sizeof($phone_ele) > 0) {
			$this->phone = trim($phone_ele[0]->plaintext);
		}

		$name_ele = $html->find('div.name');
		if (sizeof($name_ele) > 0) {
			$this->full_name = trim(str_replace("Edit Contact Profile","",$name_ele[0]->plaintext));
			$parts = preg_split('/\s+/', $this->full_name, -1, PREG_SPLIT_NO_EMPTY);
			$this->first_name = $parts[0];
			array_shift($parts);
			$this->last_name = implode(' ',$parts);

		}



		$address_ele = $html->find('div.address');
		if (sizeof($address_ele) > 0) {
			$this->full_address = trim(str_replace("Address:","",$address_ele[0]->plaintext));
			//  555 Any Street
			// Huntsville , Texas
			// United States
			// 77320
			$addresses = explode("\n",$this->full_address);
			$this->street = trim($addresses[0]);
			$city_state = explode(",",$addresses[1]);
			$this->city = trim($city_state[0]);
			$this->state = trim($city_state[1]);
			$this->country = trim($addresses[2]);
			$this->postal = trim($addresses[3]);
		}



		// class name
		foreach ($html->find("div.info") as $element) {
			$what = $element->plaintext;
			if (strpos($what, 'product_ids') !== false) {
				//get product ids json
				$step1 = str_replace('purchase:','',$what);
				$step2 = html_entity_decode(trim($step1));
				$step3 = str_replace('=>',':',$step2);
				$json_products = JsonHelpers::fromString($step3);
				if (array_key_exists('product_ids',$json_products)) {
					$this->product_ids = $json_products['product_ids'];
				}

				if (array_key_exists('stripe_customer_token',$json_products)) {
					$this->stripe_customer_token = $json_products['stripe_customer_token'];
				}



			}
			if (strpos($what, 'user_order_token') !== false) {
				$split = explode(':',$what);
				if (sizeof($split) > 1) {
					$this->user_referal_token = trim($split[1]);
				}
			}
		}
	}
}