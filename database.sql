-- ============================================================
-- BASE DE DATOS: NEXUS CONSULTING
-- Trabajo Final PHP - Instituto Tecnológico
-- ============================================================

CREATE DATABASE IF NOT EXISTS nexus_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nexus_web;

-- ------------------------------------------------------------
-- TABLA: users_data
-- ------------------------------------------------------------
CREATE TABLE users_data (
    idUser      INT          NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(100) NOT NULL,
    apellidos   VARCHAR(150) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    telefono    VARCHAR(20)  NOT NULL,
    fecha_nacimiento DATE    NOT NULL,
    direccion   VARCHAR(255),
    sexo        ENUM('Hombre','Mujer','Otro') NOT NULL,
    PRIMARY KEY (idUser)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: users_login
-- ------------------------------------------------------------
CREATE TABLE users_login (
    idLogin  INT          NOT NULL AUTO_INCREMENT,
    idUser   INT          NOT NULL UNIQUE,
    usuario  VARCHAR(80)  NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol      ENUM('admin','user') NOT NULL DEFAULT 'user',
    PRIMARY KEY (idLogin),
    CONSTRAINT fk_login_user FOREIGN KEY (idUser) REFERENCES users_data(idUser) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: citas
-- ------------------------------------------------------------
CREATE TABLE citas (
    idCita       INT          NOT NULL AUTO_INCREMENT,
    idUser       INT          NOT NULL,
    fecha_cita   DATE         NOT NULL,
    motivo_cita  TEXT,
    PRIMARY KEY (idCita),
    CONSTRAINT fk_cita_user FOREIGN KEY (idUser) REFERENCES users_data(idUser) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: noticias
-- ------------------------------------------------------------
CREATE TABLE noticias (
    idNoticia INT          NOT NULL AUTO_INCREMENT,
    titulo    VARCHAR(255) NOT NULL UNIQUE,
    imagen    VARCHAR(255) NOT NULL,
    texto     LONGTEXT     NOT NULL,
    fecha     DATE         NOT NULL,
    idUser    INT          NOT NULL,
    PRIMARY KEY (idNoticia),
    CONSTRAINT fk_noticia_user FOREIGN KEY (idUser) REFERENCES users_data(idUser) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- DATOS DE PRUEBA
-- ------------------------------------------------------------

-- Admin (password: Admin1234)
INSERT INTO users_data (nombre, apellidos, email, telefono, fecha_nacimiento, direccion, sexo)
VALUES ('Admin', 'Nexus', 'admin@nexus.com', '600000001', '1985-01-01', 'Calle Principal 1, Madrid', 'Hombre');

INSERT INTO users_login (idUser, usuario, password, rol)
VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Usuario normal (password: User1234)
INSERT INTO users_data (nombre, apellidos, email, telefono, fecha_nacimiento, direccion, sexo)
VALUES ('Laura', 'García López', 'laura@email.com', '612345678', '1995-06-15', 'Avenida del Sol 22, Madrid', 'Mujer');

INSERT INTO users_login (idUser, usuario, password, rol)
VALUES (2, 'laura95', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Noticias de ejemplo
INSERT INTO noticias (titulo, imagen, texto, fecha, idUser) VALUES
('Nexus abre nueva sede en Barcelona', 'noticia1.jpg', 'Nos complace anunciar la apertura de nuestra nueva oficina en Barcelona, ampliando así nuestra presencia en toda España. Este nuevo espacio nos permitirá atender mejor a nuestros clientes del área mediterránea y contratar talento local. La oficina contará con un equipo de 20 profesionales especializados en consultoría digital y transformación empresarial.', '2025-03-10', 1),
('Nexus certifica a su equipo en Cloud Computing', 'noticia2.jpg', 'Todo el equipo técnico de Nexus Consulting ha superado con éxito las certificaciones oficiales de AWS y Google Cloud Platform. Esta inversión en formación refleja nuestro compromiso con ofrecer soluciones tecnológicas de vanguardia a nuestros clientes. En los próximos meses iniciaremos una nueva ronda de certificaciones en ciberseguridad.', '2025-02-20', 1),
('Nexus lanza su nueva plataforma de gestión', 'noticia3.jpg', 'Hoy presentamos NexusManager, nuestra plataforma propia de gestión de proyectos y clientes desarrollada íntegramente por nuestro equipo. Esta herramienta permite a nuestros clientes hacer seguimiento en tiempo real de sus proyectos, gestionar documentación y comunicarse directamente con su equipo asignado. Está disponible en web y app móvil.', '2025-01-15', 1);

-- Citas de ejemplo
INSERT INTO citas (idUser, fecha_cita, motivo_cita) VALUES
(2, '2026-04-10', 'Consulta sobre transformación digital'),
(2, '2026-05-05', 'Revisión de propuesta de proyecto');
