<?php
/*
 * Plugin Name:       WP-BMKG Custom API
 * Plugin URI:        https://github.com/infoBMKG/wca-bmkg-plugin/
 * Description:       WordPress Custom REST API for BMKG Content
 * Version:           1.7
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            Raksaka Indra
 * Author URI:        https://github.com/raksakaindra
 * License:           MIT
 * License URI:       https://www.mit.edu/~amini/LICENSE.md
 * Update URI:        https://github.com/infoBMKG/wca-bmkg-plugin/
 * Text Domain:       wp-bmkg-custom-api
 */

// Exit if accessed directly.
defined("ABSPATH") || exit();

// --- Plugin Updater Class (REVISED for get_plugin_data scope) ---
if (!class_exists("wcaBmkgUpdate")) {
    class wcaBmkgUpdate
    {
        public $plugin_slug;
        public $plugin_basename_file;
        // public $version; // Version utama diambil dinamis di hook 'update'
        public $cache_key;
        public $cache_allowed;
        public $remote_url;

        public function __construct()
        {
            $this->plugin_basename_file = plugin_basename(__FILE__);
            $this->plugin_slug = dirname($this->plugin_basename_file);

            $this->remote_url =
                "https://raw.githubusercontent.com/infoBMKG/wca-bmkg-plugin/main/plugin-update.json";
            $this->cache_key = "wca_bmkg_upd_" . md5($this->remote_url);
            $this->cache_allowed = false; // Aktifkan jika perlu caching response remote

            add_filter("plugins_api", [$this, "info"], 20, 3);
            add_filter("pre_set_site_transient_update_plugins", [
                $this,
                "update",
            ]);
            add_action("upgrader_process_complete", [$this, "purge"], 10, 2);
        }

        public function request()
        {
            $remote = get_transient($this->cache_key);
            if (isset($_GET["force-check"]) && $_GET["force-check"] == 1) {
                $remote = false;
            }

            if (false === $remote || !$this->cache_allowed) {
                $remote_get = wp_remote_get(
                    $this->remote_url . "?cache=" . rand(10, 100),
                    [
                        "timeout" => 10,
                        "headers" => ["Accept" => "application/json"],
                    ]
                );
                if (
                    is_wp_error($remote_get) ||
                    200 !== wp_remote_retrieve_response_code($remote_get) ||
                    empty(wp_remote_retrieve_body($remote_get))
                ) {
                    set_transient(
                        $this->cache_key,
                        [
                            "error" => true,
                            "wp_error" => is_wp_error($remote_get)
                                ? $remote_get->get_error_message()
                                : null,
                        ],
                        MINUTE_IN_SECONDS * 5
                    );
                    return false;
                }
                $remote_body = wp_remote_retrieve_body($remote_get);
                set_transient($this->cache_key, $remote_body, DAY_IN_SECONDS);
            } else {
                $remote_body = $remote;
                $maybe_error = json_decode($remote_body, true);
                if (
                    is_array($maybe_error) &&
                    isset($maybe_error["error"]) &&
                    $maybe_error["error"] === true
                ) {
                    return false; // Cached error
                }
            }
            $remote_data = json_decode($remote_body);
            if (json_last_error() !== JSON_ERROR_NONE) {
                delete_transient($this->cache_key);
                return false;
            }
            return $remote_data;
        }

        function info($res, $action, $args)
        {
            if ("plugin_information" !== $action) {
                return $res;
            }
            if (!isset($args->slug) || $this->plugin_slug !== $args->slug) {
                return $res;
            }
            $remote = $this->request();
            if (
                !$remote ||
                !isset($remote->slug) ||
                $remote->slug !== $this->plugin_slug
            ) {
                return $res;
            }

            $res = new stdClass();
            $res->name = isset($remote->name) ? $remote->name : "";
            $res->slug = $remote->slug;
            $res->version = isset($remote->version) ? $remote->version : "";
            $res->tested = isset($remote->tested) ? $remote->tested : "";
            $res->requires = isset($remote->requires) ? $remote->requires : "";
            $res->author = isset($remote->author) ? $remote->author : "";
            $res->author_profile = isset($remote->author_profile)
                ? $remote->author_profile
                : "";
            $res->download_link = isset($remote->download_url)
                ? $remote->download_url
                : "";
            $res->trunk = $res->download_link;
            $res->requires_php = isset($remote->requires_php)
                ? $remote->requires_php
                : "";
            $res->last_updated = isset($remote->last_updated)
                ? $remote->last_updated
                : "";
            $res->sections = [];
            if (isset($remote->sections->description)) {
                $res->sections["description"] = $remote->sections->description;
            }
            if (isset($remote->sections->installation)) {
                $res->sections["installation"] =
                    $remote->sections->installation;
            }
            if (isset($remote->sections->changelog)) {
                $res->sections["changelog"] = $remote->sections->changelog;
            }
            if (!empty($remote->banners)) {
                $res->banners = [];
                if (isset($remote->banners->low)) {
                    $res->banners["low"] = $remote->banners->low;
                }
                if (isset($remote->banners->high)) {
                    $res->banners["high"] = $remote->banners->high;
                }
            }
            return $res;
        }

        public function update($transient)
        {
            // Pastikan fungsi get_plugin_data tersedia
            if (!function_exists("get_plugin_data")) {
                require_once ABSPATH . "wp-admin/includes/plugin.php";
            }
            if (!function_exists("get_plugin_data")) {
                error_log(
                    "WCA BMKG Updater: get_plugin_data function not found."
                );
                return $transient;
            }

            // Dapatkan data plugin saat ini
            $current_plugin_file_path =
                WP_PLUGIN_DIR . "/" . $this->plugin_basename_file;
            if (!file_exists($current_plugin_file_path)) {
                error_log(
                    "WCA BMKG Updater: Plugin file not found at " .
                        $current_plugin_file_path
                );
                return $transient;
            }
            $current_plugin_data = get_plugin_data($current_plugin_file_path);
            $current_version = isset($current_plugin_data["Version"])
                ? $current_plugin_data["Version"]
                : "0";

            // Pastikan transient valid
            if (!is_object($transient)) {
                $transient = new stdClass();
            }
            if (!isset($transient->response)) {
                $transient->response = [];
            }
            if (!isset($transient->no_update)) {
                $transient->no_update = [];
            }

            $remote = $this->request();

            if (
                $remote &&
                isset($remote->version) &&
                version_compare($current_version, $remote->version, "<") && // Bandingkan versi terinstall vs remote
                version_compare(
                    isset($remote->requires) ? $remote->requires : "0",
                    get_bloginfo("version"),
                    "<="
                ) &&
                version_compare(
                    isset($remote->requires_php) ? $remote->requires_php : "0",
                    PHP_VERSION,
                    "<"
                )
            ) {
                // Update available
                $res = new stdClass();
                $res->slug = $this->plugin_slug;
                $res->plugin = $this->plugin_basename_file;
                $res->new_version = $remote->version;
                $res->tested = isset($remote->tested) ? $remote->tested : "";
                $res->package = isset($remote->download_url)
                    ? $remote->download_url
                    : "";
                $res->url = isset($remote->homepage) ? $remote->homepage : "";
                $transient->response[$res->plugin] = $res;
                if (isset($transient->no_update[$res->plugin])) {
                    unset($transient->no_update[$res->plugin]);
                }
            } else {
                // No update or failed remote check
                if (!isset($transient->response[$this->plugin_basename_file])) {
                    $res = new stdClass();
                    $res->id = $this->plugin_basename_file;
                    $res->slug = $this->plugin_slug;
                    $res->plugin = $this->plugin_basename_file;
                    $res->new_version = $current_version; // Tampilkan versi saat ini
                    $res->url = isset($current_plugin_data["PluginURI"])
                        ? $current_plugin_data["PluginURI"]
                        : "";
                    $res->package = "";
                    $res->icons = [];
                    $res->banners = [];
                    $res->banners_rtl = [];
                    $res->tested = isset($current_plugin_data["Tested Up To"])
                        ? $current_plugin_data["Tested Up To"]
                        : "";
                    $res->requires_php = isset(
                        $current_plugin_data["RequiresPHP"]
                    )
                        ? $current_plugin_data["RequiresPHP"]
                        : "";
                    $res->compatibility = new stdClass();
                    $transient->no_update[$res->plugin] = $res;
                }
            }
            return $transient;
        }

        public function purge($upgrader, $options)
        {
            if (
                !is_array($options) ||
                !isset($options["action"]) ||
                !isset($options["type"])
            ) {
                return;
            }
            if (
                $this->cache_allowed &&
                "update" === $options["action"] &&
                "plugin" === $options["type"] &&
                isset($options["plugins"])
            ) {
                foreach ($options["plugins"] as $plugin_basename) {
                    if ($plugin_basename === $this->plugin_basename_file) {
                        delete_transient($this->cache_key);
                        break;
                    }
                }
            }
        }
    } // End class wcaBmkgUpdate
    new wcaBmkgUpdate();
} // End if class_exists

// --- Helper Functions for REST API Argument Validation (Defined Globally) ---
// (Definisi fungsi wca_validate_... diletakkan di sini, sama seperti versi sebelumnya)
/** Validates if a parameter is numeric and greater than zero. */
function wca_validate_numeric_gt_zero($param)
{
    return is_numeric($param) && $param > 0;
}
/** Validates if a parameter is numeric and greater than or equal to zero. */
function wca_validate_numeric_ge_zero($param)
{
    return is_numeric($param) && $param >= 0;
}
/** Validates if a parameter is a valid slug string. */
function wca_validate_slug($param)
{
    return is_string($param) && preg_match('/^[a-zA-Z0-9-]+$/', $param) === 1;
}
/** Validates if a parameter is a non-empty string after trimming. */
function wca_validate_non_empty_string($param)
{
    return is_string($param) && !empty(trim($param));
}
/** Validates if a category ID exists. */
function wca_validate_category_exists($param)
{
    return is_numeric($param) &&
        $param > 0 &&
        term_exists(absint($param), "category");
}
/** Validates if a tag ID exists. */
function wca_validate_tag_exists($param)
{
    return is_numeric($param) &&
        $param > 0 &&
        term_exists(absint($param), "post_tag");
}
/** Validates the 'perpage' parameter (between 1 and 100). */
function wca_validate_perpage($param)
{
    return is_numeric($param) && $param > 0 && $param <= 100;
}

// --- Original Callback Functions (with minor safety/consistency tweaks) ---

// List Posts Func (Original Structure)
function wca_list_posts($param)
{
    // Parameter tetap $param sesuai route lama
    // Ambil parameter (sudah divalidasi/disanitasi oleh 'args' di register_rest_route)
    $cat_id = isset($param["cat"]) ? $param["cat"] : 0;
    $per_page = isset($param["perpage"]) ? $param["perpage"] : 12; // Default jika tidak ada
    $offset = isset($param["offset"]) ? $param["offset"] : 0;

    $args = [
        "post_type" => "post",
        "post_status" => "publish",
        "cat" => $cat_id,
        "posts_per_page" => $per_page,
        "offset" => $offset,
        "no_found_rows" => false, // Butuh found_posts
    ];

    $query = new WP_Query($args);
    $posts = $query->get_posts();

    // Kembalikan array kosong jika tidak ada post (Sesuai struktur asli)
    if (empty($posts)) {
        // return new WP_Error("post_not_found", "Post not found.", ["status" => 404]); // Alternatif error
        return []; // Return empty array as per original likely behavior
    }

    $data = [];
    $i = 0;
    $total_posts = (int) $query->found_posts; // Ambil total sekali

    foreach ($posts as $post) {
        $post_id = $post->ID;
        $data[$i]["total"] = $total_posts; // Masukkan total ke setiap item
        $data[$i]["date"] = $post->post_date;
        $data[$i]["title"] = html_entity_decode(
            apply_filters("the_title", $post->post_title)
        );
        $data[$i]["slug"] = $post->post_name;
        // Proses excerpt untuk mendapatkan teks bersih
        $raw_excerpt = has_excerpt($post_id)
            ? $post->post_excerpt
            : get_the_excerpt($post_id);
        $filtered_excerpt = apply_filters("the_excerpt", $raw_excerpt);
        $decoded_excerpt = html_entity_decode($filtered_excerpt);
        $plain_excerpt = wp_strip_all_tags($decoded_excerpt);
        $data[$i]["excerpt"] = $plain_excerpt;

        // Ambil featured image (lebih efisien)
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $data[$i]["featured_image"] = [
                "thumbnail" => wp_get_attachment_image_url(
                    $thumbnail_id,
                    "thumbnail"
                ),
                "medium" => wp_get_attachment_image_url(
                    $thumbnail_id,
                    "medium"
                ),
                "large" => wp_get_attachment_image_url($thumbnail_id, "large"),
                "full" => wp_get_attachment_image_url($thumbnail_id, "full"),
            ];
        } else {
            $data[$i]["featured_image"] = null;
        }
        $i++;
    }

    // Kembalikan WP_REST_Response agar header JSON benar
    return new WP_REST_Response($data, 200);
}

// List Posts by Tag Func (Original Structure)
function wca_list_posts_tag($param)
{
    $tag_id = isset($param["tag_id"]) ? $param["tag_id"] : 0;
    $per_page = isset($param["perpage"]) ? $param["perpage"] : 12;
    $offset = isset($param["offset"]) ? $param["offset"] : 0;

    $args = [
        "post_type" => "post",
        "post_status" => "publish",
        "tag_id" => $tag_id,
        "posts_per_page" => $per_page,
        "offset" => $offset,
        "no_found_rows" => false,
    ];

    $query = new WP_Query($args);
    $posts = $query->get_posts();

    if (empty($posts)) {
        return [];
    } // Return empty array

    $data = [];
    $i = 0;
    $total_posts = (int) $query->found_posts;

    foreach ($posts as $post) {
        $post_id = $post->ID;
        $data[$i]["total"] = $total_posts;
        $data[$i]["date"] = $post->post_date;
        $data[$i]["title"] = html_entity_decode(
            apply_filters("the_title", $post->post_title)
        );
        $data[$i]["slug"] = $post->post_name;
        // Proses excerpt untuk mendapatkan teks bersih
        $raw_excerpt = has_excerpt($post_id)
            ? $post->post_excerpt
            : get_the_excerpt($post_id);
        $filtered_excerpt = apply_filters("the_excerpt", $raw_excerpt);
        $decoded_excerpt = html_entity_decode($filtered_excerpt);
        $plain_excerpt = wp_strip_all_tags($decoded_excerpt);
        $data[$i]["excerpt"] = $plain_excerpt;

        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $data[$i]["featured_image"] = [
                "thumbnail" => wp_get_attachment_image_url(
                    $thumbnail_id,
                    "thumbnail"
                ),
                "medium" => wp_get_attachment_image_url(
                    $thumbnail_id,
                    "medium"
                ),
                "large" => wp_get_attachment_image_url($thumbnail_id, "large"),
                "full" => wp_get_attachment_image_url($thumbnail_id, "full"),
            ];
        } else {
            $data[$i]["featured_image"] = null;
        }
        $i++;
    }
    return new WP_REST_Response($data, 200);
}

// Single Post Func (Original Structure + Safety Check)
function wca_posts($slug)
{
    // Parameter $slug adalah array ['slug' => 'value']
    $the_slug = isset($slug["slug"]) ? $slug["slug"] : "";
    if (empty($the_slug)) {
        return new WP_Error("invalid_slug", "Invalid slug provided.", [
            "status" => 400,
        ]);
    }

    $args = [
        "name" => $the_slug,
        "post_type" => "post",
        "post_status" => "publish", // Pastikan hanya publish
        "posts_per_page" => 1,
        "no_found_rows" => true,
    ];

    $query = new WP_Query($args);

    // --- Safety Check DULU ---
    if (!$query->have_posts()) {
        return new WP_Error("post_not_found", "Post not found.", [
            "status" => 404,
        ]);
    }
    // --- END Safety Check ---

    $post = $query->posts[0]; // Sekarang aman akses index 0
    $post_id = $post->ID; // Dapatkan ID

    $data = []; // Inisialisasi array
    $data["date"] = $post->post_date;
    $data["title"] = html_entity_decode(
        apply_filters("the_title", $post->post_title)
    );
    $data["author"] = get_the_author_meta("display_name", $post->post_author);
    $data["content"] = apply_filters("the_content", $post->post_content);
    // Proses excerpt untuk mendapatkan teks bersih
    $raw_excerpt_single = has_excerpt($post_id)
        ? $post->post_excerpt
        : get_the_excerpt($post_id);
    $filtered_excerpt_single = apply_filters(
        "the_excerpt",
        $raw_excerpt_single
    );
    $decoded_excerpt_single = html_entity_decode($filtered_excerpt_single);
    $plain_excerpt_single = wp_strip_all_tags($decoded_excerpt_single);
    $data["excerpt"] = $plain_excerpt_single;

    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) {
        $data["featured_image"] = [
            "medium" => wp_get_attachment_image_url($thumbnail_id, "medium"),
            "large" => wp_get_attachment_image_url($thumbnail_id, "large"),
            "full" => wp_get_attachment_image_url($thumbnail_id, "full"),
        ];
    } else {
        $data["featured_image"] = null;
    }

    return new WP_REST_Response($data, 200);
}

// Search Posts Func (Original Structure)
function wca_search_posts($param)
{
    // Parameter tetap $param
    $cat_id = isset($param["cat"]) ? $param["cat"] : 0;
    $search_term = isset($param["search"]) ? $param["search"] : "";
    $offset = isset($param["offset"]) ? $param["offset"] : 0;
    $posts_per_page = 12; // Default per page

    $args = [
        "posts_per_page" => $posts_per_page,
        "post_type" => "post",
        "post_status" => "publish",
        "cat" => $cat_id,
        "s" => $search_term,
        "offset" => $offset,
        "no_found_rows" => false,
    ];

    $query = new WP_Query($args);
    $posts = $query->get_posts();

    // Kembalikan array kosong jika tidak ada hasil search (Sesuai struktur asli)
    if (empty($posts)) {
        return [];
    }

    $data = [];
    $i = 0;
    $total_posts = (int) $query->found_posts;

    foreach ($posts as $post) {
        $post_id = $post->ID;
        $data[$i]["total"] = $total_posts;
        $data[$i]["date"] = $post->post_date;
        $data[$i]["title"] = html_entity_decode(
            apply_filters("the_title", $post->post_title)
        );
        $data[$i]["slug"] = $post->post_name;
        // Proses excerpt untuk mendapatkan teks bersih
        $raw_excerpt = has_excerpt($post_id)
            ? $post->post_excerpt
            : get_the_excerpt($post_id);
        $filtered_excerpt = apply_filters("the_excerpt", $raw_excerpt);
        $decoded_excerpt = html_entity_decode($filtered_excerpt);
        $plain_excerpt = wp_strip_all_tags($decoded_excerpt);
        $data[$i]["excerpt"] = $plain_excerpt;

        // Hanya thumbnail dan medium sesuai kode asli search
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $data[$i]["featured_image"] = [
                "thumbnail" => wp_get_attachment_image_url(
                    $thumbnail_id,
                    "thumbnail"
                ),
                "medium" => wp_get_attachment_image_url(
                    $thumbnail_id,
                    "medium"
                ),
            ];
        } else {
            $data[$i]["featured_image"] = null;
        }
        $i++;
    }

    return new WP_REST_Response($data, 200);
}

// Single Page Func (Original Structure + Safety Check)
function wca_pages($slug)
{
    // Parameter $slug adalah array ['slug' => 'value']
    $the_slug = isset($slug["slug"]) ? $slug["slug"] : "";
    if (empty($the_slug)) {
        return new WP_Error("invalid_slug", "Invalid slug provided.", [
            "status" => 400,
        ]);
    }

    $args = [
        "name" => $the_slug, // Gunakan page slug
        "post_type" => "page",
        "post_status" => "publish",
        "posts_per_page" => 1,
        "no_found_rows" => true,
    ];

    $query = new WP_Query($args);

    // --- Safety Check DULU ---
    if (!$query->have_posts()) {
        return new WP_Error("page_not_found", "Page not found.", [
            "status" => 404,
        ]);
    }
    // --- END Safety Check ---

    $post = $query->posts[0]; // Sekarang aman

    $data = [];
    $data["date"] = $post->post_date;
    $data["title"] = html_entity_decode(
        apply_filters("the_title", $post->post_title)
    );
    $data["content"] = apply_filters("the_content", $post->post_content);
    // Pages biasanya tidak punya excerpt/featured image by default

    return new WP_REST_Response($data, 200);
}

// --- Add Custom Endpoint (Original URLs + Validation/Sanitization Added) ---
add_action("rest_api_init", function () {
    $namespace = "wca/v1";

    // Endpoint: List posts by category (URL & Callback Asli)
    register_rest_route(
        $namespace,
        "/posts/(?P<cat>\d+)/(?P<perpage>\d+)/(?P<offset>\d+)", // URL Asli
        [
            "methods" => WP_REST_Server::READABLE, // 'GET'
            "callback" => "wca_list_posts", // Callback Asli
            "permission_callback" => "__return_true", // Tambahan: Izin Akses
            "args" => [
                // Tambahan: Validasi & Sanitasi
                "cat" => [
                    "description" => __("Category ID.", "wp-bmkg-custom-api"),
                    "type" => "integer",
                    "required" => true,
                    "validate_callback" => "wca_validate_category_exists",
                    "sanitize_callback" => "absint",
                ],
                "perpage" => [
                    "description" => __(
                        "Number of posts per page.",
                        "wp-bmkg-custom-api"
                    ),
                    "type" => "integer",
                    "required" => true,
                    "validate_callback" => "wca_validate_perpage",
                    "sanitize_callback" => "absint",
                ],
                "offset" => [
                    "description" => __(
                        "Number of posts to offset.",
                        "wp-bmkg-custom-api"
                    ),
                    "type" => "integer",
                    "required" => true,
                    "validate_callback" => "wca_validate_numeric_ge_zero",
                    "sanitize_callback" => "absint",
                ],
            ],
        ]
    );

    // Endpoint: List posts by tag (URL & Callback Asli)
    register_rest_route(
        $namespace,
        "/posts-tag/(?P<tag_id>\d+)/(?P<perpage>\d+)/(?P<offset>\d+)", // URL Asli
        [
            "methods" => WP_REST_Server::READABLE,
            "callback" => "wca_list_posts_tag", // Callback Asli
            "permission_callback" => "__return_true",
            "args" => [
                "tag_id" => [
                    "description" => __("Tag ID.", "wp-bmkg-custom-api"),
                    "type" => "integer",
                    "required" => true,
                    "validate_callback" => "wca_validate_tag_exists",
                    "sanitize_callback" => "absint",
                ],
                "perpage" => [
                    "description" => __(
                        "Number of posts per page.",
                        "wp-bmkg-custom-api"
                    ),
                    "type" => "integer",
                    "required" => true,
                    "validate_callback" => "wca_validate_perpage",
                    "sanitize_callback" => "absint",
                ],
                "offset" => [
                    "description" => __(
                        "Number of posts to offset.",
                        "wp-bmkg-custom-api"
                    ),
                    "type" => "integer",
                    "required" => true,
                    "validate_callback" => "wca_validate_numeric_ge_zero",
                    "sanitize_callback" => "absint",
                ],
            ],
        ]
    );

    // Endpoint: Get single post by slug (URL & Callback Asli)
    register_rest_route($namespace, "/posts/(?P<slug>[a-zA-Z0-9-]+)", [
        // URL Asli
        "methods" => WP_REST_Server::READABLE,
        "callback" => "wca_posts", // Callback Asli
        "permission_callback" => "__return_true",
        "args" => [
            "slug" => [
                "description" => __("The post slug.", "wp-bmkg-custom-api"),
                "type" => "string",
                "required" => true,
                "validate_callback" => "wca_validate_slug",
                "sanitize_callback" => "sanitize_key",
            ],
        ],
    ]);

    // Endpoint: Search posts (URL & Callback Asli)
    register_rest_route(
        $namespace,
        "/search/(?P<cat>\d+)/(?P<search>.+)/(?P<offset>\d+)", // URL Asli
        [
            "methods" => WP_REST_Server::READABLE,
            "callback" => "wca_search_posts", // Callback Asli
            "permission_callback" => "__return_true",
            "args" => [
                "cat" => [
                    "description" => __(
                        "Category ID to limit search.",
                        "wp-bmkg-custom-api"
                    ),
                    "type" => "integer",
                    "required" => true,
                    "validate_callback" => "wca_validate_category_exists",
                    "sanitize_callback" => "absint",
                ],
                "search" => [
                    "description" => __("Search query.", "wp-bmkg-custom-api"),
                    "type" => "string",
                    "required" => true,
                    "validate_callback" => "wca_validate_non_empty_string",
                    "sanitize_callback" => function ($param) {
                        return sanitize_text_field(urldecode($param));
                    },
                ],
                "offset" => [
                    "description" => __(
                        "Number of posts to offset.",
                        "wp-bmkg-custom-api"
                    ),
                    "type" => "integer",
                    "required" => true,
                    "validate_callback" => "wca_validate_numeric_ge_zero",
                    "sanitize_callback" => "absint",
                ],
            ],
        ]
    );

    // Endpoint: Get single page by slug (URL & Callback Asli)
    register_rest_route($namespace, "/pages/(?P<slug>[a-zA-Z0-9-]+)", [
        // URL Asli
        "methods" => WP_REST_Server::READABLE,
        "callback" => "wca_pages", // Callback Asli
        "permission_callback" => "__return_true",
        "args" => [
            "slug" => [
                "description" => __("The page slug.", "wp-bmkg-custom-api"),
                "type" => "string",
                "required" => true,
                "validate_callback" => "wca_validate_slug",
                "sanitize_callback" => "sanitize_key",
            ],
        ],
    ]);
}); // End add_action('rest_api_init')

// Optional: Add text domain loading for translation
// add_action('plugins_loaded', function() {
//    load_plugin_textdomain('wp-bmkg-custom-api', false, dirname(plugin_basename(__FILE__)) . '/languages/');
// });

// Add PDF iframe button in editor
require_once plugin_dir_path(__FILE__) . "includes/iframe-pdf-button.php";

// Add shortcode grid item
require_once plugin_dir_path(__FILE__) . "includes/shortcode-grid-item.php";

?>
