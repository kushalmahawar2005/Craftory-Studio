<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Log received data for debugging
$raw_data = file_get_contents('php://input');
error_log("Received data: " . $raw_data);

// Get POST data
$data = json_decode($raw_data, true);

// Validate required fields
$required_fields = ['name', 'email', 'phone', 'project_details', 'budget'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit();
    }
}

// Sanitize input data
$name = filter_var($data['name'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
$project_details = filter_var($data['project_details'], FILTER_SANITIZE_STRING);
$budget = filter_var($data['budget'], FILTER_SANITIZE_STRING);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit();
}

// Validate phone number (10 digits)
if (!preg_match('/^\d{10}$/', preg_replace('/[^0-9]/', '', $phone))) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid phone number format']);
    exit();
}

// Create email content
$to = "your-email@example.com"; // Replace with your email
$subject = "New Project Request from $name";

$message = "
<html>
<head>
    <title>New Project Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 5px; }
        .content { padding: 20px; }
        .footer { text-align: center; padding: 20px; color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Project Request</h2>
        </div>
        <div class='content'>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Phone:</strong> $phone</p>
            <p><strong>Budget:</strong> $budget</p>
            <p><strong>Project Details:</strong></p>
            <p>$project_details</p>
        </div>
        <div class='footer'>
            <p>This is an automated message from your website contact form.</p>
        </div>
    </div>
</body>
</html>
";

// Set email headers
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: $email" . "\r\n";
$headers .= "Reply-To: $email" . "\r\n";

// Send email
try {
    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
    } else {
        throw new Exception('Failed to send email');
    }
} catch (Exception $e) {
    error_log("Email error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send email: ' . $e->getMessage()]);
}
?> 