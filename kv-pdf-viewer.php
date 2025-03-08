<?php
/**
 * Plugin Name: PDF Embed & SEO Optimize
 * Plugin URI: https://dross.net/#media
 * Description: PDF Embed & SEO Optimize is a powerful WordPress plugin that integrates Mozilla's PDF.js viewer to serve PDFs through a viewer URL, enhancing SEO with Schema Data, Open Graph Tags, Twitter Cards, and other Meta Tags. Also analytics tracking codes can be injected.
 * Version: 1.0.5
 * Author: Dross:Media
 * Author URI: https://dross.net/#media
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kv-pdf-viewer
 *  * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}



function kv_enqueue_admin_scripts( $hook ) {
    global $post;

    // Only load on post-new and post edit screens for 'pdf_viewer' post type
    if ( ( $hook === 'post-new.php' || $hook === 'post.php' ) && isset( $post ) && $post->post_type === 'pdf_viewer' ) {
        
        // Load Select2 CSS from a CDN
        wp_enqueue_style( 'select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' );

        // Load Select2 JS from a CDN
        wp_enqueue_script( 'select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js', array( 'jquery' ), null, true );

        // Register the custom script (best practice before enqueueing)
        wp_enqueue_script('kv-pdf-viewer', plugin_dir_url(__FILE__) . 'js/script.js', ['jquery','select2-js'], null, true);

        // Localize script - Pass PHP data to JavaScript
        $kv_pdf_upload_data = array(
            'title'        => __( 'Choose PDF', 'kv-pdf-viewer' ),
            'uploadedText' => __( 'Upload PDF', 'kv-pdf-viewer' ),
            'removeText'   => __( 'Remove PDF', 'kv-pdf-viewer' )
        );

        wp_localize_script( 'kv-pdf-viewer', 'kv_pdf_upload_data', $kv_pdf_upload_data );

        // Finally, enqueue the script after localization

    }
}

add_action( 'admin_enqueue_scripts', 'kv_enqueue_admin_scripts' );


function kv_enqueue_frontend_scripts() {
    // Register PDF.js core library
    // wp_enqueue_script('pdfjs', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.min.mjs', array(), null, true);
    // wp_enqueue_script('pdfjs', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.worker.min.js', array(), null, true);
    wp_register_script(
        'pdfjs-core',
        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.mjs',
        [],
        '3.4.120',
        false
    );

    // Register PDF.js worker (needed for background processing)
    wp_register_script(
        'pdfjs-worker',
        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.worker.mjs',
        [],
        '3.4.120',
        false
    );


    // Register PDF.js viewer JS (the “web” viewer layer)
    wp_register_script(
        'pdfjs-viewer',
        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf_viewer.mjs',
        ['pdfjs-core'],
        '3.4.120',
        false
    );    

        // Register our custom initialization script.
        // wp_register_script(
        //     'kv-pdf-viewer-init',
        //     plugin_dir_url(__FILE__) . 'js/fe-script.js',
        //     ['jquery'],
        //     '1.0.0',
        //     false
        // );

        wp_enqueue_style(
            'kv-pdf-viewer-style',
            plugin_dir_url(__FILE__) . 'css/style.css',
            [],
            null,
            true
        );
        wp_enqueue_style(
            'kv-viewer-style',
            plugin_dir_url(__FILE__) . 'includes/viewer/css/style.css',
            [],
            null,
            true
        );

            // ENQUEUE CSS PROPERLY
    wp_enqueue_style(
        'kv-pdf-viewer-css',
        plugin_dir_url(__FILE__) . 'includes/viewer/viewer.css',
        array(),
        null,
        true
    );

        // Register PDF.js viewer CSS (includes default toolbar styling, etc.)
        // wp_enqueue_style(
        //     'pdfjs-viewer-css',
        //     'https://unpkg.com/browse/pdfjs-dist@4.10.38/web/pdf_viewer.css',
        //     [],
        //     null,
        //     true
        // );

            wp_enqueue_script(
                'fluent-dom',
                'https://cdn.jsdelivr.net/npm/@fluent/dom@0.10.0/index.min.js',
                array(),
                '0.10.0',
                true
            );

        // wp_enqueue_style(
        //     'pdfjs-viewer-css', 
        //     plugin_dir_url(__FILE__) . 'includes/viewer/pdf_viewer.css', 
        //     array('pdfjs-viewer'), // load after viewer.css
        //     '1.0.0'
        // );
    


    // Only enqueue these assets if the current post contains our shortcode
    // (to avoid loading on every page).
    if ( is_singular() ) {
        wp_enqueue_script('pdfjs-core');
        wp_enqueue_script('pdfjs-worker');
        wp_enqueue_script('pdfjs-viewer');
        // wp_enqueue_script('kv-pdf-viewer-init');
    }
    
}
add_action('wp_enqueue_scripts', 'kv_enqueue_frontend_scripts');

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

require_once plugin_dir_path(__FILE__) . 'includes/kv-pdf-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'html-sitemap.php';
require_once plugin_dir_path( __FILE__ ) . 'admin-docs.php';


/**
 * Register the "PDF Viewer" custom post type.
 */
function kv_register_pdf_viewer_post_type() {
    $labels = array(
        'name'                  => __( 'Pdf', 'kv-pdf-viewer' ),
        'singular_name'         => __( 'PDF Viewer', 'kv-pdf-viewer' ),
        'menu_name'             => __( 'PDF Viewers', 'kv-pdf-viewer' ),
        'name_admin_bar'        => __( 'PDF Viewer', 'kv-pdf-viewer' ),
        'add_new'               => __( 'Add New', 'kv-pdf-viewer' ),
        'add_new_item'          => __( 'Add New PDF Viewer', 'kv-pdf-viewer' ),
        'new_item'              => __( 'New PDF Viewer', 'kv-pdf-viewer' ),
        'edit_item'             => __( 'Edit PDF Viewer', 'kv-pdf-viewer' ),
        'view_item'             => __( 'View PDF Viewer', 'kv-pdf-viewer' ),
        'all_items'             => __( 'All PDF Viewers', 'kv-pdf-viewer' ),
        'search_items'          => __( 'Search PDF Viewers', 'kv-pdf-viewer' ),
        'parent_item_colon'     => __( 'Parent PDF Viewer:', 'kv-pdf-viewer' ),
        'not_found'             => __( 'No PDF viewers found.', 'kv-pdf-viewer' ),
        'not_found_in_trash'    => __( 'No PDF viewers found in Trash.', 'kv-pdf-viewer' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true, // Makes it visible both on the front end and in the admin
        'has_archive'        => true,
        'rewrite'            => array( 
            'slug'       => 'pdf', // Customize the URL slug here
            'with_front' => false,        // Set to false if you don't want the front base included
        ),
        'supports'           => array( 'title', 'thumbnail' ), // Customize as needed
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
        __( 'PDF Upload', 'kv-pdf-viewer' ),       // Title.
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
                    <button type="button" class="button" id="kv_upload_pdf_button"><?php _e( 'Upload PDF', 'kv-pdf-viewer' ); ?></button>
                </p>
                <iframe src="<?php echo esc_url( $pdf_url ); ?>" width="100%" height="500"></iframe>
            <?php else : ?>
                <p><?php _e( 'No PDF uploaded. Please upload a PDF file.', 'kv-pdf-viewer' ); ?></p>
                <p>
                    <button type="button" class="button" id="kv_upload_pdf_button"><?php _e( 'Upload PDF', 'kv-pdf-viewer' ); ?></button>
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

