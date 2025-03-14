<?php
/**
 * Plugin Name: PDF Embed & SEO Optimize
 * Plugin URI: https://dross.net/#media
 * Description: PDF Embed & SEO Optimize is a powerful WordPress plugin that integrates Mozilla's PDF.js viewer to serve PDFs through a viewer URL, enhancing SEO with Schema Data, Open Graph Tags, Twitter Cards, and other Meta Tags. Also analytics tracking codes can be injected.
 * Version: 1.0.6
 * Author: Dross:Media
 * Author URI: https://dross.net/#media
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: drossmedia-pdf-viewer
 *  * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}



function drossmedia_enqueue_admin_scripts( $hook ) {
    global $post;

    // Only load on post-new and post edit screens for 'pdf_viewer' post type
    if ( ( $hook === 'post-new.php' || $hook === 'post.php' ) && isset( $post ) && $post->post_type === 'pdf_viewer' ) {
        $plugin_url = plugin_dir_url(__FILE__);
        
        // Enqueue Select2 CSS
    wp_enqueue_style('select2-css', $plugin_url . 'css/select2.min.css');

    // Enqueue Select2 JS
    wp_enqueue_script('select2-js', $plugin_url . 'js/select2.full.min.js', array('jquery'), null, true);


        // Register the custom script (best practice before enqueueing)
        wp_enqueue_script('drossmedia-pdf-viewer', plugin_dir_url(__FILE__) . 'js/script.js', ['jquery','select2-js'], null, true);

        // Localize script - Pass PHP data to JavaScript
        $kv_pdf_upload_data = array(
            'title'        => __( 'Choose PDF', 'kv-pdf-viewer' ),
            'uploadedText' => __( 'Upload PDF', 'kv-pdf-viewer' ),
            'removeText'   => __( 'Remove PDF', 'kv-pdf-viewer' )
        );

        wp_localize_script( 'drossmedia-pdf-viewer', 'kv_pdf_upload_data', $kv_pdf_upload_data );

        // Register the custom script (best practice before enqueueing)
        wp_enqueue_script('drossmedia-pdf-viewer-fe', plugin_dir_url(__FILE__) . 'js/fe-script.js', array('jquery'), null, true);

            // Mark the script as a module
        add_filter('script_loader_tag', function ($tag, $handle) {
            if ($handle === 'drossmedia-pdf-viewer-fe') {
                return str_replace('src', 'type="module" src', $tag);
            }
            return $tag;
        }, 10, 2);
        
        // Ensure a valid PDF Viewer post
        $pdf_document = get_post_meta( $post->ID, '_kv_pdf_file', true );

        // Decode JSON if it exists.
        $pdf_data = $pdf_document ? json_decode( $pdf_document, true ) : array();
        $pdf_url   = isset( $pdf_data['url'] ) ? $pdf_data['url'] : '';

        $kv_pdf_upload_url = array(
            'pdfUrl'   => $pdf_url,
        );

            // Localize script: pass PDF URL, AJAX URL, nonce, and post ID.
    wp_localize_script(
        'drossmedia-pdf-viewer-fe',
        'kv_pdf_upload_url',
        array(
            'pdfUrl'   => $pdf_url,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('kv_save_pdf_file'),
            'post_id'  => get_the_ID(),
        )
    );

        // Finally, enqueue the script after localization

    }
}

add_action( 'admin_enqueue_scripts', 'drossmedia_enqueue_admin_scripts' );


function drossmedia_enqueue_frontend_scripts() {
    $plugin_url = plugin_dir_url(__FILE__);
        

// Enqueue Select2 JS
wp_enqueue_script('select2-js', $plugin_url . 'js/select2.full.min.js', array('jquery'), null, true);

        // Register our custom initialization script.
        wp_register_script(
            'kv-pdf-viewer-init',
            plugin_dir_url(__FILE__) . 'js/fe-script.js',
            ['jquery'],
            '1.0.0',
            false
        );

        wp_enqueue_style(
            'drossmedia-pdf-viewer-style',
            plugin_dir_url(__FILE__) . 'css/style.css',
            [],
            null,
            true
        );
        wp_enqueue_style(
            'drossmedia-viewer-style',
            plugin_dir_url(__FILE__) . 'includes/viewer/css/style.css',
            [],
            null,
            true
        );

            wp_enqueue_script(
                'fluent-dom', $plugin_url . 'js/fluentdom.min.js',
                array(),
                '0.10.0',
                true
            );
    
}
add_action('wp_enqueue_scripts', 'drossmedia_enqueue_frontend_scripts');

function my_pdfjs_inline_override() {
    $override_script = "
      if (typeof PDFJSDev === 'undefined') {
        window.PDFJSDev = {
          eval: function(code) {
            if (code === 'BUNDLE_VERSION') return '4.10.38';
            if (code === 'BUNDLE_BUILD') return '4.10.38';
            return null;
          },
          test: function(condition) {
            // Return true for any condition so that restrictions are bypassed.
            return true;
          }
        };
      }
    ";
    wp_add_inline_script('pdfjs-core', $override_script, 'before');
}
add_action('wp_enqueue_scripts', 'my_pdfjs_inline_override');

function add_module_type_attribute( $tag, $handle, $src ) {
    // List the handles that should be treated as modules.
    $module_handles = array( 'pdfjs-core', 'pdfjs-worker', 'pdfjs-viewer' );
    if ( in_array( $handle, $module_handles, true ) ) {
        // Modify the tag to add type="module"
        $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'add_module_type_attribute', 10, 3 );

require_once plugin_dir_path(__FILE__) . 'includes/drossmedia-pdf-viewer-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'html-sitemap.php';
require_once plugin_dir_path( __FILE__ ) . 'admin-docs.php';


/**
 * Register the "PDF Viewer" custom post type.
 */
function kv_register_pdf_viewer_post_type() {
    $labels = array(
        'name'                  => __( 'Pdf', 'drossmedia-pdf-viewer' ),
        'singular_name'         => __( 'PDF Viewer', 'drossmedia-pdf-viewer' ),
        'menu_name'             => __( 'PDF Viewers', 'drossmedia-pdf-viewer' ),
        'name_admin_bar'        => __( 'PDF Viewer', 'drossmedia-pdf-viewer' ),
        'add_new'               => __( 'Add New', 'drossmedia-pdf-viewer' ),
        'add_new_item'          => __( 'Add New PDF Viewer', 'drossmedia-pdf-viewer' ),
        'new_item'              => __( 'New PDF Viewer', 'drossmedia-pdf-viewer' ),
        'edit_item'             => __( 'Edit PDF Viewer', 'drossmedia-pdf-viewer' ),
        'view_item'             => __( 'View PDF Viewer', 'drossmedia-pdf-viewer' ),
        'all_items'             => __( 'All PDF Viewers', 'drossmedia-pdf-viewer' ),
        'search_items'          => __( 'Search PDF Viewers', 'drossmedia-pdf-viewer' ),
        'parent_item_colon'     => __( 'Parent PDF Viewer:', 'drossmedia-pdf-viewer' ),
        'not_found'             => __( 'No PDF viewers found.', 'drossmedia-pdf-viewer' ),
        'not_found_in_trash'    => __( 'No PDF viewers found in Trash.', 'drossmedia-pdf-viewer' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true, // Makes it visible both on the front end and in the admin
        'has_archive'        => true,
        'rewrite'            => array( 
            'slug'       => 'pdf', // Customize the URL slug here
            'with_front' => false,        // Set to false if you don't want the front base included
        ),
       'supports' => array('title', 'thumbnail'), // Customize as needed
        'capability_type'    => 'post',
        'show_in_rest'       => true, // Enable Gutenberg editor support
        'menu_position'      => 20,   // Position the menu; adjust as necessary
        'menu_icon'          => 'dashicons-media-document', // Choose an appropriate dashicon
    );

    register_post_type( 'pdf_viewer', $args );
}
add_action( 'init', 'kv_register_pdf_viewer_post_type' );

/**
 * Register the "Meta Details" metabox for the PDF Viewer post type.
 */


/**
 * Register the "PDF Upload" metabox.
 */
function kv_add_pdf_upload_metabox() {
    add_meta_box(
        'kv_pdf_upload',                         // Unique ID.
        __( 'PDF Upload', 'drossmedia-pdf-viewer' ),       // Title.
        'kv_pdf_upload_callback',                // Callback to display the field.
        'pdf_viewer',                           // Post type.
        'normal',                               // Context.
        'default'                               // Priority.
    );
}
add_action( 'add_meta_boxes', 'kv_add_pdf_upload_metabox' );



function kv_pdf_upload_callback( $post ) {
    // Add nonce for security.
    wp_nonce_field( 'kv_save_pdf_file', 'kv_pdf_file_nonce' );

    // Retrieve the existing PDF document from post meta.
    $pdf_document = get_post_meta( $post->ID, '_kv_pdf_file', true );

    // Decode JSON if it exists.
    $pdf_data = $pdf_document ? json_decode( $pdf_document, true ) : array();
    $pdf_url   = isset( $pdf_data['url'] ) ? $pdf_data['url'] : '';
    $pdf_title = isset( $pdf_data['title'] ) ? $pdf_data['title'] : '';
    ?>
    <div id="kv_pdf_upload_container">
        <div id="kv_pdf_preview">
            <?php if ( $pdf_url ) : ?>
                <p>
                    <button type="button" class="button" id="kv_upload_pdf_button"><?php _e( 'Upload PDF', 'drossmedia-pdf-viewer' ); ?></button>
                </p>
                <iframe src="<?php echo esc_url( $pdf_url ); ?>" width="100%" height="500"></iframe>
            <?php else : ?>
                <p><?php _e( 'No PDF uploaded. Please upload a PDF file.', 'drossmedia-pdf-viewer' ); ?></p>
                <p>
                    <button type="button" class="button" id="kv_upload_pdf_button"><?php _e( 'Upload PDF', 'drossmedia-pdf-viewer' ); ?></button>
                </p>
            <?php endif; ?>
        </div>

        <!-- Hidden inputs for the URL and title -->
        <input type="hidden" id="kv_pdf_url" name="kv_pdf_url" value="<?php echo esc_attr( $pdf_url ); ?>" />
        <input type="hidden" id="kv_pdf_title" name="kv_pdf_title" value="<?php echo esc_attr( $pdf_title ); ?>" />
    </div>
    <?php
}

function kv_save_pdf_file( $post_id ) {
    // Verify nonce.
    if ( ! isset( $_POST['kv_pdf_file_nonce'] ) || ! wp_verify_nonce( $_POST['kv_pdf_file_nonce'], 'kv_save_pdf_file' ) ) {
        return;
    }
    // Prevent autosave.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // Check user permissions.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    // Save or update the PDF file data.
    if ( isset( $_POST['kv_pdf_url'], $_POST['kv_pdf_title'] ) ) {
        $pdf_url   = sanitize_text_field( wp_unslash( $_POST['kv_pdf_url'] ) );
        $pdf_title = sanitize_text_field( wp_unslash( $_POST['kv_pdf_title'] ) );
        $pdf_data  = array(
            'url'   => $pdf_url,
            'title' => $pdf_title,
        );
        update_post_meta( $post_id, '_kv_pdf_file', wp_json_encode( $pdf_data ) );
    }
}
add_action( 'save_post', 'kv_save_pdf_file' );


add_filter('template_include', 'kv_load_pdf_viewer_single_template');
function kv_load_pdf_viewer_single_template($template) {
    if ( is_singular('pdf_viewer') ) {
        $plugin_template = plugin_dir_path(__FILE__) . 'single-pdf_viewer.php';
        if ( file_exists($plugin_template) ) {
            return $plugin_template;
        }
    }
    return $template;
}
/**
 * AJAX handler to save PDF metadata.
 */
function drossmedia_ajax_save_pdf_file() {
    // 1. Security: Verify the AJAX nonce.
    check_ajax_referer( 'kv_save_pdf_file', 'kv_pdf_file_nonce' );

    // 2. Get and validate the post ID.
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => 'Invalid post ID.' ) );
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to edit this post.' ) );
    }

    // 3. Sanitize and process incoming PDF metadata.
    $pdf_url       = isset( $_POST['pdf_url'] ) ? esc_url_raw( $_POST['pdf_url'] ) : '';
    $pdf_title     = isset( $_POST['kv_pdf_title'] ) ? sanitize_text_field( wp_unslash( $_POST['kv_pdf_title'] ) ) : '';
    $creation_date = isset( $_POST['creation_date'] ) ? sanitize_text_field( wp_unslash( $_POST['creation_date'] ) ) : '';
    $modification_date = isset( $_POST['modification_date'] ) ? sanitize_text_field( wp_unslash( $_POST['modification_date'] ) ) : '';
    $description   = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
    $author   = isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '';


    // 4. Verify that required fields are provided.
    if ( empty( $pdf_url ) || empty( $pdf_title ) ) {
        wp_send_json_error( array( 'message' => 'Missing required PDF data.' ) );
    }

    // 5. Prepare the PDF data array.
    $pdf_data = array(
        'url'           => $pdf_url,
        'title'         => $pdf_title,
        'creation_date' => $creation_date,
        'modification_date' => $modification_date,
        'description'   => $description,
        'author' =>  $author
    );
    // 6. Save or update the PDF metadata in post meta.
    if (!empty($pdf_data)) {
        update_post_meta( $post_id, '_kv_pdf_file', wp_json_encode( $pdf_data ) );

        wp_send_json_success( array( 'message' => 'PDF metadata saved successfully.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to update PDF metadata.' ) );
    }
}
add_action( 'wp_ajax_drossmedia_save_pdf_file', 'drossmedia_ajax_save_pdf_file' );
add_action( 'wp_ajax_nopriv_kv_save_pdf_file', 'drossmedia_ajax_save_pdf_file' );

function kv_set_pdf_worker_url() {
    $pdf_worker_url = plugin_dir_url(__FILE__) . 'js/pdfworker.mjs';
    ?>
    <script>
        window.pdfWorkerUrl = "<?php echo esc_url($pdf_worker_url); ?>";
    </script>
    <?php
}
add_action('wp_head', 'kv_set_pdf_worker_url');

