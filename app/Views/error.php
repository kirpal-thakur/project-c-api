<!DOCTYPE html>
<html>
<head>
    <title>Error</title>
</head>
<body>
    <h1>Subscription Creation Failed</h1>
    <p><?= session('error') ?></p>
    <p><?php echo '<pre>'; print_r(session()); echo '</pre>'; ?></p>
    <p><?php //echo '<pre>'; print_r($e); echo '</pre>'; ?></p>
</body>
</html>
