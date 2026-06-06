<?php
// submit_contact.php

// Prevent any output before JSON
ob_start();

// Configure error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set Timezone to India Standard Time
date_default_timezone_set('Asia/Kolkata');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    // Only process POST requests
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method. Please use POST.");
    }

    // Get the form fields
    $name = strip_tags(trim($_POST["name"] ?? ''));
    $email = filter_var(trim($_POST["email"] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = strip_tags(trim($_POST["phone"] ?? ''));
    $company = strip_tags(trim($_POST["company"] ?? ''));
    $subject = strip_tags(trim($_POST["subject"] ?? ''));
    $message = trim($_POST["message"] ?? '');
    $other_subject = strip_tags(trim($_POST["other_subject"] ?? ''));
    // Handle checkbox (might not be sent if unchecked)
    $newsletter = isset($_POST["newsletter"]) ? "Yes" : "No";

    // Combine subject if 'other' is selected
    $finalSubject = $subject;
    if ($subject === 'other' && !empty($other_subject)) {
        $finalSubject = 'Other: ' . $other_subject;
    }

    // Basic Validation
    if (empty($name) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($phone) || empty($company) || ($subject === 'other' && empty($other_subject))) {
        throw new Exception("Please fill in all required fields (Name, Email, Phone, Company, Message) correctly.");
    }

    // Define file path
    $uploadDir = __DIR__ . '/assets';
    $jsonFile = $uploadDir . '/contact_submissions.json';

    // Ensure directory exists
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception("Failed to create storage directory.");
        }
    }

    // SECURITY: Create .htaccess to prevent public access to the JSON file
    $htaccessFile = $uploadDir . '/.htaccess';
    $htaccessContent = "<FilesMatch \"\\.(json|log)$\">\n    Require all denied\n</FilesMatch>";
    
    // Create or update if it's the old blocking version
    if (!file_exists($htaccessFile) || trim(file_get_contents($htaccessFile)) !== trim($htaccessContent)) {
        file_put_contents($htaccessFile, $htaccessContent);
    }

    // Load existing data
    $submissions = [];
    if (file_exists($jsonFile)) {
        $jsonContent = file_get_contents($jsonFile);
        $submissions = json_decode($jsonContent, true);
        if (!is_array($submissions)) {
            $submissions = [];
        }
    }

    // Prepare new entry
    $newEntry = [
        'id' => uniqid('msg_', true),
        'date' => date('Y-m-d H:i:s'),
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'company' => $company,
        'subject' => $finalSubject,
        'message' => $message,
        'newsletter' => $newsletter
    ];

    // Prepend to array (newest first)
    array_unshift($submissions, $newEntry);

    // Save to file (Secure Backup)
    if (file_put_contents($jsonFile, json_encode($submissions, JSON_PRETTY_PRINT | LOCK_EX)) === false) {
        throw new Exception("Failed to write to storage file.");
    }

    $response['success'] = true;
    $response['message'] = "Thank you! We have received your message and will get back to you shortly.";

} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Clear buffer and output JSON
ob_end_clean();
echo json_encode($response);
exit;
?>