<?php
session_start();
include("conexion.php");
header('Content-Type: application/json'); // Avisamos que es JSON

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["error" => "No has iniciado sesión"]);
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];

$sql = "SELECT p.* FROM productos p
        INNER JOIN favoritos f ON p.id_producto = f.id_producto
        WHERE f.id_usuario = $id_usuario";

$resultado = mysqli_query($conexion, $sql);
$productos = [];

if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $fila['precio'] = (float)$fila['precio'];
        $productos[] = $fila;
    }
}

echo json_encode($productos);