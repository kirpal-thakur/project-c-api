<!-- app/Views/subscribe.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Subscribe</title>
    <style>
        #card-element {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
        }
        .StripeElement {
            width: 100%;
        }
        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h2>Subscribe to a Plan</h2>
    <form action="<?= base_url('subscription/cancel') ?>" method="post" id="payment-form">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" placeholder="Enter your email" required>
        </div>
        
        <div class="form-group">
            <label for="subscription_id">Subscription ID:</label>
            <input type="text" name="subscription_id" id="subscription_id" placeholder="Enter your subscription id" required>
            <!-- <input type="text" name="user_id" id="user_id" placeholder="Enter user_id" required> -->
        </div>
        
        <button type="submit">Cancel Subscription</button>
    </form>
</body>
</html>
