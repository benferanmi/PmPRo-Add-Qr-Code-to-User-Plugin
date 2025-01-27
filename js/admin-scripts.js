jQuery(document).ready(function ($) {
    // Update QR Code
    $('.update-qr-btn').on('click', function () {
        const userId = $(this).data('user-id');
        const uniqueIdField = $(`.unique-id-input[data-user-id="${userId}"]`);
        $.post(benAjax.ajax_url, {
            action: 'ben_update_qr_code',
            nonce: benAjax.nonce,
            user_id: userId
        })
            .done(function (response) {
                if (response.success) {
                    alert('QR Code updated successfully.');
                    uniqueIdField.val(response.data.unique_id);
                    $(`img[data-user-id="${userId}"]`).attr('src', response.data.qr_code_url);
                } else {
                    alert('Error updating QR Code.');
                }
            })
            .fail(function () {
                alert('Error with server request.');
            });
    });

    // View User Info
    $('.view-info-btn').on('click', function () {
        const userId = $(this).data('user-id'); // Retrieve the correct user ID

        $.post(benAjax.ajax_url, {
            action: 'ben_get_user_info',
            nonce: benAjax.nonce,
            user_id: userId // Send the correct user ID to the server
        })
            .done(function (response) {
                if (response.success) {
                    const data = response.data;

                    // Populate modal fields with correct user data
                    $('#modal-user-id').text(data.user_id);
                    $('#modal-user-name').text(data.name);
                    $('#modal-user-email').text(data.email);
                    $('#modal-user-plan').text(data.plan);
                    $('#modal-user-start').text(data.startdate);
                    $('#modal-user-expiry').text(data.enddate);
                    $('#modal-user-status').text(data.status);
                    $('#modal-user-initial-payment').text(data.initial_payment);
                    $('#modal-user-billing-amount').text(data.billing_amount);
                    $('#modal-user-profile-image').attr('src', data.profile_image);

                    // Populate the response debug area
                    $('#modal-response-debug').val(userId);

                    $('#user-info-modal').fadeIn(); // Show the modal
                } else {
                    alert('Error retrieving user information: ' + response.data);
                }
            })
            .fail(function () {
                alert('Error fetching user info. Please try again.');
            });
    });

    // Close Modal
    $('#close-user-info').on('click', function () {
        $('#user-info-modal').fadeOut();
    });

    // Search filter
    $('#search-user').on('keyup', function () {
        var value = $(this).val().toLowerCase();
        $('#qr-code-manager-table tbody tr').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});
