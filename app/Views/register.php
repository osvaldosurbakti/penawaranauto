<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>

<body>
    <h2>Register</h2>
    <?php if (session()->get('error')): ?>
        <p style="color: red;"><?= session()->get('error') ?></p>
    <?php endif; ?>
    <form action="/register/process" method="post">
        <!-- Input Nama Marketing -->
        <label for="marketing_name">Nama Marketing:</label>
        <input type="text" name="marketing_name" id="marketing_name" required>
        <br>

        <!-- Input Email -->
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
        <br>

        <!-- Input Nomor HP -->
        <label for="phone">Nomor HP:</label>
        <input type="text" name="phone" id="phone" required>
        <br>

        <!-- Input Password -->
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <br>

        <!-- Dropdown Pilihan Cabang -->
        <label for="branch">Cabang:</label>
        <select name="branch" id="branch" required>
            <option value="Jakarta">Jakarta</option>
            <option value="Surabaya">Surabaya</option>
            <option value="Medan">Medan</option>
            <option value="Bandung">Bandung</option>
            <option value="Yogyakarta">Yogyakarta</option>
            <option value="Semarang">Semarang</option>
            <option value="Bali">Bali</option>
        </select>
        <br>

        <button type="submit">Register</button>
    </form>
    <a href="/login">Login</a>
</body>

</html>