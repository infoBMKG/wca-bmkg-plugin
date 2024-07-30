<?php
/*
 * Plugin Name:       WP-BMKG Custom API
 * Plugin URI:        https://github.com/infoBMKG/wca-bmkg-plugin/
 * Description:       WordPress Custom REST API for BMKG Content
 * Version:           1.4
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            Raksaka Indra
 * Author URI:        https://github.com/raksakaindra
 * License:           MIT
 * License URI:       https://www.mit.edu/~amini/LICENSE.md
 * Update URI:        https://github.com/infoBMKG/wca-bmkg-plugin/
 */

// Plugin updater
// Thanks to https://github.com/rudrastyh/misha-update-checker
defined( 'ABSPATH' ) || exit;

if( ! class_exists( 'wcaBmkgUpdate' ) ) {

	class wcaBmkgUpdate{

		public $plugin_slug;
		public $plugin_basename_file;
		public $version;
		public $cache_key;
		public $cache_allowed;

		public function __construct() {

			$this->plugin_slug = plugin_basename( __DIR__ );
			$this->plugin_basename_file = plugin_basename( __FILE__ );
			$this->version = '1.4';
			$this->cache_key = 'wca_bmkg_upd';
			$this->cache_allowed = false;

			add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update' ) );  //Fix: Update hook that this not called that much often and make it compatible with autoupdate-feature
			add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );

		}

		public function request(){

			$remote = get_transient( $this->cache_key );

			//Enable force check via update-core.php
			if(isset($_GET['force-check']) && $_GET['force-check'] == 1){
				$remote = false;
			}

			if( false === $remote || ! $this->cache_allowed ) {

				$remote = wp_remote_get(
					'https://raw.githubusercontent.com/infoBMKG/wca-bmkg-plugin/main/plugin-update.json?cache=' . rand(10,100),
					array(
						'timeout' => 10,
						'headers' => array(
							'Accept' => 'application/json'
						)
					)
				);

				if(
					is_wp_error( $remote )
					|| 200 !== wp_remote_retrieve_response_code( $remote )
					|| empty( wp_remote_retrieve_body( $remote ) )
				) {
					return false;
				}

				set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

			}

			$remote = json_decode( wp_remote_retrieve_body( $remote ) );

			return $remote;

		}


		function info( $res, $action, $args ) {

			// print_r( $action );
			// print_r( $args );

			// do nothing if you're not getting plugin information right now
			if( 'plugin_information' !== $action ) {
				return $res;
			}

			// do nothing if it is not our plugin
			if( $this->plugin_slug !== $args->slug ) {
				return $res;
			}

			// get updates
			$remote = $this->request();

			if( ! $remote ) {
				return $res;
			}

			$res = new stdClass();

			$res->name = $remote->name;
			$res->slug = $remote->slug;
			$res->version = $remote->version;
			$res->tested = $remote->tested;
			$res->requires = $remote->requires;
			$res->author = $remote->author;
			$res->author_profile = $remote->author_profile;
			$res->download_link = $remote->download_url;
			$res->trunk = $remote->download_url;
			$res->requires_php = $remote->requires_php;
			$res->last_updated = $remote->last_updated;

			$res->sections = array(
				'description' => $remote->sections->description,
				'installation' => $remote->sections->installation,
				'changelog' => $remote->sections->changelog
			);

			if( ! empty( $remote->banners ) ) {
				$res->banners = array(
					'low' => $remote->banners->low,
					'high' => $remote->banners->high
				);
			}

			return $res;

		}

		public function update( $transient ) {

			$remote = $this->request();

			if(
				$remote
				&& version_compare( $this->version, $remote->version, '<' )
				&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' )
				&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
			) {
				//Update available
				
				$res = new stdClass();
				$res->slug = $this->plugin_slug;
				$res->plugin = $this->plugin_basename_file; 
				$res->new_version = $remote->version;
				$res->tested = $remote->tested;
				$res->package = $remote->download_url;

				$transient->response[ $res->plugin ] = $res;

	    		}else{
				//No update or no connection
				
				$res = new stdClass();
				$res->id = $this->plugin_basename_file;
				$res->slug = $this->plugin_slug;
				$res->plugin = $this->plugin_basename_file;
				$res->new_version = $this->version;
				$res->url = '';
				$res->package = '';
				$res->icons = [];
				$res->banners = [];
				$res->banners_rtl = [];
				$res->tested = '';
				$res->requires_php = '';
				$res->compatibility = new stdClass();

				$transient->no_update[$res->plugin] = $res;
				
			}

			return $transient;

		}

		public function purge( $upgrader, $options ){

			if (
				$this->cache_allowed
				&& 'update' === $options['action']
				&& 'plugin' === $options[ 'type' ]
			) {
				// just clean the cache when new plugin version is installed
				delete_transient( $this->cache_key );
			}

		}


	}

	new wcaBmkgUpdate();

}

// List Posts Func
function wca_list_posts($param) {
	$args = [
		'post_type' => 'post',
		'post_status' => 'publish',
		'cat' => $param['cat'],
		'posts_per_page' => $param['perpage'],
		'offset' => $param['offset']
	];
	
	$query = new WP_Query($args);
	$posts = $query->get_posts();

	$data = [];
	$i = 0;

	foreach($posts as $post) {
		$data[$i]['total'] = $query->found_posts;
		$data[$i]['date'] = $post->post_date;
		$data[$i]['title'] = apply_filters('the_title', $post->post_title);
		$data[$i]['slug'] = $post->post_name;
		$data[$i]['excerpt'] = apply_filters('the_excerpt', $post->post_excerpt);
		$data[$i]['featured_image']['thumbnail'] = get_the_post_thumbnail_url($post->ID, 'thumbnail');
		$data[$i]['featured_image']['medium'] = get_the_post_thumbnail_url($post->ID, 'medium');
		$data[$i]['featured_image']['large'] = get_the_post_thumbnail_url($post->ID, 'large');
		$data[$i]['featured_image']['full'] = get_the_post_thumbnail_url($post->ID, 'full');
		$i++;
	}
	
	if ( empty( $posts ) ) {
		return new WP_Error( 'post_not_found', 'Post not found.', array('status' => 404));
	}

	return $data;
}

// Single Post Func
function wca_posts( $slug ) {
	$args = [
		'name' => $slug['slug'],
		'post_type' => 'post'
	];

	$query = new WP_Query($args);
	$post = $query->get_posts();

	$data['date'] = $post[0]->post_date;
	$data['title'] = apply_filters('the_title', $post[0]->post_title);
	$data['author'] = get_the_author_meta('display_name', $post[0]->post_author);
	$data['content'] = apply_filters('the_content', $post[0]->post_content);
	$data['excerpt'] = apply_filters('the_excerpt', $post[0]->post_excerpt);
	$data['featured_image']['medium'] = get_the_post_thumbnail_url($post[0]->ID, 'medium');
	$data['featured_image']['large'] = get_the_post_thumbnail_url($post[0]->ID, 'large');
	$data['featured_image']['full'] = get_the_post_thumbnail_url($post[0]->ID, 'full');
	
	if ( empty( $post ) ) {
		return new WP_Error( 'post_not_found', 'Post not found.', array('status' => 404));
	}

	return $data;
}

// Search Posts Func
function wca_search_posts($query) {
	$args = [
		'posts_per_page' => 12,
		'post_type' => 'post',
		'post_status' => 'publish',
		'cat' => $query['cat'],
		's' => $query['search'],
		'offset' => $query['offset']
	];
	
	$query = new WP_Query($args);
	$posts = $query->get_posts();

	$data = [];
	$i = 0;

	foreach($posts as $post) {
		$data[$i]['total'] = $query->found_posts;
		$data[$i]['date'] = $post->post_date;
		$data[$i]['title'] = apply_filters('the_title', $post->post_title);
		$data[$i]['slug'] = $post->post_name;
		$data[$i]['excerpt'] = apply_filters('the_excerpt', $post->post_excerpt);
		$data[$i]['featured_image']['thumbnail'] = get_the_post_thumbnail_url($post->ID, 'thumbnail');
		$data[$i]['featured_image']['medium'] = get_the_post_thumbnail_url($post->ID, 'medium');
		$i++;
	}
	
	if ( empty( $posts ) ) {
		return new WP_Error( 'post_not_found', 'Post not found.', array('status' => 404));
	}

	return $data;
}

// Single Page Func
function wca_pages( $slug ) {
	$args = [
		'name' => $slug['slug'],
		'post_type' => 'page'
	];

	$query = new WP_Query($args);
	$post = $query->get_posts();

	$data['date'] = $post[0]->post_date;
	$data['title'] = apply_filters('the_title', $post[0]->post_title);
	$data['content'] = apply_filters('the_content', $post[0]->post_content);
	
	if ( empty( $post ) ) {
		return new WP_Error( 'page_not_found', 'Page not found.', array('status' => 404));
	}

	return $data;
}

// Add Custom Endpoint
add_action('rest_api_init', function() {
	register_rest_route('wca/v1', 'posts/(?P<cat>\d+)/(?P<perpage>\d+)/(?P<offset>\d+)', array(
		'methods' => 'GET',
		'callback' => 'wca_list_posts',
		// 'args' => array(
		// 	'cat' => array(
		// 		'validate_callback' => is_numeric
		// 	),
		// 	'perpage' => array(
		// 		'validate_callback' => is_numeric
		// 	),
		// 	'offset' => array(
		// 		'validate_callback' => is_numeric
		// 	),
		// ),
	));

	register_rest_route( 'wca/v1', 'posts/(?P<slug>[a-zA-Z0-9-]+)', array(
		'methods' => 'GET',
		'callback' => 'wca_posts',
		// 'args' => array(
		// 	'slug' => array(
		// 		'validate_callback' => is_string
		// 	),
		// ),
	));
	
	register_rest_route( 'wca/v1', 'search/(?P<cat>\d+)/(?P<search>.+)/(?P<offset>\d+)', array(
		'methods' => 'GET',
		'callback' => 'wca_search_posts',
		// 'args' => array(
		// 	'cat' => array(
		// 		'validate_callback' => is_numeric
		// 	),
		// 	'search' => array(
		// 		'validate_callback' => is_string
		// 	),
		// 	'offset' => array(
		// 		'validate_callback' => is_numeric
		// 	),
		// ),
	));
	
	register_rest_route( 'wca/v1', 'pages/(?P<slug>[a-zA-Z0-9-]+)', array(
		'methods' => 'GET',
		'callback' => 'wca_pages',
		// 'args' => array(
		// 	'slug' => array(
		// 		'validate_callback' => is_string
		// 	),
		// ),
	));
});