// import * as pdfjsLib from "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.mjs";
// globalThis.pdfjsLib = pdfjsLib;
// import "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.worker.mjs";
// import * as pdfjsViewer from "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf_viewer.mjs";

// Use jQuery to wait until the DOM is ready.
jQuery(document).ready(function ($) {
  // Retrieve the PDF URL and default scale from your container.
  const init = function () {
    var container = $(".kv-pdf-viewer");
    if (!container.length) {
      console.error("No .kv-pdf-viewer container found.");
      return;
    }
    var pdfUrl = container.data("pdf-url");
    var defaultScale = parseFloat(container.data("scale") || "1.0");

    // Set the workerSrc for PDF.js (if needed).
    pdfjsLib.GlobalWorkerOptions.workerSrc =
      "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.worker.mjs";

    // Define optional CMAP parameters.
    var CMAP_URL = "https://unpkg.com/browse/pdfjs-dist@4.10.38/cmaps/";
    var CMAP_PACKED = true;

    // Create an event bus.
    var eventBus = new pdfjsViewer.EventBus();

    // Set up the link service and find controller.
    var pdfLinkService = new pdfjsViewer.PDFLinkService({ eventBus: eventBus });
    var pdfFindController = new pdfjsViewer.PDFFindController({
      eventBus: eventBus,
      linkService: pdfLinkService,
    });

    // Get the container element for the viewer.
    var containerEl = document.getElementById("viewerContainer");
    if (!containerEl) {
      console.error("No element with id 'viewerContainer' found.");
      return;
    }

    // Instantiate the PDFSinglePageViewer (or PDFViewer for continuous view).
    var pdfSinglePageViewer = new pdfjsViewer.PDFSinglePageViewer({
      container: containerEl,
      eventBus: eventBus,
      linkService: pdfLinkService,
      findController: pdfFindController,
    });

    // Wire the viewer with the link service.
    pdfLinkService.setViewer(pdfSinglePageViewer);

    // When pages are initialized, set the initial scale.
    eventBus.on("pagesinit", function () {
      pdfSinglePageViewer.currentScaleValue = defaultScale;
      $("#page-num").text(pdfSinglePageViewer.currentPageNumber);
    });

    // Navigation event handlers.
    $("#prev-page").on("click", function () {
      if (pdfSinglePageViewer.currentPageNumber > 1) {
        pdfSinglePageViewer.currentPageNumber--;
        $("#page-num").text(pdfSinglePageViewer.currentPageNumber);
      }
    });

    $("#next-page").on("click", function () {
      if (
        pdfSinglePageViewer.pdfDocument &&
        pdfSinglePageViewer.currentPageNumber <
          pdfSinglePageViewer.pdfDocument.numPages
      ) {
        pdfSinglePageViewer.currentPageNumber++;
        $("#page-num").text(pdfSinglePageViewer.currentPageNumber);
      }
    });

    // Load the PDF document.
    pdfjsLib
      .getDocument({
        url: pdfUrl,
        cMapUrl: CMAP_URL,
        cMapPacked: CMAP_PACKED,
      })
      .promise.then(function (pdfDocument) {
        pdfSinglePageViewer.setDocument(pdfDocument);
        pdfLinkService.setDocument(pdfDocument, null);
        $("#page-count").text(pdfDocument.numPages);
      })
      .catch(function (error) {
        console.error("Error loading PDF:", error);
      });
  };
  document.addEventListener("DOMContentLoaded", init);
});
