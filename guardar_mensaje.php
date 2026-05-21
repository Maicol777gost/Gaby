<?php
include("conexion.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$nombre  = trim($_POST['nombre'] ?? '');
$email   = trim($_POST['email'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

// Validación mínima antes de tocar la BD
if (empty($nombre) || empty($email) || empty($mensaje)) {
    header("Location: contacto.php?error=1");
    exit();
}

// FIX C4: Usar sentencias preparadas en lugar de real_escape_string + concatenación
$stmt = $conexion->prepare("INSERT INTO mensajes (nombre, email, mensaje, fecha) VALUES (?, ?, ?, NOW())");
if ($stmt) {
    $stmt->bind_param("sss", $nombre, $email, $mensaje);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: contacto.php?ok=1");
        exit();
    }
    $stmt->close();
}

// Solo muestra error si algo falla (sin exponer detalles técnicos)
header("Location: contacto.php?error=1");
exit();
?>