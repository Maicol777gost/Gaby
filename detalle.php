<?php
session_start();
include 'conexion.php';

/* =========================================================
   1. VALIDAR Y OBTENER PRODUCTO
   ========================================================= */
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM productos WHERE id_producto = $id";
    $resultado = mysqli_query($conexion, $query);
    $producto = mysqli_fetch_assoc($resultado);

    if (!$producto) {
        header("Location: index.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}

/* =========================================================
   2. CONTADORES
   ========================================================= */
$cantidad_carrito = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidad_carrito += $item['cantidad'];
    }
}

$cantidad_favoritos = 0;
if (isset($_SESSION['usuario_id'])) {
    $u_id_fav = $_SESSION['usuario_id'];
    $sql_fav_count = "SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = $u_id_fav";
    $res_fav_count = mysqli_query($conexion, $sql_fav_count);
    $row_fav_count = mysqli_fetch_assoc($res_fav_count);
    $cantidad_favoritos = $row_fav_count['total'];
}

/* =========================================================
   3. RESEÑAS Y DATOS USUARIO
   ========================================================= */
$sql_reseñas = "SELECT nombre, email, mensaje, estrellas, fecha 
                FROM reseñas 
                WHERE id_producto = $id 
                ORDER BY fecha DESC";
$reseñas = mysqli_query($conexion, $sql_reseñas);

$u_nombre_resena = $_SESSION['usuario_nombre'] ?? "";
$u_email_resena = $_SESSION['usuario_email'] ?? ""; 
$solo_lectura = isset($_SESSION['usuario_id']) ? "readonly" : "";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($producto['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?> | D' Fiordaliza Style</title>
    <link rel="stylesheet" href="./styles.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
    .detalle-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
    .card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 40px; display: flex; gap: 50px; align-items: center; }
    .product-img { width: 75%; max-width: 850px; height: auto; min-width: 400px; border-radius: 15px; object-fit: cover; display: block; }
    .product-info { flex: 1; display: flex; flex-direction: column; gap: 20px; }
    .product-info h2 { font-size: 32px; color: #333; margin: 0; }
    .precio { font-size: 28px; color: #e63946; font-weight: bold; margin: 0; }
    .seccion-resenas { max-width: 1400px; margin: 50px auto; padding: 0 15px; }
    .form { background: white; padding: 50px; border-radius: 20px; margin-bottom: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); max-width: 600px; margin-left: auto; margin-right: auto; }
    .form input, .form textarea { width: 100%; padding: 12px 15px; margin-bottom: 12px; border-radius: 12px; border: 1px solid #ddd; transition: 0.3s; box-sizing: border-box; }
    .form input:focus, .form textarea:focus { border-color: #f6c1d4; outline: none; }
    .estrellas { display: flex; flex-direction: row-reverse; justify-content: center; margin-bottom: 15px; }
    .estrellas input { display: none; }
    .estrellas label { font-size: 32px; color: #ccc; cursor: pointer; transition: 0.2s; }
    .estrellas input:checked ~ label, .estrellas label:hover, .estrellas label:hover ~ label { color: gold; transform: scale(1.1); }
    .form button { width: 100%; padding: 14px; border: none; background: linear-gradient(135deg, #f6c1d4, #c9b6e4); color: white; border-radius: 12px; cursor: pointer; font-weight: bold; transition: 0.3s; }
    .form button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.15); }
    #lista-reseñas { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
    .resena { background: white; padding: 18px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: 0.3s; display: flex; flex-direction: column; gap: 8px; width: 300px; box-sizing: border-box; }
    .no-reseñas { width: 100%; text-align: center; padding: 20px; background: #fff; border-radius: 12px; }
    .button.btn-primary { display: inline-block; padding: 12px 20px; background: linear-gradient(135deg, #f6c1d4, #c9b6e4); color: #fff; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-align: center; text-decoration: none; box-sizing: border-box; }
    .button.btn-outline { display: inline-block; padding: 12px 20px; background: #e6f9ec; color: #28a745; border: 2px solid #28a745; border-radius: 12px; font-weight: 600; cursor: not-allowed; transition: all 0.3s ease; text-align: center; text-decoration: none; box-sizing: border-box; }
    .acciones-producto { display: flex; align-items: center; gap: 15px; margin-top: 15px; }
    .favorito { display: flex; align-items: center; justify-content: center; width: 45px; height: 45px; border-radius: 12px; background: #f8f8f8; font-size: 20px; color: #999; cursor: pointer; transition: all 0.3s ease; flex-shrink: 0; }
    .favorito.activo { color: #e63946; background: #ffe5e5; }
    .modal-personalizado { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(5px); }
    .modal-contenido { background-color: white; margin: 12% auto; padding: 35px; border-radius: 25px; width: 90%; max-width: 380px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3); position: relative; animation: zoomIn 0.3s ease; box-sizing: border-box; }
    @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .cerrar-modal { position: absolute; right: 20px; top: 15px; font-size: 25px; cursor: pointer; color: #bbb; }
    .modal-acciones { margin-top: 25px; display: flex; flex-direction: column; gap: 12px; }
    .btn-cancelar { background: none; border: none; color: #999; cursor: pointer; text-decoration: underline; font-size: 14px; }
    .btn-volver-arriba { position: absolute; top: 20px; left: 20px; width: 40px; height: 40px; background-color: rgba(255, 255, 255, 0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #333; text-decoration: none; font-size: 18px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 10; transition: all 0.3s ease; }
    .btn-volver-arriba:hover { background-color: #f6c1d4; color: white; transform: translateX(-5px); }
    @media (max-width: 900px) { .card { flex-direction: column; gap: 25px; padding: 30px; } .product-img { width: 100%; max-width: 450px; } .seccion-resenas { margin: 30px auto; padding: 0 20px; } .form { max-width: 95%; padding: 30px; } .resena { width: calc(50% - 10px); } }
    @media (max-width: 600px) { .detalle-container { width: 100%; margin: 10px 0; padding: 0 10px; box-sizing: border-box; } .card { display: block !important; width: 100%; padding: 15px; border-radius: 12px; box-sizing: border-box; } .product-img { display: block !important; width: 100% !important; max-width: 100% !important; min-width: 0 !important; height: auto !important; max-height: 350px; object-fit: cover; border-radius: 10px; margin: 0 auto 15px auto; } .product-info { display: block !important; width: 100% !important; text-align: center; } .product-info h2 { font-size: 22px; text-align: center; margin: 0 0 10px 0; } .precio { font-size: 20px; text-align: center; margin: 0 0 15px 0; display: block; } .seccion-resenas { margin: 20px auto; } .form { padding: 25px 15px; border-radius: 15px; } .estrellas label { font-size: 38px; } #lista-reseñas { gap: 15px; } .resena { width: 100%; padding: 20px; } .acciones-producto { display: flex !important; flex-direction: row !important; align-items: center; justify-content: center; gap: 10px; width: 100%; margin-top: 15px; box-sizing: border-box; } .button.btn-primary, .button.btn-outline { flex: 1 !important; display: inline-block; padding: 12px; font-size: 15px; text-align: center; box-sizing: border-box; margin: 0; } .favorito { width: 50px !important; height: 48px !important; display: flex !important; align-items: center; justify-content: center; flex-shrink: 0; margin: 0; border-radius: 12px; } .modal-contenido { width: 95%; margin: 25% auto; padding: 25px 15px; } }
    @media (max-width: 430px) { .form h2 { font-size: 18px; } .btn-volver-arriba { width: 35px; height: 35px; top: 15px; left: 15px; } }

    /* --- ESTILOS DEL MODAL --- */
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

    /* Estilos del Menú */
    .menu { background: #fff; border-bottom: 1px solid #eee; padding: 10px 0; }
    .menu-links { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
    .menu-links a { text-decoration: none; color: #555; font-weight: 600; font-size: 15px; transition: all 0.3s ease; white-space: nowrap; }
    .menu-links a:hover { color: #f6c1d4; }

    /* Animación para el icono del carrito al agregar un producto */
    .animando-al-carrito {
        animation: popCart 0.4s ease-in-out;
    }
    @keyframes popCart {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); color: #f6c1d4; }
        100% { transform: scale(1); }
    }

    @media (max-width: 768px) {
        .menu { padding: 12px 0; background: #fff; }
        .menu-links { display: flex; flex-direction: row; flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; gap: 12px; padding: 4px 15px; -webkit-overflow-scrolling: touch; }
        .menu-links a { display: inline-block !important; font-size: 14px; padding: 8px 14px; background: #fff5f8; border-radius: 15px; border: 1px solid #f6c1d4; color: #704d66; }
        .menu-links a:hover { background: #f6c1d4; color: white; }
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
        
<div class="detalle-container">
    <div class="card" style="position: relative;">
        <a href="javascript:history.back()" class="btn-volver-arriba" title="Volver atrás">
            <i class="fa-solid fa-arrow-left"></i>
        </a>

        <img src="imagenes/<?php echo htmlspecialchars($producto['imagen'], ENT_QUOTES, 'UTF-8'); ?>" class="product-img" id="product-image-detail" alt="<?php echo htmlspecialchars($producto['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?>">

        <div class="product-info">
            <h2><?php echo htmlspecialchars($producto['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="precio">RD$ <?php echo number_format($producto['precio'], 2); ?></p>
            <p><?php echo htmlspecialchars($producto['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>

            <div class="acciones-producto">
                <?php 
                $es_favorito = false;
                if (isset($_SESSION['usuario_id'])) {
                    $id_user = $_SESSION['usuario_id'];
                    $id_prod = $producto['id_producto'];
                    $check_fav = mysqli_query($conexion, "SELECT id_favorito FROM favoritos WHERE id_usuario = $id_user AND id_producto = $id_prod");
                    if (mysqli_num_rows($check_fav) > 0) { $es_favorito = true; }
                }
                ?>
                
                <div class="favorito <?php echo $es_favorito ? 'activo' : ''; ?>" 
                     onclick="<?php echo isset($_SESSION['usuario_id']) ? "gestionarFavorito(this, {$producto['id_producto']})" : "abrirModal('fav')"; ?>">
                    <i class="<?php echo $es_favorito ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                </div>

                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <form class="form-carrito" style="flex:1; width:100%;">
                        <input type="hidden" name="accion" value="agregar">
                        <input type="hidden" name="id" value="<?php echo $producto['id_producto']; ?>">
                        <button type="submit" class="button btn-primary" style="width:100%;">Agregar al carrito</button>
                    </form>
                <?php else: ?>
                    <button type="button" class="button btn-primary" style="flex:1; width:100%;" onclick="abrirModal('cart')">
                        Agregar al carrito
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="seccion-resenas">
    <h2 style="text-align:center;">Deja tu reseña</h2>
    
    <?php if(isset($_GET['success'])) : ?>
        <div style="background:#d4edda; color:#155724; padding:10px; margin-bottom:15px; border-radius:10px; text-align:center;">
            ✅ Reseña enviada correctamente
        </div>
    <?php endif; ?>

    <form class="form" id="formResena" action="procesar_mensaje.php" method="POST">
        <input type="hidden" name="id_producto" value="<?php echo $id; ?>">
        
        <input type="text" name="nombre" placeholder="Tu nombre" value="<?php echo $u_nombre_resena; ?>" <?php echo $solo_lectura; ?> required>
        <input type="email" name="email" placeholder="Tu correo electrónico" value="<?php echo $u_email_resena; ?>" required>
        
        <textarea name="mensaje" placeholder="Escribe tu reseña..." required></textarea>
        
        <div class="estrellas">
            <input type="radio" name="estrellas" value="5" id="estrella5"><label for="estrella5">★</label>
            <input type="radio" name="estrellas" value="4" id="estrella4"><label for="estrella4">★</label>
            <input type="radio" name="estrellas" value="3" id="estrella3"><label for="estrella3">★</label>
            <input type="radio" name="estrellas" value="2" id="estrella2"><label for="estrella2">★</label>
            <input type="radio" name="estrellas" value="1" id="estrella1" required><label for="estrella1">★</label>
        </div>

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <button type="submit">Enviar reseña</button>
        <?php else: ?>
            <button type="button" onclick="abrirModal('review')">Enviar reseña</button>
        <?php endif; ?>
    </form>
    
    <h2 style="text-align:center; margin-top: 40px;">Reseñas de clientes</h2>
    <div id="lista-reseñas">
        <?php
        mysqli_data_seek($reseñas, 0); 

        if (mysqli_num_rows($reseñas) > 0) {
            while ($fila = mysqli_fetch_assoc($reseñas)) {
                $stars = str_repeat("★", $fila['estrellas']) . str_repeat("☆", 5 - $fila['estrellas']);
        ?>
                <div class="resena">
                    <h3><?php echo htmlspecialchars($fila['nombre']); ?></h3>
                    <p style="color: gold; font-size: 18px;"><?php echo $stars; ?></p>
                    <p><?php echo htmlspecialchars($fila['mensaje']); ?></p>
                    <small><?php echo date("d/m/Y", strtotime($fila['fecha'])); ?></small>
                </div>
        <?php
            }
        } else { 
            echo "<div class='no-reseñas'>Este producto aún no tiene reseñas. ¡Sé el primero en comentar!</div>"; 
        }
        ?>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p>Gabriela Morán Vargas • 2026 • D' Fiordaliza Style • Santiago, RD</p>
        <div class="footer-bottom">
            &copy; 2026 Todos los derechos reservados.
        </div>
    </div>
</footer>

<script>
function abrirModal(tipo) {
    const modal = document.getElementById("modalLogin");
    const titulo = document.getElementById("modalTitulo");
    const mensaje = document.getElementById("modalMensaje");
    const icono = document.getElementById("modalIcono");

    if (tipo === 'fav') {
        titulo.innerText = "¡Guarda tus favoritos! ❤️";
        mensaje.innerText = "Para guardar este artículo en tu lista de deseos, primero debes entrar a tu cuenta.";
        icono.className = "fa-solid fa-heart";
    } else if (tipo === 'review') {
        titulo.innerText = "¡Tu opinión cuenta! ✍️";
        mensaje.innerText = "Para publicar tu reseña y ayudar a otros compradores, necesitas iniciar sesión primero.";
        icono.className = "fa-solid fa-star-half-stroke";
    } else {
        titulo.innerText = "¡Casi es tuyo! ✨";
        mensaje.innerText = "Para agregar este producto al carrito y realizar tu compra, por favor inicia sesión.";
        icono.className = "fa-solid fa-cart-shopping";
    }

    modal.style.display = "block";
}

function cerrarModal() {
    document.getElementById("modalLogin").style.display = "none";
}

window.onclick = function(event) {
    if (event.target == document.getElementById("modalLogin")) cerrarModal();
}

document.addEventListener("DOMContentLoaded", function () {
    const cartCountSpan = document.getElementById("cart-count");
    const favCountSpan = document.getElementById("fav-count");
    const cartIconLink = document.querySelector(".cart-icon");
    const productImageDetail = document.getElementById("product-image-detail");

    window.gestionarFavorito = function(elemento, idProducto) {
        const icono = elemento.querySelector('i');
        fetch("procesar_favorito.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id_producto: idProducto })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'added') {
                icono.classList.replace('fa-regular', 'fa-solid');
                elemento.classList.add('activo');
                if(favCountSpan) favCountSpan.textContent = (parseInt(favCountSpan.textContent) || 0) + 1;
            } else if (data.status === 'removed') {
                icono.classList.replace('fa-solid', 'fa-regular');
                elemento.classList.remove('activo');
                if(favCountSpan) favCountSpan.textContent = Math.max(0, (parseInt(favCountSpan.textContent) || 0) - 1);
            }
        });
    }

    document.querySelectorAll(".form-carrito").forEach(form => {
        form.addEventListener("submit", function(e){
            e.preventDefault();
            let formData = new FormData(this);

            if (productImageDetail && cartIconLink) {
                // Clonamos la imagen para la animación
                let clone = productImageDetail.cloneNode(true);
                let rect = productImageDetail.getBoundingClientRect();
                let cartRect = cartIconLink.getBoundingClientRect();

                clone.style.position = "fixed";
                clone.style.left = rect.left + "px";
                clone.style.top = rect.top + "px";
                clone.style.width = rect.width + "px";
                clone.style.height = rect.height + "px";
                clone.style.transition = "all 0.8s cubic-bezier(0.42, 0, 0.58, 1)";
                clone.style.zIndex = "9999";
                clone.style.pointerEvents = "none";
                clone.style.borderRadius = "20px";

                document.body.appendChild(clone);

                requestAnimationFrame(() => {
                    clone.style.left = cartRect.left + "px";
                    clone.style.top = cartRect.top + "px";
                    clone.style.width = "20px";
                    clone.style.height = "20px";
                    clone.style.opacity = "0.2";
                });

                setTimeout(() => { 
                    clone.remove(); 
                    // Agrega la clase de animación 'pop' al icono de carrito del menú
                    cartIconLink.classList.add("animando-al-carrito");
                    setTimeout(() => cartIconLink.classList.remove("animando-al-carrito"), 400);
                }, 800);
            }

            fetch("carrito.php", { method: "POST", body: formData })
            .then(res => res.text())
            .then(res => {
                if(res.trim() === "ok" || res.includes("ok")){
                    if(cartCountSpan) cartCountSpan.textContent = (parseInt(cartCountSpan.textContent) || 0) + 1;
                    this.innerHTML = `<button type="button" class="button btn-outline" style="width:100%;" disabled>✔ Agregado</button>`;
                }
            });
        });
    });
});
</script>
</body>
</html>
