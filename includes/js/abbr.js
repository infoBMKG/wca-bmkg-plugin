(function () {
  tinymce.PluginManager.add("bmkgAbbrPlugin", function (editor, url) {
    editor.addButton("bmkg_abbr_button", {
      text: "Istilah",
      tooltip: "Sisipkan Istilah",
      icon: "help",
      onclick: function () {
        var selectedContent = editor.selection.getContent({ format: "text" });

        if (!selectedContent || selectedContent.trim() === "") {
          alert("Silakan blok teks terlebih dahulu untuk dijadikan abbr.");
          return;
        }

        var title = prompt("Masukkan teks tooltip untuk istilah:", "");
        if (title === null || title.trim() === "") {
          if (title !== null) alert("Tooltip wajib diisi.");
          return;
        }

        var shortcode =
          '[abbr title="' +
          title.trim().replace(/"/g, '\\"') +
          '"]' +
          selectedContent +
          "[/abbr]";
        editor.execCommand("mceInsertContent", false, shortcode);
      },
    });
  });
})();
