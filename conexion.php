<?php
// Obtener variables de entorno (útil para Render) o usar valores por defecto (InfinityFree)
$host = getenv('DB_HOST') ?: "sql312.infinityfree.com";
$user = getenv('DB_USER') ?: "if0_41790423";
$pass = getenv('DB_PASSWORD') ?: "AIJdBUJpoZ";
$db   = getenv('DB_NAME') ?: "if0_41790423_fiordaliza";
$port = getenv('DB_PORT') ?: null;
$ssl  = getenv('DB_SSL') ?: (strpos($host, 'tidbcloud.com') !== false ? 'true' : 'false');

if ($ssl === 'true') {
    $conexion = mysqli_init();
    // Usar la CA del sistema (Let's Encrypt / ISRG Root X1 que ya viene preinstalada en Render/Linux)
    mysqli_ssl_set($conexion, NULL, NULL, NULL, NULL, NULL);
    
    $con_success = mysqli_real_connect(
        $conexion,
        $host,
        $user,
        $pass,
        $db,
        $port ?: 4000,
        NULL,
        MYSQLI_CLIENT_SSL
    );

    if (!$con_success) {
        die("Error de conexión segura (SSL): " . mysqli_connect_error());
    }
} else {
    if ($port) {
        $conexion = mysqli_connect($host, $user, $pass, $db, $port);
    } else {
        $conexion = mysqli_connect($host, $user, $pass, $db);
    }

    if (!$conexion) {
        die("Error de conexión: " . mysqli_connect_error());
    }
}

// Aceptar ñ, tildes, emojis, etc.
mysqli_set_charset($conexion, "utf8mb4");

// =========================================================================
// MIGRACIÓN AUTOSANADORA (Creación automática de tablas si no existen)
// =========================================================================
try {
    // 0. Crear tabla usuarios si no existe (con id_usuario AUTO_INCREMENT)
    $conexion->query("CREATE TABLE IF NOT EXISTS `usuarios` (
        `id_usuario`  INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `nombre`      VARCHAR(150) NOT NULL,
        `email`       VARCHAR(200) NOT NULL UNIQUE,
        `contraseña`  VARCHAR(255) NOT NULL,
        `rol`         ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
        `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 1. Crear tabla carrito si no existe
    $conexion->query("CREATE TABLE IF NOT EXISTS `carrito` (
        `id_carrito`  INT AUTO_INCREMENT PRIMARY KEY,
        `id_usuario`  INT,
        `id_producto` INT NOT NULL,
        `cantidad`    INT NOT NULL DEFAULT 1,
        INDEX `idx_carrito_usuario` (`id_usuario`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. Crear tabla favoritos si no existe
    $conexion->query("CREATE TABLE IF NOT EXISTS `favoritos` (
        `id_favorito` INT AUTO_INCREMENT PRIMARY KEY,
        `id_usuario`  INT NOT NULL,
        `id_producto` INT NOT NULL,
        UNIQUE KEY `uq_fav` (`id_usuario`, `id_producto`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (Throwable $e) {
    // Silencioso: si el usuario de la base de datos no tiene permisos de CREATE,
    // evitamos colapsar la conexión y permitimos que la página intente cargar.
}
