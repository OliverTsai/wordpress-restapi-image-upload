<?php
/**
 * Plugin Name: Custom File Upload
 * Description: This plugin allows users to upload files using a custom endpoint.
 * Version: 1.0.0
 * Author: Oliver
 */

/**
 * Register the plugin's hooks and filters.
 */
function custom_file_upload_init() {
    add_action( 'rest_api_init', 'custom_file_upload_register_endpoints' );
}
add_action( 'plugins_loaded', 'custom_file_upload_init' );

/**
 * Register the custom endpoint for file upload.
 */
function custom_file_upload_register_endpoints() {
    register_rest_route(
        'custom-file-upload/v1',
        '/upload',
        array(
            'methods'  => 'POST',
            'callback' => 'custom_file_upload_handle_upload',
            'args'     => array(
                'context' => array(
                    'required' => false,
                    'type'     => 'string',
                ),
                'theme'   => array(
                    'required' => false,
                    'type'     => 'string',
                ),
            ),
        )
    );
}

/**
 * Handle the file upload request.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return WP_REST_Response The REST API response object.
 */
function custom_file_upload_handle_upload( WP_REST_Request $request ) {
    
    $response = array();
    $parameters = $request->get_params();

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    //upload only images and files with the following extensions
    $file_extension_type = array('jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tiff', 'tif', 'ico', 'zip', 'pdf', 'docx');
    $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $file_extension_type)) {
        return wp_send_json(
            array(
                'success' => false,
                'data'    => array(
                    'message'  => __('The uploaded file is not a valid file. Please try again.'),
                    'filename' => esc_html($_FILES['file']['name']),
                ),
            )
        );
    }

    $attachment_id = media_handle_upload('file', null, []);

    if (is_wp_error($attachment_id)) {
        return wp_send_json(
            array(
                'success' => false,
                'data'    => array(
                    'message'  => $attachment_id->get_error_message(),
                    'filename' => esc_html($_FILES['file']['name']),
                ),
            )
        );
    }

    if (isset($parameters['context']) && isset($parameters['theme'])) {
        if ('custom-background' === $parameters['context']) {
            update_post_meta($attachment_id, '_wp_attachment_is_custom_background', $parameters['theme']);
        }

        if ('custom-header' === $parameters['context']) {
            update_post_meta($attachment_id, '_wp_attachment_is_custom_header', $parameters['theme']);
        }
    }

    $attachment = wp_prepare_attachment_for_js($attachment_id);
    if (!$attachment) {
        return wp_send_json(
            array(
                'success' => false,
                'data'    => array(
                    'message'  => __('Image cannot be uploaded.'),
                    'filename' => esc_html($_FILES['file']['name']),
                ),
            )
        );
    }

    return wp_send_json(
        array(
            'success' => true,
            'data'    => $attachment,
        )
    );

}


