# WP-BMKG Custom API (`wca-bmkg-plugin`)

Plugin WordPress untuk custom REST API endopoint untuk post, page, dan search. Plugin ini melakukan custom endpoint URL pada response REST API berikut:

1. List post cat
2. List post tag
3. Single post
4. Search post
5. Single page

## URL Endpoint

1. List post cat: `{site}/wp-json/wca/v1/posts/{category}/{post_per_page}/{offset}`
2. List post tag: `{site}/wp-json/wca/v1/posts-tag/{tag_id}/{post_per_page}/{offset}`
3. Single post: `{site}/wp-json/wca/v1/posts/{slug}`
4. Search post: `{site}/wp-json/wca/v1/search/{category}/{search}/{offset}`
5. Single page: `{site}/wp-json/wca/v1/pages/{slug}`

Keterangan:

- `category: int`
- `tag_id: int`
- `post_per_page: int`
- `offiset: int`
- `slug: string [a-zA-Z0-9-]`
- `search: string`

## Referensi

https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/

## Shortcode

Plugin ini juga menambahkan shortcode di editor classic TinyMCE:

1. PDF HTML iframe

```html
<iframe class="pdf" src="https://docs.google.com/viewer?url=https://content.bmkg.go.id/wp-content/uploads/prakiraan_cuaca_mingguan-8.pdf&amp;embedded=true"></iframe><br class="hidden" />- <em>Klik <a href="https://content.bmkg.go.id/wp-content/uploads/prakiraan_cuaca_mingguan-8.pdf" target="_blank" rel="noopener noreferrer">tautan ini</a> jika PDF di atas tidak muncul</em>.
```

2. HTML grid item

```html
<!-- Shortcode lengkap -->
[bmkg_grid_item image_src="https://content.bmkg.go.id/wp-content/uploads/Prospek-cuaca-mingguan-A.jpg" title="Grid Item" link_url="https://content.bmkg.go.id/wp-content/uploads/Prospek-cuaca-mingguan-A.jpg" link_text="Selengkapnya"]Ini adalah paragraf yang panjang.[/bmkg_grid_item]
```

```html
<!-- Shortcode tanpa link -->
[bmkg_grid_item image_src="https://content.bmkg.go.id/wp-content/uploads/Prospek-cuaca-mingguan-A.jpg" title="Grid Item"]Ini adalah paragraf yang panjang.[/bmkg_grid_item]
```

## Changelog

- `v1.0`: 12 Mei 2024
- `v1.1`: 22 Mei 2024
- `v1.2`: 19 Juni 2024
- `v1.3`: 30 Juli 2024
- `v1.4`: 30 Juli 2024
- `v1.5`: 16 Desember 2024
- `v1.6`: 11 April 2025
- `v1.7`: 19 April 2025
- `v1.8`: 26 April 2025