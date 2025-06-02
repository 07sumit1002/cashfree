<?php
ini_set('display_errors', 0); // Disable in production
error_reporting(E_ALL);

include('connection.php');

$client_secret = 'cfsk_ma_test_8b9ae5f3d8cfd9681ca72645d702bd99_c1b3f5f5';
$raw_body = file_get_contents('php://input');
$headers = getallheaders();
$signature = $headers['x-webhook-signature'] ?? '';
$timestamp = $headers['x-webhook-timestamp'] ?? '';

$signed_payload = $timestamp . $raw_body;
$computed_signature = base64_encode(hash_hmac('sha256', $signed_payload, $client_secret, true));

if ($signature !== $computed_signature) {
    error_log("Invalid signature: Expected $computed_signature, Received $signature");
    http_response_code(400);
    exit;
}

$data = json_decode($raw_body, true);
if ($data['event'] === 'PAYMENT_SUCCESS_WEBHOOK') {
    $order_id = $data['data']['order']['order_id'] ?? '';
    $payment_amount = $data['data']['payment']['payment_amount'] ?? 0;
    $payment_time = $data['data']['payment']['payment_time'] ?? '';
    $transaction_id = $data['data']['payment']['cf_payment_id'] ?? '';

    // Check for duplicate transaction
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM moneyadded WHERE transactionid = ?");
    $checkStmt->bind_param("s", $transaction_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    if ($result->fetch_assoc()['count'] > 0) {
        http_response_code(200);
        exit;
    }
    $checkStmt->close();

    // Fetch retailerId
    $retailerId = '';
    $stmt = $conn->prepare("SELECT retailerId FROM addmoney WHERE id = ? LIMIT 1");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $retailerId = $row['retailerId'];
    }
    $stmt->close();

    // Insert payment record
    $insertStmt = $conn->prepare("INSERT INTO moneyadded (amount, retailerId, transactionid, status, date) VALUES (?, ?, ?, 'approved', ?)");
    $insertStmt->bind_param("ssss", $payment_amount, $retailerId, $transaction_id, $payment_time);
    if ($insertStmt->execute()) {
        http_response_code(200);
    } else {
        error_log("Database insert failed: " . $conn->error);
        http_response_code(500);
    }
    $insertStmt->close();
} else {
    http_response_code(200);
}
?>