<?php
// includes/session.php — Gestión de sesión centralizada

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function estaLogueado(): bool {
    return isset($_SESSION['idUser']);
}

function esAdmin(): bool {
    return estaLogueado() && $_SESSION['rol'] === 'admin';
}

function esUser(): bool {
    return estaLogueado() && $_SESSION['rol'] === 'user';
}

function requiereLogin(): void {
    if (!estaLogueado()) {
        header('Location: /login.php');
        exit;
    }
}

function requiereAdmin(): void {
    if (!esAdmin()) {
        header('Location: /index.php');
        exit;
    }
}
