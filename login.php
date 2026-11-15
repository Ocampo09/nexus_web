<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

if (estaLogueado()) { header('Location: /index.php'); exit; }

$error = '';
$ok    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario']  ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = 'Por favor, rellena todos los campos.';
    } else {
        $st = $conn->prepare("
            SELECT l.idLogin, l.password, l.rol, d.idUser, d.nombre
            FROM users_login l
            JOIN users_data d ON l.idUser = d.idUser
            WHERE l.usuario = ?
        ");
        $st->bind_param('s', $usuario);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();

        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['idUser']  = $row['idUser'];
            $_SESSION['nombre']  = $row['nombre'];
            $_SESSION['usuario'] = $usuario;
            $_SESSION['rol']     = $row['rol'];
            $ok = true;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — Nexus Consulting</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<div class="page" style="max-width:440px; padding-top:5rem; padding-bottom:3rem;">
    <div class="card">
        <h1 class="card-title">Bienvenido</h1>
        <p class="card-subtitle">
            ¿No tienes cuenta? <a href="/registro.php">Regístrate aquí</a>
        </p>

        <?php if ($ok): ?>
            <div class="alert alert-success">✅ Sesión iniciada. Redirigiendo…</div>
            <script>setTimeout(()=>location.href='/index.php', 1500)</script>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!$ok): ?>
        <form method="POST" novalidate>
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top:.5rem;">Iniciar sesión</button>
        </form>

        <div style="margin-top:1.5rem; padding-top:1.2rem; border-top:1px solid var(--border); font-size:.82rem; color:var(--text-muted);">
            <strong>Cuentas de prueba:</strong><br>
            Admin: <code>admin</code> / <code>password</code><br>
            Usuario: <code>laura95</code> / <code>password</code>
        </div>
        <?php endif; ?>
    </div>
</div>
<footer><p>© <?= date('Y') ?> <span>Nexus Consulting</span>.</p></footer>
<script src="/js/main.js"></script>
</body>
</html>
