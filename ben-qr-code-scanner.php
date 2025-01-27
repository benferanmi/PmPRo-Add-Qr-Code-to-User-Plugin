<?php

// Ensure this file is accessed through WordPress only
defined('ABSPATH') or die('No script kiddies please!');

// Enqueue html5-qrcode library for the QR code scanner page
add_action('admin_enqueue_scripts', function ($hook_suffix) {
    if ($hook_suffix === 'toplevel_page_ben-qr-code-scanner') {
        wp_enqueue_script('html5-qrcode', 'https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js', [], null, true);
    }
});

// Create the QR code scanner page content
function ben_qr_code_scanner_page()
{
    if (!current_user_can('administrator') && !current_user_can('manage_qr_codes')) {
        wp_die('You do not have permission to access this page.');
    }
    ?>
    <div class="wrap benwrap">
        <h1>QR Code Scanner</h1>
        <div class="benw-spinner">
            <div id="restapi_loader" style="display: none; margin-top: 10px;">
                <img src="https://cdn.pixabay.com/animation/2022/10/11/03/16/03-16-39-160_512.gif" alt="Loading...">
            </div>

        </div>

        <div class="brrcs-head">
            <div>
                <!-- Option to start/stop scanning or input unique ID -->
                <button id="scan-btn" class="button-primary">Start Scanning</button>
                <button id="stop-btn" class="button-secondary" style="display: none;">Stop Scanning</button>
            </div>

            <p><strong>OR</strong></p>

            <div class="brrcs-head-r">
                <label for="unique-id-input">Enter Unique ID: </label>
                <input type="text" id="unique-id-input" class="regular-text" placeholder="Enter Unique ID" />
                <button id="lookup-btn" class="button-primary">Look Up User</button>
            </div>
        </div>
        <!-- Loading indicator -->
        <div id="loading-indicator" style="display: none;">Loading...</div>


        <!-- Scanner preview -->
        <div id="preview" style="width: 300px; height: 200px; margin-top: 20px; border: 1px solid #ccc;"></div>

        <div class="ben-userinfo">
            <div class="benui-each">
                <!-- Fetch and display the user information from the database -->
                <?php ben_display_user_info_from_db(); ?>
            </div>

            <!-- User information display -->
            <div class="benui-each">
                <div>
                    <h2>User Information from inputted Unique Id, display below</h2>
                </div>
                <br>
                <div id="user-info" style="display: none; margin-top: 20px;">
                    <h3>User Information</h3>
                    <p><strong>User ID:</strong> <span id="modal-user-id"></span></p>
                    <div class="uim_user">
                        <img id="modal-user-profile-image" src="" alt="User Profile Picture" />
                    </div>
                    <p><strong>Name:</strong> <span id="modal-user-name"></span></p>
                    <p><strong>Email:</strong> <span id="modal-user-email"></span></p>
                    <p><strong>Plan:</strong> <span id="modal-user-plan"></span></p>
                    <p><strong>Start Date:</strong> <span id="modal-user-start"></span></p>
                    <p><strong>Expiry Date:</strong> <span id="modal-user-expiry"></span></p>
                    <p><strong>Status:</strong> <span id="modal-user-status"></span></p>
                    <p><strong>Initial Payment:</strong> <span id="modal-user-initial-payment"></span></p>
                    <p><strong>Billing Amount:</strong> <span id="modal-user-billing-amount"></span></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let qrCodeScanner;

        // Start scanning
        document.getElementById('scan-btn').addEventListener('click', function () {
            try {
                if (!qrCodeScanner) {
                    qrCodeScanner = new Html5Qrcode("preview");
                }

                document.getElementById('scan-btn').style.display = 'none';
                document.getElementById('stop-btn').style.display = 'inline-block';

                qrCodeScanner.start(
                    { facingMode: "environment" }, // Use the back camera
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText, decodedResult) => {
                        try {
                            if (!/^[a-zA-Z0-9]{8}$/.test(decodedText)) {
                                alert('Invalid QR Code format.');
                                qrCodeScanner.stop();
                                document.getElementById('scan-btn').style.display = 'inline-block';
                                document.getElementById('stop-btn').style.display = 'none';
                                return;
                            }
                            qrCodeScanner.stop(); // Stop scanning after a successful decode
                            document.getElementById('scan-btn').style.display = 'inline-block';
                            document.getElementById('stop-btn').style.display = 'none';

                            // Fetch user information using the decoded unique ID
                            fetchUserInfo(decodedText);
                        } catch (innerError) {
                            console.error("Error processing QR Code:", innerError);
                            alert("An error occurred while processing the QR Code. Please try again.");
                        }
                    },
                    (errorMessage) => {
                        console.warn(`QR Code scan error: ${errorMessage}`);
                    }
                ).catch((err) => {
                    console.error(`Error starting QR scanner: ${err}`);
                    alert("QR Code scanning feature is unavailable. Please try again later.");
                });
            } catch (outerError) {
                console.error("Error initializing QR Code scanner:", outerError);
                alert("An unexpected error occurred while starting the scanner. Please refresh the page and try again.");
            }
        });

        // Stop scanning
        document.getElementById('stop-btn').addEventListener('click', function () {
            try {
                if (qrCodeScanner) {
                    qrCodeScanner.stop().then(() => {
                        document.getElementById('preview').innerHTML = ''; // Clear preview
                        document.getElementById('scan-btn').style.display = 'inline-block';
                        document.getElementById('stop-btn').style.display = 'none';
                    }).catch((err) => {
                        console.error(`Error stopping QR scanner: ${err}`);
                        alert("An error occurred while stopping the scanner. Please try again.");
                    });
                }
            } catch (error) {
                console.error("Error during scanner stop operation:", error);
                alert("An unexpected error occurred while stopping the scanner.");
            }
        });

        // Manually lookup user by entering Unique ID
        document.getElementById('lookup-btn').addEventListener('click', function () {
            try {
                const uniqueId = document.getElementById('unique-id-input').value;
                if (uniqueId) {
                    lookupUserInfo(uniqueId);
                } else {
                    alert("Please enter a Unique ID to proceed.");
                }
            } catch (error) {
                console.error("Error initiating user lookup:", error);
                alert("An unexpected error occurred. Please try again.");
            }
        });

        function lookupUserInfo(uniqueId) {
            try {
                document.getElementById('restapi_loader').style.display = 'block';
                const data = {
                    action: 'ben_scanner_get_user_info',
                    unique_id: uniqueId,
                    nonce: '<?php echo wp_create_nonce("ben_update_qr_code_nonce"); ?>'
                };

                jQuery.post(ajaxurl, data, function (response) {
                    try {
                        if (response.success) {
                            document.getElementById('restapi_loader').style.display = 'none';
                            const user = response.data;
                            document.getElementById('user-info').style.display = 'block';
                            document.getElementById('modal-user-id').innerText = user.user_id;
                            document.getElementById('modal-user-name').innerText = user.name;
                            document.getElementById('modal-user-email').innerText = user.email;
                            document.getElementById('modal-user-plan').innerText = user.plan;
                            document.getElementById('modal-user-start').innerText = user.startdate;
                            document.getElementById('modal-user-expiry').innerText = user.enddate;
                            document.getElementById('modal-user-status').innerText = user.status;
                            document.getElementById('modal-user-initial-payment').innerText = user.initial_payment;
                            document.getElementById('modal-user-billing-amount').innerText = user.billing_amount;
                            // Update the profile image
                            document.getElementById('modal-user-profile-image').setAttribute('src', user.profile_image);
                        } else {
                            document.getElementById('restapi_loader').style.display = 'none';
                            alert(response.data && response.data.msg ? response.data.msg : 'User not found.');
                        }
                    } catch (innerError) {
                        console.error("Error processing user information response:", innerError);
                        alert("An error occurred while processing the user information.");
                    }
                }).fail(function () {
                    alert('An error occurred. Please try again.');
                });
            } catch (error) {
                console.error("Error during user lookup:", error);
                document.getElementById('restapi_loader').style.display = 'none';
                alert("An unexpected error occurred while fetching user information.");
            }
        }
    </script>

    <?php
}


// Register the QR code scanner admin page
add_action('admin_menu', function () {
    add_menu_page('QR Code Scanner', 'QR Scanner', 'manage_qr_codes_scanner', 'ben-qr-code-scanner', 'ben_qr_code_scanner_page');
});

// Add AJAX handler for fetching user info by unique ID
add_action('wp_ajax_ben_scanner_get_user_info', function () {
    try {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ben_update_qr_code_nonce')) {
            wp_send_json_error(['msg' => 'Invalid nonce or permission error'], 403);
        }

        $unique_id = sanitize_text_field($_POST['unique_id']);
        $user_query = new WP_User_Query([
            'meta_key' => 'unique_id',
            'meta_value' => $unique_id,
            'number' => 1
        ]);

        if ($user_query->get_total() > 0) {
            $user = $user_query->get_results()[0];

            global $wpdb;
            $memberships_table = $wpdb->prefix . 'pmpro_memberships_users';

            // Fetch membership data
            $membership = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT mu.membership_id, mu.startdate, mu.enddate, mu.initial_payment, mu.billing_amount
                    FROM $memberships_table mu
                    WHERE mu.user_id = %d AND mu.status = 'active'
                    ORDER BY mu.id DESC LIMIT 1",
                    $user->ID
                )
            );

            // Get user profile image (either from user_meta or default avatar)
            $profile_image = get_user_meta($user->ID, 'profile_image', true); // If 'profile_image' exists in user_meta
            if (empty($profile_image)) {
                $profile_image = get_avatar_url($user->ID); // Get the default avatar if no profile image is set
            }

            $response_data = [
                'user_id' => $user->ID,
                'name' => $user->display_name,
                'profile_image' => $profile_image, // Include profile image in the response
                'email' => $user->user_email,
                'plan' => $membership ? pmpro_getLevel($membership->membership_id)->name ?? 'Unknown Plan' : 'No active membership',
                'startdate' => $membership && $membership->startdate != '0000-00-00 00:00:00' ? date_i18n('F j, Y', strtotime($membership->startdate)) : 'N/A',
                'enddate' => $membership && $membership->enddate != '0000-00-00 00:00:00' ? date_i18n('F j, Y', strtotime($membership->enddate)) : 'N/A',
                'status' => $membership && strtotime($membership->enddate) > time() ? 'Active' : 'Expired',
                'initial_payment' => $membership && $membership->initial_payment ? number_format($membership->initial_payment, 2) : '0.00',
                'billing_amount' => $membership && $membership->billing_amount ? number_format($membership->billing_amount, 2) : '0.00',
            ];

            wp_send_json_success($response_data);
        } else {
            wp_send_json_error(['msg' => 'User not found.']);
        }

    } catch (Exception $e) {
        // Log the error and send a generic error message
        error_log("Error fetching user info: " . $e->getMessage());
        wp_send_json_error(['msg' => 'An unexpected error occurred. Please try again later.']);
    }
});

// Custom authentication function
function ben_api_permission_callback(WP_REST_Request $request)
{
    try {
        $auth_token = $request->get_header('Authorization');

        // Check if the Authorization header exists
        if (!$auth_token || strpos($auth_token, 'Bearer ') !== 0) {
            return new WP_Error('rest_forbidden', 'Missing or invalid Authorization token', ['status' => 403]);
        }

        // Extract the token from the header
        $token = str_replace('Bearer ', '', $auth_token);

        // Validate the token (replace this with your token validation logic)
        $valid_token = '78y87y875487hfhfdhgute8ut478854';

        // Check if the token is valid
        if ($token !== $valid_token) {
            return new WP_Error('rest_forbidden', 'Invalid token', ['status' => 403]);
        }

        // Return true if the token is valid
        return true;

    } catch (Exception $e) {
        // Log the error for debugging purposes
        error_log('Error in ben_api_permission_callback: ' . $e->getMessage());

        // Return a generic error response
        return new WP_Error('rest_forbidden', 'An error occurred during token validation', ['status' => 500]);
    }
}


// Register REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route('ben/v1', '/lookup-user', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'ben_lookup_user_via_api',
        'permission_callback' => 'ben_api_permission_callback', // Use custom authentication
    ]);
});

// Fetch and display the latest user info from the 'restapi_user_info' table
function ben_display_user_info_from_db()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'restapi_user_info';

    try {
        // Initial output container
        echo '<div id="restapi_content_wrapper">';
        echo '<div><h2>Display User Information From Scanned QR Code</h2></div>';
        echo '<br/>';
        ben_render_user_info();
        echo '</div>';

        // Add Refresh and Clear buttons with loader
        echo '<div id="restapi_controls" style="margin-top: 20px;">';
        echo '<button id="restapi_refresh" onclick="refreshUserInfo()">Refresh</button>';
        echo '<button id="restapi_clear" onclick="clearUserInfo()">Clear</button>';
        echo '<div id="restapi_loader" style="display: none; margin-top: 10px;">Loading...</div>'; // Loader
        echo '</div>';

    } catch (Exception $e) {
        // Catch any errors during database query or rendering
        error_log("Error fetching or displaying user info: " . $e->getMessage());
        echo '<div>Error occurred while fetching user data. Please try again later.</div>';
    }

    // Add JavaScript for button functionality
    ?>
    <script>
        function refreshUserInfo() {
            // Show loader
            document.getElementById('restapi_loader').style.display = 'block';

            // Make an AJAX request to fetch the latest user info
            jQuery.post(ajaxurl, { action: 'ben_refresh_user_info' }, function (response) {
                try {
                    if (response.success) {
                        document.getElementById('restapi_content_wrapper').innerHTML = response.data.html;
                    } else {
                        alert('Failed to refresh user info. Please try again.');
                    }
                } catch (e) {
                    console.error('Error in processing the response:', e);
                    alert('An unexpected error occurred while processing the user info.');
                }
            }).fail(function () {
                alert('An error occurred while refreshing user info.');
            }).always(function () {
                // Hide loader
                document.getElementById('restapi_loader').style.display = 'none';
            });
        }

        function clearUserInfo() {
            // Clear the displayed content
            document.getElementById('restapi_content_wrapper').innerHTML = '';
        }
    </script>

    <?php
}


// Helper function to render user info
function ben_render_user_info()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'restapi_user_info';

    try {
        // Fetch the latest record
        $user_info_row = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");

        if ($user_info_row) {
            // Try to decode the JSON data
            $user_info = json_decode($user_info_row->user_info, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Handle JSON decoding error
                throw new Exception("Error decoding user info JSON: " . json_last_error_msg());
            }

            // Output the user information as needed
            echo '<div id="restapi_content"><h3>User Info</h3>';
            echo '<p><strong>User ID:</strong> ' . esc_html($user_info['user_id']) . '</p>';
            echo '<div class="uim_user"><img src="' . esc_html($user_info['profile_image']) . '" class="modal-user-profile-image" alt="User Profile Image" /></div>';
            echo '<p><strong>Name:</strong> ' . esc_html($user_info['name']) . '</p>';
            echo '<p><strong>Email:</strong> ' . esc_html($user_info['email']) . '</p>';
            echo '<p><strong>Plan:</strong> ' . esc_html($user_info['plan']) . '</p>';
            echo '<p><strong>Start Date:</strong> ' . esc_html($user_info['startdate']) . '</p>';
            echo '<p><strong>Expiry Date:</strong> ' . esc_html($user_info['enddate']) . '</p>';
            echo '<p><strong>Status:</strong> ' . esc_html($user_info['status']) . '</p>';
            echo '<p><strong>Initial Payment:</strong> ' . esc_html($user_info['initial_payment']) . '</p>';
            echo '<p><strong>Billing Amount:</strong> ' . esc_html($user_info['billing_amount']) . '</p>';
            echo '</div>';
            echo '<div id="restapi_unique_id">';
            echo '<p><strong>Unique ID:</strong> ' . esc_html($user_info_row->unique_id) . '</p>';
            echo '</div>';
        } else {
            echo '<p>No user information found.</p>';
        }
    } catch (Exception $e) {
        // Handle errors during database retrieval or JSON decoding
        error_log("Error fetching or rendering user info: " . $e->getMessage());
        echo '<p>Error occurred while fetching user data. Please try again later.</p>';
    }
}

// Register the AJAX handler for refreshing user info
add_action('wp_ajax_ben_refresh_user_info', 'ben_refresh_user_info_callback');

function ben_refresh_user_info_callback()
{
    ob_start();
    ben_render_user_info();
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}

// REST API callback function
function ben_lookup_user_via_api(WP_REST_Request $request)
{
    global $wpdb;

    try {
        // Get the unique_id parameter and sanitize it
        $unique_id = sanitize_text_field($request->get_param('unique_id'));

        // Validate unique_id
        if (empty($unique_id) || strlen($unique_id) !== 8) {
            return new WP_REST_Response(['error' => 'Unique ID must be exactly 8 characters long'], 400);
        }

        // Query the user by unique_id
        $user_query = new WP_User_Query([
            'meta_key' => 'unique_id',
            'meta_value' => $unique_id,
            'number' => 1
        ]);

        if ($user_query->get_total() > 0) {
            $user = $user_query->get_results()[0];
            $memberships_table = $wpdb->prefix . 'pmpro_memberships_users';

            // Retrieve membership details
            $membership = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT mu.membership_id, mu.startdate, mu.enddate, mu.initial_payment, mu.billing_amount
                    FROM $memberships_table mu
                    WHERE mu.user_id = %d AND mu.status = 'active'
                    ORDER BY mu.id DESC LIMIT 1",
                    $user->ID
                )
            );

            // Get profile image
            $profile_image = get_user_meta($user->ID, 'profile_image', true) ?: get_avatar_url($user->ID);

            // Prepare response data
            $response_data = [
                'unique_id' => $unique_id,
                'user_id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'profile_image' => $profile_image,
                'plan' => $membership ? pmpro_getLevel($membership->membership_id)->name ?? 'Unknown Plan' : 'No active membership',
                'startdate' => $membership && $membership->startdate != '0000-00-00 00:00:00' ? date_i18n('F j, Y', strtotime($membership->startdate)) : 'N/A',
                'enddate' => $membership && $membership->enddate != '0000-00-00 00:00:00' ? date_i18n('F j, Y', strtotime($membership->enddate)) : 'N/A',
                'status' => $membership && strtotime($membership->enddate) > time() ? 'Active' : 'Expired',
                'initial_payment' => $membership && $membership->initial_payment ? number_format($membership->initial_payment, 2) : '0.00',
                'billing_amount' => $membership && $membership->billing_amount ? number_format($membership->billing_amount, 2) : '0.00',
            ];

            // Convert response data to JSON and update the database
            $user_info_json = wp_json_encode($response_data);

            // Database operations (clearing and inserting data)
            $table_name = $wpdb->prefix . 'restapi_user_info';

            // Clear the table before inserting the new data
            $wpdb->query("TRUNCATE TABLE $table_name");

            // Insert the new user info into the table
            $wpdb->insert(
                $table_name,
                [
                    'user_info' => $user_info_json,
                    'unique_id' => $unique_id,
                ],
                [
                    '%s', // user_info
                    '%s', // unique_id
                ]
            );

            return new WP_REST_Response($response_data, 200);
        } else {
            return new WP_REST_Response(['error' => 'User not found.'], 404);
        }
    } catch (Exception $e) {
        // Log the error
        error_log('Error in ben_lookup_user_via_api: ' . $e->getMessage());

        // Send an error response
        return new WP_REST_Response(['error' => 'An unexpected error occurred. Please try again later.'], 500);
    }
}
