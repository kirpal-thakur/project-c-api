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
    <form action="<?= base_url('subscription/upgrade') ?>" method="post" id="payment-form">
        <!-- <div class="form-group">
            <label for="email">Email:</label>
            <input type="text" name="email" id="email" placeholder="Enter your email" required>
        </div> -->
        <div class="form-group">
            <label for="plan_id">Select Plan:</label>
            <select name="plan_id" id="plan_id">
                <option value="">Select Plan</option>
                <?php
                    if($packages){
                        foreach($packages as $package){ ?>
                            <option value="<?= $package['stripe_plan_id']; ?>"><?= $package['title'] .' ('. $package['interval'] .')'; ?></option> 
                <?php    }
                    }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="subscription_id">Subscription Id:</label>
            <input type="text" name="subscription_id" id="subscription_id" placeholder="Subscription Id" required>
        </div>

        <div class="form-group">
            <label for="sub_item_id">Subscription Item Id:</label>
            <input type="text" name="sub_item_id" id="sub_item_id" placeholder="Subscription Item Id" required>
        </div>

        <div class="form-group">
            <label for="coupon_code">Coupon Code:</label>
            <input type="text" name="coupon_code" id="coupon_code" placeholder="Coupon Code" >
        </div>
        <button type="submit">Subscribe</button>
    </form>

   
</body>
</html>
