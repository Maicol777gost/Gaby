<?php
include("conexion.php");
session_start();

$pedido_completado = false;
$id_pedido_nuevo = 0; // FIX C3: declarar en scope global para el template HTML

if (isset($_POST['confirmar'])) {
    // 1. Capturar datos del formulario y limpiar para evitar inyecciones SQL
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $email = mysqli_real_escape_string($conexion, $_POST['email']);
    $metodo_pago = mysqli_real_escape_string($conexion, $_POST['metodo_pago']);
    $direccion_completa = mysqli_real_escape_string($conexion, $_POST['provincia'] . ", " . $_POST['ciudad'] . ", " . $_POST['calle']);
    
    // Usamos el ID de usuario de la sesión. Si no está logueado, podrías manejar un ID 0 o redirigir al login.
    // FIX C2: La clave correcta es 'usuario_id', no 'id_usuario'
    $id_usuario = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : NULL;

    // 2. Iniciar transacción para que si algo falla, no se guarde nada a medias
    mysqli_begin_transaction($conexion);

    try {
        // --- PASO A: Calcular el total real desde la tabla carrito ---
        $check_user = $id_usuario ? "id_usuario = $id_usuario" : "id_usuario IS NULL";
        $sqlCalcularTotal = "SELECT SUM(p.precio * c.cantidad) as total 
                             FROM carrito c 
                             JOIN productos p ON c.id_producto = p.id_producto 
                             WHERE $check_user";
        
        $resTotal = mysqli_query($conexion, $sqlCalcularTotal);
        $filaTotal = mysqli_fetch_assoc($resTotal);
        $total_final = $filaTotal['total'] ?? 0;

        if ($total_final > 0) {
            // --- PASO B: Insertar en la tabla 'pedidos' (en plural como tu imagen) ---
            // Nota: He ajustado los nombres a los que definimos en el CREATE TABLE
            $stmt_pedido = $conexion->prepare("INSERT INTO pedidos (id_usuario, total, fecha_pedido, estado) VALUES (?, ?, NOW(), 'pendiente')");
            $stmt_pedido->bind_param("id", $id_usuario, $total_final);
            
            if (!$stmt_pedido->execute()) {
                throw new Exception("Error al crear el pedido");
            }
            $stmt_pedido->close();
            $id_pedido_nuevo = $conexion->insert_id;

            // --- PASO C: Mover de 'carrito' a 'detalles_pedido' ---
            $sqlDetalle = "INSERT INTO detalles_pedido (id_pedido, id_producto, cantidad, precio_unitario)
                           SELECT '$id_pedido_nuevo', c.id_producto, c.cantidad, p.precio 
                           FROM carrito c
                           INNER JOIN productos p ON c.id_producto = p.id_producto
                           WHERE $check_user";

            if (!mysqli_query($conexion, $sqlDetalle)) {
                throw new Exception("Error al guardar los detalles: " . mysqli_error($conexion));
            }

            // --- PASO D: Vaciar el carrito en la base de datos y en la sesión ---
            mysqli_query($conexion, "DELETE FROM carrito WHERE $check_user");
            $_SESSION['carrito'] = [];

            mysqli_commit($conexion);
            $pedido_completado = true;

        } else {
            throw new Exception("El carrito está vacío.");
        }

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        die("Error en la compra: " . $e->getMessage()); 
    }
} else {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido Confirmado - D' Fiordaliza Style</title>
    <link rel="stylesheet" href="./styles.css?v=1">
    <style>
        .gracias-container { min-height: 80vh; display: flex; justify-content: center; align-items: center; padding: 20px; font-family: 'Poppins', sans-serif; }
        .gracias-box { background: #fff; padding: 40px; border-radius: 20px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 400px; border: 2px solid #f6c1d4; }
        .gracias-box h1 { color: #704d66; margin-bottom: 15px; }
        .gracias-box p { color: #555; }
        .btn-volver { display: inline-block; padding: 12px 25px; border-radius: 25px; background: linear-gradient(135deg, #f6c1d4, #c9b6e4); color: white; text-decoration: none; font-weight: bold; margin-top: 20px; transition: 0.3s; }
        .btn-volver:hover { transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="gracias-container">
        <div class="gracias-box">
            <?php if ($pedido_completado): ?>
                <h1>🎉 ¡Compra Exitosa!</h1>
                <p>Gracias por confiar en <strong>D' Fiordaliza Style</strong>.</p>
                <p>Tu pedido #<?php echo $id_pedido_nuevo; ?> ha sido procesado.</p>
                <a href="index.php" class="btn-volver">Volver a la tienda</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>