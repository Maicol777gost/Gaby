<?php
session_start();
include("conexion.php");

$cantidad_carrito = 0;

if (isset($_SESSION['usuario_id'])) {
    $u_id = (int)$_SESSION['usuario_id'];
    $sql_cart_count = "SELECT SUM(cantidad) as total FROM carrito WHERE id_usuario = $u_id";
    $res_cart_count = mysqli_query($conexion, $sql_cart_count);
    if ($res_cart_count) {
        $row_cart_count = mysqli_fetch_assoc($res_cart_count);
        $cantidad_carrito = $row_cart_count['total'] ?? 0;
    }
} else {
    // Si es invitado y se usa el carrito de sesión
    if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $item) {
            $cantidad_carrito += (isset($item['cantidad'])) ? (int)$item['cantidad'] : 0;
        }
    }
}

echo $cantidad_carrito;
