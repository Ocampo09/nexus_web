<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requiereAdmin();

$errores = [];
$ok      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // CREAR usuario
    if ($accion === 'crear') {
        $nombre    = trim($_POST['nombre']    ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $telefono  = trim($_POST['telefono']  ?? '');
        $fnac      = trim($_POST['fecha_nacimiento'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $sexo      = trim($_POST['sexo']      ?? '');
        $usuario   = trim($_POST['usuario']   ?? '');
        $password  = $_POST['password']       ?? '';
        $rol       = in_array($_POST['rol'] ?? '', ['admin','user']) ? $_POST['rol'] : 'user';

        if (empty($nombre)||empty($apellidos)||!filter_var($email,FILTER_VALIDATE_EMAIL)||empty($telefono)||empty($fnac)||empty($sexo)||empty($usuario)||strlen($password)<6) {
            $errores[] = 'Completa todos los campos obligatorios correctamente.';
        }
        if (empty($errores)) {
            $st = $conn->prepare("SELECT idUser FROM users_data WHERE email=?");
            $st->bind_param('s', $email); $st->execute();
            if ($st->get_result()->num_rows) $errores[] = 'Email ya existente.';
            $st->close();
        }
        if (empty($errores)) {
            $st = $conn->prepare("SELECT idLogin FROM users_login WHERE usuario=?");
            $st->bind_param('s', $usuario); $st->execute();
            if ($st->get_result()->num_rows) $errores[] = 'Usuario ya existente.';
            $st->close();
        }
        if (empty($errores)) {
            $conn->begin_transaction();
            try {
                $st = $conn->prepare("INSERT INTO users_data (nombre,apellidos,email,telefono,fecha_nacimiento,direccion,sexo) VALUES (?,?,?,?,?,?,?)");
                $st->bind_param('sssssss', $nombre,$apellidos,$email,$telefono,$fnac,$direccion,$sexo);
                $st->execute(); $idNew = $conn->insert_id; $st->close();
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $st = $conn->prepare("INSERT INTO users_login (idUser,usuario,password,rol) VALUES (?,?,?,?)");
                $st->bind_param('isss', $idNew,$usuario,$hash,$rol); $st->execute(); $st->close();
                $conn->commit();
                $ok = 'Usuario creado correctamente.';
            } catch(Exception $e) { $conn->rollback(); $errores[] = 'Error al crear usuario.'; }
        }
    }

    // EDITAR usuario
    if ($accion === 'editar') {
        $idEdit    = (int)($_POST['idUser'] ?? 0);
        $nombre    = trim($_POST['nombre']    ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $telefono  = trim($_POST['telefono']  ?? '');
        $fnac      = trim($_POST['fecha_nacimiento'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $sexo      = trim($_POST['sexo']      ?? '');
        $rol       = in_array($_POST['rol'] ?? '', ['admin','user']) ? $_POST['rol'] : 'user';
        $password  = $_POST['password'] ?? '';

        if (empty($nombre)||empty($apellidos)||!filter_var($email,FILTER_VALIDATE_EMAIL)||empty($telefono)||empty($fnac)||empty($sexo)) {
            $errores[] = 'Completa los campos obligatorios.';
        }
        if (empty($errores)) {
            $st = $conn->prepare("UPDATE users_data SET nombre=?,apellidos=?,email=?,telefono=?,fecha_nacimiento=?,direccion=?,sexo=? WHERE idUser=?");
            $st->bind_param('sssssss i', $nombre,$apellidos,$email,$telefono,$fnac,$direccion,$sexo,$idEdit);
            $st->bind_param('sssssssi', $nombre,$apellidos,$email,$telefono,$fnac,$direccion,$sexo,$idEdit);
            $st->execute(); $st->close();
            $update_rol = "UPDATE users_login SET rol=?";
            $params = [$rol];
            if (!empty($password) && strlen($password)>=6) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $update_rol .= ", password=?";
                $params[] = $hash;
            }
            $update_rol .= " WHERE idUser=?";
            $params[] = $idEdit;
            $st = $conn->prepare($update_rol);
            $types = str_repeat('s', count($params)-1) . 'i';
            $st->bind_param($types, ...$params);
            $st->execute(); $st->close();
            $ok = 'Usuario actualizado.';
        }
    }

    // BORRAR usuario
    if ($accion === 'borrar') {
        $idDel = (int)($_POST['idUser'] ?? 0);
        if ($idDel === (int)$_SESSION['idUser']) { $errores[] = 'No puedes eliminar tu propia cuenta.'; }
        else {
            $st = $conn->prepare("DELETE FROM users_data WHERE idUser=?");
            $st->bind_param('i', $idDel); $st->execute(); $st->close();
            $ok = 'Usuario eliminado.';
        }
    }
}

// Cargar usuarios
$res = $conn->query("SELECT d.*, l.usuario, l.rol FROM users_data d JOIN users_login l ON l.idUser = d.idUser ORDER BY d.idUser ASC");
$usuarios = $res->fetch_all(MYSQLI_ASSOC);

$editando = null;
if (isset($_GET['editar'])) {
    $idEdit = (int)$_GET['editar'];
    foreach ($usuarios as $u) { if ($u['idUser'] == $idEdit) { $editando = $u; break; } }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de usuarios — Nexus</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<div class="page" style="padding-top:2.5rem;">
    <div class="page-header" style="padding-top:0;">
        <h1>Administración de <span style="color:var(--accent)">usuarios</span></h1>
    </div>
    <?php foreach ($errores as $e): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    <?php if ($ok): ?><div class="alert alert-success">✅ <?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:2rem; align-items:start;">
        <!-- Formulario -->
        <div class="card">
            <h2 class="card-title" style="font-size:1.1rem;"><?= $editando ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
            <form method="POST" novalidate>
                <input type="hidden" name="accion" value="<?= $editando ? 'editar' : 'crear' ?>">
                <?php if ($editando): ?><input type="hidden" name="idUser" value="<?= $editando['idUser'] ?>"><?php endif; ?>
                <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" value="<?= htmlspecialchars($editando['nombre'] ?? '') ?>" required></div>
                <div class="form-group"><label>Apellidos *</label><input type="text" name="apellidos" value="<?= htmlspecialchars($editando['apellidos'] ?? '') ?>" required></div>
                <div class="form-group"><label>Email *</label><input type="email" name="email" value="<?= htmlspecialchars($editando['email'] ?? '') ?>" required></div>
                <div class="form-group"><label>Teléfono *</label><input type="tel" name="telefono" value="<?= htmlspecialchars($editando['telefono'] ?? '') ?>" required></div>
                <div class="form-group"><label>Fecha nacimiento *</label><input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($editando['fecha_nacimiento'] ?? '') ?>" required></div>
                <div class="form-group"><label>Dirección</label><input type="text" name="direccion" value="<?= htmlspecialchars($editando['direccion'] ?? '') ?>"></div>
                <div class="form-group"><label>Sexo *</label>
                    <select name="sexo"><?php foreach(['Hombre','Mujer','Otro'] as $s): ?><option value="<?= $s ?>" <?= ($editando['sexo']??'')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select>
                </div>
                <?php if (!$editando): ?>
                <div class="form-group"><label>Usuario *</label><input type="text" name="usuario" required></div>
                <?php endif; ?>
                <div class="form-group"><label>Contraseña <?= $editando ? '(dejar vacío para no cambiar)' : '*' ?></label><input type="password" name="password" <?= $editando?'':'required' ?>></div>
                <div class="form-group"><label>Rol *</label>
                    <select name="rol"><option value="user" <?= ($editando['rol']??'user')==='user'?'selected':'' ?>>User</option><option value="admin" <?= ($editando['rol']??'')==='admin'?'selected':'' ?>>Admin</option></select>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= $editando ? 'Guardar cambios' : 'Crear usuario' ?></button>
                <?php if ($editando): ?><a href="/admin/usuarios-administracion.php" class="btn btn-ghost btn-block" style="margin-top:.5rem;">Cancelar</a><?php endif; ?>
            </form>
        </div>

        <!-- Tabla -->
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Usuario</th><th>Rol</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['idUser'] ?></td>
                    <td><?= htmlspecialchars($u['nombre'].' '.$u['apellidos']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['usuario']) ?></td>
                    <td><span class="badge badge-<?= $u['rol'] ?>"><?= $u['rol'] ?></span></td>
                    <td>
                        <a href="?editar=<?= $u['idUser'] ?>" class="btn btn-ghost btn-sm">Editar</a>
                        <?php if ($u['idUser'] != $_SESSION['idUser']): ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar usuario?')">
                            <input type="hidden" name="accion" value="borrar">
                            <input type="hidden" name="idUser" value="<?= $u['idUser'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<footer><p>© <?= date('Y') ?> <span>Nexus Consulting</span>.</p></footer>
<script src="/js/main.js"></script>
</body>
</html>
