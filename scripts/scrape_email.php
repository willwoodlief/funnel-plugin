<?php
require_once realpath( dirname( __FILE__ ) ) . '/../vendor/autoload.php';
require_once realpath( dirname( __FILE__ ) ) . '/../includes/JsonHelpers.php';
class EcomhubFiScrapeEmail
{
	var $email = null;
	var $user_referal_token = null;
	var $product_ids = [];
	var $stripe_customer_token = null;

	public function __construct($email_body) {
		$html = str_get_html($email_body);
		$email_ele = $html->find('div.email');
		if (sizeof($email_ele) > 0) {
			$this->email = $email_ele[0]->plaintext;
		}


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