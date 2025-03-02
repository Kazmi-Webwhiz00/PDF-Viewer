jQuery(document).ready(function ($) {
  var mediaUploader;
  $("#kv_meta_tags").select2({
    tags: true, // Allow users to create new tags
    tokenSeparators: [","], // Use comma as separator for new tags
    placeholder: "Enter meta tags", // Placeholder text for the field
    width: "resolve", // Ensure proper width handling
  });
  // Open media uploader when clicking the "Upload PDF" button.
  $("#kv_pdf_upload_container").on(
    "click",
    "#kv_upload_pdf_button",
    function (e) {
      e.preventDefault();
      // If an instance already exists, open it.
      if (mediaUploader) {
        mediaUploader.open();
        return;
      }

      // Create a new media uploader instance.
      mediaUploader = wp.media({
        title: kv_pdf_upload_data.title,
        button: {
          text: kv_pdf_upload_data.uploadedText,
        },
        multiple: false,
      });
      let pdfFile = {};
      // When a file is selected, update the preview and hidden input.
      mediaUploader.on("select", function () {
        var attachment = mediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();
        if (attachment.mime === "application/pdf") {
          console.log("::attachment", attachment.url);
          // Update the hidden input with the new PDF URL.
          var pdfDocument = {
            url: attachment.url,
            info: {
              title: attachment.title || "Untitled PDF",
              // You can add additional metadata here if needed.
            },
            getDownloadInfo: function () {
              return Promise.reject(
                new Error("getDownloadInfo not available from attachment")
              );
            },
            getPageLayout: function () {
              return Promise.reject(
                new Error("getPageLayout not available from attachment")
              );
            },
            getOpenAction: function () {
              return Promise.reject(
                new Error("getOpenAction not available from attachment")
              );
            },
          };
          pdfFile = JSON.stringify(pdfDocument);
          $("#kv_pdf_file").val(pdfFile);

          // Build the new preview HTML that includes the Upload PDF button.
          var previewHtml =
            '<p><button type="button" class="button" id="kv_upload_pdf_button">' +
            kv_pdf_upload_data.uploadedText +
            "</button></p>";
          previewHtml +=
            '<iframe src="' +
            pdfDocument.url +
            '" width="100%" height="400"></iframe>';
          previewHtml +=
            '<p><input type="hidden" id="kv_pdf_file" name="kv_pdf_file" value=' +
            pdfFile +
            " /></p>";
          // Replace the container's HTML with the updated preview.
          $("#kv_pdf_upload_container").html(previewHtml);

          // Reset mediaUploader so a new instance can be created next time.
          mediaUploader = null;
        } else {
          alert("Please select a valid PDF file.");
        }
      });

      mediaUploader.open();
    }
  );

  // Loop through each canvas element within our PDF viewer container.
  // $(".kv-pdf-viewer > canvas").each(function () {
  //   alert("Hello");
  //   var canvas = this;
  //   var $canvas = $(canvas);

  //   // Retrieve the PDF URL and scale from data attributes.
  //   var pdfUrl = $canvas.data("pdf-url");
  //   console.log("::", pdfUrl);
  //   var scale = parseFloat($canvas.data("scale")) || 1.5;

  //   var context = canvas.getContext("2d");
  //   var outputScale = window.devicePixelRatio || 1;

  //   // Use PDF.js to load the PDF.
  //   pdfjsLib
  //     .getDocument(pdfUrl)
  //     .promise.then(function (pdf) {
  //       // Get the first page.
  //       pdf.getPage(1).then(function (page) {
  //         var viewport = page.getViewport({ scale: scale });

  //         // Adjust for HiDPI displays.
  //         canvas.width = Math.floor(viewport.width * outputScale);
  //         canvas.height = Math.floor(viewport.height * outputScale);
  //         canvas.style.width = Math.floor(viewport.width) + "px";
  //         canvas.style.height = Math.floor(viewport.height) + "px";

  //         var transform =
  //           outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null;

  //         var renderContext = {
  //           canvasContext: context,
  //           viewport: viewport,
  //           transform: transform,
  //         };

  //         page.render(renderContext).promise.then(function () {
  //           console.log("PDF page rendered on canvas.");
  //         });
  //       });
  //     })
  //     .catch(function (error) {
  //       console.error("Error loading PDF: ", error);
  //     });
  // });
});
