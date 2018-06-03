<?php
class EcomhubFiListEvents
{
    /**
     * gets min, max,avg of the important fields see sql statement for the names
     * @return object
     * @throws
     */
    public static function get_stats_array() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ecombhub_fi_funnels';
        /** @noinspection SqlResolve */
        $res = $wpdb->get_results(
            " 
            select count(id) number_completed,
min(created_at_ts) as min_created_at_ts, max(created_at_ts) as max_created_at_ts,
count(user_id_read) as total_user_actions, count(invoice_number) as total_invoices,
count(error_message) as total_errors,sum(order_items) as total_items, sum(order_total) as total_of_orders
            from $table_name where is_completed = 1;
            ");

        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error );
        }
        return $res[0];

    }

	/**
	 * @return array
	 * @throws Exception
	 */
    public static function do_query_from_post() {
        if (array_key_exists( 'start_index',$_POST) ) {
            $start_index = intval($_POST['start_index']);
        } else {
            $start_index = null;
        }

        if (array_key_exists( 'limit',$_POST) ) {
            $limit = intval($_POST['limit']);
        } else {
            $limit = null;
        }

        if (array_key_exists( 'sort_by',$_POST) ) {
            $sort_by = $_POST['sort_by'];
        } else {
            $sort_by = null;
        }

        if (array_key_exists( 'sort_direction',$_POST) ) {
            $sort_direction = intval($_POST['sort_direction']);
        } else {
            $sort_direction = null;
        }

        if (array_key_exists( 'search_column',$_POST) ) {
            $search_column = $_POST['search_column'];
        } else {
            $search_column = null;
        }

        if (array_key_exists( 'search_value',$_POST) ) {
            $search_value = $_POST['search_value'];
        } else {
            $search_value = null;
        }

        return EcomhubFiListEvents::get_search_results_array($start_index,$limit,$sort_by,
            $sort_direction,$search_column,$search_value);
    }

    /**
     * @param $start_index
     * @param $limit
     * @param $sort_by
     * @param $sort_direction
     * @param $search_column
     * @param $search_value
     * @return array
     * @throws Exception
     */
    public static function get_search_results_array($start_index,$limit,$sort_by,
                                                    $sort_direction, $search_column,$search_value) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'ecombhub_fi_funnels';
        $user_table_name = $wpdb->prefix . 'users';

        $where_clause = '';
        $search_value = trim($search_value);
        if (!empty($search_column) && !empty($search_value)) {
            $escaped_value = sanitize_text_field($search_value);
            switch ($search_column) {
                case 'invoice_number':
	            case 'comments':
	            {
		            $where_clause .= " AND ($search_column LIKE '%$escaped_value%' ) ";
		            break;
	            }
	            case 'user_nicename': {
		            $where_clause .= " AND (
		                        (user_nicename LIKE '%$escaped_value%') OR 
		                        (user_email LIKE '%$escaped_value%') 
		                        ) ";
		            break;
	            }
            }
        }

        $sort_by_clause = " order by id asc";
        $sort_by = trim($sort_by);
        $sort_direction = intval($sort_direction);
        if ($sort_by) {

            switch ($sort_by) {
                case 'created_at_ts':
                case 'user_nicename':
	            case 'user_email':
	            case 'email_from':
	            case 'email_subject':
	            case 'is_error':
	            case 'user_id_read':
	            case 'order_total':
	            case 'order_items':
                case 'invoice_number': {
                     if ($sort_direction > 0) {
                         $sort_by_clause = " ORDER BY $sort_by ASC ";
                     } else {
                         $sort_by_clause = " ORDER BY $sort_by DESC ";
                     }
                     break;
                }
                default:
            }
        }


        $start_index = intval($start_index);
        $limit = intval($limit);
         if ($start_index > 0 && $limit > 0) {
             $offset_clause = "LIMIT $limit OFFSET $start_index";
         } elseif ($limit > 0) {
             $offset_clause = "LIMIT $limit";
         } elseif ($start_index > 0) {
             $offset_clause = "OFFSET $start_index";
         } else {
             $offset_clause = '';
         }


        //add in meta section of start and limit

        $res = $wpdb->get_results( /** @lang text */
                            "
                select f.id,f.is_completed,f.user_id_read,f.invoice_number,
                   created_at_ts, u.user_nicename,
                  u.user_email, f.email_from, f.email_attachment_files_saved,
                  f.email_subject,f.is_error,f.comments,f.order_total,f.order_items
                from $table_name f
                 LEFT JOIN $user_table_name u ON u.id = f.user_id_read
                 where ( f.is_completed = 1 ) 
                $where_clause $sort_by_clause  $offset_clause;"
        );

        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error );
        }

         $meta = [
             'start_index'=>$start_index,
             'limit'=>$limit,
             'sort_by'=>$sort_by,
             'sort_direction'=>$sort_direction,
             'search_column'=>$search_column,
             'search_value'=>$search_value
         ];
        return ['meta'=>$meta,'results'=>$res];

    }

    /**
     * @param int $funnel_transaction_id
     * @return array|bool
     * @throws Exception
     */
    public static function get_details_of_one($funnel_transaction_id) {
        global $wpdb;
	    $funnel_table_name = $wpdb->prefix . 'ecombhub_fi_funnels';
	    $user_table_name = $wpdb->prefix . 'users';
	    // $user_table_name = $wpdb->prefix . 'wp_users';
	    $funnel_transaction_id = intval($funnel_transaction_id);

        /** @noinspection SqlResolve */
        $survey_res = $wpdb->get_results("
        select f.id,f.created_at_ts,f.is_completed,f.user_id_read,f.raw_email,f.comments,f.invoice_number,
        f.email_to,f.email_from,f.email_subject,f.email_body,f.email_attachment_files_saved,f.is_error,
        f.error_message,u.user_nicename,
                  u.user_email
        from $funnel_table_name f
         LEFT JOIN $user_table_name u ON u.id = f.user_id_read
         where f.id = $funnel_transaction_id;
        ");

        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error );
        }

        if (empty($survey_res)) {return false;}
		return $survey_res[0];

    }

	/**
	 * Gets array of all posts that have the meta type of _funnel_product_id
	 * @return array
	 * @throws Exception
	 */
    public static function get_store_funnel_codes() {
	    global $wpdb;
	    $post_table_name = $wpdb->prefix . 'posts';
	    $meta_table_name = $wpdb->prefix . 'postmeta';


	    $survey_res = $wpdb->get_results("
				select p.post_title, p.ID as 'id', m.meta_id,m.meta_value as 'product_id' from $post_table_name p
				INNER JOIN $meta_table_name m ON m.post_id = p.ID
				where m.meta_key = '_funnel_product_id';"
	    );

	    if ($wpdb->last_error) {
		    throw new Exception($wpdb->last_error );
	    }

	    if (empty($survey_res)) {return [];}
	    return $survey_res;
    }

    public static function unbind_post_from_funnel($post_id_unbind) {
	    return delete_post_meta( $post_id_unbind, '_funnel_product_id');
    }

    public static function bind_post_to_funnel($post_id_bind,$product_id) {
	    delete_post_meta( $post_id_bind, '_funnel_product_id');
    	return add_post_meta( $post_id_bind, '_funnel_product_id', $product_id, true );
    }



}