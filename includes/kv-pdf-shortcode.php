<?php

/**
 * Usage: [kv_pdf_viewer]
 */

function kv_pdf_viewer_shortcode() {
    global $post;

    // Ensure a valid PDF Viewer post
    if ( !isset($post->ID) || get_post_type($post->ID) !== 'pdf_viewer' ) {
        return '<p>' . __('Invalid PDF Viewer post.', 'kv-pdf-viewer') . '</p>';
    }

    $post_id = $post->ID;
    $container_id = 'kv_pdf_viewer_' . $post_id;
    
    // Retrieve the existing PDF document from post meta.
// Retrieve the stored value to verify that it was saved correctly

$pdf_document = get_post_meta( $post->ID, '_kv_pdf_file', true );
$pdf_file = str_replace('"', "'", $pdf_document);
// error_log("PDF Document6 (decoded): " . print_r($pdf_file, true));

// error_log("PDF Document JSON Decode Error: " . print_r($pdf_document, true));

// If no PDF data is stored, return a message.
if ( empty( $pdf_document ) ) {
    error_log("PDF Document: " . print_r($pdf_document, true));
    return '<p>No PDF uploaded.</p>';
}

// error_log("PDF Document1 (decoded): " . print_r($pdf_data, true));

// Check if JSON decoding was successful
if ( json_last_error() !== JSON_ERROR_NONE ) {
    error_log("PDF Document JSON Decode Error: " . json_last_error_msg());
    return '<p>Error reading PDF data.</p>';
}

$pdf_data = json_decode($pdf_document, true);
error_log("PDF Document1 (decoded): " . print_r($pdf_data, true));

// Check if the URL exists in the decoded data
if ( empty($pdf_data['url']) ) {
    return '<p>' . __('No PDF available for this viewer.', 'kv-pdf-viewer') . '</p>';
}

    // Path to the official PDF.js "viewer.html" in your plugin includes/viewer/web/viewer.html
    $viewer_html_path = plugin_dir_path(__FILE__) . '/viewer/web/viewer.html';
    if ( ! file_exists($viewer_html_path) ) {
        return '<p>Error: viewer.html not found in plugin.</p>';
    }

    // 1) Read the viewer.html
    ob_start();
    include $viewer_html_path;
    $viewer_html = ob_get_clean();

    // 2) Replace references to local CSS/JS with plugin_dir_url
    //    This example shows some references. You must do this for *all* references in viewer.html:
    $plugin_viewer_url = plugin_dir_url(__FILE__) . '/viewer/web/';


    $search_replace_map = [
        'href="viewer.css"'        => 'href="' . $plugin_viewer_url . 'viewer.css"',
        'href="pdf_viewer.css"'    => 'href="' . $plugin_viewer_url . 'pdf_viewer.css"',
        'src="pdf.js"'             => 'src="' . $plugin_viewer_url . 'pdf.js"',
        'src="pdf.worker.js"'      => 'src="' . $plugin_viewer_url . 'pdf.worker.js"',
        'src="pdf_viewer.js"'      => 'src="' . $plugin_viewer_url . 'pdf_viewer.js"',
        'src="viewer.js"'          => 'src="' . $plugin_viewer_url . 'viewer.js"',
    ];

    foreach ($search_replace_map as $search => $replace) {
        $viewer_html = str_replace($search, $replace, $viewer_html);
    }

    // If you see other references inside viewer.html (like 'locale.properties', or 'cmaps' paths),
    // you'll need to do similar replacements to point them to your plugin's copies.

    // 3) Create a container div for the viewer
    ob_start();
    ?>
    <div 
        id="<?php echo esc_attr($container_id); ?>" 
        class="kv-pdf-viewer"
        data-pdf-url="<?php echo $pdf_data['url']; ?>"
        data-scale="1.3"
        data-pdf-title="<?php echo esc_attr($pdf_data['info']['title']); ?>"
    >
        <?php echo $viewer_html; ?>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('kv_pdf_viewer', 'kv_pdf_viewer_shortcode');
