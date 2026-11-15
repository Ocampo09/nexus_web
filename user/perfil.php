<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requiereLogin();

$idUser  = $_SESSION['idUser'];
$errores = [];
$ok      = false;

// Cargar datos actuales
$st = $conn->prepare("SELECT d.*, l.usuario FROM users_data d JOIN users_login l ON l.idUser = d.idUser WHERE d.idUser = ?");
$st->bind_param('i', $idUser);
$st->execute();
$datos = $st->get_result()->fetch_assoc();
$st->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'perfil') {
        $nombre    = trim($_POST['nombre']    ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $telefono  = trim($_POST['telefono']  ?? '');
        $fnac      = trim($_POST['fecha_nacimiento'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $sexo      = trim($_POST['sexo']      ?? '');

        if (empty($nombre))    $errores[] = 'El nombre es obligatorio.';
        if (empty($apellidos)) $errores[] = 'Los apellidos son obligatorios.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido.';
        if (empty($telefono))  $errores[] = 'El teléfono es obligatorio.';
        if (empty($fnac))      $errores[] = 'La fecha de nacimiento es obligatoria.';
        if (!in_array($sexo, ['Hombre','Mujer','Otro'])) $errores[] = 'Sexo no válido.';

        // Comprobar email único (excluyendo el propio)
        if (empty($errores)) {
            $st = $conn->prepare("SELECT idUser FROM users_data WHERE email = ? AND idUser != ?");
            $st->bind_param('si', $email, $idUser);
            $st->execute();
            if ($st->get_result()->num_rows > 0) $errores[] = 'Ese email ya está en uso por otra cuenta.';
            $st->close();
        }

        if (empty($errores)) {
            $st = $conn->prepare("UPDATE users_data SET nombre=?, apellidos=?, email=?, telefono=?, fecha_nacimiento=?, direccion=?, sexo=? WHERE idUser=?");
            $st->bind_param('sssssssi', $nombre, $apellidos, $email, $telefono, $fnac, $direccion, $sexo, $idUser);
            $st->execute();
            $st->close();
            $_SESSION['nombre'] = $nombre;
            $ok = true;
            // Refrescar datos
            $st = $conn->prepare("SELECT d.*, l.usuario FROM users_data d JOIN users_login l ON l.idUser = d.idUser WHERE d.idUser = ?");
            $st->bind_param('i', $idUser);
            $st->execute();
            $datos = $st->get_result()->fetch_assoc();
            $st->close();
        }
    }

    if ($accion === 'password') {
        $pass_actual = $_POST['pass_actual'] ?? '';
        $pass_nueva  = $_POST['pass_nueva']  ?? '';
        $pass_rep    = $_POST['pass_rep']    ?? '';

        $st = $conn->prepare("SELECT password FROM users_login WHERE idUser = ?");
        $st->bind_param('i', $idUser);
        $st->execute();
        $hash = $st->get_result()->fetch_assoc()['password'];
        $st->close();

        if (!password_verify($pass_actual, $hash)) $errores[] = 'La contraseña actual es incorrecta.';
        if (strlen($pass_nueva) < 6) $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        if ($pass_nueva !== $pass_rep) $errores[] = 'Las contraseñas nuevas no coinciden.';

        if (empty($errores)) {
            $nuevo_hash = password_hash($pass_nueva, PASSWORD_BCRYPT);
            $st = $conn->prepare("UPDATE users_login SET password=? WHERE idUser=?");
            $st->bind_param('si', $nuevo_hash, $idUser);
            $st->execute();
            $st->close();
            $ok = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil — Nexus Consulting</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<div class="page" style="max-width:640px; padding-top:3rem;">
    <div class="page-header" style="padding-top:0;">
        <h1>Mi <span style="color:var(--accent)">perfil</span></h1>
        <p>Gestiona tu información personal y credenciales</p>
    </div>

    <?php foreach ($errores as $e): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <?php if ($ok): ?>
        <div class="alert alert-success">✅ Cambios guardados correctamente.</div>
    <?php endif; ?>

    <!-- Datos personales -->
    <div class="card" style="margin-bottom:1.5rem;">
        <h2 class="card-title" style="font-size:1.2rem;">Datos personales</h2>
        <p class="card-subtitle">El nombre de usuario <strong><?= htmlspecialchars($datos['usuario']) ?></strong> no puede modificarse.</p>
        <form method="POST" novalidate>
            <input type="hidden" name="accion" value="perfil">
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($datos['nombre']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Apellidos *</label>
                    <input type="text" name="apellidos" value="<?= htmlspecialchars($datos['apellidos']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($datos['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Teléfono *</label>
                    <input type="tel" name="telefono" value="<?= htmlspecialchars($datos['telefono']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Fecha de nacimiento *</label>
                    <input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($datos['fecha_nacimiento']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Sexo *</label>
                    <select name="sexo">
                        <?php foreach (['Hombre','Mujer','Otro'] as $s): ?>
                        <option value="<?= $s ?>" <?= $datos['sexo'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="direccion" value="<?= htmlspecialchars($datos['direccion'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </form>
    </div>

    <!-- Cambiar contraseña -->
    <div class="card">
        <h2 class="card-title" style="font-size:1.2rem;">Cambiar contraseña</h2>
        <p class="card-subtitle">La contraseña actual no se muestra por seguridad.</p>
        <form method="POST" novalidate>
            <input type="hidden" name="accion" value="password">
            <div class="form-group">
                <label>Contraseña actual *</label>
                <input type="password" name="pass_actual" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Nueva contraseña *</label>
                    <input type="password" name="pass_nueva" required>
                </div>
                <div class="form-group">
                    <label>Repetir nueva *</label>
                    <input type="password" name="pass_rep" required>
                </div>
            </div>
            <button type="submit" class="btn btn-ghost">Cambiar contraseña</button>
        </form>
    </div>
</div>
<footer><p>© <?= date('Y') ?> <span>Nexus Consulting</span>.</p></footer>
<script src="/js/main.js"></script>
</body>
</html>
