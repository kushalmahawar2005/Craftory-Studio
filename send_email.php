<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for CORS and JSON response
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get raw POST data
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);
    
    // Log received data for debugging
    error_log('Received data: ' . print_r($data, true));
    
    // Validate required fields
    $requiredFields = ['name', 'email', 'phone', 'projectDetails', 'budget', 'serviceName'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Sanitize input data
    $name = htmlspecialchars($data['name']);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $phone = preg_replace('/[^0-9]/', '', $data['phone']);
    $projectDetails = htmlspecialchars($data['projectDetails']);
    $budget = htmlspecialchars($data['budget']);
    $serviceName = htmlspecialchars($data['serviceName']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Validate phone number
    if (strlen($phone) !== 10) {
        throw new Exception('Phone number must be 10 digits');
    }
    
    // Create email content
    $to = "hello@craftory.studio"; // Replace with your email
    $subject = "New Project Request: $serviceName";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #f8f9fa; padding: 20px; border-radius: 5px; }
            .content { padding: 20px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Project Request</h2>
                <p><strong>Service:</strong> $serviceName</p>
            </div>
            <div class='content'>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Phone:</strong> $phone</p>
                <p><strong>Budget Range:</strong> $budget</p>
                <p><strong>Project Details:</strong></p>
                <p>$projectDetails</p>
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
    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
    } else {
        throw new Exception('Failed to send email');
    }
    
} catch (Exception $e) {
    error_log('Error in send_email.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 