
 
<?php
// webhook.php
// $client_id = "675981d68b89d3fe882b1de25c189576";
// $client_secret = "cfsk_ma_prod_3359a871c0d4920d8cb750355e34e61c_1c215b44";
// $client_id = "TEST10638123ef47f38254757fcc9e4732183601";
// $client_secret = "cfsk_ma_test_8b9ae5f3d8cfd9681ca72645d702bd99_c1b3f5f5"; 
// Enable error reporting for debugging (disable in production)

// createorder.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

include('connection.php');

$client_id = 'TEST10638123ef47f38254757fcc9e4732183601';
$client_secret = 'cfsk_ma_test_8b9ae5f3d8cfd9681ca72645d702bd99_c1b3f5f5';
$api_version = '2023-08-01';
$environment = 'sandbox';
$base_url = $environment === 'production' ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';

$amount = $_POST['amount'] ?? null;
$email = $_POST['email'] ?? null;
$phone = $_POST['phone'] ?? '9999999999';

if (!$amount || !is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid phone number']);
    exit;
}

$order_id = 'order_' . uniqid();
$customer_id = preg_replace('/[^a-zA-Z0-9_-]/', '_', $email);

$order_data = [
    'order_id' => $order_id,
    'order_amount' => (float)$amount,
    'order_currency' => 'INR',
    'customer_details' => [
        'customer_id' => $customer_id,
        'customer_email' => $email,
        'customer_phone' => $phone,
        'customer_name' => 'Customer'
    ],
    'order_meta' => [
        'return_url' => 'http://localhost/webazu2/AddMoney.php?msg=success'.$order_id,
        // 'notify_url' => 'http://localhost/webazu2/webhook.php'
    ],
    'order_note' => 'Add Money'
];

$ch = curl_init("$base_url/orders");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-version: ' . $api_version,
    'x-client-id: ' . $client_id,
    'x-client-secret: ' . $client_secret
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($response === false) {
    error_log("cURL error: " . curl_error($ch));
    http_response_code(500);
    echo json_encode(['error' => 'Failed to communicate with Cashfree']);
    curl_close($ch);
    exit;
}
curl_close($ch);

if ($http_code === 200) {
    $response_data = json_decode($response, true);
    if (isset($response_data['payment_session_id'])) {
        $currentDateTime = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO addmoney (id, retailerId, amount, status, date) VALUES (?, ?, ?, 'pending', ?)");
        $stmt->bind_param("ssss", $order_id, $email, $amount, $currentDateTime);
        $stmt->execute();
        $stmt->close();
        echo json_encode([
            'payment_session_id' => $response_data['payment_session_id'],
            'order_id' => $order_id
        ]);
    } else {
        error_log("Invalid response from Cashfree: " . $response);
        http_response_code(500);
        echo json_encode(['error' => 'Invalid response from Cashfree']);
    }
} else {
    error_log("Cashfree API error: " . $response);
    http_response_code($http_code);
    echo $response;
}
?>