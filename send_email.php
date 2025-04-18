<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for CORS and JSON response
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get raw POST data
        $rawData = file_get_contents('php://input');
        
        // Log received data for debugging
        error_log("Received data: " . $rawData);
        
        // Decode JSON data
        $data = json_decode($rawData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data: ' . json_last_error_msg());
        }
        
        // Validate required fields
        $requiredFields = ['name', 'email', 'phone', 'projectDetails', 'budget', 'serviceName'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Sanitize input data
        $name = htmlspecialchars($data['name']);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars($data['phone']);
        $projectDetails = htmlspecialchars($data['projectDetails']);
        $budget = htmlspecialchars($data['budget']);
        $serviceName = htmlspecialchars($data['serviceName']);
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';  // Replace with your SMTP host
            $mail->SMTPAuth = true;
            $mail->Username = 'kushalmahawar114@gmail.com';  // Replace with your email
            $mail->Password = 'Kbmbjy11';     // Replace with your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('noreply@craftory.studio', 'Craftory Studio');
            $mail->addAddress('kushalmahawar114@gmail.com');
            $mail->addReplyTo($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "New Project Inquiry - " . $serviceName;
            
            // Email content with HTML formatting
            $mail->Body = "
            <html>
            <head>
                <title>New Project Inquiry</title>
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
                        <h2>New Project Inquiry Details</h2>
                    </div>
                    <div class='content'>
                        <p><strong>Service/Package:</strong> {$serviceName}</p>
                        <p><strong>Name:</strong> {$name}</p>
                        <p><strong>Email:</strong> {$email}</p>
                        <p><strong>Phone:</strong> {$phone}</p>
                        <p><strong>Budget Range:</strong> {$budget}</p>
                        <p><strong>Project Details:</strong></p>
                        <p>{$projectDetails}</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message from your website contact form.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->AltBody = "
            New Project Inquiry Details\n\n
            Service/Package: {$serviceName}\n
            Name: {$name}\n
            Email: {$email}\n
            Phone: {$phone}\n
            Budget Range: {$budget}\n
            Project Details: {$projectDetails}
            ";

            $mail->send();
            
            echo json_encode([
                'success' => true,
                'message' => 'Email sent successfully'
            ]);
            
        } catch (Exception $e) {
            throw new Exception("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
        
    } catch (Exception $e) {
        error_log("Error in send_email.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 