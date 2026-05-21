<?php
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

// 1. Capturamos y limpiamos los datos
$id_producto = intval($_POST['id_producto']);
$nombre    = trim($_POST['nombre'] ?? '');
$email     = trim($_POST['email'] ?? '');
$mensaje   = trim($_POST['mensaje'] ?? '');
$estrellas = intval($_POST['estrellas']);

// 2. Validaciones básicas
if ($id_producto <= 0 || empty($nombre) || empty($email) || empty($mensaje) || $estrellas < 1 || $estrellas > 5) {
    header("Location: detalle.php?id=$id_producto&error=1");
    exit();
}

// 3. Sentencia preparada (sin real_escape_string)
$stmt = $conexion->prepare("INSERT INTO reseñas (nombre, email, mensaje, estrellas, fecha, id_producto) VALUES (?, ?, ?, ?, NOW(), ?)");
$stmt->bind_param("sssii", $nombre, $email, $mensaje, $estrellas, $id_producto);

// 4. Ejecutar y redireccionar
if ($stmt->execute()) {
    $stmt->close();
    header("Location: detalle.php?id=$id_producto&success=1#lista-reseñas");
    exit();
} else {
    $stmt->close();
    header("Location: detalle.php?id=$id_producto&error=1");
    exit();
}
?>