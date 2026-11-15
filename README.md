# 📁 Nexus Consulting — Trabajo Final PHP

## Estructura del proyecto

```
nexus_web/
├── database.sql                  ← Script SQL (importar en phpMyAdmin)
├── index.php                     ← Página de inicio
├── noticias.php                  ← Listado de noticias
├── registro.php                  ← Registro de nuevos usuarios
├── login.php                     ← Inicio de sesión
├── logout.php                    ← Cierre de sesión
├── css/
│   └── style.css                 ← Estilos del sitio
├── js/
│   └── main.js                   ← JavaScript (navbar, alertas)
├── uploads/
│   └── noticias/                 ← Imágenes de noticias subidas
├── includes/
│   ├── db.php                    ← Conexión a MySQL
│   ├── session.php               ← Gestión de sesiones y roles
│   └── navbar.php                ← Navbar dinámica
├── user/
│   ├── perfil.php                ← Perfil del usuario (rol: user o admin)
│   └── citaciones.php            ← CRUD de citas para usuarios
└── admin/
    ├── usuarios-administracion.php   ← CRUD de usuarios (solo admin)
    ├── citas-administracion.php      ← CRUD de citas de cualquier usuario (solo admin)
    └── noticias-administracion.php   ← CRUD de noticias (solo admin)
```

---

## 🚀 Instalación paso a paso

### 1. Instalar XAMPP
- Descarga XAMPP desde: https://www.apachefriends.org/es/index.html
- Instálalo y abre el **Panel de control de XAMPP**
- Arranca los módulos **Apache** y **MySQL**

### 2. Copiar el proyecto
- Descomprime la carpeta `nexus_web` en: `C:\xampp\htdocs\nexus_web`

### 3. Importar la base de datos
- Abre el navegador y ve a: http://localhost/phpmyadmin
- Haz clic en **"Nuevo"** (barra izquierda)
- Nombre de la base de datos: `nexus_web` → clic en **Crear**
- Ve a la pestaña **Importar**
- Selecciona el archivo `database.sql` y haz clic en **Continuar**

### 4. Verificar la conexión (si es necesario)
- Abre `includes/db.php`
- Si tu MySQL tiene contraseña, cambia: `define('DB_PASS', 'TU_CONTRASEÑA');`

### 5. Acceder al sitio
- Abre el navegador: http://localhost/nexus_web/

---

## 👤 Cuentas de prueba

| Usuario  | Contraseña | Rol   |
|----------|------------|-------|
| admin    | password   | admin |
| laura95  | password   | user  |

---

## ✅ Funcionalidades implementadas

### Base de datos (1 punto)
- [x] Tabla `users_data` con todos los campos requeridos
- [x] Tabla `users_login` con clave foránea, usuario único, contraseña y rol
- [x] Tabla `citas` con clave foránea a users_data
- [x] Tabla `noticias` con clave foránea a users_data

### Sitio web general (4 puntos)
- [x] `index.php` — portada con secciones, servicios, estadísticas y últimas noticias
- [x] `noticias.php` — muestra todas las noticias con título, fecha, texto, imagen y autor
- [x] `registro.php` — formulario completo, validación PHP, contraseña encriptada (bcrypt), redirige al login
- [x] `login.php` — autenticación con password_verify, redirige al index
- [x] Navbar dinámica en TODAS las páginas, resalta página activa

### Usuarios — rol "user" (2.5 puntos)
- [x] `user/perfil.php` — ver y modificar datos personales; cambiar contraseña (no visible)
- [x] `user/citaciones.php` — solicitar, modificar y borrar citas futuras

### Administradores — rol "admin" (2.5 puntos)
- [x] `admin/usuarios-administracion.php` — CRUD completo de usuarios
- [x] `admin/citas-administracion.php` — gestión de citas de cualquier usuario
- [x] `admin/noticias-administracion.php` — CRUD completo de noticias con subida de imagen

### Seguridad
- [x] Contraseñas encriptadas con `password_hash()` (bcrypt)
- [x] Prepared statements en todas las consultas (prevención SQL injection)
- [x] Control de acceso por rol en páginas protegidas
- [x] Validación de formularios en PHP (campos obligatorios, formatos, unicidad)
- [x] `htmlspecialchars()` en todas las salidas (prevención XSS)
