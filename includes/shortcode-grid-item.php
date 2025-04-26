<?php
// Exit if accessed directly.
defined("ABSPATH") || exit();

/**
 * Shortcode [bmkg_grid_item]...[/bmkg_grid_item] (Enclosing)
 * Menampilkan item grid 1/3 gambar + 2/3 teks. Link opsional.
 *
 * @param array $atts Atribut shortcode.
 * @param string|null $content Konten deskripsi (wajib).
 * @return string HTML output.
 */
function bmkg_grid_item_shortcode_handler($atts, $content = null)
{
    // Atribut default
    $attributes = shortcode_atts(
        [
            "image_src" => "",
            "image_alt" => "",
            "title" => "",
            "link_url" => "",
            "link_text" => "Selengkapnya", // Default link text
        ],
        $atts,
        "bmkg_grid_item"
    );

    // Validasi input wajib
    if (
        empty($attributes["image_src"]) ||
        empty($attributes["title"]) ||
        is_null($content) ||
        trim($content) === ""
    ) {
        return ""; // Return empty if required data missing
    }

    // Sanitasi atribut
    $image_src = esc_url($attributes["image_src"]);
    $image_alt = !empty(trim($attributes["image_alt"]))
        ? esc_attr(trim($attributes["image_alt"]))
        : esc_attr($attributes["title"]); // Fallback alt ke title
    $title = esc_html($attributes["title"]);

    // Proses konten deskripsi (trim, shortcode, paragraf otomatis)
    $processed_content = wpautop(do_shortcode(trim($content)));

    // Siapkan HTML untuk link (jika ada)
    $link_html = "";
    if (!empty($attributes["link_url"]) && $attributes["link_url"] !== "#") {
        $link_url = esc_url($attributes["link_url"]);
        $link_text = esc_html($attributes["link_text"]);
        // Rakit HTML link
        $link_html = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex justify-start items-center text-blue-primary text-base font-medium hover:underline">
                %s
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon" class="w-4 h-4 stoke-2 ml-2"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"></path></svg>
            </a>',
            $link_url,
            $link_text
        );
    }

    // Rakit HTML output final
    $output = <<<HTML
<div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-3 gap-0 md:gap-8 mb-6 md:mb-8">
    <div class="md:col-span-1 flex justify-center md:justify-start items-start">
        <img class="h-auto max-w-full rounded-lg object-cover" src="{$image_src}" alt="{$image_alt}" />
    </div>
    <div class="md:col-span-2">
      <h3 class="text-lg xl:text-2xl font-bold mb-4">{$title}</h3>
      <div>{$processed_content}</div>
      {$link_html}
    </div>
</div>
HTML;

    return $output;
}

// Daftarkan shortcode
add_shortcode("bmkg_grid_item", "bmkg_grid_item_shortcode_handler");

?>
