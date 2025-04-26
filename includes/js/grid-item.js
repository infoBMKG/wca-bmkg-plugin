(function () {
  // Daftarkan plugin TinyMCE: 'bmkgGridItemPlugin'
  tinymce.PluginManager.add("bmkgGridItemPlugin", function (editor, url) {
    // Tambahkan tombol: 'bmkg_grid_item_button'
    editor.addButton("bmkg_grid_item_button", {
      text: "Grid",
      tooltip: "Sisipkan Grid Item",
      icon: "wp_page",
      onclick: function () {
        // Ambil konten/deskripsi yang dipilih
        var selectedContent = editor.selection.getContent({ format: "html" });

        if (!selectedContent || selectedContent.trim() === "") {
          alert(
            "Silakan pilih teks yang akan dijadikan deskripsi terlebih dahulu.",
          );
          return;
        }

        // Minta atribut via prompt
        var imageUrl = prompt("URL Gambar (Wajib):");
        if (imageUrl === null || imageUrl.trim() === "") {
          if (imageUrl !== null) alert("URL Gambar wajib diisi!");
          return;
        }

        var title = prompt("Judul (Wajib):");
        if (title === null || title.trim() === "") {
          if (title !== null) alert("Judul wajib diisi!");
          return;
        }

        var altText = prompt("Teks Alt Gambar (Opsional):", "");
        var linkUrl = prompt(
          "URL Link (Opsional, kosongkan jika tidak ada link):",
          "",
        );
        var linkText = "Selengkapnya"; // Default

        if (linkUrl && linkUrl.trim() !== "" && linkUrl !== "#") {
          var customLinkText = prompt(
            "Teks Link (Opsional, default: Selengkapnya):",
            linkText,
          );
          linkText =
            customLinkText && customLinkText.trim() !== ""
              ? customLinkText.trim()
              : linkText;
        } else {
          linkUrl = ""; // Pastikan kosong jika tidak valid
        }

        // Rakit string atribut
        var attsString =
          ' image_src="' + imageUrl.trim() + '" title="' + title.trim() + '"';
        if (altText && altText.trim() !== "") {
          attsString += ' image_alt="' + altText.trim() + '"';
        }
        if (linkUrl) {
          attsString += ' link_url="' + linkUrl.trim() + '"';
          attsString += ' link_text="' + linkText + '"';
        }

        // Buat shortcode lengkap
        var shortcode =
          "[bmkg_grid_item" +
          attsString +
          "]" +
          selectedContent +
          "[/bmkg_grid_item]";

        // Sisipkan shortcode
        editor.execCommand("mceInsertContent", false, shortcode);
      },
    });
  });
})();
