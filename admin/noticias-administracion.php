<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requiereAdmin();

$errores = [];
$ok      = '';
$idUser  = $_SESSION['idUser'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear' || $accion === 'editar') {
        $idNoticia = (int)($_POST['idNoticia'] ?? 0);
        $titulo    = trim($_POST['titulo'] ?? '');
        $texto     = trim($_POST['texto']  ?? '');
        $fecha     = $_POST['fecha']  ?? '';
        $imagen_actual = trim($_POST['imagen_actual'] ?? '');

        if (empty($titulo)) $errores[] = 'El título es obligatorio.';
        if (empty($texto))  $errores[] = 'El texto es obligatorio.';
        if (empty($fecha))  $errores[] = 'La fecha es obligatoria.';

        // Manejo de imagen
        $nombre_imagen = $imagen_actual;
        if (!empty($_FILES['imagen']['name'])) {
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $errores[] = 'Formato de imagen no permitido.';
            } else {
                $nombre_imagen = uniqid('img_', true) . '.' . $ext;
                $destino = __DIR__ . '/../uploads/noticias/' . $nombre_imagen;
                if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
                    $errores[] = 'Error al subir la imagen.';
                    $nombre_imagen = $imagen_actual;
                }
            }
        } elseif ($accion === 'crear') {
            $errores[] = 'La imagen es obligatoria.';
        }

        if (empty($errores)) {
            if ($accion === 'crear') {
                $st = $conn->prepare("INSERT INTO noticias (titulo,imagen,texto,fecha,idUser) VALUES (?,?,?,?,?)");
                $st->bind_param('ssssi', $titulo,$nombre_imagen,$texto,$fecha,$idUser);
            } else {
                $st = $conn->prepare("UPDATE noticias SET titulo=?,imagen=?,texto=?,fecha=? WHERE idNoticia=?");
                $st->bind_param('ssssi', $titulo,$nombre_imagen,$texto,$fecha,$idNoticia);
            }
            $st->execute();
            if ($conn->errno) $errores[] = 'Error: título duplicado u otro problema.';
            else $ok = $accion === 'crear' ? 'Noticia creada.' : 'Noticia actualizada.';
            $st->close();
        }
    }

    if ($accion === 'borrar') {
        $idDel = (int)($_POST['idNoticia'] ?? 0);
        // Obtener imagen para borrar archivo
        $st = $conn->prepare("SELECT imagen FROM noticias WHERE idNoticia=?");
        $st->bind_param('i', $idDel); $st->execute();
        $row = $st->get_result()->fetch_assoc(); $st->close();
        if ($row) {
            $f = __DIR__ . '/../uploads/noticias/' . $row['imagen'];
            if (file_exists($f)) unlink($f);
        }
        $st = $conn->prepare("DELETE FROM noticias WHERE idNoticia=?");
        $st->bind_param('i', $idDel); $st->execute(); $st->close();
        $ok = 'Noticia eliminada.';
    }
}

$res = $conn->query("SELECT n.*, u.nombre FROM noticias n JOIN users_data u ON n.idUser=u.idUser ORDER BY n.fecha DESC");
$noticias = $res->fetch_all(MYSQLI_ASSOC);

$editando = null;
if (isset($_GET['editar'])) {
    $idEdit = (int)$_GET['editar'];
    foreach ($noticias as $n) { if ($n['idNoticia'] == $idEdit) { $editando = $n; break; } }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de noticias — Nexus</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<div class="page" style="padding-top:2.5rem;">
    <div class="page-header" style="padding-top:0;">
        <h1>Administración de <span style="color:var(--accent)">noticias</span></h1>
    </div>
    <?php foreach ($errores as $e): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    <?php if ($ok): ?><div class="alert alert-success">✅ <?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:2rem; align-items:start;">
        <!-- Formulario -->
        <div class="card">
            <h2 class="card-title" style="font-size:1.1rem;"><?= $editando ? 'Editar noticia' : 'Nueva noticia' ?></h2>
            <form method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="accion" value="<?= $editando ? 'editar' : 'crear' ?>">
                <?php if ($editando): ?>
                    <input type="hidden" name="idNoticia" value="<?= $editando['idNoticia'] ?>">
                    <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($editando['imagen']) ?>">
                <?php endif; ?>
                <div class="form-group"><label>Título *</label><input type="text" name="titulo" value="<?= htmlspecialchars($editando['titulo'] ?? '') ?>" required></div>
                <div class="form-group"><label>Imagen <?= $editando ? '(dejar vacío para no cambiar)' : '*' ?></label>
                    <input type="file" name="imagen" accept="image/*" style="color:var(--text);">
                    <?php if ($editando && $editando['imagen']): ?>
                        <p style="font-size:.8rem; color:var(--text-muted); margin-top:.3rem;">Actual: <?= htmlspecialchars($editando['imagen']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="form-group"><label>Texto *</label><textarea name="texto" rows="6" required><?= htmlspecialchars($editando['texto'] ?? '') ?></textarea></div>
                <div class="form-group"><label>Fecha *</label><input type="date" name="fecha" value="<?= htmlspecialchars($editando['fecha'] ?? date('Y-m-d')) ?>" required></div>
                <button type="submit" class="btn btn-primary btn-block"><?= $editando ? 'Guardar cambios' : 'Publicar noticia' ?></button>
                <?php if ($editando): ?><a href="/admin/noticias-administracion.php" class="btn btn-ghost btn-block" style="margin-top:.5rem;">Cancelar</a><?php endif; ?>
            </form>
        </div>

        <!-- Tabla noticias -->
        <div>
            <?php if (empty($noticias)): ?>
                <div class="alert alert-info">ℹ️ No hay noticias publicadas.</div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>ID</th><th>Título</th><th>Fecha</th><th>Autor</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($noticias as $n): ?>
                    <tr>
                        <td><?= $n['idNoticia'] ?></td>
                        <td style="max-width:180px;"><?= htmlspecialchars(mb_substr($n['titulo'],0,50)) ?><?= mb_strlen($n['titulo'])>50?'…':'' ?></td>
                        <td><?= date('d/m/Y', strtotime($n['fecha'])) ?></td>
                        <td><?= htmlspecialchars($n['nombre']) ?></td>
                        <td>
                            <a href="?editar=<?= $n['idNoticia'] ?>" class="btn btn-ghost btn-sm">Editar</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar noticia?')">
                                <input type="hidden" name="accion" value="borrar">
                                <input type="hidden" name="idNoticia" value="<?= $n['idNoticia'] ?>">
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
</div>
<footer><p>© <?= date('Y') ?> <span>Nexus Consulting</span>.</p></footer>
<script src="/js/main.js"></script>
</body>
</html>
