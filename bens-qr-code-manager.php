<?php
/**
 * Plugin Name: Ben's QR Code Manager
 * Description: Generate, manage, and display QR codes for users with unique IDs. Includes user info retrieval and real-time updates. connect to an external qr code to get the unique id of a user and use it to fetch information as regarding the user
 * Version: 1.5.1
 * Author: Benjamin Feranmi
 */

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('ben-admin-scripts', plugin_dir_url(__FILE__) . 'js/admin-scripts.js', ['jquery'], '1.0.0', true);
    wp_enqueue_style('ben-admin-styles', plugin_dir_url(__FILE__) . 'css/admin-styles.css', [], '1.0.0');
    wp_localize_script('ben-admin-scripts', 'benAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ben_update_qr_code_nonce'),
    ]);
});

// Generate Unique ID
function ben_generate_unique_id()
{
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    return substr(str_shuffle($characters), 0, 8);
}

// Generate QR Code URL
function ben_generate_qr_code_url($unique_id)
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($unique_id);
}

// Automatically add QR code after member checkout
add_action('pmpro_after_checkout', function ($user_id) {
    $unique_id = ben_generate_unique_id();
    $qr_code_url = ben_generate_qr_code_url($unique_id);

    update_user_meta($user_id, 'unique_id', $unique_id);
    update_user_meta($user_id, 'qr_code_url', $qr_code_url);

    ben_send_qr_email($user_id, $unique_id, $qr_code_url);
});

// Send Email with QR Code
function ben_send_qr_email($user_id, $unique_id, $qr_code_url)
{
    $user = get_userdata($user_id);
    $to = $user->user_email;
    $subject = 'Your Unique QR Code';
    $message = "
        <html>
        <body>
            <p>Hello <strong>{$user->display_name}</strong>,</p>
            <br />
            <p>Your unique ID: <strong>{$unique_id}</strong></p>
            <br />
            <p>Your QR Code:</p>
            <img src='{$qr_code_url}' alt='Your QR Code' style='max-width: 200px; height: auto;'>

            <p>Best Regards,<br>Team</p>

            <span style='font-size: 20px; color: red ;'>Note: Always Come to the qym with with either your unique id, or the qr code image. Thanks.</span>
        </body>
        </html>
    ";

    // Specify the email headers to indicate HTML content
    $headers = [
        'Content-Type: text/html; charset=UTF-8'
    ];

    wp_mail($to, $subject, $message, $headers);
}

// Create admin menu
add_action('admin_menu', function () {
    add_menu_page(
        'QR Code Manager',
        'QR Code Manager',
        'manage_qr_codes',
        'ben-qr-code-manager',
        'ben_qr_code_manager_page',
        'dashicons-qrcode',
        6
    );

    // Add link to QR Code Scanner page
    add_submenu_page(
        'ben-qr-code-manager',
        'QR Code Scanner',
        'QR Code Scanner',
        'manage_qr_codes',
        'ben-qr-code-scanner',
        'ben_qr_code_scanner_page' // Callback function for the scanning page
    );
});

// Including necessary pages

// the scanner page
include_once plugin_dir_path(__FILE__) . 'ben-qr-code-scanner.php';

// Admin page content
function ben_qr_code_manager_page()
{
    // Number of users per page
    $users_per_page = 20;

    // Get current page number from query parameter
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

    // Get sorting order from query parameters
    $order_by = isset($_GET['order_by']) ? sanitize_text_field($_GET['order_by']) : 'ID';
    $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'ASC';


    // Calculate the offset
    $offset = ($current_page - 1) * $users_per_page;

    // Fetch users with pagination and sorting
    $users = get_users(array(
        'number' => $users_per_page,
        'offset' => $offset,
        'orderby' => $order_by,
        'order' => $order,
    ));

    // Get total number of users
    $total_users = count_users();
    $total_users_count = $total_users['total_users'];

    // Calculate total number of pages
    $total_pages = ceil($total_users_count / $users_per_page);

    // Generate sorting URLs
    $base_url = admin_url('admin.php?page=ben-qr-code-manager');
    $order_by_name_asc = add_query_arg(array('order_by' => 'display_name', 'order' => 'ASC'), $base_url);
    $order_by_name_desc = add_query_arg(array('order_by' => 'display_name', 'order' => 'DESC'), $base_url);
    $order_by_newest = add_query_arg(array('order_by' => 'ID', 'order' => 'DESC'), $base_url);
    $order_by_oldest = add_query_arg(array('order_by' => 'ID', 'order' => 'ASC'), $base_url);


    ?>
        <div class="wrap">
            <h1>QR Code Manager</h1>
        
             <div>
                <a href="<?php echo admin_url('admin.php?page=ben-qr-code-scanner'); ?>" class="button-primary">Go to QR Code Scanning</a>
            </div>
        
            <div class="ben-qrcode-topfliters">
                 <!-- Sorting Filters -->
            <div class="sorting-filters">
                <strong>Sort By:</strong>
                <a href="<?php echo esc_url($order_by_name_asc); ?>" class="button-secondary">Name (A-Z)</a>
                <a href="<?php echo esc_url($order_by_name_desc); ?>" class="button-secondary">Name (Z-A)</a>
                <a href="<?php echo esc_url($order_by_newest); ?>" class="button-secondary">Newest</a>
                <a href="<?php echo esc_url($order_by_oldest); ?>" class="button-secondary">Oldest</a>
            </div>
        
          <div class="fliter-search">
                <input type="text" id="search-user" placeholder="Search by User ID, Name, or Email" />
          </div>
            <br />
            </div>
       
            <table class="widefat fixed" id="qr-code-manager-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Unique ID</th>
                        <th>QR Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($users as $user) {
                        $unique_id = get_user_meta($user->ID, 'unique_id', true);
                        $qr_code_url = get_user_meta($user->ID, 'qr_code_url', true);
                        ?>
                            <tr>
                                <td><?php echo esc_html($user->ID); ?></td>
                                <td><?php echo esc_html($user->display_name); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td>
                                    <input type="text" value="<?php echo esc_attr($unique_id); ?>"
                                        data-user-id="<?php echo esc_attr($user->ID); ?>" class="unique-id-input" />
                                </td>
                                <td>
                                    <img src="<?php echo esc_url($qr_code_url); ?>" alt="QR Code" class="qr-code-img" />
                                </td>
                                <td class="ben-but">
                                    <button class="update-qr-btn button-primary"
                                        data-user-id="<?php echo esc_attr($user->ID); ?>">Update QR Code</button>
                                    <button class="view-info-btn button-secondary"
                                        data-user-id="<?php echo esc_attr($user->ID); ?>">View Info</button>
                                </td>
                            </tr>
                            <?php
                    }
                    ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="benqr-tablenav-pages">
                <?php
                $base_url = admin_url('admin.php?page=ben-qr-code-manager');
                $pagination_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%', $base_url),
                    'format' => '',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_text' => '&laquo; Previous',
                    'next_text' => 'Next &raquo;',
                    'type' => 'list',
                ));

                if ($pagination_links) {
                    echo $pagination_links;
                }
                ?>
            </div>

            <!-- User Info Modal -->
            <div id="user-info-modal" style="display: none;">
                <div class="modal-content">
                    <button id="close-user-info">Close</button>
                    <h3>User Information</h3>
                    <div class="uim_user">
                        <img id="modal-user-profile-image" class="uimu_img" src="" alt="User Profile Picture" />
                    </div>
                    <p><strong>User ID:</strong> <span id="modal-user-id"></span></p>
                    <p><strong>Name:</strong> <span id="modal-user-name"></span></p>
                    <p><strong>Email:</strong> <span id="modal-user-email"></span></p>
                    <p><strong>Plan:</strong> <span id="modal-user-plan"></span></p>
                    <p><strong>Start Date:</strong> <span id="modal-user-start"></span></p>
                    <p><strong>Expiry Date:</strong> <span id="modal-user-expiry"></span></p>
                    <p><strong>Status:</strong> <span id="modal-user-status"></span></p>
                    <p><strong>Initial Payment:</strong> <span id="modal-user-initial-payment"></span></p>
                    <p><strong>Billing Amount:</strong> <span id="modal-user-billing-amount"></span></p>

                    <h4>Response Debug</h4>
                    <textarea id="modal-response-debug" style="width: 100%; height: 50px;" readonly></textarea>
                </div>
            </div>
        </div>
        <?php
}


// Handle AJAX request for updating QR Code
add_action('wp_ajax_ben_update_qr_code', function () {
    check_ajax_referer('ben_update_qr_code_nonce', 'nonce');

    $user_id = intval($_POST['user_id']);
    $unique_id = ben_generate_unique_id();
    $qr_code_url = ben_generate_qr_code_url($unique_id);

    update_user_meta($user_id, 'unique_id', $unique_id);
    update_user_meta($user_id, 'qr_code_url', $qr_code_url);

    ben_send_qr_email($user_id, $unique_id, $qr_code_url);

    wp_send_json_success(['qr_code_url' => $qr_code_url, 'unique_id' => $unique_id]);
});

// Handle AJAX request for user info with improvements
add_action('wp_ajax_ben_get_user_info', function () {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ben_update_qr_code_nonce')) {
        wp_send_json_error(['msg' => 'Invalid nonce or permission error'], 403);
    }

    $user_id = intval($_POST['user_id']); // Ensure correct user ID is retrieved
    $user = get_userdata($user_id);

    if ($user) {
        global $wpdb;
        $memberships_table = $wpdb->prefix . 'pmpro_memberships_users';

        $membership = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT mu.membership_id, mu.startdate, mu.enddate, mu.initial_payment, mu.billing_amount
                 FROM $memberships_table mu
                 WHERE mu.user_id = %d AND mu.status = 'active'
                 ORDER BY mu.id DESC LIMIT 1",
                $user_id
            )
        );

        // Get user profile image (either from user_meta or default avatar)
        $profile_image = get_user_meta($user_id, 'profile_image', true); // If 'profile_image' exists in user_meta
        if (empty($profile_image)) {
            $profile_image = get_avatar_url($user_id); // Get the default avatar if no profile image is set
        }


        $plan_name = $membership ? pmpro_getLevel($membership->membership_id)->name ?? 'Unknown Plan' : 'No active membership';
        $start_date = $membership && $membership->startdate != '0000-00-00 00:00:00' ? date_i18n('F j, Y', strtotime($membership->startdate)) : 'N/A';
        $end_date = $membership && $membership->enddate != '0000-00-00 00:00:00' ? date_i18n('F j, Y', strtotime($membership->enddate)) : 'N/A';
        $status = $membership && strtotime($membership->enddate) > time() ? 'Active' : 'Expired';
        $initial_payment = $membership && $membership->initial_payment ? number_format($membership->initial_payment, 2) : '0.00';
        $billing_amount = $membership && $membership->billing_amount ? number_format($membership->billing_amount, 2) : '0.00';

        wp_send_json_success([
            'user_id' => $user_id,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'plan' => $plan_name,
            'startdate' => $start_date,
            'enddate' => $end_date,
            'status' => $status,
            'initial_payment' => $initial_payment,
            'billing_amount' => $billing_amount,
            'profile_image' => $profile_image,
        ]);
    } else {
        wp_send_json_error(['msg' => 'User not found.']);
    }
});

