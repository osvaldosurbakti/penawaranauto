<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>

<body>
    <h2>Login</h2>
    <?php if (session()->get('error')): ?>
        <p style="color: red;"><?= session()->get('error') ?></p>
    <?php endif; ?>
    <form action="/login/process" method="post">
        <!-- Ganti username menjadi email -->
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
        <br>

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <br>

        <button type="submit">Login</button>
    </form>

    <a href="/register">Register</a>
</body>

</html>