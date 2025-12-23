<?php
/**
 * Plugin Name: Video Leads Manager
 * Description: Creates DB table for video form leads shortcode use like( [custom_video_form video_url="https://example.com/video.mp4"] )
 * Author: Sandeep sharma
 * Version: 1.0.0
 */

register_activation_hook(__FILE__, 'vl_create_video_leads_table');

function vl_create_video_leads_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'video_leads';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/* -------------------------------------------------
 * 2. ENQUEUE SCRIPTS
 * ------------------------------------------------- */
add_action('wp_enqueue_scripts', 'vl_enqueue_scripts');

function vl_enqueue_scripts() {
    wp_enqueue_script('jquery');

    wp_enqueue_script(
        'vl-custom-js',
        plugin_dir_url(__FILE__) . 'custom-form.js',
        array('jquery'),
        null,
        true
    );

    wp_localize_script('vl-custom-js', 'formAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('form_nonce')
    ));
}

/* -------------------------------------------------
 * 3. AJAX FORM HANDLER
 * ------------------------------------------------- */
add_action('wp_ajax_submit_custom_form', 'vl_submit_custom_form');
add_action('wp_ajax_nopriv_submit_custom_form', 'vl_submit_custom_form');

function vl_submit_custom_form() {
    check_ajax_referer('form_nonce', 'nonce');

    global $wpdb;
    $table = $wpdb->prefix . 'video_leads';

    $name  = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');

    if (empty($name) || empty($email)) {
        wp_send_json_error('All fields are required');
    }

    $wpdb->insert(
        $table,
        array(
            'name'  => $name,
            'email' => $email,
        ),
        array('%s', '%s')
    );

    if ($wpdb->last_error) {
        wp_send_json_error($wpdb->last_error);
    }

    wp_send_json_success('Saved');
}

/* -------------------------------------------------
 * 4. SHORTCODE WITH POPUP + VIDEO
 * ------------------------------------------------- */
add_shortcode('custom_video_form', 'vl_video_form_shortcode');

function vl_video_form_shortcode($atts) {

    $atts = shortcode_atts(array(
        'video_url' => '',
    ), $atts);

    if (empty($atts['video_url'])) {
        return '<p><strong>Error:</strong> video_url is missing.</p>';
    }

    ob_start();
    ?>
    <button id="open-video-popup">▶ Watch Video</button>

    <div id="video-popup" style="display:none;">
        <div class="popup-inner">
            <span class="popup-close">&times;</span>

            <form id="custom-video-form">
                <input type="text" id="name" placeholder="Your Name" required>
                <input type="email" id="email" placeholder="Your Email" required>
                <button type="submit">Unlock Video</button>
                <p id="form-message"></p>
            </form>

            <div id="video-wrapper" style="display:none;">
                <video id="popup-video" width="100%" controls>
                    <source src="<?php echo esc_url($atts['video_url']); ?>" type="video/mp4">
                </video>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/* -------------------------------------------------
 * 5. ADMIN MENU + DATA DISPLAY
 * ------------------------------------------------- */
add_action('admin_menu', 'vl_admin_menu');

function vl_admin_menu() {
    add_menu_page(
        'Video Leads',
        'Video Leads',
        'manage_options',
        'video-leads',
        'vl_render_admin_page',
        'dashicons-video-alt3',
        25
    );
}

function vl_render_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'video_leads';

    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    ?>
    <div class="wrap">
        <h1>Video Form Submissions</h1>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results): ?>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row->id); ?></td>
                            <td><?php echo esc_html($row->name); ?></td>
                            <td><?php echo esc_html($row->email); ?></td>
                            <td><?php echo esc_html($row->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No records found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}