(function () {
  // Nama plugin 'custom_shortcode' harus cocok dengan kunci di PHP mce_external_plugins
  tinymce.create("tinymce.plugins.custom_shortcode", {
    init: function (editor) {
      // Nama tombol 'custom_shortcode' harus cocok dengan kunci di PHP mce_buttons
      editor.addButton("custom_shortcode", {
        text: "PDF",
        icon: "wp_page", // Atau ikon lain yang sesuai
        tooltip: "Add PDF iframe", // Tooltip diubah sedikit
        onclick: function () {
          // Ambil teks yang dipilih
          var selectedText = editor.selection.getContent({ format: "text" });

          // Pastikan ada teks dipilih dan tidak hanya spasi
          if (selectedText && selectedText.trim() !== "") {
            // Hapus spasi ekstra di awal/akhir nama file
            selectedText = selectedText.trim();

            // Konstruksi URL (seperti kode asli Anda)
            var base_url = "https://content.bmkg.go.id/wp-content/uploads/";
            var pdf_url = base_url + selectedText;
            // Gunakan encodeURIComponent saat menyematkan URL di dalam parameter URL lain
            var viewer_url =
              "https://docs.google.com/viewer?url=" +
              encodeURIComponent(pdf_url) +
              "&embedded=true";

            // Konstruksi HTML mentah (seperti kode asli Anda)
            var html_output =
              '<iframe src="' +
              viewer_url +
              '" class="pdf"></iframe><br class="hidden" />- <em>Klik <a href="' +
              pdf_url +
              '" target="_blank" rel="noopener noreferrer">tautan ini</a> jika PDF di atas tidak muncul</em>.';
            // Ditambahkan rel="noopener noreferrer" untuk keamanan

            // Ganti teks yang dipilih dengan HTML mentah
            editor.selection.setContent(html_output);
          } else {
            // Beri tahu pengguna jika tidak ada teks yang dipilih
            alert("Pilih teks (nama file PDF) terlebih dahulu!");
          }
        },
      });
    },
  });

  // Mendaftarkan plugin ke TinyMCE
  // Nama plugin 'custom_shortcode' harus cocok dengan kunci di PHP mce_external_plugins
  tinymce.PluginManager.add(
    "custom_shortcode",
    tinymce.plugins.custom_shortcode,
  );
})();
