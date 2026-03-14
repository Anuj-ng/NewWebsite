<?php
// ── CONFIG ──────────────────────────────────────────────
$to      = "hello@vasuist.com";          // ← your receiving email
$subject = "New Contact Form Submission – Vasuist";
// ────────────────────────────────────────────────────────

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

// Only allow POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit;
}

// ── Sanitize inputs ──────────────────────────────────────
function clean($value)
{
    return htmlspecialchars(strip_tags(trim($value)));
}

$name    = clean($_POST["name"]    ?? "");
$company = clean($_POST["company"] ?? "");
$email   = clean($_POST["email"]   ?? "");
$size    = clean($_POST["size"]    ?? "Not specified");
$message = clean($_POST["message"] ?? "");

// Frameworks checkboxes (array)
$frameworks = [];
if (!empty($_POST["fw"]) && is_array($_POST["fw"])) {
    $allowed = ["iso27001", "soc2", "dpdp", "gdpr", "sebi", "nist", "eucra", "unsure"];
    foreach ($_POST["fw"] as $fw) {
        if (in_array($fw, $allowed)) {
            $frameworks[] = strtoupper($fw);
        }
    }
}
$frameworksStr = !empty($frameworks) ? implode(", ", $frameworks) : "None selected";

// ── Validation ───────────────────────────────────────────
if (empty($name) || empty($company) || empty($email)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Name, company, and email are required."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid email address."]);
    exit;
}

// ── Build email body ─────────────────────────────────────
$body = "
New contact form submission from Vasuist.com

──────────────────────────────
NAME       : {$name}
COMPANY    : {$company}
EMAIL      : {$email}
TEAM SIZE  : {$size}
FRAMEWORKS : {$frameworksStr}
──────────────────────────────

MESSAGE:
{$message}

──────────────────────────────
Sent from: vasuist.com/contact
";

// ── Headers ──────────────────────────────────────────────
$headers  = "From: Vasuist Contact Form <noreply@vasuist.com>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// ── Send ─────────────────────────────────────────────────
$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    echo json_encode(["success" => true, "message" => "Message sent successfully."]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Mail failed. Please try again or email us directly."]);
}
