<?php
session_start();
include 'conexion.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
$id_user = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
if ($id_user === 0) {
    echo json_encode(['status' => 'not_logged']);
    exit;
}

// Leer JSON del body
$data = json_decode(file_get_contents("php://input"), true);
$id_prod = isset($data['id_producto']) ? (int)$data['id_producto'] : 0;

if ($id_prod === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
    exit;
}

// USAR SENTENCIAS PREPARADAS (Fix A1 - SQLi)
$check = $conexion->prepare("SELECT id_favorito FROM favoritos WHERE id_usuario = ? AND id_producto = ?");
$check->bind_param("ii", $id_user, $id_prod);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    $del = $conexion->prepare("DELETE FROM favoritos WHERE id_usuario = ? AND id_producto = ?");
    $del->bind_param("ii", $id_user, $id_prod);
    $del->execute();
    $del->close();
    $status = 'removed';
} else {
    $check->close();
    $ins = $conexion->prepare("INSERT INTO favoritos (id_usuario, id_producto) VALUES (?, ?)");
    $ins->bind_param("ii", $id_user, $id_prod);
    $ins->execute();
    $ins->close();
    $status = 'added';
}

// Contar total actual de favoritos
$count = $conexion->prepare("SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = ?");
$count->bind_param("i", $id_user);
$count->execute();
$row = $count->get_result()->fetch_assoc();
$count->close();

echo json_encode(['status' => $status, 'total' => (int)$row['total']]);
?>