<?php
// create_order.php

$client_id = 'TEST10638123ef47f38254757fcc9e4732183601'; // Replace with your Cashfree Client ID
$client_secret = 'cfsk_ma_test_8b9ae5f3d8cfd9681ca72645d702bd99_c1b3f5f5'; // Replace with your Cashfree Client Secret

$amount = $_POST['amount'] ?? 0;
$email = $_POST['email'] ?? '';

$order_id = "ORDER_" . time(); // Unique order ID
$return_url = "https://yourdomain.com/payment_success.php"; // After payment
$notify_url = "https://yourdomain.com/webhook.php"; // Webhook endpoint

// Step 1: Generate auth token
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://sandbox.cashfree.com/pg/credentials/token",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-client-id: $client_id",
        "x-client-secret: $client_secret"
    ]
]);
$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);
$auth_token = $result['data']['token'] ?? '';

if (!$auth_token) {
    echo json_encode(['error' => 'Unable to fetch token']);
    exit;
}

// Step 2: Create order
$data = [
    "order_amount" => (float) $amount,
    "order_currency" => "INR",
    "order_id" => $order_id,
    "customer_details" => [
        "customer_id" => "cust_" . time(),
        "customer_email" => $email
    ],
    "order_meta" => [
        "return_url" => $return_url,
        "notify_url" => $notify_url
    ]
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://sandbox.cashfree.com/pg/orders",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-api-version: 2022-09-01",
        "Authorization: Bearer $auth_token"
    ]
]);

$response = curl_exec($curl);
curl_close($curl);

echo $response;
