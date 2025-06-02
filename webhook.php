<?php
// webhook.php

// Your webhook secret key from Cashfree Dashboard
$client_secret = 'cfsk_ma_test_8b9ae5f3d8cfd9681ca72645d702bd99_c1b3f5f5';

// Read POST body
$raw_body = file_get_contents('php://input');
$headers = getallheaders();

$signature = $headers['x-webhook-signature'] ?? '';
$timestamp = $headers['x-webhook-timestamp'] ?? '';

$signed_payload = $timestamp . $raw_body;
$computed_signature = base64_encode(hash_hmac('sha256', $signed_payload, $client_secret, true));

if ($signature !== $computed_signature) {
    http_response_code(400);
    echo 'Invalid signature';
    exit;
}

// Process webhook data
$data = json_decode($raw_body, true);
if ($data['type'] === 'PAYMENT_SUCCESS_WEBHOOK') {
    // Save transaction to DB or update order status
    file_put_contents("webhook_log.txt", json_encode($data, JSON_PRETTY_PRINT));
}

http_response_code(200);
echo 'Webhook received';
