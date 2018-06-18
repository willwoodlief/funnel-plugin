<?php
require_once realpath( dirname( __FILE__ ) ) . '/scrape_email.php';
require_once realpath( dirname( __FILE__ ) ) . '/../includes/JsonHelpers.php';
require_once realpath( dirname( __FILE__ ) ) . '/../includes/curl_helper.php';

class EcomhubFiProductAlreadyPurchasedException extends Exception {}
class EcomhubFiNoAssociatedProductFoundException extends Exception {}

class EcomhubFiConnectOrder {
	var $user = null;
	var $completed_orders = [];
	var $post_ids = [];


	/**
	 * EcomhubFiConnectOrder constructor.
	 *
	 * if the ($email_id, string (args) ) is done, then will parse the email body, args is the email body
	 *
	 * if the ($email_id,array (args) , then will need $email_id to be completed
	 *  and behavior depends on what is in args and what is in $operation_name
	 *
	 *      possible values in args is:
	 *          user_id            : string (but only used in REDO_ORDERS)
	 *          user_id_reference  : string (but only used in REDO_ORDERS)
	 *          invoice_number     : string
	 *          email_from_notice  : string
	 *          comments : string
	 *
	 *         product_ids: array of ints  (but only used in REDO_ORDERS)
	 *         b_using_post_ids: boolean (but only used in REDO_ORDERS), only used if product_ids used
	 *          payment_method: overrides set payment method (but only used in REDO_ORDERS)
	 *
	 *
	 *
	 *      if   invoice_number and/or email_from_notice and/or comments is there then the id is updated
	 *          if UPDATE_KEEP_ORDERS command then that is all that is done
	 *
	 *
	 *      any of the following require a command of REDO_ORDERS
	 *      if  'user_id' and/or user_id_reference is set and is different from the older user_id
	 *           will  update the main row of mail
	 *          if both used, must point to the same user or exception raised
	 *          if product_ids missing or empty in array,
	 *              then will replay the funnel orders with the new user id and delete the older ones
	 *
	 *      if  REDO_ORDERS set then will delete the older orders use the new post/product_ids
	 *          to create new ones. If the user_id and  user_id_reference is both missing will use
	 *              the original user, will use product_ids or post_ids depending on how b_using_post_ids is set
	 *              default is true for using post ids
	 *
	 *
	 *     $operation_name:
	 *          PARSE_BODY, same as null, expects $args to be a string and will scrape the email and make new orders
	 *          UPDATE_KEEP_ORDERS  , only updates invoice_number and/or email_from_notice and/or notes
	 *          REDO_ORDERS, redoes the orders, using the args mentioned above
	 *          DELETE_MAIL, will ignored all args and just delete all the mail and any orders that may have happened
	 *
	 * @param integer $mail_id (required)
	 * @param string|array|null $args
	 * @param string|null $command , default null
	 *
	 * @throws
	 */
	public function __construct( $mail_id, $args = null, $command = null ) {
		if ( empty( $mail_id ) || ! is_integer( $mail_id ) ) {
			throw new Exception( "Mail ID is not an integer" );
		}
		global $wpdb;
		$funnel_table_name = $wpdb->prefix . 'ecombhub_fi_funnels';

		$options = get_option( 'ecomhub_fi_options' );

		$checkout       = function ( $name ) use ( $options ) {
			if ( ! isset( $options[ $name ] ) || empty( trim( $options[ $name ] ) ) ) {
				throw new Exception( "Could not find plugin option of $name" );
			}

			return $options[ $name ];
		};
		$payment_method = $checkout( 'woo_payment_type' );

		if ( empty( $command ) || ( $command == 'PARSE_BODY' ) ) {
			if ( ! is_string( $args ) ) {
				throw new Exception( "Expected to find string body to parse" );
			}

			$update_args = [];
			try {

				$scraper            = new EcomhubFiScrapeEmail( $args ); //if args is a string
				$user_referal_token = $scraper->user_referal_token;

				$found_user_id = self::find_user_by_reference( $user_referal_token );
				$product_array = $scraper->product_ids;

				$invoice_number                   = $scraper->stripe_customer_token;
				$email_from_notice                = $scraper->email;
				$update_args['user_id_read']      = $found_user_id;
				$update_args['invoice_number']    = $invoice_number;
				$update_args['email_from_notice'] = $email_from_notice;
				$update_args['user_id_reference'] = $user_referal_token;
				self::update_mail_and_create_orders( $mail_id, $found_user_id, $product_array, false, $payment_method, $update_args, $scraper );

			} catch ( Exception $e ) {
				throw $e;
			}
		} elseif ( $command == 'UPDATE_KEEP_ORDERS' ) {
			$update_args = [];
			if ( ! is_array( $args ) ) {
				throw new Exception( "Args needs to be an array for option UPDATE_KEEP_ORDERS" );
			}
			if ( array_key_exists( 'invoice_number', $args ) ) {
				$update_args['invoice_number'] = $args['invoice_number'];
			}
			if ( array_key_exists( 'email_from_notice', $args ) ) {
				$update_args['email_from_notice'] = $args['email_from_notice'];
			}
			if ( array_key_exists( 'comments', $args ) ) {
				$update_args['comments'] = $args['comments'];
			}

			if ( ! empty( $update_args ) ) {
				$b_check = $wpdb->update(
					$funnel_table_name,
					$update_args,
					array( 'id' => $mail_id )
				);

				if ( $wpdb->last_error ) {
					throw new Exception( $wpdb->last_error );
				}

				if ( $b_check === false ) {
					throw new Exception( "Could not update $funnel_table_name " . print_r( $update_args, true ) );
				}
			} else {
				throw new Exception( "Nothing was updated " );
			}

		} elseif ( $command == 'REDO_ORDERS' ) {

			if ( ! is_array( $args ) ) {
				throw new Exception( "Need args to be array for this option" );
			}
			$args['is_error'] = 0;
			$res              = $wpdb->get_results(
			/** @lang text */
				" 
            select id,user_id_read,email_body
            from $funnel_table_name where id = $mail_id;
            " );

			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}
			if ( empty( $res ) ) {
				throw new Exception( "Could not find mail id" . $mail_id );
			}
			$funnel_row = $res[0];


			if ( array_key_exists( 'product_ids', $args ) ) {
				$product_array = $args['product_ids'];
				if ( array_key_exists( 'b_using_post_ids', $args ) ) {
					$b_using_posts = $args['b_using_post_ids'];
				} else {
					$b_using_posts = true;
				}
			} else {
				$scraper       = new EcomhubFiScrapeEmail( $funnel_row->email_body );
				$product_array = $scraper->product_ids;
				if ( empty( $product_array ) ) {
					throw new Exception( "No Products Found" );
				}
				$b_using_posts = false;
			}


			$user_id = null;
			if ( array_key_exists( 'user_id', $args ) ) {
				$user_id = $args['user_id'];
			} else {
				//see if reference set
				if ( array_key_exists( 'user_id_reference', $args ) ) {
					$user_referal_token = $args['user_id_reference'];
					$found_user_id      = self::find_user_by_reference( $user_referal_token );
					if ( empty( $found_user_id ) ) {
						throw new Exception( "cannot find user" );
					}
					$user_id                   = $found_user_id;
					$args['user_id_reference'] = $user_referal_token;
				} else {
					$user_id = $funnel_row->user_id_read;
				}

			}

			if ( empty( $user_id ) ) {
				throw new Exception( "Cannot find any user id" );
			}

			if ( array_key_exists( 'payment_method', $args ) && ! empty( $args['payment_method'] ) ) {
				$payment_method = $args['payment_method'];
			}


			// delete all orders in woo
			self::delete_all_orders_in_funnel_row( $mail_id );
			$args['user_id_read'] = $user_id;
			unset( $args['user_id'] );
			unset( $args['product_ids'] );
			unset( $args['b_using_post_ids'] );
			unset( $args['payment_method'] );

			self::update_mail_and_create_orders( $mail_id, $user_id, $product_array, $b_using_posts, $payment_method, $args );

		} elseif ( $command == 'DELETE_MAIL' ) {
			//delete all order rows, delete main row
			self::delete_all_orders_in_funnel_row( $mail_id );

			$b_check = $wpdb->delete( $funnel_table_name, array( 'id' => $mail_id ) );

			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}

			if ( $b_check === false ) {
				throw new Exception( "Could not delete from  $funnel_table_name with mail id of $mail_id " );
			}
		} else {
			//unknown
			throw new Exception( "Unknown command in EcomhubFiConnectOrder: $command" );
		}


	}

	/**
	 * @throws Exception
	 */
	public static function sort_all_the_good_and_ugly() {

		$args = [];
		if ( array_key_exists( 'id', $_POST ) ) {
			$id = intval( $_POST['id'] );
		} else {
			throw new Exception( "Could not find ID" );
		}

		if ( array_key_exists( 'user_login', $_POST ) ) {

			//find user id
			$nice_username = sanitize_text_field( $_POST['user_login'] );
			if ( $nice_username ) {
				$user = get_user_by( 'login', $nice_username );
				if ( ! $user ) {
					throw new Exception( "Cannot find user from username of " . $_POST['user_login'] );
				}
				$args['user_id'] = $user->id;
			}

		}

		if ( array_key_exists( 'invoice_number', $_POST ) ) {
			$args['invoice_number'] = sanitize_text_field( $_POST['invoice_number'] );
		}

		if ( array_key_exists( 'comments', $_POST ) ) {
			$args['comments'] = sanitize_text_field( $_POST['comments'] );
		}

		if ( array_key_exists( 'orders', $_POST ) ) {
			$args['product_ids']      = $_POST['orders'];
			$args['b_using_post_ids'] = true;
		}

		if ( array_key_exists( 'payment_method', $_POST ) ) {
			$args['payment_method'] = sanitize_text_field( $_POST['payment_method'] );
		}

		if ( isset( $args['user_id'] ) || isset( $args['product_ids'] ) || isset( $args['payment_method'] ) ) {
			$method = 'REDO_ORDERS';
		} else {
			$method = 'UPDATE_KEEP_ORDERS';
		}

		new EcomhubFiConnectOrder( $id, $args, $method );

		return true;
	}

	/**
	 * @param $mail_id
	 *
	 * @return integer - how many deleted
	 * @throws Exception
	 */
	public static function delete_all_orders_in_funnel_row( $mail_id ) {
		global $wpdb;
		$funnel_order_table_name = $wpdb->prefix . 'ecombhub_fi_funnel_orders';
		$mail_id                 = intval( $mail_id );
		//get all orders
		$res = $wpdb->get_results(
		/** @lang text */
			" 
            select id,post_product_id,order_id 
            from $funnel_order_table_name where ecombhub_fi_funnel_id = $mail_id;
            " );

		if ( $wpdb->last_error ) {
			throw new Exception( $wpdb->last_error );
		}

		$count = 0;
		foreach ( $res as $row ) {
			$order_id         = $row->order_id;
			$dependent_row_id = $row->id;
			$woo              = self::delete_woo_order( $order_id, $http_code );
			if ( $http_code != 200 ) {
				throw new Exception( "Cannot delete order # $order_id from woo [row $dependent_row_id] : " . print_r( $woo, true ) );
			}

			$b_check = $wpdb->delete( $funnel_order_table_name, array( 'id' => $dependent_row_id ) );

			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}

			if ( $b_check === false ) {
				throw new Exception( "Could not delete from  $funnel_order_table_name with id id of $dependent_row_id " );
			}
			$count ++;
		}

		return $count;
	}

	/**
	 * @param integer $mail_id
	 * @param integer $found_user_id
	 * @param array $product_array
	 * @param boolean $b_using_posts
	 * @param string $payment_method
	 * @param array $update_args
	 * @param EcomhubFiScrapeEmail $data
	 *
	 * @throws Exception
	 */
	public static function update_mail_and_create_orders(
		$mail_id, $found_user_id, array $product_array, $b_using_posts, $payment_method,
		array &$update_args, $data = null
	) {
		global $wpdb;
		$funnel_table_name = $wpdb->prefix . 'ecombhub_fi_funnels';

		try {


			if ( empty( $found_user_id ) ) {
				throw new Exception( "cannot find user" );
			}
			if ( empty( $product_array ) ) {
				throw new Exception( "No Products Found" );
			}

			$update_args['user_id_read'] = $found_user_id;
			$total_sum                   = 0;
			foreach ( $product_array as $product_id ) {
				if ( empty( trim( $product_id ) ) ) {
					throw new Exception( "Product ID was empty: " . print_r( $product_array, true ) );
				}

				try {
					$da_order = self::create_funnel_order( $mail_id, $product_id, $b_using_posts, $found_user_id, $payment_method, $sum, $data );
					self::update_user_fields_if_not_set($found_user_id,$data);
					self::create_shop_membership_order($mail_id,$da_order['id'],$da_order['post_id'],$found_user_id, $payment_method, $sum_addition, $data );
				} catch ( Exception $e ) {
					$update_args['is_error']      = 1;
					$update_args['error_message'] = "Order with Product ID of $product_id has the following error: " . $e->getMessage();
					break;
				}

				if ( $da_order['is_error'] ) {
					$update_args['is_error']      = 1;
					$update_args['error_message'] = "Order with Product ID of $product_id has the following error: " . $da_order['error_message'];
				}

				$total_sum += $sum;
			}
			$update_args['order_total'] = $total_sum;
			$update_args['order_items'] = sizeof( $product_array );

		} catch ( Exception $e ) {
			$update_args['is_error']      = 1;
			$update_args['error_message'] = $e->getMessage();
			$update_args['error_trace']   = $e->getTraceAsString();
		} finally {
			$update_args['is_completed'] = 1;
			$b_check                     = $wpdb->update(
				$funnel_table_name,
				$update_args,
				array( 'id' => $mail_id )
			);

			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}

			if ( $b_check === false ) {
				throw new Exception( "Could not update $funnel_table_name " . print_r( $update_args, true ) );
			}
		}
	}

	public static function add_access_product($main_product_id) {

	}
	/**
	 * @param $mail_id
	 * @param $funnel_product_id
	 * @param bool $b_using_post_id
	 * @param $user_id
	 * @param $payment_type
	 * @param &$order_total
	 * @param EcomhubFiScrapeEmail $data
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function create_funnel_order( $mail_id, $funnel_product_id, $b_using_post_id, $user_id, $payment_type, &$order_total, $data = null ) {

		global $wpdb;
		$funnel_order_table_name = $wpdb->prefix . 'ecombhub_fi_funnel_orders';

		if ( empty( $mail_id ) ) {
			throw new Exception( "Mail ID is empty;" );
		}
		$ret = [
			'ecombhub_fi_funnel_id' => $mail_id,
			'funnel_product_id'     => $funnel_product_id,
			'user_id'               => $user_id,
			'payment_type'          => $payment_type
		];
		try {
			//try to get post_id
			if ( ! $b_using_post_id ) {
				$post_id = self::find_post_by_funnel_id( $funnel_product_id );
			} else {
				$post_id = $funnel_product_id;
			}


			$ret['post_product_id'] = $post_id;
			if ( empty( $post_id ) ) {
				throw new Exception( "Cannot find post id from funnel code of $funnel_product_id" );
			}
			if ( empty( $funnel_product_id ) ) {
				throw new Exception( "funnel_product_id is empty" );
			}
			if ( empty( $user_id ) ) {
				throw new Exception( "user id is empty" );
			}
			$http_code = 0;
			$woo       = null;
			$b_already_bought  = wc_customer_bought_product(null,$user_id,$post_id);
			if ($b_already_bought) {
				throw new Exception("$post_id aleady bought by user id of $user_id");
			}
			try {
				//check to see if product already sold, if so then exit with an error



				$woo = self::make_woo_order( $user_id, $post_id, $payment_type, $http_code, $data );
				if ( $http_code != 201 ) {
					throw new Exception( "Did not get 201 code when creating order" );
				}
			} catch ( Exception $e ) {
				throw new Exception( "Could not create order: " . $e->getMessage() );
			} finally {
				$ret['order_output'] = JsonHelpers::toString( $woo );
				if ($woo && array_key_exists( 'order_id', $woo ) ) {
					$ret['order_id'] = $woo['order_id'];
				} else {
					$ret['order_id'] = null;
				}

				if ( array_key_exists( 'total', $woo ) ) {
					$order_total        = $woo['total'];
					$ret['order_total'] = $order_total;
				} else {
					$order_total        = null;
					$ret['order_total'] = $order_total;
				}
			}


		} catch ( Exception $e ) {
			$ret['is_error']      = 1;
			$ret['error_message'] = $e->getMessage();
			$ret['error_trace']   = $e->getTraceAsString();
		} finally {
			$b_check = $wpdb->insert(
				$funnel_order_table_name,
				$ret
			);

			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}

			if ( $b_check === false ) {
				throw new Exception( "Could not insert $funnel_order_table_name" );
			}

			if ( ! $wpdb->insert_id ) {
				throw new Exception( "Could not insert $funnel_order_table_name row" );
			}

			$ret['id'] = $wpdb->insert_id;
			$ret['post_id'] = $post_id;
		}

		return $ret;
	}


	/**
	 * @param $mail_id
	 * @param $funnel_order_id
	 * @param $parent_post_product_id
	 * @param $user_id
	 * @param $payment_type
	 * @param $order_total
	 * @param null $data
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function create_shop_membership_order( $mail_id,$funnel_order_id,$parent_post_product_id, $user_id, $payment_type, &$order_total, $data = null ) {
		global $wpdb;
		$funnel_order_table_name = $wpdb->prefix . 'ecombhub_fi_funnel_orders';

		if ( empty( $mail_id ) ) {
			throw new Exception( "Mail ID is empty;" );
		}

		//see if there is a membership item with this post
		$membership_product_id = get_post_meta($parent_post_product_id,'ecomhub_fi_shop_membership_item',true);

		$ret = [
			'payment_type'          => $payment_type,
			'extra_order_output' => null,
			'extra_order_product_id' => $membership_product_id,
			'extra_error_message'        => null,
			'extra_error_trace'          => null
		];
		try {
			if ($membership_product_id) {
				//check to see if they already have one
				$b_already_bought  = wc_customer_bought_product(null,$user_id,$membership_product_id);
				if ($b_already_bought) {
					throw new EcomhubFiProductAlreadyPurchasedException(" user [$user_id] Already Owns Shop membership post id of $membership_product_id");
				}
			}

			if ( empty( $membership_product_id ) ) {
				throw new EcomhubFiNoAssociatedProductFoundException( "Cannot find a membership product id from product id of [$parent_post_product_id]" );
			}
			if ( empty( $funnel_order_id ) ) {
				throw new Exception( "funnel order id  is empty" );
			}
			if ( empty( $user_id ) ) {
				throw new Exception( "user id is empty" );
			}
			$http_code = 0;
			$woo       = null;
			try {

				$woo = self::make_woo_order( $user_id, $membership_product_id, $payment_type, $http_code, $data );
				if ( $http_code != 201 ) {
					throw new Exception( "Did not get 201 code when creating order" );
				}
			} catch ( Exception $e ) {
				throw new Exception( "Could not create order: " . $e->getMessage() );
			} finally {
				$ret['extra_order_output'] = JsonHelpers::toString( $woo );
				if ( array_key_exists( 'order_id', $woo ) ) {
					$ret['extra_order_id'] = $woo['order_id'];
				} else {
					$ret['extra_order_id'] = null;
				}

				if ( array_key_exists( 'total', $woo ) ) {
					$order_total        = $woo['total'];
					$ret['extra_order_total'] = $order_total;
				} else {
					$order_total        = null;
					$ret['extra_order_total'] = $order_total;
				}
			}


		}
		catch (EcomhubFiProductAlreadyPurchasedException $pe) {
			$ret['is_error']      = 0;
			$ret['extra_error_message'] = $pe->getMessage();
			$ret['extra_error_trace']   = $pe->getTraceAsString();
		}
		catch(EcomhubFiNoAssociatedProductFoundException $ne) {
			$ret['is_error']      = 0;
			$ret['extra_error_message'] = $ne->getMessage();
			$ret['extra_error_trace']   = $ne->getTraceAsString();
		}
		catch ( Exception $e ) {
			$ret['is_error']      = 1;
			$ret['extra_error_message'] = $e->getMessage();
			$ret['extra_error_trace']   = $e->getTraceAsString();
		} finally {

			$b_check = $wpdb->update($funnel_order_table_name,
				$ret,
				array('id' => $funnel_order_id)
			);


			if ( $wpdb->last_error ) {
				throw new Exception( $wpdb->last_error );
			}

			if ( $b_check === false ) {
				throw new Exception( "Could not update $funnel_order_table_name, id of $funnel_order_id" );
			}


			$ret['id'] = $funnel_order_id;
		}

		return $ret;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function check_order_for_shop_membership($order) {
		require_once realpath( dirname( __FILE__ ) ) ."/../admin/ecomhub-fi-list-events.php";
		$items = $order->get_items();
		foreach ( $items as $item ) {
			$product_id = $item['product_id'];
			//see if this product is part of the ecomhub_fi_shop_membership_item meta for any product
			$b_is_shop_membership_product = EcomhubFiListEvents::is_post_shop_membership_item($product_id);
			if ( $b_is_shop_membership_product ) {
		//		ecom_fi_log_to_debug_alert("looking at product id","$product_id , was seen as target");
				return true;

			}
		}
	//	ecom_fi_log_to_debug_alert("looking at product id","did not find anything for [$product_id]");
		return false;
	}
	/**
	 *
	 * creates an order using the woo rest api for this server
	 *  (creds stored in this plugins options under woo_rest_api_key and _woo_rest_api_secret)
	 *  (api endpoint stored in plugin options under _woo_endpoint)
	 *  (payment type stored in this plugins options under the _woo_payment_type)
	 *
	 * @param $user_id
	 * @param $post_id
	 * @param $payment_method
	 * @param integer &$http_code {OUT REF}
	 * @param EcomhubFiScrapeEmail $data
	 *
	 * @return array
	 * @throws
	 */
	public static function make_woo_order( $user_id, $post_id, $payment_method, &$http_code, $data = null ) {
		$order_id = null;

		$payload = [
			"customer_note"=> "Paid for through Click Funnels",

//			"billing"        => [
//				"first_name" => $data->first_name,
//				"last_name"  => $data->last_name,
//				"address_1"  => $data->street,
//				"city"       => $data->city,
//				"state"      => $data->state,
//				"postcode"   => $data->postal,
//				"country"    => $data->country,
//				"email"      => $data->email,
//				"phone"      => $data->phone
//			],

			"payment_method" => $payment_method,
			"customer_id"    => $user_id,
			"status"         => "completed",
			"date_paid"      => date( 'Y-m-d\TH:i:s' ),
			"date_completed" => date( 'Y-m-d\TH:i:s' ),
			"set_paid"       => true,
			"line_items"     => [
				[
					"product_id" => $post_id,
					"quantity"   => 1
				]
			]
		];


		$ret      = EcomhubFiConnectOrder::talk_to_woo( 'POST', 'orders', $payload, $http_code );
		$order_id = $ret['id'];
		$total    = $ret['total'];

		return [ 'order_id' => $order_id, 'total' => $total, 'raw' => $ret ];
	}

	/**
	 * @param integer $user_id
	 * @param EcomhubFiScrapeEmail $data
	 */
	public static function update_user_fields_if_not_set($user_id,$data) {
		$user = get_user_by( 'id', $user_id );
		if (empty($user->last_name)) {
			$user->last_name = $data->last_name;
		}
		if (empty($user->first_name)) {
			$user->first_name = $data->first_name;
		}
		if (empty($user->display_name)) {
			$user->display_name = $data->full_name;
		}

		wp_update_user( $user );
	}

	/**
	 * Deletes an order from woo
	 *  (creds stored in this plugins options under _woo_rest_api_key and _woo_rest_api_secret)
	 *  (api endpoint stored in plugin options under _woo_endpoint)
	 *
	 * @param integer $order_id
	 * @param integer $http_code {REF OUT}
	 *
	 * @return array
	 * @throws
	 */
	public static function delete_woo_order( $order_id, &$http_code ) {
		$subpath = "orders/$order_id";

		return EcomhubFiConnectOrder::talk_to_woo( 'DELETE', $subpath, null, $http_code, false );
	}

	/**
	 * checks user meta data for an entry of _funnel_reference
	 * if found returns the user and keeps that meta
	 *
	 * @param string $user_id_reference
	 * @param integer $check_against_user_id , if provided will throw an exception if the user does not match
	 *
	 * @return integer
	 * @throws
	 */
	public static function find_user_by_reference( $user_id_reference, $check_against_user_id = null ) {
		global $wpdb;
		$user_table_name = $wpdb->prefix . 'users';
		$meta_table_name = $wpdb->prefix . 'usermeta';


		$survey_res = $wpdb->get_results( /** @lang text */
			"
				select  p.ID as 'id' from $user_table_name p
				INNER JOIN $meta_table_name m ON m.user_id = p.ID
				where m.meta_key = '_ecomhub_fi_funnel_reference' and m.meta_value = '$user_id_reference';"
		);

		if ( $wpdb->last_error ) {
			throw new Exception( $wpdb->last_error );
		}

		if ( empty( $survey_res ) ) {
			return null;
		}
		$found_id = $survey_res[0]->id;
		if ( $check_against_user_id ) {
			if ( $check_against_user_id != $found_id ) {
				throw new Exception( "Does not match in getting user by reference $user_id_reference,$check_against_user_id != $found_id " );
			}
		}

		return $found_id;
	}

	/**
	 * @param string $funnel_id
	 *
	 * @return integer  - post id
	 * @throws
	 */
	public static function find_post_by_funnel_id( $funnel_id ) {


		global $wpdb;
		$post_table_name = $wpdb->prefix . 'posts';
		$meta_table_name = $wpdb->prefix . 'postmeta';


		$survey_res = $wpdb->get_results( /** @lang text */
			"
				select  p.ID as 'id', m.meta_id,m.meta_value  from $post_table_name p
				INNER JOIN $meta_table_name m ON m.post_id = p.ID
				where m.meta_key = '_funnel_product_id' ;"
		);

		if ( $wpdb->last_error ) {
			throw new Exception( $wpdb->last_error );
		}

		if ( empty( $survey_res ) ) {
			return null;
		}
		foreach ($survey_res as $s) {
			$this_funnel_code = null;
			$un = $s->meta_value;
			$s->unserialized = unserialize($un);
			if ($s->unserialized === false) {
				if ($s->meta_value) {
					$this_funnel_code = $s->meta_value;
					if ($this_funnel_code == $funnel_id) {
						return $s->id;
					}
				}
			}
			if (is_array($s->unserialized)) {
				foreach ($s->unserialized as $hm) {
					$this_funnel_code = $hm;
					if ($this_funnel_code == $funnel_id) {
						return $s->id;
					}
				}
			}
		}

		return null;
	}

	/**
	 * goes through each post and updates the meta _funnel_product_id of the post
	 * if one already exists it will be changed to the new one
	 *
	 * @param $posts_x_products : array [ ['post'=>,'product'=>] ,...]
	 *
	 * @throws
	 */

	public static function associate_posts_with_funnel_product_ids( array $posts_x_products ) {
		require_once realpath( dirname( __FILE__ ) ) . '/../admin/ecomhub-fi-list-events.php';

		foreach ( $posts_x_products as $x ) {
			$post_id    = $x['post'];
			$product_id = $x['product'];
			$b_what     = EcomhubFiListEvents::bind_post_to_funnel( $post_id, $product_id );
			if ( ! $b_what ) {
				throw new Exception( "Could not find $post_id to product id of $product_id" );
			}
		}

	}


	/**
	 * @param string $method
	 * @param string $subpath
	 * @param mixed $payload
	 * @param integer $http_code {OUT REF}
	 * @param bool $b_debug
	 *
	 * @return array
	 * @throws CurlHelperException
	 * @throws Exception
	 */
	public static function talk_to_woo( $method, $subpath, $payload, &$http_code, $b_debug = false ) {


		$options = get_option( 'ecomhub_fi_options' );

		$checkout = function ( $name ) use ( $options ) {
			if ( ! isset( $options[ $name ] ) || empty( trim( $options[ $name ] ) ) ) {
				throw new Exception( "Could not find plugin option of $name" );
			}

			return $options[ $name ];
		};

		$username     = $checkout( 'woo_rest_api_key' );
		$password     = $checkout( 'woo_rest_api_secret' );
		$url          = $checkout( 'woo_api_endpoint' ) . '/' . $subpath;
		$payload_json = JsonHelpers::toStringAgnostic( $payload );

		$headers = [
			"Authorization: Basic " . base64_encode( "$username:$password" ),
			'Accept: application/json',
			'Content-Type: application/json'
		];


		$custom_request = false;
		switch ( $method ) {
			case 'GET':
				{
					$b_post = false;
					break;
				}
			case 'POST':
				{
					$b_post = true;
					break;
				}
			default:
				{
					$b_post         = true;
					$custom_request = $method;
				}
		}


		$resp = curl_helper( $url, $payload_json, $http_code, $b_post, 'json',
			$b_debug, false, false, $headers, $custom_request );


		return $resp;
	}


}