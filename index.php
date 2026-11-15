<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

// Últimas 3 noticias para el home
$res = $conn->query("SELECT n.*, u.nombre FROM noticias n JOIN users_data u ON n.idUser = u.idUser ORDER BY n.fecha DESC LIMIT 3");
$noticias = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus Consulting — Inicio</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<!-- HERO -->
<section class="hero">
    <div class="page">
        <p class="hero-label">Consultoría · Tecnología · Transformación</p>
        <h1>Impulsamos el futuro <span>digital</span> de tu empresa</h1>
        <p>En Nexus Consulting convertimos los retos tecnológicos en oportunidades de crecimiento. Soluciones a medida, resultados reales.</p>
        <div class="hero-btns">
            <a href="/noticias.php" class="btn btn-primary">Ver noticias</a>
            <?php if (!estaLogueado()): ?>
                <a href="/registro.php" class="btn btn-ghost">Únete ahora</a>
            <?php else: ?>
                <a href="/user/citaciones.php" class="btn btn-ghost">Mis citas</a>
            <?php endif; ?>
        </div>

        <!-- Tarjetas de servicios -->
        <div class="hero-grid">
            <div class="hero-card">
                <div class="hero-card-icon">🚀</div>
                <h3>Transformación Digital</h3>
                <p>Modernizamos tus procesos con tecnología de vanguardia adaptada a tu sector.</p>
            </div>
            <div class="hero-card">
                <div class="hero-card-icon">☁️</div>
                <h3>Cloud & Infraestructura</h3>
                <p>Migramos y optimizamos tu infraestructura en la nube con AWS y GCP.</p>
            </div>
            <div class="hero-card">
                <div class="hero-card-icon">🔒</div>
                <h3>Ciberseguridad</h3>
                <p>Protegemos tus activos digitales con auditorías y soluciones de seguridad avanzadas.</p>
            </div>
            <div class="hero-card">
                <div class="hero-card-icon">📊</div>
                <h3>Business Intelligence</h3>
                <p>Convertimos tus datos en decisiones estratégicas con dashboards en tiempo real.</p>
            </div>
        </div>
    </div>
</section>

<!-- ÚLTIMAS NOTICIAS -->
<?php if (!empty($noticias)): ?>
<section class="section" style="background: var(--bg2); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
    <div class="page">
        <h2 class="section-title">Últimas <span>noticias</span></h2>
        <div class="news-grid">
            <?php foreach ($noticias as $n): ?>
            <article class="news-card">
                <?php
                $img_path = '/uploads/noticias/' . htmlspecialchars($n['imagen']);
                $full_path = __DIR__ . '/uploads/noticias/' . $n['imagen'];
                if (file_exists($full_path)):
                ?>
                    <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($n['titulo']) ?>">
                <?php else: ?>
                    <div class="news-card-img-placeholder">📰</div>
                <?php endif; ?>
                <div class="news-card-body">
                    <p class="news-card-meta">
                        <?= date('d/m/Y', strtotime($n['fecha'])) ?>
                        <span>por <?= htmlspecialchars($n['nombre']) ?></span>
                    </p>
                    <h2><?= htmlspecialchars($n['titulo']) ?></h2>
                    <p><?= nl2br(htmlspecialchars(mb_substr($n['texto'], 0, 120))) ?>…</p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <div style="margin-top: 2rem;">
            <a href="/noticias.php" class="btn btn-ghost">Ver todas las noticias →</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- SOBRE NOSOTROS -->
<section class="section">
    <div class="page">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center;">
            <div>
                <h2 class="section-title">¿Por qué elegir <span>Nexus</span>?</h2>
                <p style="color:var(--text-muted); margin-bottom:1rem;">Llevamos más de 10 años ayudando a empresas de todos los tamaños a alcanzar sus objetivos tecnológicos. Nuestro equipo multidisciplinar combina experiencia técnica con visión de negocio.</p>
                <p style="color:var(--text-muted); margin-bottom:1.5rem;">Trabajamos con metodologías ágiles para garantizar entregas rápidas, transparentes y alineadas con tus necesidades reales.</p>
                <?php if (!estaLogueado()): ?>
                <a href="/registro.php" class="btn btn-primary">Solicitar consulta gratuita</a>
                <?php else: ?>
                <a href="/user/citaciones.php" class="btn btn-primary">Solicitar cita</a>
                <?php endif; ?>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <?php
                $stats = [
                    ['150+', 'Clientes satisfechos'],
                    ['10+', 'Años de experiencia'],
                    ['98%', 'Tasa de éxito'],
                    ['24/7', 'Soporte técnico'],
                ];
                foreach ($stats as $s):
                ?>
                <div class="hero-card" style="text-align:center; padding:1.5rem 1rem;">
                    <div style="font-family:'Syne',sans-serif; font-size:2rem; font-weight:800; color:var(--accent);"><?= $s[0] ?></div>
                    <div style="font-size:.85rem; color:var(--text-muted); margin-top:.3rem;"><?= $s[1] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<footer>
    <p>© <?= date('Y') ?> <span>Nexus Consulting</span>. Todos los derechos reservados.</p>
</footer>

<script src="/js/main.js"></script>
</body>
</html>
