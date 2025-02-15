# WP-BMKG Custom API (`wca-bmkg-plugin`)

Plugin WordPress untuk custom REST API endopoint untuk post, page, dan search. Plugin ini melakukan custom endpoint URL pada response REST API berikut:

1. List post
2. Single post
3. Search post
4. Single page

## URL Endpoint

1. List post: `{site}/wp-json/wca/v1/posts/{category}/{post_per_page}/{offset}`
2. Single post: `{site}/wp-json/wca/v1/posts/{slug}`
3. Search post: `{site}/wp-json/wca/v1/search/{category}/{search}/{offset}`
4. Single page: `{site}/wp-json/wca/v1/pages/{slug}`

Keterangan:

- `category: int`
- `post_per_page: int`
- `offiset: int`
- `slug: string [a-zA-Z0-9-]`
- `search: string`

## Referensi

https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/

## Changelog

- `v1.0`: 12 Mei 2024
- `v1.1`: 22 Mei 2024
- `v1.2`: 19 Juni 2024
- `v1.3`: 30 Juli 2024
- `v1.4`: 30 Juli 2024
