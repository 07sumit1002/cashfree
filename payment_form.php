<!-- payment_form.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Cashfree Payment Demo</title>
</head>
<body>
    <h2>Make a Payment</h2>
    <form id="payForm">
        <label>Amount:</label>
        <input type="number" name="amount" id="amount" required><br><br>
        <label>Email:</label>
        <input type="email" name="email" id="email" required><br><br>
        <button type="submit">Pay Now</button>
    </form>

    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <script>
        const cashfree = Cashfree({ mode: "sandbox" }); // Change to "production" when live

        document.getElementById('payForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const amount = document.getElementById('amount').value;
            const email = document.getElementById('email').value;

            const response = await fetch('create_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `amount=${amount}&email=${email}`
            });

            const data = await response.json();

            if (data.payment_session_id) {
                cashfree.checkout({
                    paymentSessionId: data.payment_session_id,
                    redirectTarget: "_self"
                });
            } else {
                alert("Error: " + (data.message || "Could not create order"));
                console.log(data);
            }
        });
    </script>
</body>
</html>
