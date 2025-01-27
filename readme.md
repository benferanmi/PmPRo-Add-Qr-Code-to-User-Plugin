## Ben's QR Code Manager

 Description
Ben's QR Code Manager is a custom WordPress plugin designed to generate, manage, and display QR codes linked to user accounts. Seamlessly integrated with the Paid Memberships Pro (PMPro) plugin, this tool provides an efficient way to manage membership details and validate user access via QR codes. It supports real-time scanning and API-based validation for mobile and web-based applications.

 Features
# User-Centric Features
1. Automated QR Code Generation: Each user is issued a unique QR code upon registration or checkout via PMPro.
2. Email Delivery: QR codes are automatically sent to users via email with a personalized message.
3. Encrypted and Secure QR Codes: Ensures user data protection.

# Admin Features
1. PMPro Integration: 
   - Automatically syncs QR codes and membership details with PMPro subscriptions.
   - Displays PMPro-specific details, such as membership plans and payment statuses.
2. Admin Dashboard: 
   - View, search, and manage QR codes and user details.
   - Pagination for handling large user bases efficiently.
3. Membership Data Management:
   - Update membership details like `initial_payment`, `billing_amount`, and expiration dates.
   - Display profile images and payment history for a personalized admin view.
4. Scanner Page:
   - A dedicated page for scanning and validating QR codes in real time.
   - Ideal for gyms, events, or membership-based businesses.
5. Error Handling: Robust feedback for smoother operations.

# Developer Features
1. REST API Integration: 
   - Endpoint: `/wp-json/ben/v1/lookup-user?unique_id={unique-id}`
   - Used for retrieving user details from external systems or mobile apps.
2. Mobile Scanning Support:
   - Integration with the HTTP Shortcuts app for sending scanned QR codes to the API.
   - Compatible with the Binary Eye app for QR code scanning.

 Installation
1. Download and Upload: Download the plugin files and upload them to your `wp-content/plugins/` directory.
2. Activate the Plugin: Navigate to the WordPress admin dashboard and activate the plugin.
3. PMPro Setup: Ensure that the Paid Memberships Pro plugin is installed and configured.

 Usage
# Admin Workflow
1. Access the QR Code Manager dashboard from the WordPress admin panel.
2. Use the search or pagination features to find specific users.
3. Manage QR codes or update user membership information via intuitive modals.
4. Use the scanner page to validate QR codes in real-time.

# Mobile Workflow

Prerequisites
Binary Eye App (available on Google Play or F-Droid) for QR code scanning.
HTTP Shortcuts App (available on Google Play) for sending data to the API.
Steps for Setup
Install the Apps:

- Download and install Binary Eye for scanning QR codes.
- Download and install HTTP Shortcuts for sending scanned data.
- Configure HTTP Shortcuts:

- Open the HTTP Shortcuts app.
- Click the + button to create a new shortcut.
- Set the following fields:
- Name: Enter a name for the shortcut (e.g., "Scan QR Code").
- Method: Select GET or POST (depending on your API endpoint configuration).
- URL: Enter your API endpoint: https://yourdomain.com/wp-json/ben/v1/lookup-user?unique_id={unique-id}.
- In the Request Body (if using POST):
- Add { "unique_id": "{{result}}" } to dynamically pass the scanned QR code.
- Save the shortcut.
- Link HTTP Shortcuts with Binary Eye:

- Open Binary Eye and scan a QR code.
- After scanning, tap the Share button in Binary Eye.
- Select the configured HTTP Shortcut from the share menu.

# Test the Setup:

- Scan a sample QR code.
- Verify that the API responds correctly with user details.
# For Users
- Upon registration, users receive a unique QR code via email.
- Users can use their QR codes for identification or membership access.


 Changelog
# Version 1.5.0
- Final Release: Stable integration with PMPro, enhanced scanner functionality, and mobile support.

# Previous Versions
**1.4.5**: Updated email content and included QR code images in user emails.
- **1.4.4**: Enhanced user profile image display and API responses.
- **1.3.6**: Added REST API-based user data retrieval and scanner page improvements.
- **1.3.1**: Introduced the `/lookup-user` REST API endpoint.
- **1.1.4**: Displayed `initial_payment` and `billing_amount` in user details.
- **1.1.3**: Improved error handling for AJAX requests.

 Upcoming Features
- Advanced analytics and logging for QR code scans.
- Expanded mobile app integration with additional scanning tools.
- Push notifications for membership updates or expirations.

 License
This plugin is open-source and distributed under the [MIT License](https://opensource.org/licenses/MIT).

