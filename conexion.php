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
