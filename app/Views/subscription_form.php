<!DOCTYPE html>
<html>
<head>
    <title>Subscribe</title>
</head>
<body class="testssssssssssssssssss">
    <!-- <form action="/subscription/createSubscription" method="post" id="subscription-form"> -->
    <form action="/create-subscription" method="post" id="subscription-form">
        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" />

        <label for="email">Email:</label>
        <input type="email" name="email" required>

        <label for="card-element">Credit or debit card:</label>
        <div id="card-element"></div>

        <button type="submit">Submit Payment</button>
    </form>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        var stripe = Stripe('<?= (new \Config\Stripe())->publishableKey ?>');
        var elements = stripe.elements();
        var card = elements.create('card');
        card.mount('#card-element');

        var form = document.getElementById('subscription-form');
        form.addEventListener('submit', async function(event) {
            event.preventDefault();

            const {token, error} = await stripe.createToken(card);
            if (error) {
                // Inform the user if there was an error
                console.error(error);
            } else {
                // Send the token to your server
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'stripeToken');
                hiddenInput.setAttribute('value', token.id);
                form.appendChild(hiddenInput);

                form.submit();
            }
        });
    </script>
</body>
</html>
