
<?php
// sample-download.php
// POST fields: first_name, last_name, email, phone, country, company, use_case
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = 'info@visionglobalbpo.com';
    $first = htmlspecialchars($_POST['first_name'] ?? '');
    $last  = htmlspecialchars($_POST['last_name']  ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = htmlspecialchars($_POST['phone']   ?? '');
    $country = htmlspecialchars($_POST['country'] ?? '');
    $company = htmlspecialchars($_POST['company'] ?? '');
    $use_case = htmlspecialchars($_POST['use_case'] ?? '');
    if (!$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email']);
        exit;
    }
    // Internal notification
    $subject = "New Sample Dataset Request — $first $last";
    $body = "Name: $first $last\nEmail: $email\nPhone: $phone\nCountry: $country\nCompany: $company\nUse Case: $use_case";
    mail($to, $subject, $body, "From: noreply@visionglobalbpo.com");
    // Send dataset link to user
    $user_subject = "Your Free Sample Annotated Dataset — Vision Global";
    $user_body = "Hi $first,\n\nThank you for your interest! Here is your download link for the 50-image COCO JSON sample dataset:\n\n[INSERT DOWNLOAD LINK HERE]\n\nIf you have any questions or want to discuss your annotation project, reply to this email or call us at +1 (408)-878-6865.\n\nBest,\nVision Global Team";
    mail($email, $user_subject, $user_body, "From: info@visionglobalbpo.com");
    http_response_code(200);
    echo json_encode(['success' => true]);
}
?>