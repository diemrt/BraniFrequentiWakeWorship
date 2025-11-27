<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF invalido';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (login($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Credenziali errate';
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-2xl mb-4">Login</h2>
    <?php if (isset($error)): ?>
        <p class="text-red-500"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="text" name="username" placeholder="Username" class="w-full p-2 mb-2 border" required>
        <input type="password" name="password" placeholder="Password" class="w-full p-2 mb-2 border" required>
        <button type="submit" class="w-full bg-blue-600 text-white p-2">Login</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>