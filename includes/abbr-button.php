<?php
// Add abbr button to TinyMCE
function wca_bmkg_add_abbr_editor_button()
{
    if (!current_user_can("edit_posts") && !current_user_can("edit_pages")) {
        return;
    }
    if (get_user_option("rich_editing") !== "true") {
        return;
    }
    add_filter("mce_external_plugins", "wca_bmkg_register_abbr_tinymce_plugin");
    add_filter("mce_buttons", "wca_bmkg_add_abbr_tinymce_button");
}
add_action("admin_head", "wca_bmkg_add_abbr_editor_button");

function wca_bmkg_register_abbr_tinymce_plugin($plugin_array)
{
    $js_file_path = plugin_dir_path(__FILE__) . "js/abbr.js";
    $js_file_url = plugin_dir_url(__FILE__) . "js/abbr.js";
    if (file_exists($js_file_path)) {
        $plugin_array["bmkgAbbrPlugin"] =
            $js_file_url . "?ver=" . filemtime($js_file_path);
    } else {
        error_log("File JS abbr.js tidak ditemukan: " . $js_file_path);
    }
    return $plugin_array;
}

function wca_bmkg_add_abbr_tinymce_button($buttons)
{
    array_push($buttons, "bmkg_abbr_button");
    return $buttons;
}

?>
