<?php
// includes/navbar.php — Barra de navegación dinámica

require_once __DIR__ . '/session.php';

$pagina_actual = basename($_SERVER['PHP_SELF']);
function navLink(string $href, string $label, string $pagina_actual): string {
    $activo = (basename($href) === $pagina_actual) ? ' class="active"' : '';
    return "<a href=\"$href\"$activo>$label</a>";
}
?>
<nav class="navbar">
    <div class="nav-brand">
        <a href="/index.php"><span class="brand-icon">⬡</span> Nexus Consulting</a>
    </div>
    <button class="nav-toggle" id="navToggle" aria-label="Menú">
        <span></span><span></span><span></span>
    </button>
    <div class="nav-links" id="navLinks">
        <?= navLink('/index.php', 'Inicio', $pagina_actual) ?>
        <?= navLink('/noticias.php', 'Noticias', $pagina_actual) ?>

        <?php if (esAdmin()): ?>
            <?= navLink('/admin/usuarios-administracion.php', 'Usuarios', $pagina_actual) ?>
            <?= navLink('/admin/citas-administracion.php', 'Citas Admin', $pagina_actual) ?>
            <?= navLink('/admin/noticias-administracion.php', 'Noticias Admin', $pagina_actual) ?>
            <?= navLink('/user/perfil.php', 'Mi Perfil', $pagina_actual) ?>
            <a href="/logout.php" class="nav-logout">Cerrar sesión</a>
        <?php elseif (esUser()): ?>
            <?= navLink('/user/citaciones.php', 'Mis Citas', $pagina_actual) ?>
            <?= navLink('/user/perfil.php', 'Mi Perfil', $pagina_actual) ?>
            <a href="/logout.php" class="nav-logout">Cerrar sesión</a>
        <?php else: ?>
            <?= navLink('/registro.php', 'Registrarse', $pagina_actual) ?>
            <?= navLink('/login.php', 'Iniciar sesión', $pagina_actual) ?>
        <?php endif; ?>
    </div>
</nav>
