<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requiereAdmin();

$errores = [];
$ok      = '';
$hoy     = date('Y-m-d');

// Usuarios para selector
$res_u = $conn->query("SELECT d.idUser, d.nombre, d.apellidos FROM users_data d JOIN users_login l ON l.idUser=d.idUser WHERE l.rol='user' ORDER BY d.nombre");
$usuariosLista = $res_u->fetch_all(MYSQLI_ASSOC);

$idUserSel = (int)($_GET['user'] ?? $_POST['idUserSel'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $idUserSel = (int)($_POST['idUserSel'] ?? 0);

    if ($accion === 'crear') {
        $fecha  = $_POST['fecha_cita']  ?? '';
        $motivo = trim($_POST['motivo_cita'] ?? '');
        if (!$idUserSel) $errores[] = 'Selecciona un usuario.';
        if (empty($fecha)) $errores[] = 'La fecha es obligatoria.';
        if (empty($errores)) {
            $st = $conn->prepare("INSERT INTO citas (idUser,fecha_cita,motivo_cita) VALUES (?,?,?)");
            $st->bind_param('iss', $idUserSel,$fecha,$motivo); $st->execute(); $st->close();
            $ok = 'Cita creada.';
        }
    }
    if ($accion === 'editar') {
        $idCita = (int)($_POST['idCita'] ?? 0);
        $fecha  = $_POST['fecha_cita']  ?? '';
        $motivo = trim($_POST['motivo_cita'] ?? '');
        if (empty($fecha)) $errores[] = 'La fecha es obligatoria.';
        if (empty($errores)) {
            $st = $conn->prepare("UPDATE citas SET fecha_cita=?,motivo_cita=? WHERE idCita=?");
            $st->bind_param('ssi', $fecha,$motivo,$idCita); $st->execute(); $st->close();
            $ok = 'Cita actualizada.';
        }
    }
    if ($accion === 'borrar') {
        $idCita = (int)($_POST['idCita'] ?? 0);
        $st = $conn->prepare("DELETE FROM citas WHERE idCita=?");
        $st->bind_param('i', $idCita); $st->execute(); $st->close();
        $ok = 'Cita eliminada.';
    }
}

// Cargar citas del usuario seleccionado
$citas = [];
if ($idUserSel) {
    $st = $conn->prepare("SELECT * FROM citas WHERE idUser=? ORDER BY fecha_cita ASC");
    $st->bind_param('i', $idUserSel); $st->execute();
    $citas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
}

$editando = null;
if (isset($_GET['editar'])) {
    $idEdit = (int)$_GET['editar'];
    foreach ($citas as $c) { if ($c['idCita'] == $idEdit) { $editando = $c; break; } }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de citas — Nexus</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<div class="page" style="padding-top:2.5rem;">
    <div class="page-header" style="padding-top:0;">
        <h1>Administración de <span style="color:var(--accent)">citas</span></h1>
    </div>
    <?php foreach ($errores as $e): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    <?php if ($ok): ?><div class="alert alert-success">✅ <?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <!-- Selector de usuario -->
    <div class="card" style="margin-bottom:1.5rem;">
        <form method="GET" style="display:flex; align-items:flex-end; gap:1rem; flex-wrap:wrap;">
            <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                <label>Seleccionar usuario</label>
                <select name="user" onchange="this.form.submit()">
                    <option value="">— Elige un usuario —</option>
                    <?php foreach ($usuariosLista as $u): ?>
                    <option value="<?= $u['idUser'] ?>" <?= $idUserSel==$u['idUser']?'selected':'' ?>>
                        <?= htmlspecialchars($u['nombre'].' '.$u['apellidos']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($idUserSel): ?>
    <div style="display:grid; grid-template-columns:1fr 2fr; gap:2rem; align-items:start;">
        <!-- Formulario -->
        <div class="card">
            <h2 class="card-title" style="font-size:1.1rem;"><?= $editando ? 'Editar cita' : 'Nueva cita' ?></h2>
            <form method="POST" novalidate>
                <input type="hidden" name="accion" value="<?= $editando ? 'editar' : 'crear' ?>">
                <input type="hidden" name="idUserSel" value="<?= $idUserSel ?>">
                <?php if ($editando): ?><input type="hidden" name="idCita" value="<?= $editando['idCita'] ?>"><?php endif; ?>
                <div class="form-group">
                    <label>Fecha *</label>
                    <input type="date" name="fecha_cita" value="<?= htmlspecialchars($editando['fecha_cita'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Motivo</label>
                    <textarea name="motivo_cita" rows="3"><?= htmlspecialchars($editando['motivo_cita'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= $editando ? 'Actualizar' : 'Crear cita' ?></button>
                <?php if ($editando): ?><a href="?user=<?= $idUserSel ?>" class="btn btn-ghost btn-block" style="margin-top:.5rem;">Cancelar</a><?php endif; ?>
            </form>
        </div>

        <!-- Tabla citas -->
        <div>
            <?php if (empty($citas)): ?>
                <div class="alert alert-info">ℹ️ Este usuario no tiene citas.</div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Fecha</th><th>Estado</th><th>Motivo</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($citas as $c): ?>
                    <tr>
                        <td><?= $c['idCita'] ?></td>
                        <td><?= date('d/m/Y', strtotime($c['fecha_cita'])) ?></td>
                        <td><span class="badge badge-<?= $c['fecha_cita'] >= $hoy ? 'future' : 'past' ?>"><?= $c['fecha_cita'] >= $hoy ? 'Futura' : 'Pasada' ?></span></td>
                        <td><?= htmlspecialchars($c['motivo_cita'] ?? '—') ?></td>
                        <td>
                            <a href="?user=<?= $idUserSel ?>&editar=<?= $c['idCita'] ?>" class="btn btn-ghost btn-sm">Editar</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar cita?')">
                                <input type="hidden" name="accion" value="borrar">
                                <input type="hidden" name="idCita" value="<?= $c['idCita'] ?>">
                                <input type="hidden" name="idUserSel" value="<?= $idUserSel ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-info">ℹ️ Selecciona un usuario para gestionar sus citas.</div>
    <?php endif; ?>
</div>
<footer><p>© <?= date('Y') ?> <span>Nexus Consulting</span>.</p></footer>
<script src="/js/main.js"></script>
</body>
</html>
