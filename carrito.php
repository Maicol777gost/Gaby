<?php
session_start();
include("conexion.php");

/* ===== CREAR CARRITO ===== */
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

/* ===== CONTADOR DEL CARRITO ===== */
$cantidad_carrito = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach($_SESSION['carrito'] as $item){
        $cantidad_carrito += (isset($item['cantidad'])) ? $item['cantidad'] : 0;
    }
}

// Helper: obtener id_usuario de forma segura
$id_usuario = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;

/* =========================================================
    🔥 SOPORTE AJAX (POST) → NO REDIRIGE
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($accion == "agregar" && $id > 0) {
        // Consulta preparada para obtener el producto
        $stmt = $conexion->prepare("SELECT * FROM productos WHERE id_producto = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $producto = $res->fetch_assoc();
        $stmt->close();

        if ($producto) {
            if (isset($_SESSION['carrito'][$id])) {
                $_SESSION['carrito'][$id]['cantidad']++;
            } else {
                $_SESSION['carrito'][$id] = [
                    "nombre" => $producto['nombre_producto'],
                    "precio" => $producto['precio'],
                    "imagen" => $producto['imagen'],
                    "cantidad" => 1
                ];
            }

            // Sincronizar con la BD usando prepared statements (solo si el usuario está logueado)
            if ($id_usuario) {
                $chk = $conexion->prepare("SELECT id_carrito FROM carrito WHERE id_producto = ? AND id_usuario = ?");
                $chk->bind_param("ii", $id, $id_usuario);
                $chk->execute();
                $chk->store_result();

                if ($chk->num_rows > 0) {
                    $chk->close();
                    $upd = $conexion->prepare("UPDATE carrito SET cantidad = cantidad + 1 WHERE id_producto = ? AND id_usuario = ?");
                    $upd->bind_param("ii", $id, $id_usuario);
                    $upd->execute();
                    $upd->close();
                } else {
                    $chk->close();
                    $ins = $conexion->prepare("INSERT INTO carrito (id_usuario, id_producto, cantidad) VALUES (?, ?, 1)");
                    $ins->bind_param("ii", $id_usuario, $id);
                    $ins->execute();
                    $ins->close();
                }
            }
        }
        echo "ok";
        exit;
    }
}

/* =========================================================
    🔧 ACCIONES NORMALES (GET) → CON REDIRECCIÓN
   ========================================================= */
$accion = $_GET['accion'] ?? "";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($accion == "comprar") {
    $_SESSION['carrito'] = [];
    if ($id_usuario) {
        $del = $conexion->prepare("DELETE FROM carrito WHERE id_usuario = ?");
        $del->bind_param("i", $id_usuario);
        $del->execute();
        $del->close();
    }

    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Redireccionando...</title></head><body>";
    echo "<script>alert('¡Gracias por tu compra en D\\' Fiordaliza Style! 🛒\\nTu pedido ha sido procesado con éxito.'); window.location='index.php';</script>";
    echo "</body></html>";
    exit;
}

if ($accion == "eliminar" && $id > 0) {
    unset($_SESSION['carrito'][$id]);
    if ($id_usuario) {
        $del = $conexion->prepare("DELETE FROM carrito WHERE id_producto = ? AND id_usuario = ?");
        $del->bind_param("ii", $id, $id_usuario);
        $del->execute();
        $del->close();
    }
    header("Location: carrito.php");
    exit;
}

if ($accion == "sumar" && $id > 0) {
    if (isset($_SESSION['carrito'][$id])) $_SESSION['carrito'][$id]['cantidad']++;
    if ($id_usuario) {
        $upd = $conexion->prepare("UPDATE carrito SET cantidad = cantidad + 1 WHERE id_producto = ? AND id_usuario = ?");
        $upd->bind_param("ii", $id, $id_usuario);
        $upd->execute();
        $upd->close();
    }
    header("Location: carrito.php");
    exit;
}

if ($accion == "restar" && $id > 0) {
    if (isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id]['cantidad']--;
        if ($_SESSION['carrito'][$id]['cantidad'] <= 0) unset($_SESSION['carrito'][$id]);
    }
    if ($id_usuario) {
        $upd = $conexion->prepare("UPDATE carrito SET cantidad = cantidad - 1 WHERE id_producto = ? AND id_usuario = ?");
        $upd->bind_param("ii", $id, $id_usuario);
        $upd->execute();
        $upd->close();

        // Limpiar registros con cantidad <= 0
        $conexion->query("DELETE FROM carrito WHERE cantidad <= 0 AND id_usuario = $id_usuario");
    }
    header("Location: carrito.php");
    exit;
}

if ($accion == "vaciar") {
    $_SESSION['carrito'] = [];
    if ($id_usuario) {
        $del = $conexion->prepare("DELETE FROM carrito WHERE id_usuario = ?");
        $del->bind_param("i", $id_usuario);
        $del->execute();
        $del->close();
    }
    header("Location: carrito.php");
    exit;
}

$cantidad_favoritos = 0;
if (isset($_SESSION['usuario_id'])) {
    $u_id_fav = (int)$_SESSION['usuario_id'];
    $stmt_fav = $conexion->prepare("SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = ?");
    $stmt_fav->bind_param("i", $u_id_fav);
    $stmt_fav->execute();
    $row_fav_count = $stmt_fav->get_result()->fetch_assoc();
    $cantidad_favoritos = $row_fav_count['total'] ?? 0;
    $stmt_fav->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito | D' Fiordaliza Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./styles.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .vacio {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 40px;
            text-align: center;
            background: #ffffff;
            border-radius: 20px;
            max-width: 550px;
            margin: 40px auto;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
            transition: transform 0.3s ease;
        }
        .vacio:hover { transform: translateY(-5px); }
        .vacio-icon-wrapper {
            background: #fff5f8;
            width: 120px; height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            color: #f6c1d4;
        }
        .vacio-icon-wrapper i { font-size: 50px; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
        .vacio h2 { font-size: 26px; color: #333; margin-bottom: 15px; font-weight: 700; }
        .vacio p { font-size: 16px; color: #777; margin-bottom: 30px; line-height: 1.6; }
        .vacio .btn-primary {
            background-color: #333;
            color: white; padding: 14px 35px;
            border-radius: 50px; text-decoration: none;
            font-weight: 600; transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .vacio .btn-primary:hover { background-color: #f6c1d4; color: #333; transform: scale(1.05); }

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
    
    /* Estilo del botón favorito en el grid */
    .favorito-btn { border: none; background: #f0f0f0; padding: 10px; border-radius: 10px; cursor: pointer; transition: 0.3s; }
    .favorito-btn.activo { background: #ffe5e5; color: #e63946; }
    .cart-icon, .fav-icon { position: relative; display: inline-block; }
    #cart-count, #fav-count { position: absolute; top: -5px; right: -10px; background-color: #f2b2b2; color: white; font-size: 12px; font-weight: 700; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-family: 'Poppins', sans-serif; }

    /* =========================================================
        🎨 ESTILOS GENERALES Y RESPONSIVOS PARA EL MENÚ
       ========================================================= */
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

    /* 📱 AJUSTES EXCLUSIVOS PARA TELÉFONOS */
    @media (max-width: 768px) {
        .menu {
            padding: 12px 0;
            background: #fff;
        }

        .menu-links {
            display: flex;
            flex-direction: row; /* Fila horizontal */
            flex-wrap: nowrap; /* Impide que se bajen los elementos */
            justify-content: flex-start; /* Alinea todo al inicio a la izquierda */
            overflow-x: auto; /* Permite deslizar si hay muchos elementos */
            gap: 12px;
            padding: 4px 15px;
            -webkit-overflow-scrolling: touch; /* Desplazamiento suave con el dedo */
        }

        /* Convertimos los enlaces en botones compactos y cómodos para tocar */
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

html, body {
    height: 100%;
    margin: 0;
    display: flex;
    flex-direction: column;
}

/* IMPORTANTE: NO centrar todo */
main {
    flex: 1;
}

/* --- CONTENEDOR DEL CARRITO --- */
.cart-container { 
    padding: 60px 20px;
    min-height: 60vh;
    display: flex;
    flex-direction: column;
    align-items: center; /* centra el bloque general */
    font-family: sans-serif;
}

.section-title {
    font-size: 20px;
    margin-bottom: 10px;
    color: #5a5858;
}

.empty-item {
    font-size: 65px;
    margin-top: 20px;
    margin-bottom: -50px;
    color: #666;
    text-align: center;
}

.empty-cart {
    font-size: 19px;
    margin-top: 0;
    margin-bottom: 10px;
    color: #666;
    text-align: center;
}

/* BOTÓN centrado */
.empty-cart-btn {
    display: block;
    width: fit-content;
    margin: 0 auto;
    padding: 8px 30px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    background: linear-gradient(135deg, #f6c1d4, #c9b6e4);
    color: white;
    transition: all 0.3s ease;
}

/* --- LISTA DE PRODUCTOS --- */
.cart-list {
    width: 100%;
    max-width: 1000px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ITEM */
.cart-item {
    display: flex;
    align-items: center;
    gap: 20px;
    background: white;
    border-radius: 15px;
    padding: 20px;
    border: 1px solid #f6c1d4;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    transition: 0.3s;
}

.cart-item:hover {
    transform: translateY(-3px);
}

.cart-item img {
    width: 190px;
    height: 190px;
    object-fit: cover;
    border-radius: 10px;
}

/* TEXTO A LA IZQUIERDA */
.cart-info {
    flex: 1;
    text-align: left;
    font-size: 20px;
}

.cart-info h2 {
    font-size: 20px;
    margin-bottom: 8px;
    color: #444;
}

/* --- ACCIONES --- */
.acciones {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 100px;
}

.acciones a {
    padding: 8px 12px;
    font-size: 20px;
    border-radius: 8px;
    color: white;
    text-align: center;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
}

.btn-sumar { background: #6fcf97; }
.btn-restar { background: #4a7af2; }
.btn-eliminar { background: #ff6b6b; }

.acciones a:hover {
    opacity: 0.85;
    transform: scale(1.02);
}

.cart-summary,
.total-row {
    justify-content: left;
    width: 100%;
    max-width: 1000px;
    font-size: 20px;
    font-weight: 600;
    margin-top: 15px;
}

/* --- BOTONES FINALES UNIFICADOS --- */
.cart-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 25px;
    justify-content: center;
    width: 100%;
    max-width: 1000px;
}

.cart-buttons a {
    flex: 1;
    min-width: 180px;
    max-width: 250px;
    padding: 14px 20px;
    border-radius: 25px;
    background: linear-gradient(135deg, #f6c1d4, #c9b6e4);
    color: white;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    text-align: center;
    transition: all 0.3s ease;
    box-sizing: border-box;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* Botón de vaciar un poco más claro sin romper el diseño */
.btn-outline {
    background: white;
    color: #555;
    border: 2px solid #f6c1d4;
}

.cart-buttons a:hover {
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
}

.cart-buttons a.btn-outline:hover {
    background: #fff5f8;
    color: #111;
}

/* --- ADAPTACIÓN PARA TELÉFONOS --- */
@media (max-width: 768px) {
    .cart-container {
        padding: 30px 15px;
    }

    .cart-item {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
        gap: 15px;
        padding: 15px;
    }

    .cart-item img {
        width: 100%;
        height: 200px;
        margin: 0 auto;
    }

    .cart-info {
        text-align: center;
        font-size: 18px;
    }

    .acciones {
        flex-direction: row;
        justify-content: center;
        width: 100%;
        gap: 10px;
    }

    .acciones a {
        flex: 1;
        font-size: 18px;
        padding: 12px;
    }

    .cart-buttons {
        flex-direction: column;
        align-items: center;
    }

    .cart-buttons a {
        width: 100%;
        max-width: 100%;
        padding: 15px;
    }
}
    </style>
</head>
<body>

<div id="modalLogin" class="modal-personalizado">
    <div class="modal-contenido">
        <span class="cerrar-modal" onclick="cerrarModal()">&times;</span>
        <i id="modalIcono" class="fa-solid fa-heart" style="font-size: 50px; color: #f6c1d4; margin-bottom: 15px;"></i>
        <h3 id="modalTitulo">¡Guarda tus favoritos! ❤️</h3>
        <p id="modalMensaje">Para guardar este artículo en tu lista de deseos, primero debes entrar a tu cuenta.</p>
        <div class="modal-acciones">
            <a href="login.php" class="button btn-primary">Iniciar Sesión</a>
            <button onclick="cerrarModal()" class="btn-cancelar">Quizás luego</button>
        </div>
    </div>
</div>

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

<main class="container" style="padding: 15px;">
    <h1 class="section-title" style="text-align: center; margin-top: 20px; font-weight: 800; color: #333;">Tu Carrito</h1>

    <?php if (!isset($_SESSION['usuario_id'])): ?>
        <div class="vacio">
            <div class="vacio-icon-wrapper">
                <i class="fa-solid fa-user-lock"></i>
            </div>
            <h2>¡Hola! Te extrañamos</h2>
            <p>Para poder añadir artículos a tu carrito y personalizar tu experiencia, necesitas iniciar sesión.</p>
            <a href="login.php" class="btn-primary">Iniciar Sesión</a>
        </div>
    <?php elseif (!empty($_SESSION['carrito'])): ?>
        <div class="cart-container">
            <div class="cart-list">
                <?php 
                $total = 0;
                foreach ($_SESSION['carrito'] as $id_p => $item):
                    $sub = $item['precio'] * $item['cantidad'];
                    $total += $sub;
                ?>
                <div class="cart-item card">
                    <a href="detalle.php?id=<?php echo (int)$id_p; ?>">
                        <img src="imagenes/<?php echo htmlspecialchars($item['imagen']); ?>" class="cart-img" alt="Producto">
                    </a>
                    <div class="cart-info">
                        <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                        <p class="price">Precio: RD$ <?php echo number_format($item['precio'], 2); ?></p>
                        <p>Cantidad: <strong><?php echo (int)$item['cantidad']; ?></strong></p>
                        <p class="subtotal">Subtotal: <strong>RD$ <?php echo number_format($sub, 2); ?></strong></p>
                    </div>
                    <div class="acciones">
                        <a href="?accion=sumar&id=<?php echo (int)$id_p; ?>" class="btn-action">➕</a>
                        <a href="?accion=restar&id=<?php echo (int)$id_p; ?>" class="btn-action">➖</a>
                        <a href="?accion=eliminar&id=<?php echo (int)$id_p; ?>" class="btn-action delete">❌</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary card">
                <h2>Resumen del Pedido</h2>
                <div class="total-row">
                    <span>TOTAL A PAGAR:</span>
                    <span class="total-price">RD$ <?php echo number_format($total, 2); ?></span>
                </div>
            </div>

            <div class="cart-buttons">
                <a href="productos.php" class="button btn-primary">⬅ Seguir comprando</a>                    
                <a href="checkout.php" class="button btn-primary">💳 Finalizar Compra</a>
                <a href="?accion=vaciar" class="btn-outline">🗑 Vaciar carrito</a>
            </div>
        </div>
    <?php else: ?>
        <div class="vacio">
            <div class="vacio-icon-wrapper">
                <i class="fa-solid fa-cart-plus"></i>
            </div>
            <h2>Tu carrito está vacío</h2>
            <p>Parece que aún no has elegido nada. ¡Explora nuestra tienda y descubre lo que tenemos para ti!</p>
            <a href="productos.php" class="btn-primary">Explorar tienda</a>
        </div>
    <?php endif; ?>
</main>

<footer class="footer">
    <p>Gabriela Morán Vargas • 2026 • D' Fiordaliza Style • Santiago, RD</p>
    <div class="footer-bottom">&copy; 2026 Todos los derechos reservados.</div>
</footer>

</body>
</html>
