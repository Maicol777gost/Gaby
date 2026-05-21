<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('Por favor, inicia sesión.'); window.location='login.php';</script>";
    exit();
}

$u_id = $_SESSION['usuario_id'];
$u_nombre = $_SESSION['usuario_nombre'] ?? "Cliente";
$u_email = $_SESSION['usuario_email'] ?? "";

if (empty($u_email)) {
    $stmt = $conexion->prepare("SELECT email FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $u_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $u_email = $row['email'];
        $_SESSION['usuario_email'] = $u_email;
    }
    $stmt->close();
}

$mostrar_exito = false;
$id_pedido_generado = 0;

// --- LÓGICA DE PROCESAMIENTO ---
if (isset($_POST['confirmar'])) {
    $telefono = mysqli_real_escape_string($conexion, $_POST['telefono_contacto']);
    $provincia = mysqli_real_escape_string($conexion, $_POST['provincia']);
    $ciudad = mysqli_real_escape_string($conexion, $_POST['ciudad']);
    $calle_num = mysqli_real_escape_string($conexion, $_POST['calle_numero']);

    // 1. Insertar Dirección
    mysqli_query($conexion, "INSERT INTO direcciones (id_usuario, provincia, ciudad, sector, calle_numero, telefono_contacto) 
                VALUES ('$u_id', '$provincia', '$ciudad', '$ciudad', '$calle_num', '$telefono')");

    // 2. Calcular Total consultando solo la base de datos
    $sql_t = "SELECT SUM(p.precio * c.cantidad) as total FROM carrito c JOIN productos p ON c.id_producto = p.id_producto WHERE c.id_usuario = $u_id";
    $res_t = mysqli_query($conexion, $sql_t);
    $total_final = mysqli_fetch_assoc($res_t)['total'] ?? 0;

    if ($total_final > 0) {
        // 3. Crear Pedido
        $fecha = date("Y-m-d H:i:s");
        mysqli_query($conexion, "INSERT INTO pedidos (id_usuario, fecha_pedido, total, estado) VALUES ('$u_id', '$fecha', '$total_final', 'pendiente')");
        $id_nuevo_pedido = mysqli_insert_id($conexion);
        $id_pedido_generado = $id_nuevo_pedido;

        // 4. Detalles del Pedido
        $items = mysqli_query($conexion, "SELECT id_producto, cantidad FROM carrito WHERE id_usuario = $u_id");
        while ($item = mysqli_fetch_assoc($items)) {
            $id_p = $item['id_producto'];
            $can = $item['cantidad'];
            $res_p = mysqli_query($conexion, "SELECT precio FROM productos WHERE id_producto = $id_p");
            $pre = mysqli_fetch_assoc($res_p)['precio'];
            mysqli_query($conexion, "INSERT INTO detalles_pedido (id_pedido, id_producto, cantidad, precio_unitario) 
                                    VALUES ('$id_nuevo_pedido', '$id_p', '$can', '$pre')");
        }
        
        // Vaciamos el carrito de la base de datos y de la sesión
        mysqli_query($conexion, "DELETE FROM carrito WHERE id_usuario = '$u_id'");
        $_SESSION['carrito'] = []; 

        $mostrar_exito = true;
    }
}

// Consulta para el resumen lateral (Carrito en Base de Datos)
$sql_carrito = "SELECT p.nombre_producto, p.precio, c.cantidad, (p.precio * c.cantidad) as subtotal 
                FROM carrito c JOIN productos p ON c.id_producto = p.id_producto WHERE c.id_usuario = $u_id";
$res_resumen = mysqli_query($conexion, $sql_carrito);

// CONTADOR DE CARRITO: Contamos lo que realmente está en la base de datos
$sql_count_cart = "SELECT SUM(cantidad) as total_items FROM carrito WHERE id_usuario = $u_id";
$res_count_cart = mysqli_query($conexion, $sql_count_cart);
$row_count_cart = mysqli_fetch_assoc($res_count_cart);
$cantidad_carrito = $row_count_cart['total_items'] ?? 0;

// Actualizamos también la sesión para que el icono del header sea exacto
$_SESSION['carrito_cantidad'] = $cantidad_carrito;

// CONTADOR DE FAVORITOS
$cantidad_favoritos = 0;
if (isset($_SESSION['usuario_id'])) {
    $u_id_fav = $_SESSION['usuario_id'];
    $sql_fav_count = "SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = $u_id_fav";
    $res_fav_count = mysqli_query($conexion, $sql_fav_count);
    $row_fav_count = mysqli_fetch_assoc($res_fav_count);
    $cantidad_favoritos = $row_fav_count['total'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - D' Fiordaliza Style</title>
    <link rel="stylesheet" href="./styles.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fffaf6; margin:0; }
        
        .checkout-layout { 
            display: flex; 
            gap: 30px; 
            max-width: 1100px; 
            margin: 40px auto; 
            padding: 20px; 
            box-sizing: border-box;
        }

        .form-side { 
            flex: 2; 
            background: #fff; 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        }

        .summary-side { 
            flex: 1; 
            background: #fefefe; 
            padding: 25px; 
            border-radius: 20px; 
            border: 2px solid #f6c1d4; 
            position: sticky; 
            top: 20px; 
            height: fit-content; 
        }

        .form h3 { border-left: 5px solid #f6c1d4; padding-left: 10px; color: #704d66; margin-top: 25px; }
        .form input, .form select { width: 100%; padding: 12px; margin-top: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 10px; outline: none; box-sizing: border-box; }
        .resumen-item { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding: 10px 0; font-size: 14px; }
        .total-box { margin-top: 20px; padding-top: 15px; border-top: 2px solid #704d66; display: flex; justify-content: space-between; font-weight: bold; font-size: 20px; color: #704d66; }
        
        /* BOTONES UNIFICADOS */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-confirmar, .btn-volver { 
            flex: 1;
            padding: 15px; 
            border-radius: 25px; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 15px; 
            text-align: center;
            text-decoration: none;
            transition: 0.3s; 
            box-sizing: border-box;
            display: inline-block;
        }

        .btn-confirmar {
            background: linear-gradient(135deg, #f4b4ce, #c1a8e1); 
            color: white; 
            border: none;
        }

        .btn-volver {
            background: white;
            color: #555;
            border: 2px solid #f6c1d4;
        }

        .btn-confirmar:hover, .btn-volver:hover { 
            transform: scale(1.03); 
            filter: brightness(1.05); 
        }

        /* OVERLAY EXITO */
        .overlay-exito { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(112, 77, 102, 0.8); 
            display: flex; align-items: center; justify-content: center; 
            z-index: 10000; backdrop-filter: blur(8px); 
            padding: 20px;
        }
        .card-felicidades { 
            background: white; padding: 45px 30px; border-radius: 30px; 
            text-align: center; max-width: 420px; width: 100%; 
            box-shadow: 0 25px 60px rgba(0,0,0,0.2); 
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex; flex-direction: column; align-items: center;
        }
        .card-felicidades i { 
            font-size: 85px; color: #f4b4ce; margin-bottom: 25px; 
            display: block; filter: drop-shadow(0 5px 15px rgba(244,180,206,0.3));
        }
        .card-felicidades h2 { 
            color: #704d66; font-size: 30px; font-weight: 800; 
            margin-bottom: 12px; line-height: 1.2;
        }
        .card-felicidades p { 
            color: #777; margin-bottom: 30px; font-size: 16px; 
            line-height: 1.5; max-width: 320px;
        }

        .btn-volver-arriba {
            position: absolute;
            top: 25px;
            left: 25px;
            width: 42px;
            height: 42px;
            background: #fff;
            border: 2px solid #f6c1d4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #704d66;
            font-size: 18px;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            z-index: 10;
        }

        .btn-volver-arriba:hover {
            background: #f6c1d4;
            color: white;
            transform: scale(1.08);
        }

        .form-side h2 {
            padding-left: 55px;
        }

        @media (max-width: 768px) {
            .btn-volver-arriba {
                top: 15px;
                left: 15px;
                width: 36px;
                height: 36px;
                font-size: 16px;
            }
            
            .form-side h2 {
                padding-left: 45px;
                font-size: 18px;
            }
        }
        
        @keyframes popIn { 
            0% { transform: scale(0.7); opacity: 0; } 
            100% { transform: scale(1); opacity: 1; } 
        }

        @media (max-width: 768px) { 
            .checkout-layout { 
                flex-direction: column; 
                padding: 15px; 
                margin: 15px auto;
                gap: 20px;
            } 
            
            .summary-side { 
                order: -1; 
                position: static; 
                padding: 15px;
                border-radius: 16px;
            } 

            .form-side {
                padding: 20px;
                border-radius: 16px;
            }

            .form h3 {
                font-size: 16px;
                margin-top: 20px;
            }

            .form input, .form select {
                padding: 14px;
                font-size: 14px;
                border-radius: 12px;
            }

            .total-box {
                font-size: 18px;
            }

            .action-buttons {
                flex-direction: row;
                gap: 8px;
                width: 100%;
            }

            .btn-confirmar, .btn-volver {
                flex: 1;
                font-size: 13px;
                padding: 14px 5px;
                border-radius: 25px;
            }
        }

        .modal-personalizado {
            display: none; 
            position: fixed;
            z-index: 10000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
        }
        .modal-contenido {
            background-color: white;
            margin: 12% auto;
            padding: 35px;
            border-radius: 25px;
            width: 90%;
            max-width: 380px; 
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            position: relative;
            animation: zoomIn 0.3s ease;
        }
        @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .cerrar-modal { position: absolute; right: 20px; top: 15px; font-size: 25px; cursor: pointer; color: #bbb; }
        .modal-acciones { margin-top: 25px; display: flex; flex-direction: column; gap: 12px; }
        .btn-cancelar { background: none; border: none; color: #999; cursor: pointer; text-decoration: underline; font-size: 14px; }
        .button.btn-primary { display: inline-block; padding: 12px 20px; background: linear-gradient(135deg, #f6c1d4, #c9b6e4); color: #fff; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; }
        
        .favorito-btn { border: none; background: #f0f0f0; padding: 10px; border-radius: 10px; cursor: pointer; transition: 0.3s; }
        .favorito-btn.activo { background: #ffe5e5; color: #e63946; }

        .menu {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .menu-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .menu-links a {
            text-decoration: none;
            color: #555;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .menu-links a:hover {
            color: #f6c1d4;
        }

        @media (max-width: 768px) {
            .menu {
                padding: 12px 0;
                background: #fff;
            }

            .menu-links {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                justify-content: flex-start;
                overflow-x: auto;
                gap: 12px;
                padding: 4px 15px;
                -webkit-overflow-scrolling: touch;
            }

            .menu-links a {
                display: inline-block !important;
                font-size: 14px;
                padding: 8px 14px;
                background: #fff5f8;
                border-radius: 15px;
                border: 1px solid #f6c1d4;
                color: #704d66;
            }

            .menu-links a:hover {
                background: #f6c1d4;
                color: white;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-main container">
        <a class="brand" href="index.php">
            <img src="imagenes/logo.jpeg" alt="Logo">
            <div>
                <h1>D' Fiordaliza Style</h1>
                <p>Moda para Damas • Caballeros • Niños</p>
            </div>
        </a>

        <div class="search-box">
            <form action="resultados_busqueda.php" method="GET" style="display: flex; width: 100%; align-items: center;">
                <input type="text" name="termino" placeholder="¿Qué estás buscando?">
                <button type="submit" style="background: none; border: none; cursor: pointer; padding-right: 10px;">🔍</button>
            </form>
        </div>
        
        <div class="header-icons">
             <?php if (isset($_SESSION['usuario_id'])): ?>
                <div class="user-logged-container" style="display: flex; align-items: center; gap: 12px;">
                    <a href="<?php echo (($_SESSION['rol'] ?? '') === 'admin') ? 'admi/dashboard.php' : 'dashboard.php'; ?>" title="Mi Panel">
                        <i class="fa-solid fa-circle-user header-icon-size" style="color: white;"></i>
                    </a>
                    <a href="logout.php" title="Cerrar Sesión">
                        <i class="fa-solid fa-right-from-bracket header-icon-size" style="color: white;"></i>
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php" title="Iniciar Sesión"><i class="fa-solid fa-user header-icon-size" style="color: white;"></i></a>
            <?php endif; ?>

            <a href="carrito.php" class="cart-icon">
                <i class="fa-solid fa-cart-shopping icon"></i>
                <span id="cart-count"><?php echo $cantidad_carrito; ?></span>
            </a>

            <a href="favoritos.php" class="fav-icon">
                <i class="fa-solid fa-heart icon"></i>
                <span id="fav-count"><?php echo $cantidad_favoritos; ?></span>
            </a>

            <a href="contacto.php">
                <i class="fa-solid fa-phone icon"></i>
            </a>
        </div>
    </div>

    <nav class="menu">
        <div class="menu-links">
            <a href="productos.php">Productos</a>
            <a href="damas.php">Damas</a>
            <a href="caballeros.php">Caballeros</a>
            <a href="calzado.php">Calzado</a>
            <a href="accesorios.php">Accesorios</a>
            <a href="cartera.php">Carteras</a>
            <a href="ninos.php">Niños</a>
            <a href="perfumes.php">Perfumes</a>
        </div>
    </nav>
</header>

<?php if ($mostrar_exito): ?>
    <div class="overlay-exito">
        <div class="card-felicidades">
            <i class="fa-solid fa-circle-check"></i>
            <h2>¡Felicidades, Compra Realizada! ✨</h2>
            <p>Tu pedido ha sido registrado. El equipo de <strong>D' Fiordaliza Style</strong> se pondrá en contacto pronto.</p>
            <a href="index.php" class="btn-confirmar" style="text-decoration: none; display: block;">VOLVER AL INICIO</a>
        </div>
    </div>
<?php endif; ?>

<div class="checkout-layout">
    <div class="form-side" style="position: relative;">
        <a href="javascript:history.back()" class="btn-volver-arriba" title="Volver atrás">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h2 style="color: #333; margin-top: 0;">Información de Envío</h2>
        <form class="form" method="POST">
            <h3><i class="fa-solid fa-user"></i> Datos del Cliente</h3>
            <label>Nombre</label>
            <input type="text" value="<?php echo htmlspecialchars($u_nombre, ENT_QUOTES, 'UTF-8'); ?>" disabled>
            <label>Correo Electrónico</label>
            <input type="email" value="<?php echo htmlspecialchars($u_email, ENT_QUOTES, 'UTF-8'); ?>" disabled>
            <label>Teléfono para contacto</label>
            <input type="text" name="telefono_contacto" placeholder="Teléfono para contacto" required>

            <h3><i class="fa-solid fa-location-dot"></i> Dirección de Entrega</h3>
            <input type="text" name="provincia" placeholder="Provincia (Ej: Santiago)" required>
            <input type="text" name="ciudad" placeholder="Ciudad o Sector" required>
            <input type="text" name="calle_numero" placeholder="Calle y No. de casa" required>

            <h3><i class="fa-solid fa-credit-card"></i> Método de Pago</h3>
            <select name="metodo_pago" required>
                <option value="Entrega">Pago contra entrega 🛵</option>
                <option value="Transferencia">Transferencia Bancaria 🏦</option>
            </select>
            
            <div class="action-buttons">
                <button type="submit" name="confirmar" class="btn-confirmar">✅ Confirmar</button>
            </div>
        </form>
    </div>

    <div class="summary-side">
        <h3 style="margin-top: 0; color: #704d66;"><i class="fa-solid fa-receipt"></i> Resumen de Compra</h3>
        <div class="resumen-lista">
            <?php 
            $total_acumulado = 0;
            if($res_resumen && mysqli_num_rows($res_resumen) > 0):
                while($row = mysqli_fetch_assoc($res_resumen)): 
                    $total_acumulado += $row['subtotal'];
            ?>
                <div class="resumen-item">
                    <span><?php echo $row['nombre_producto']; ?> <small>(x<?php echo $row['cantidad']; ?>)</small></span>
                    <strong>RD$ <?php echo number_format($row['subtotal'], 2); ?></strong>
                </div>
            <?php 
                endwhile; 
            else:
                echo "<p>No hay artículos en el carrito.</p>";
            endif;
            ?>
        </div>

        <div class="total-box">
            <span>TOTAL</span>
            <span>RD$ <?php echo number_format($total_acumulado, 2); ?></span>
        </div>
        <p style="font-size: 11px; color: #888; margin-top: 15px; text-align: center;">
            Al confirmar, aceptas nuestros términos de entrega.
        </p>
    </div>
</div>

<footer class="footer">
    <p>D' Fiordaliza Style • Santiago, RD</p>
    <div class="footer-bottom">&copy; 2026 Todos los derechos reservados.</div>
</footer>

</body>
</html>
