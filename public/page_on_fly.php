<?php
if (!class_exists('WP_EX_PAGE_ON_THE_FLY')){
	/**
	 * WP_EX_PAGE_ON_THE_FLY
	 * @author Ohad Raz
	 * @since 0.1
	 * Class to create pages "On the FLY"
	 * Usage:
	 *   $args = array(
	 *       'slug' => 'fake_slug',
	 *       'post_title' => 'Fake Page Title',
	 *       'post content' => 'This is the fake page content'
	 *   );
	 *   new WP_EX_PAGE_ON_THE_FLY($args);
	 */
	class WP_EX_PAGE_ON_THE_FLY
	{

		public $slug ='';
		public $args = array();
		public $partial = null;
		/**
		 * __construct
		 * @param array $args post to create on the fly
		 * @author Ohad Raz
		 *
		 */
		function __construct($args){
			add_filter('the_posts',array($this,'fly_page'));
			$this->args = $args;
			$this->slug = $args['slug'];
			$this->partial = $args['partial'];

		}

		/**
		 * @return string
		 */
		public function get_partial_content() {
			//pull in the content
			ob_start();
			require_once $this->partial;
			$html = ob_get_contents();
			ob_end_clean();
			return $html;
		}
		/**
		 * fly_page
		 * the Money function that catches the request and returns the page as if it was retrieved from the database
		 * @param  array $posts
		 * @return array
		 * @author Ohad Raz
		 */
		public function fly_page($posts){
			global $wp,$wp_query;
			$page_slug = $this->slug;

			//check if user is requesting our fake page
			if(count($posts) == 0 && (strtolower($wp->request) == $page_slug || $wp->query_vars['page_id'] == $page_slug)){


				$content = $this->get_partial_content();
				//create a fake post
				$post = new stdClass;
				$post->post_author = 1;
				$post->post_name = $page_slug;
				$post->guid = get_bloginfo('wpurl' . '/' . $page_slug);
				$post->post_title = 'page title';
				//put your custom content here
				$post->post_content = $content;
				//just needs to be a number - negatives are fine
				$post->ID = -42;
				$post->post_status = 'static';
				$post->comment_status = 'closed';
				$post->ping_status = 'closed';
				$post->comment_count = 0;
				//dates may need to be overwritten if you have a "recent posts" widget or similar - set to whatever you want
				$post->post_date = current_time('mysql');
				$post->post_date_gmt = current_time('mysql',1);

				$post = (object) array_merge((array) $post, (array) $this->args);
				$posts = NULL;
				$posts[] = $post;

				$wp_query->is_page = true;
				$wp_query->is_singular = true;
				$wp_query->is_home = false;
				$wp_query->is_archive = false;
				$wp_query->is_category = false;
				unset($wp_query->query["error"]);
				$wp_query->query_vars["error"]="";
				$wp_query->is_404 = false;
			}

			return $posts;
		}
	}//end class
}//end if