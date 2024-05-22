<?php
/*
 * Plugin Name:       WP-BMKG Custom API
 * Plugin URI:        https://github.com/infoBMKG/wca-bmkg-plugin/
 * Description:       WordPress Custom REST API for BMKG Content
 * Version:           1.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Raksaka Indra
 * Author URI:        https://github.com/raksakaindra
 * License:           MIT
 * License URI:       https://www.mit.edu/~amini/LICENSE.md
 * Update URI:        https://github.com/infoBMKG/wca-bmkg-plugin/
 */

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
		'args' => array(
			'cat' => array(
				'validate_callback' => is_numeric
			),
			'perpage' => array(
				'validate_callback' => is_numeric
			),
			'offset' => array(
				'validate_callback' => is_numeric
			),
		),
	));

	register_rest_route( 'wca/v1', 'posts/(?P<slug>[a-zA-Z0-9-]+)', array(
		'methods' => 'GET',
		'callback' => 'wca_posts',
// 		'args' => array(
// 			'slug' => array(
// 				'validate_callback' => is_string
// 			),
// 		),
	));
	
	register_rest_route( 'wca/v1', 'search/(?P<cat>\d+)/(?P<search>.+)/(?P<offset>\d+)', array(
		'methods' => 'GET',
		'callback' => 'wca_search_posts',
		'args' => array(
			'cat' => array(
				'validate_callback' => is_numeric
			),
// 			'search' => array(
// 				'validate_callback' => is_string
// 			),
			'offset' => array(
				'validate_callback' => is_numeric
			),
		),
	));
	
	register_rest_route( 'wca/v1', 'pages/(?P<slug>[a-zA-Z0-9-]+)', array(
		'methods' => 'GET',
		'callback' => 'wca_pages',
// 		'args' => array(
// 			'slug' => array(
// 				'validate_callback' => is_string
// 			),
// 		),
	));
});
