<?php
// Obtener variables de entorno (útil para Render) o usar valores por defecto (InfinityFree)
$host = getenv('DB_HOST') ?: "sql312.infinityfree.com";
$user = getenv('DB_USER') ?: "if0_41790423";
$pass = getenv('DB_PASSWORD') ?: "AIJdBUJpoZ";
$db   = getenv('DB_NAME') ?: "if0_41790423_fiordaliza";
$port = getenv('DB_PORT') ?: null;

if ($port) {
    $conexion = mysqli_connect($host, $user, $pass, $db, $port);
} else {
    $conexion = mysqli_connect($host, $user, $pass, $db);
}

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Aceptar ñ, tildes, emojis, etc.
mysqli_set_charset($conexion, "utf8mb4");
?>

