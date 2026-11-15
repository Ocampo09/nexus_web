<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

if (estaLogueado()) { header('Location: /index.php'); exit; }

$errores = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y sanear
    $nombre    = trim($_POST['nombre']    ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $telefono  = trim($_POST['telefono']  ?? '');
    $fnac      = trim($_POST['fecha_nacimiento'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $sexo      = trim($_POST['sexo']      ?? '');
    $usuario   = trim($_POST['usuario']   ?? '');
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password2']      ?? '';

    // Validaciones
    if (empty($nombre))    $errores[] = 'El nombre es obligatorio.';
    if (empty($apellidos)) $errores[] = 'Los apellidos son obligatorios.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido.';
    if (empty($telefono))  $errores[] = 'El teléfono es obligatorio.';
    if (empty($fnac))      $errores[] = 'La fecha de nacimiento es obligatoria.';
    if (empty($sexo) || !in_array($sexo, ['Hombre','Mujer','Otro'])) $errores[] = 'El sexo es obligatorio.';
    if (empty($usuario) || strlen($usuario) < 3) $errores[] = 'El nombre de usuario debe tener al menos 3 caracteres.';
    if (strlen($password) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($password !== $password2) $errores[] = 'Las contraseñas no coinciden.';

    if (empty($errores)) {
        // Comprobar unicidad email
        $st = $conn->prepare("SELECT idUser FROM users_data WHERE email = ?");
        $st->bind_param('s', $email);
        $st->execute();
        if ($st->get_result()->num_rows > 0) $errores[] = 'Ya existe una cuenta con ese email.';
        $st->close();
    }
    if (empty($errores)) {
        // Comprobar unicidad usuario
        $st = $conn->prepare("SELECT idLogin FROM users_login WHERE usuario = ?");
        $st->bind_param('s', $usuario);
        $st->execute();
        if ($st->get_result()->num_rows > 0) $errores[] = 'Ese nombre de usuario ya está en uso.';
        $st->close();
    }

    if (empty($errores)) {
        $conn->begin_transaction();
        try {
            // Insertar users_data
            $st = $conn->prepare("INSERT INTO users_data (nombre, apellidos, email, telefono, fecha_nacimiento, direccion, sexo) VALUES (?,?,?,?,?,?,?)");
            $st->bind_param('sssssss', $nombre, $apellidos, $email, $telefono, $fnac, $direccion, $sexo);
            $st->execute();
            $idUser = $conn->insert_id;
            $st->close();

            // Insertar users_login
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $rol  = 'user';
            $st = $conn->prepare("INSERT INTO users_login (idUser, usuario, password, rol) VALUES (?,?,?,?)");
            $st->bind_param('isss', $idUser, $usuario, $hash, $rol);
            $st->execute();
            $st->close();

            $conn->commit();
            $ok = true;
        } catch (Exception $e) {
            $conn->rollback();
            $errores[] = 'Error al registrar. Inténtalo de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro — Nexus Consulting</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<div class="page" style="max-width:640px; padding-top:3rem; padding-bottom:3rem;">
    <div class="card">
        <h1 class="card-title">Crear cuenta</h1>
        <p class="card-subtitle">
            ¿Ya tienes cuenta? <a href="/login.php">Inicia sesión aquí</a>
        </p>

        <?php if ($ok): ?>
            <div class="alert alert-success">✅ Registro completado correctamente. Redirigiendo al login…</div>
            <script>setTimeout(()=>location.href='/login.php', 2500)</script>
        <?php endif; ?>
        <?php foreach ($errores as $e): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <?php if (!$ok): ?>
        <form method="POST" novalidate>
            <p style="font-size:.8rem; color:var(--text-muted); margin-bottom:1.2rem; text-transform:uppercase; letter-spacing:.08em; font-weight:700;">Datos personales</p>

            <div class="form-row">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Apellidos *</label>
                    <input type="text" name="apellidos" value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Teléfono *</label>
                    <input type="tel" name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Fecha de nacimiento *</label>
                    <input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($_POST['fecha_nacimiento'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Sexo *</label>
                    <select name="sexo">
                        <option value="">Seleccionar…</option>
                        <?php foreach (['Hombre','Mujer','Otro'] as $s): ?>
                        <option value="<?= $s ?>" <?= (($_POST['sexo'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="direccion" value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">
            </div>

            <p style="font-size:.8rem; color:var(--text-muted); margin: 1.5rem 0 1rem; text-transform:uppercase; letter-spacing:.08em; font-weight:700;">Credenciales de acceso</p>

            <div class="form-group">
                <label>Nombre de usuario *</label>
                <input type="text" name="usuario" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Contraseña * (mín. 6 caracteres)</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Repetir contraseña *</label>
                    <input type="password" name="password2" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top:.5rem;">Crear cuenta</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<footer><p>© <?= date('Y') ?> <span>Nexus Consulting</span>.</p></footer>
<script src="/js/main.js"></script>
</body>
</html>
