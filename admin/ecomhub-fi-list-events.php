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
min(UNIX_TIMESTAMP(created_at)) as min_created_at_ts, max(UNIX_TIMESTAMP(created_at)) as max_created_at_ts
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
                    $where_clause .= " AND ($search_column LIKE '%$escaped_value%' ) ";
            }
        }

        $sort_by_clause = " order by id asc";
        $sort_by = trim($sort_by);
        $sort_direction = intval($sort_direction);
        if ($sort_by) {
            switch ($sort_by) {
                case 'created_at':
                case 'user_nicename':
	            case 'user_email':
	            case 'user_id_read':
                case 'invoice_id': {
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
                  UNIX_TIMESTAMP(f.created_at) as created_at_ts, u.user_nicename,
                  u.user_email
                from $table_name f
                 INNER JOIN $user_table_name u ON u.id = f.user_id_read
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
	    // $user_table_name = $wpdb->prefix . 'wp_users';
	    $funnel_transaction_id = intval($funnel_transaction_id);

        /** @noinspection SqlResolve */
        $survey_res = $wpdb->get_results("
        select id,created_at,is_completed,user_id_read,raw,comments,invoice_number
        from $funnel_table_name where id = $funnel_transaction_id;
        ");

        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error );
        }

        if (empty($survey_res)) {return false;}
		return $survey_res;


    }



}