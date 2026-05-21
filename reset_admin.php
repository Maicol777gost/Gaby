<?php
require 'conexion.php';

$email_admin = 'admin@fiordaliza.com';
$nueva_pass = 'MaicolElmejor';
$hash = password_hash($nueva_pass, PASSWORD_DEFAULT);

// Verificar si el usuario existe
$check = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
$check->bind_param("s", $email_admin);
$check->execute();
$res = $check->get_result();

if ($res->num_rows === 0) {
    // Si no existe, lo creamos
    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, contraseña, rol) VALUES ('Admin', ?, ?, 'admin')");
    $stmt->bind_param("ss", $email_admin, $hash);
} else {
    // Si existe, actualizamos su contraseña
    $stmt = $conexion->prepare("UPDATE usuarios SET contraseña = ?, rol = 'admin', nombre = 'Admin' WHERE email = ?");
    $stmt->bind_param("ss", $hash, $email_admin);
}

if ($stmt->execute()) {
    echo "<div style='font-family:sans-serif; text-align:center; padding: 40px;'>
            <h2 style='color:#10b981;'>✅ Contraseña actualizada correctamente</h2>
            <p><strong>Email:</strong> $email_admin</p>
            <p><strong>Nueva Clave:</strong> $nueva_pass</p>
            <a href='login.php' style='padding: 10px 20px; background: #c9b6e4; text-decoration: none; border-radius: 8px; color: black; font-weight: bold;'>Ir al Login</a>
          </div>";
} else {
    echo "Error al procesar: " . $conexion->error;
}

$stmt->close();
$check->close();
$conexion->close();
?>
