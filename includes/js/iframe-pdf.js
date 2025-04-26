(function () {
  // Membuat plugin TinyMCE untuk tombol embed PDF
  tinymce.create("tinymce.plugins.custom_shortcode", {
    init: function (editor) {
      // Menambahkan tombol ke editor
      editor.addButton("custom_shortcode", {
        text: "PDF",
        icon: "wp_page",
        tooltip: "Add PDF iframe (Raw HTML)",
        onclick: function () {
          var selectedText = editor.selection.getContent({ format: "text" });

          if (selectedText && selectedText.trim() !== "") {
            selectedText = selectedText.trim();

            var base_url = "https://content.bmkg.go.id/wp-content/uploads/";
            var pdf_url = base_url + selectedText;
            var viewer_url =
              "https://docs.google.com/viewer?url=" +
              pdf_url +
              "&embedded=true";

            var html_output =
              '<iframe src="' +
              viewer_url +
              '" class="pdf"></iframe><br class="hidden" />- <em>Klik <a href="' +
              pdf_url +
              '" target="_blank" rel="noopener noreferrer">tautan ini</a> jika PDF di atas tidak muncul</em>.';

            editor.selection.setContent(html_output);
          } else {
            alert("Pilih teks (nama file PDF) terlebih dahulu!");
          }
        },
      });
    },
  });

  // Mendaftarkan plugin ke TinyMCE
  tinymce.PluginManager.add(
    "custom_shortcode",
    tinymce.plugins.custom_shortcode,
  );
})();
