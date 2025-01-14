<?php 
$randomNumber = '?cache=false&avoid_cache='.rand();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Become successful now! On socceryou, you can showcase your talent to the world. This way, interested scouts and clubs will notice you and you may soon be as successful as Messi or Ronaldo!">
    <title>socceryou - succer your soccer</title>
    <link rel="icon" type="image/x-icon" href="https://socceryou.co.uk/favicon.ico<?= $randomNumber; ?>">
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background-image: url('https://socceryou.co.uk/assets/images/bnr.jpg<?= $randomNumber; ?>'); /* Replace with your background image URL */
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            text-align: center;
        }

        .banner-container {
            position: relative;
            max-width: 80%;
        }

        .banner {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            display: block;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.2);
        }

        .coming-soon-text {
            /* position: absolute; */
            bottom: 20px;
            width: 100%;
            color: #BDE34F;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 2px;
            font-size: 70px;
  text-transform: uppercase;
  font-weight: 700;
  margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="banner-container">
        <img src="https://socceryou.co.uk/assets/images/coming-soon-banner.png<?= $randomNumber; ?>" alt="Coming Soon" class="banner"> <!-- Replace with your banner image URL -->
        <div class="coming-soon-text">
            Coming Soon
        </div>
    </div>

</body>
</html>
