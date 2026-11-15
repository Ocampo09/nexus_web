<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

$res = $conn->query("SELECT n.*, u.nombre, u.apellidos FROM noticias n JOIN users_data u ON n.idUser = u.idUser ORDER BY n.fecha DESC");
$noticias = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticias — Nexus Consulting</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<div class="page">
    <div class="page-header">
        <h1>Noticias <span style="color:var(--accent)">y novedades</span></h1>
        <p>Mantente al día con todo lo que sucede en Nexus Consulting</p>
    </div>

    <?php if (empty($noticias)): ?>
        <div class="alert alert-info">ℹ️ Aún no hay noticias publicadas.</div>
    <?php else: ?>
        <div class="news-grid">
            <?php foreach ($noticias as $n): ?>
            <article class="news-card">
                <?php
                $full_path = __DIR__ . '/uploads/noticias/' . $n['imagen'];
                if (file_exists($full_path)):
                ?>
                    <img src="/uploads/noticias/<?= htmlspecialchars($n['imagen']) ?>" alt="<?= htmlspecialchars($n['titulo']) ?>">
                <?php else: ?>
                    <div class="news-card-img-placeholder">📰</div>
                <?php endif; ?>
                <div class="news-card-body">
                    <p class="news-card-meta">
                        <?= date('d/m/Y', strtotime($n['fecha'])) ?>
                        <span>por <?= htmlspecialchars($n['nombre'] . ' ' . $n['apellidos']) ?></span>
                    </p>
                    <h2><?= htmlspecialchars($n['titulo']) ?></h2>
                    <p><?= nl2br(htmlspecialchars($n['texto'])) ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<footer><p>© <?= date('Y') ?> <span>Nexus Consulting</span>. Todos los derechos reservados.</p></footer>
<script src="/js/main.js"></script>
</body>
</html>
