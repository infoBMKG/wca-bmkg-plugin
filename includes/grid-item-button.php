<?php
// Exit if accessed directly.
defined("ABSPATH") || exit();

// --- Integrasi Tombol Editor TinyMCE untuk Shortcode Grid Item ---

/**
 * Mendaftarkan tombol "Grid Item" ke editor TinyMCE.
 */
function wca_bmkg_add_grid_item_editor_button()
{
    if (!current_user_can("edit_posts") && !current_user_can("edit_pages")) {
        return;
    }
    if (get_user_option("rich_editing") !== "true") {
        return;
    }

    add_filter(
        "mce_external_plugins",
        "wca_bmkg_register_grid_item_tinymce_plugin"
    );
    add_filter("mce_buttons", "wca_bmkg_add_grid_item_tinymce_button");
}
add_action("admin_head", "wca_bmkg_add_grid_item_editor_button");

/**
 * Mendaftarkan file JS untuk plugin TinyMCE Grid Item.
 * @param array $plugin_array Array plugin eksternal.
 * @return array Array plugin yang diperbarui.
 */
function wca_bmkg_register_grid_item_tinymce_plugin($plugin_array)
{
    $js_file_path = plugin_dir_path(__FILE__) . "js/grid-item.js"; // Asumsi file ini ada di includes/, maka JS ada di ../js/
    $js_file_url = plugin_dir_url(__FILE__) . "js/grid-item.js"; // Asumsi file ini ada di includes/, maka JS ada di ../js/

    // Cek ulang path jika file ini TIDAK di dalam folder includes
    // Jika file ini ada di root plugin, path JS adalah: 'js/grid-item.js'

    if (file_exists($js_file_path)) {
        // Kunci: bmkgGridItemPlugin
        $plugin_array["bmkgGridItemPlugin"] =
            $js_file_url . "?ver=" . filemtime($js_file_path);
    } else {
        error_log(
            "File JS (grid-item-button.js) untuk WCA BMKG tidak ditemukan: " .
                $js_file_path
        );
    }
    return $plugin_array;
}

/**
 * Menambahkan KUNCI tombol BARU ke daftar tombol TinyMCE.
 * @param array $buttons Array tombol.
 * @return array Array tombol yang diperbarui.
 */
function wca_bmkg_add_grid_item_tinymce_button($buttons)
{
    // Kunci: bmkg_grid_item_button
    array_push($buttons, "bmkg_grid_item_button");
    return $buttons;
}

// --- Akhir Integrasi Tombol Editor Grid Item ---

?>
