<?php

// --- Integrasi Tombol Editor TinyMCE ---

/**
 * Mendaftarkan tombol dan plugin JS TinyMCE untuk embed PDF mentah.
 */
function wca_bmkg_add_raw_pdf_button_from_theme()
{
    // Hanya tambahkan tombol untuk pengguna yang bisa mengedit post/page
    if (!current_user_can("edit_posts") && !current_user_can("edit_pages")) {
        return;
    }
    // Hanya tambahkan jika editor visual (TinyMCE) aktif
    if (get_user_option("rich_editing") !== "true") {
        return;
    }

    // Kaitkan fungsi untuk mendaftarkan plugin JS TinyMCE
    // Gunakan nama fungsi callback yang berbeda jika perlu untuk menghindari konflik
    add_filter(
        "mce_external_plugins",
        "wca_bmkg_register_raw_pdf_tinymce_plugin"
    );
    // Kaitkan fungsi untuk menambahkan tombol ke baris editor
    // Gunakan nama fungsi callback yang berbeda jika perlu untuk menghindari konflik
    add_filter("mce_buttons", "wca_bmkg_add_raw_pdf_tinymce_button");
}
// Jalankan fungsi di admin head (atau admin_init)
add_action("admin_head", "wca_bmkg_add_raw_pdf_button_from_theme");

/**
 * Mendaftarkan file JavaScript plugin TinyMCE eksternal.
 *
 * @param array $plugin_array Array plugin eksternal yang sudah ada.
 * @return array Array plugin dengan plugin kita ditambahkan.
 */
function wca_bmkg_register_raw_pdf_tinymce_plugin($plugin_array)
{
    // Path ke file JS di dalam folder plugin Anda
    // Asumsi: file JS bernama 'iframe-pdf.js' ada di dalam folder 'js' plugin
    $js_file_path = plugin_dir_path(__FILE__) . "js/iframe-pdf.js";
    $js_file_url = plugin_dir_url(__FILE__) . "js/iframe-pdf.js";

    // Pastikan file ada sebelum menambahkannya
    if (file_exists($js_file_path)) {
        // Kunci array 'custom_shortcode' HARUS SAMA dengan nama plugin
        // yang didaftarkan di file iframe-pdf.js (baris tinymce.create dan tinymce.PluginManager.add)
        $plugin_array["custom_shortcode"] =
            $js_file_url . "?ver=" . filemtime($js_file_path);
    } else {
        error_log(
            "File JS (iframe-pdf.js) untuk WCA BMKG editor button tidak ditemukan: " .
                $js_file_path
        );
    }
    return $plugin_array;
}

/**
 * Menambahkan kunci tombol kita ke daftar tombol TinyMCE.
 *
 * @param array $buttons Array kunci tombol yang sudah ada.
 * @return array Array tombol dengan kunci tombol kita ditambahkan.
 */
function wca_bmkg_add_raw_pdf_tinymce_button($buttons)
{
    // Kunci tombol 'custom_shortcode' HARUS SAMA dengan nama tombol
    // yang didaftarkan di file iframe-pdf.js (editor.addButton)
    array_push($buttons, "custom_shortcode");
    return $buttons;
}

// --- Akhir Integrasi Tombol Editor ---

?>
