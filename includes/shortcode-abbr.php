<?php
// Exit if accessed directly
defined("ABSPATH") || exit();

/**
 * Shortcode: [abbr title="Tooltip"]Teks[/abbr]
 */
function bmkg_abbr_shortcode_handler($atts, $content = null)
{
    $atts = shortcode_atts(
        [
            "title" => "",
        ],
        $atts,
        "abbr"
    );

    if (
        is_null($content) ||
        trim($content) === "" ||
        trim($atts["title"]) === ""
    ) {
        return ""; // Tidak tampil jika tidak ada konten atau title
    }

    $output =
        '
    <span class="inline-flex items-center space-x-1">
      <abbr 
        title="' .
        esc_attr($atts["title"]) .
        '"
        class="cursor-help decoration-dotted text-blue-primary transition-colors"
      >
        ' .
        do_shortcode($content) .
        '
      </abbr>
      <svg xmlns="http://www.w3.org/2000/svg" 
           fill="none" 
           viewBox="0 0 24 24" 
           stroke-width="1.5" 
           stroke="currentColor" 
           class="size-5 text-blue-primary">
        <path stroke-linecap="round" 
              stroke-linejoin="round" 
              d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
      </svg>
    </span>';

    return $output;
}

add_shortcode("abbr", "bmkg_abbr_shortcode_handler");
