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
<body class="testsssssssssssssssssssssssss">

    <!-- <div id="mc_embed_shell">
        <link href="//cdn-images.mailchimp.com/embedcode/classic-061523.css" rel="stylesheet" type="text/css">
    <style type="text/css">
            #mc_embed_signup{background:#fff; false;clear:left; font:14px Helvetica,Arial,sans-serif; width: 600px;}
            /* Add your own Mailchimp form style overrides in your site stylesheet or in this style block.
            We recommend moving this block and the preceding CSS link to the HEAD of your HTML file. */
    </style>
    <div id="mc_embed_signup">
        <form action="https://socceryou.us17.list-manage.com/subscribe/post?u=164492b9dabe11b25be31a8ec&amp;id=7afbbb070a&amp;f_id=0044c2e1f0" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank">
            <div id="mc_embed_signup_scroll"><h2>Subscribe</h2>
                <div class="indicates-required"><span class="asterisk">*</span> indicates required</div>
                <div class="mc-field-group"><label for="mce-EMAIL">E-Mail-Adresse <span class="asterisk">*</span></label><input type="email" name="EMAIL" class="required email" id="mce-EMAIL" required="" value=""></div>
            <div id="mce-responses" class="clear">
                <div class="response" id="mce-error-response" style="display: none;"></div>
                <div class="response" id="mce-success-response" style="display: none;"></div>
            </div><div aria-hidden="true" style="position: absolute; left: -5000px;"><input type="text" name="b_164492b9dabe11b25be31a8ec_7afbbb070a" tabindex="-1" value=""></div><div class="clear"><input type="submit" name="subscribe" id="mc-embedded-subscribe" class="button" value="Subscribe"></div>
        </div>
    </form>
    </div>
    <script type="text/javascript" src="//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js"></script><script type="text/javascript">(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';fnames[3]='ADDRESS';ftypes[3]='address';fnames[4]='PHONE';ftypes[4]='phone';fnames[5]='BIRTHDAY';ftypes[5]='birthday';}(jQuery));var $mcj = jQuery.noConflict(true);</script></div> -->



    <h2>Subscribe to a Plan</h2>
    <form action="<?= base_url('subscription/subscribe') ?>" method="post" id="payment-form">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="text" name="email" id="email" placeholder="Enter your email" required>
            <!-- <input type="text" name="user_id" id="user_id" placeholder="Enter user_id" required> -->
        </div>
        <div class="form-group">
            <label for="plan_id">Select Plan:</label>
            <?php
                // echo '<pre>';
                // print_r($packages);
                // echo '</pre>';

            ?>
            <select name="plan_id" id="plan_id">
                <option value="">Select Plan</option>
                <?php
                    if($packages){
                        foreach($packages as $package){ ?>
                            <option value="<?= $package['stripe_plan_id']; ?>"><?= $package['title'] .' ('. $package['interval'] .')'; ?></option> 
                <?php    }
                    }
                ?>
                <!-- <option value="price_1Hh1ZFGQHPxvd3s">Standard Plan</option>
                <option value="price_1Hh1ZzGQHPxvd3s">Premium Plan</option> -->
            </select>
        </div>
        <div class="form-group">
            <label for="card-element">Credit or Debit Card:</label>
            <div id="card-element"></div>
        </div>

        <div class="form-group">
            <label for="coupon_code">Coupon Code:</label>
            <input type="text" name="coupon_code" id="coupon_code" placeholder="Coupon Code" >
        </div>
        <button type="submit">Subscribe</button>
    </form>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        var stripe = Stripe('<?= getenv('stripe.publish.key') ?>');
       // var stripe = Stripe('pk_test_S8FtoTkzjDTAP9qgfTOMXG2e00eSsjNTLD');
        var elements = stripe.elements();
        var card = elements.create('card');
        card.mount('#card-element');

        var form = document.getElementById('payment-form');
        form.addEventListener('submit', async function(event) {
            event.preventDefault();

            const { paymentMethod, error } = await stripe.createPaymentMethod('card', card);

            if (error) {
                console.error(error);
            } else {
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'payment_method');
                hiddenInput.setAttribute('value', paymentMethod.id);
                form.appendChild(hiddenInput);

                form.submit();
            }
        });
    </script>
</body>
</html>
