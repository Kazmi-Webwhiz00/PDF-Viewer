<?php

/**
 * Usage: [pdf_viewer]
 */
function kv_pdf_viewer_shortcode($atts) {
    global $post;

    // Extract shortcode attributes
    $atts = shortcode_atts(
        array(
            'id' => '' // Default is empty (fallback to current post)
        ),
        $atts,
        'pdf_viewer'
    );

    // Determine which post ID to use
    $post_id = !empty($atts['id']) ? intval($atts['id']) : (isset($post->ID) ? $post->ID : 0);

    // Ensure a valid PDF Viewer post
    $pdf_document = get_post_meta($post_id, '_kv_pdf_file', true);

    // Decode JSON if it exists.
    $pdf_data = $pdf_document ? json_decode($pdf_document, true) : array();
    $pdf_url   = isset($pdf_data['url']) ? $pdf_data['url'] : '';
    $pdf_title = isset($pdf_data['title']) ? $pdf_data['title'] : '';

    // If no PDF data is stored, return a message.
    if (empty($pdf_document)) {
        return '<p>No PDF uploaded.</p>';
    }

    // Check if the URL exists in the decoded data
    if (empty($pdf_data['url'])) {
        return '<p>' . __('No PDF available for this viewer.', 'kv-pdf-viewer') . '</p>';
    }

    // Path to the official PDF.js "viewer.html" in your plugin
    $viewer_html_path = plugin_dir_path(__FILE__) . '/viewer/web/viewer.html';
    if (!file_exists($viewer_html_path)) {
        return '<p>Error: viewer.html not found in plugin.</p>';
    }

    // 1) Read the viewer.html
    ob_start();
    include $viewer_html_path;
    $viewer_html = ob_get_clean();

    // 2) Replace references to local CSS/JS with plugin_dir_url
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

    // 3) Create a container div for the viewer
    ob_start();
    ?>
    <div 
        id="kv-pdf-viewer-<?php echo esc_attr($post_id); ?>" 
        class="kv-pdf-viewer"
        data-pdf-url="<?php echo esc_url($pdf_url); ?>"
        data-scale="1.3"
        data-pdf-title="<?php echo esc_attr($pdf_title); ?>"
    >
        <?php echo $viewer_html; ?>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('pdf_viewer', 'kv_pdf_viewer_shortcode');
