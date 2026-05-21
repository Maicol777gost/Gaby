-- ============================================================
-- BASE DE DATOS: fiordaliza
-- Proyecto: D' Fiordaliza Style
-- Generado: 2026-05-12
-- ============================================================

CREATE DATABASE IF NOT EXISTS `fiordaliza` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fiordaliza`;

-- ------------------------------------------------------------
-- TABLA: categorias
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias` (
    `id_categoria` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_categoria` VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO `categorias` (`id_categoria`, `nombre_categoria`) VALUES
(1, 'Damas'),
(2, 'Caballeros'),
(3, 'Niños'),
(4, 'Calzado'),
(5, 'Accesorios'),
(6, 'Logo'),
(7, 'Carteras'),
(8, 'Perfumes');

-- ------------------------------------------------------------
-- TABLA: productos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `productos` (
    `id_producto`    INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_producto` VARCHAR(200) NOT NULL,
    `precio`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `id_categoria`   INT NOT NULL DEFAULT 1,
    `imagen`         VARCHAR(255) NOT NULL DEFAULT 'default.jpg',
    `descripcion`    TEXT,
    CONSTRAINT `fk_prod_cat` FOREIGN KEY (`id_categoria`) REFERENCES `categorias`(`id_categoria`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: usuarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`     VARCHAR(100)  NOT NULL,
    `email`      VARCHAR(150)  NOT NULL UNIQUE,
    `contraseña` VARCHAR(255)  NOT NULL,
    `rol`        ENUM('cliente','admin') NOT NULL DEFAULT 'cliente'
) ENGINE=InnoDB;

-- Insertar Admin por defecto  (contraseña: MaicolElmejor)
-- Hash generado con password_hash("MaicolElmejor", PASSWORD_DEFAULT)
INSERT IGNORE INTO `usuarios` (`nombre`, `email`, `contraseña`, `rol`)
VALUES ('Admin', 'admin@fiordaliza.com', '$2y$10$876TqPEO0D6I0xROSsjjyOgNheyfLQ6V8cZDDWeEpkZUzm8eo4J4.', 'admin');

-- NOTA: Si el hash de arriba no funciona, ejecuta setup_admin.php en el navegador para
-- generar el hash correcto de forma automática.

-- ------------------------------------------------------------
-- TABLA: carrito
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `carrito` (
    `id_carrito`  INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario`  INT,
    `id_producto` INT NOT NULL,
    `cantidad`    INT NOT NULL DEFAULT 1,
    INDEX `idx_carrito_usuario` (`id_usuario`)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: favoritos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `favoritos` (
    `id_favorito` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario`  INT NOT NULL,
    `id_producto` INT NOT NULL,
    UNIQUE KEY `uq_fav` (`id_usuario`, `id_producto`),
    CONSTRAINT `fk_fav_usuario`  FOREIGN KEY (`id_usuario`)  REFERENCES `usuarios`(`id_usuario`)  ON DELETE CASCADE,
    CONSTRAINT `fk_fav_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: direcciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `direcciones` (
    `id_direccion`      INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario`        INT NOT NULL,
    `provincia`         VARCHAR(100),
    `ciudad`            VARCHAR(100),
    `sector`            VARCHAR(100),
    `calle_numero`      VARCHAR(200),
    `telefono_contacto` VARCHAR(30),
    CONSTRAINT `fk_dir_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: pedidos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
    `id_pedido`    INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario`   INT,
    `fecha_pedido` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `total`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `estado`       ENUM('pendiente','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
    INDEX `idx_pedido_usuario` (`id_usuario`)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: detalles_pedido
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `detalles_pedido` (
    `id_detalle`      INT AUTO_INCREMENT PRIMARY KEY,
    `id_pedido`       INT NOT NULL,
    `id_producto`     INT NOT NULL,
    `cantidad`        INT NOT NULL DEFAULT 1,
    `precio_unitario` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `fk_det_pedido`   FOREIGN KEY (`id_pedido`)   REFERENCES `pedidos`(`id_pedido`)   ON DELETE CASCADE,
    CONSTRAINT `fk_det_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id_producto`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: mensajes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mensajes` (
    `id`      INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`  VARCHAR(100)  NOT NULL,
    `email`   VARCHAR(150)  NOT NULL,
    `telefono` VARCHAR(30)  DEFAULT NULL,
    `mensaje` TEXT          NOT NULL,
    `fecha`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `leido`   BOOLEAN       NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: reseñas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reseñas` (
    `id_resena`  INT AUTO_INCREMENT PRIMARY KEY,
    `id_producto` INT NOT NULL,
    `nombre`     VARCHAR(100) NOT NULL,
    `email`      VARCHAR(150) NOT NULL,
    `mensaje`    TEXT         NOT NULL,
    `estrellas`  TINYINT      NOT NULL DEFAULT 5,
    `fecha`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_res_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos`(`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: configuracion_web
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `configuracion_web` (
    `clave`       VARCHAR(50)  NOT NULL PRIMARY KEY,
    `valor`       TEXT         NOT NULL,
    `descripcion` VARCHAR(200) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

INSERT IGNORE INTO `configuracion_web` (`clave`, `valor`, `descripcion`) VALUES
('titulo_bienvenida',  "Descubre tu <span>estilo único</span>",       'Título principal en la página de inicio'),
('texto_bienvenida',   "En D' Fiordaliza Style encontrarás moda moderna, elegante y diseñada para resaltar tu belleza en cada ocasión.", 'Texto secundario de bienvenida'),
('promesa_texto',      "Más que una tienda, somos tu aliado de imagen en Santiago. Ofrecemos piezas seleccionadas bajo los más altos estándares de calidad para que tu única preocupación sea lucir espectacular.", 'Texto de la sección Nuestra Promesa'),
('telefono_contacto',  '829-674-5204',                                 'Teléfono general de contacto');
