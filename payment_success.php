<?php
// payment_success.php

// DB connection (adjust credentials)
$conn = mysqli_connect("localhost", "root", "", "your_database_name");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Capture GET values
$order_id = $_GET['order_id'] ?? '';
$order_token = $_GET['order_token'] ?? '';
$txStatus = $_GET['txStatus'] ?? '';

// Insert into DB
if (!empty($order_id)) {
    $stmt = mysqli_prepare($conn, "INSERT INTO payment_logs (order_id, order_token, tx_status) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $order_id, $order_token, $txStatus);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Result</title>
</head>
<body>
    <h2>Cashfree Sandbox Payment Status</h2>

    <?php if ($txStatus === 'SUCCESS'): ?>
        <p style="color: green;"><strong>Payment Successful!</strong></p>
    <?php elseif ($txStatus === 'FAILED'): ?>
        <p style="color: red;"><strong>Payment Failed.</strong></p>
    <?php else: ?>
        <p style="color: orange;"><strong>Status: <?= htmlspecialchars($txStatus) ?></strong></p>
    <?php endif; ?>

    <h3>Details</h3>
    <ul>
        <li><strong>Order ID:</strong> <?= htmlspecialchars($order_id) ?></li>
        <li><strong>Transaction Status:</strong> <?= htmlspecialchars($txStatus) ?></li>
        <li><strong>Order Token:</strong> <?= htmlspecialchars($order_token) ?></li>
    </ul>
</body>
</html>
