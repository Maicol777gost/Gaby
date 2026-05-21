<?php
require 'conexion.php';
$resultado = $conexion->query("SELECT id_producto, imagen FROM productos");
$count = 0;
while($row = $resultado->fetch_assoc()) {
    $img = $row['imagen'];
    // Buscar prefijos de tiempo como "1778802233_nina2.jpg"
    if (preg_match('/^[0-9]+_(.+)$/', $img, $matches)) {
        $nueva_img = $conexion->real_escape_string($matches[1]);
        $id = $row['id_producto'];
        $conexion->query("UPDATE productos SET imagen = '$nueva_img' WHERE id_producto = $id");
        $count++;
    }
}
echo "¡Listo! Se corrigieron $count imágenes en la base de datos.";
?>
