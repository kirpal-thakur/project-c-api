<form action="<?php echo route_to('post_register');?>" method="post">
    <label for="username">Username:</label>
    <input type="text" name="username" required>

    <label for="email">Email:</label>
    <input type="email" name="email" required>

    <label for="password">Password:</label>
    <input type="password" name="password" required>

    <label for="password_confirm">Confirm Password:</label>
    <input type="password" name="password_confirm" required>

    <button type="submit">Register</button>
</form>