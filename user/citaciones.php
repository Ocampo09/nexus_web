<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requiereLogin();

$idUser  = $_SESSION['idUser'];
$errores = [];
$ok      = '';
$hoy     = date('Y-m-d');

// Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $fecha  = $_POST['fecha_cita']  ?? '';
        $motivo = trim($_POST['motivo_cita'] ?? '');
        if (empty($fecha)) $errores[] = 'La fecha es obligatoria.';
        elseif ($fecha <= $hoy) $errores[] = 'La fecha debe ser posterior a hoy.';
        if (empty($errores)) {
            $st = $conn->prepare("INSERT INTO citas (idUser, fecha_cita, motivo_cita) VALUES (?,?,?)");
            $st->bind_param('iss', $idUser, $fecha, $motivo);
            $st->execute();
            $st->close();
            $ok = 'Cita creada correctamente.';
        }
    }

    if ($accion === 'editar') {
        $idCita = (int)($_POST['idCita'] ?? 0);
        $fecha  = $_POST['fecha_cita']  ?? '';
        $motivo = trim($_POST['motivo_cita'] ?? '');

        // Verificar que la cita pertenece al usuario y es futura
        $st = $conn->prepare("SELECT fecha_cita FROM citas WHERE idCita=? AND idUser=?");
        $st->bind_param('ii', $idCita, $idUser);
        $st->execute();
        $cita = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$cita) { $errores[] = 'Cita no encontrada.'; }
        elseif ($cita['fecha_cita'] < $hoy) { $errores[] = 'No puedes modificar una cita pasada.'; }
        elseif (empty($fecha) || $fecha <= $hoy) { $errores[] = 'La nueva fecha debe ser posterior a hoy.'; }

        if (empty($errores)) {
            $st = $conn->prepare("UPDATE citas SET fecha_cita=?, motivo_cita=? WHERE idCita=? AND idUser=?");
            $st->bind_param('ssii', $fecha, $motivo, $idCita, $idUser);
            $st->execute();
            $st->close();
            $ok = 'Cita actualizada.';
        }
    }

    if ($accion === 'borrar') {
        $idCita = (int)($_POST['idCita'] ?? 0);
        // Verificar que es futura y del usuario
        $st = $conn->prepare("SELECT fecha_cita FROM citas WHERE idCita=? AND idUser=?");
        $st->bind_param('ii', $idCita, $idUser);
        $st->execute();
        $cita = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$cita) { $errores[] = 'Cita no encontrada.'; }
        elseif ($cita['fecha_cita'] < $hoy) { $errores[] = 'No puedes borrar una cita pasada.'; }
        else {
            $st = $conn->prepare("DELETE FROM citas WHERE idCita=? AND idUser=?");
            $st->bind_param('ii', $idCita, $idUser);
            $st->execute();
            $st->close();
            $ok = 'Cita eliminada.';
        }
    }
}

// Cargar citas del usuario
$st = $conn->prepare("SELECT * FROM citas WHERE idUser=? ORDER BY fecha_cita ASC");
$st->bind_param('i', $idUser);
$st->execute();
$citas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// Cita a editar
$editando = null;
if (isset($_GET['editar'])) {
    $idEdit = (int)$_GET['editar'];
    foreach ($citas as $c) {
        if ($c['idCita'] == $idEdit && $c['fecha_cita'] >= $hoy) { $editando = $c; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis citas — Nexus Consulting</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<div class="page" style="padding-top:2.5rem;">
    <div class="page-header" style="padding-top:0;">
        <h1>Mis <span style="color:var(--accent)">citas</span></h1>
        <p>Gestiona tus citas con Nexus Consulting</p>
    </div>

    <?php foreach ($errores as $e): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <?php if ($ok): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 1fr 2fr; gap:2rem; align-items:start;">
        <!-- Formulario -->
        <div class="card">
            <h2 class="card-title" style="font-size:1.1rem;"><?= $editando ? 'Editar cita' : 'Nueva cita' ?></h2>
            <form method="POST" novalidate>
                <input type="hidden" name="accion" value="<?= $editando ? 'editar' : 'crear' ?>">
                <?php if ($editando): ?>
                    <input type="hidden" name="idCita" value="<?= $editando['idCita'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Fecha *</label>
                    <input type="date" name="fecha_cita" min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           value="<?= htmlspecialchars($editando['fecha_cita'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Motivo</label>
                    <textarea name="motivo_cita" rows="3"><?= htmlspecialchars($editando['motivo_cita'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <?= $editando ? 'Actualizar cita' : 'Solicitar cita' ?>
                </button>
                <?php if ($editando): ?>
                    <a href="/user/citaciones.php" class="btn btn-ghost btn-block" style="margin-top:.5rem;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Lista citas -->
        <div>
            <?php if (empty($citas)): ?>
                <div class="alert alert-info">ℹ️ No tienes citas registradas aún.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Motivo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($citas as $c): ?>
                            <?php $futura = $c['fecha_cita'] >= $hoy; ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($c['fecha_cita'])) ?></td>
                                <td>
                                    <?php if ($futura): ?>
                                        <span class="badge badge-future">Planificada</span>
                                    <?php else: ?>
                                        <span class="badge badge-past">Realizada</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:200px;"><?= htmlspecialchars($c['motivo_cita'] ?? '—') ?></td>
                                <td>
                                    <?php if ($futura): ?>
                                        <a href="?editar=<?= $c['idCita'] ?>" class="btn btn-ghost btn-sm">Editar</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta cita?')">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="idCita" value="<?= $c['idCita'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:.82rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<footer><p>© <?= date('Y') ?> <span>Nexus Consulting</span>.</p></footer>
<script src="/js/main.js"></script>
</body>
</html>
