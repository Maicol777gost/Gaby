<?php
session_start();
include 'conexion.php';

/* ===========================================================
   1. CONTADORES (Carrito y Favoritos)
   =========================================================== */
$cantidad_carrito = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidad_carrito += (isset($item['cantidad'])) ? $item['cantidad'] : 0;
    }
}

$id_user = $_SESSION['usuario_id'] ?? 0;
$cantidad_favoritos = 0; // Se cambió el nombre aquí
if ($id_user > 0) {
    $res_fav_count = mysqli_query($conexion, "SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = $id_user");
    $row_fav_count = mysqli_fetch_assoc($res_fav_count);
    $cantidad_favoritos = $row_fav_count['total'] ?? 0; // Se cambió el nombre aquí
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favoritos | D' Fiordaliza Style </title>
    <link rel="stylesheet" href="styles.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style><style>
    /* --- PRODUCTOS, FAVORITOS Y CARD --- */
    .productos, .favoritos-container { padding: 30px 15px; max-width: 1400px; margin: auto; }
    .grid-favoritos { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    @media (min-width: 768px) { .grid-favoritos { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; } }
    .card { display: flex; flex-direction: column; height: 100%; background: #fff; border-radius: 24px; padding: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: all 0.3s ease; box-sizing: border-box; }
    .img-container { width: 100%; aspect-ratio: 1/1; overflow: hidden; display: flex; justify-content: center; align-items: center; background-color: #f0f0f0; border-radius: 20px; margin-bottom: 15px; }
    .img-container img { width: 100%; height: 100%; object-fit: cover; }
    .desc-corta { font-size: 14px; color: #666; margin: 5px 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .precio { font-size: 18px; font-weight: 800; color: #b33333; margin: 10px 0; }
    .buttons { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: auto; padding-top: 10px; }
    .form-carrito { flex-grow: 1; }
    .btn-primary { background: var(--gradient); color: white; border: none; width: 100%; padding: 14px; border-radius: 14px; font-weight: 700; cursor: pointer; font-size: 16px; transition: 0.3s; display: inline-block; text-align: center; text-decoration: none; box-sizing: border-box; }
    .btn-primary:hover { transform: translateY(-2px); opacity: 0.9; color: white; text-decoration: none; }
    .vacio a.btn-primary { text-decoration: none; width: auto; min-width: 200px; margin-top: 20px; }
    .favorito-btn { background: #f0f0f0; border: none; cursor: pointer; padding: 10px; display: flex; align-items: center; justify-content: center; transition: transform 0.2s ease, background 0.3s; outline: none; border-radius: 10px; width: 45px; height: 45px; flex-shrink: 0; box-sizing: border-box; }
    .favorito-btn.activo { background: #ffe5e5; }
    .favorito-btn:hover { transform: scale(1.1); }

    /* --- ESTADOS VACÍOS --- */
    .vacio { grid-column: 1/-1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 100px 30px; text-align: center; background: #fff; border-radius: 30px; max-width: 600px; margin: 60px auto; box-shadow: 0 20px 40px rgba(0,0,0,0.03); position: relative; overflow: hidden; border: 1px solid rgba(246, 193, 212, 0.3); }
    .vacio::before { content: '\f004'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; font-size: 350px; color: rgba(255, 77, 77, 0.08); z-index: 0; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-10deg); pointer-events: none; }
    .vacio-icon-wrapper { position: relative; z-index: 1; margin-bottom: 25px; }
    .vacio-icon-wrapper i { font-size: 80px; color: var(--rojo-corazon); }
    .vacio h3, .vacio p, .vacio .btn-primary { position: relative; z-index: 1; }

    /* --- CONTADORES UNIFICADOS --- */
    .cart-icon, .fav-icon { position: relative; display: inline-block; }
    #cart-count, #fav-count { position: absolute; top: -5px; right: -10px; background-color: #f2b2b2; color: white; font-size: 12px; font-weight: 700; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-family: 'Poppins', sans-serif; }

    /* --- MODAL --- */
    .modal-personalizado { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(5px); }
    .modal-contenido { background-color: white; margin: 12% auto; padding: 35px; border-radius: 25px; width: 90%; max-width: 380px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3); position: relative; animation: zoomIn 0.3s ease; box-sizing: border-box; }
    @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .cerrar-modal { position: absolute; right: 20px; top: 15px; font-size: 25px; cursor: pointer; color: #bbb; }
    .modal-acciones { margin-top: 25px; display: flex; flex-direction: column; gap: 12px; }
    .btn-cancelar { background: none; border: none; color: #999; cursor: pointer; text-decoration: underline; font-size: 14px; }
    .button.btn-primary { display: inline-block; padding: 12px 20px; background: linear-gradient(135deg, #f6c1d4, #c9b6e4); color: #fff; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; }

    /* --- MENÚ DE NAVEGACIÓN --- */
    .menu { background: #fff; border-bottom: 1px solid #eee; padding: 10px 0; }
    .menu-links { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
    .menu-links a { text-decoration: none; color: #555; font-weight: 600; font-size: 15px; transition: all 0.3s ease; white-space: nowrap; }
    .menu-links a:hover { color: #f6c1d4; }

    /* --- RESPONSIVO PARA TELÉFONOS --- */
    @media (max-width: 768px) { 
        .productos, .favoritos-container { padding: 20px 10px; } 
        .grid-favoritos { grid-template-columns: repeat(2, 1fr); gap: 12px; } 
        .card { padding: 12px; border-radius: 18px; } 
        .img-container { border-radius: 15px; margin-bottom: 10px; } 
        .desc-corta { font-size: 13px; line-height: 1.3; min-height: 34px; } 
        .precio { font-size: 16px; margin: 8px 0; } 
        .buttons { display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: space-between !important; gap: 8px !important; width: 100%; } 
        .form-carrito { flex: 1 !important; } 
        .btn-primary { padding: 11px; font-size: 13px; border-radius: 10px; width: 100%; display: block; } 
        .favorito-btn { width: 42px !important; height: 42px !important; padding: 8px !important; border-radius: 10px !important; flex-shrink: 0; } 
        .favorito-btn i { font-size: 20px; }
        .menu { padding: 12px 0; background: #fff; }
        .menu-links { display: flex; flex-direction: row; flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; gap: 12px; padding: 4px 15px; -webkit-overflow-scrolling: touch; }
        .menu-links a { display: inline-block !important; font-size: 14px; padding: 8px 14px; background: #fff5f8; border-radius: 15px; border: 1px solid #f6c1d4; color: #704d66; }
        .menu-links a:hover { background: #f6c1d4; color: white; }
    }
    @media (max-width: 480px) { 
        .grid-favoritos { gap: 8px; } 
        .card { padding: 10px; border-radius: 14px; } 
        .buttons { gap: 6px !important; } 
        .btn-primary { padding: 10px 5px; font-size: 12px; border-radius: 8px; } 
        .favorito-btn { width: 38px !important; height: 38px !important; border-radius: 8px !important; } 
        .favorito-btn i { font-size: 18px; } 
        .vacio { margin: 30px 10px; padding: 60px 20px; } 
        .vacio-icon-wrapper i { font-size: 60px; } 
        .vacio::before { font-size: 200px; } 
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

<main class="favoritos-container">
    <h2 style="text-align:center; margin-top: 30px; margin-bottom: 20px; font-weight: 800; color: #333;">Mis Favoritos <span style="color: #ff4d4d;">❤️</span></h2>
    
    <div class="grid-favoritos" id="lista-favoritos">
        <?php
        if ($id_user > 0) {
            $sql = "SELECT p.* FROM productos p 
                    INNER JOIN favoritos f ON p.id_producto = f.id_producto 
                    WHERE f.id_usuario = $id_user";
            $res = mysqli_query($conexion, $sql);

            if (mysqli_num_rows($res) > 0) {
                while ($p = mysqli_fetch_assoc($res)) {
                    ?>
                    <div class="card" id="fav-prod-<?php echo $p['id_producto']; ?>">
                        <a href="detalle.php?id=<?php echo $p['id_producto']; ?>" style="text-decoration: none; color: inherit;">
                            <div class="img-container">
                                <img src="imagenes/<?php echo $p['imagen']; ?>" alt="<?php echo $p['nombre_producto']; ?>">
                            </div>
                        </a>
                        
                        <h3><?php echo $p['nombre_producto']; ?></h3>
                        <p class="desc-corta"><?php echo $p['descripcion']; ?></p> 
                        <p class="precio">RD$ <?php echo number_format($p['precio'], 2); ?></p>
                        
                        <div class="buttons">
                            <form class="form-carrito">
                                <input type="hidden" name="accion" value="agregar">
                                <input type="hidden" name="id" value="<?php echo $p['id_producto']; ?>">
                                <button type="submit" class="btn-primary">Comprar</button>
                            </form>
                            <button class="favorito-btn activo" 
                                    id="btn-fav-<?php echo $p['id_producto']; ?>" 
                                    onclick="eliminarFavorito(<?php echo $p['id_producto']; ?>)" 
                                    title="Quitar de favoritos">
                                <i class="fa-solid fa-heart"></i>
                            </button>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '
                <div class="vacio">
                    <div class="vacio-icon-wrapper">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <h3>¡Huy! Parece que está vacío</h3>
                    <p>Aún no tienes productos guardados en tu lista de deseos. ¡Explora nuestra tienda y encuentra tus próximos favoritos!</p>
                    <a href="productos.php" class="btn-primary">Explorar tienda</a>
                </div>';
            }
        } else {
            echo '
            <div class="vacio">
                <div class="vacio-icon-wrapper">
                    <i class="fa-solid fa-user-lock" style="font-size: 70px; color: #ddd;"></i>
                </div>
                <h3>Inicia sesión</h3>
                <p>Para ver tu lista de favoritos necesitas estar logueado en tu cuenta.</p>
                <a href="login.php" class="btn-primary">Iniciar Sesión</a>
            </div>';
        }
        ?>
    </div>
</main>

<footer class="footer">
    <p>Gabriela Morán Vargas • 2026 • D' Fiordaliza Style • Santiago, RD</p>
    <div class="footer-bottom">
        &copy; 2026 Todos los derechos reservados.
    </div>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const cartCountSpan = document.getElementById("cart-count");
    const favCountSpan = document.getElementById("fav-count");

    window.eliminarFavorito = function(idProducto) {
        return fetch("procesar_favorito.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id_producto: idProducto })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'removed' || data.status === 'success') {
                const tarjeta = document.getElementById(`fav-prod-${idProducto}`);
                if (tarjeta) {
                    tarjeta.style.opacity = "0";
                    tarjeta.style.transform = "scale(0.8)";
                    setTimeout(() => {
                        tarjeta.remove();
                        if(document.querySelectorAll('.grid-favoritos .card').length === 0) location.reload();
                    }, 300);
                }
                if(favCountSpan) {
                    let actual = parseInt(favCountSpan.textContent) || 0;
                    favCountSpan.textContent = Math.max(0, actual - 1);
                }
            }
        })
        .catch(err => console.error("Error al borrar favorito:", err));
    }

    document.querySelectorAll(".form-carrito").forEach(form => {
        form.addEventListener("submit", function(e){
            e.preventDefault();
            const btn = this.querySelector('button');
            const formData = new FormData(this);
            const idProd = formData.get('id');

            btn.textContent = "⌛";
            btn.disabled = true;

            fetch("carrito.php", { method: "POST", body: formData })
            .then(res => res.text())
            .then(res => {
                if(res.trim() === "ok"){
                    if(cartCountSpan) {
                        let actual = parseInt(cartCountSpan.textContent) || 0;
                        cartCountSpan.textContent = actual + 1;
                    }
                    eliminarFavorito(idProd);
                    btn.textContent = "✔";
                    btn.style.backgroundColor = "#28a745";
                } else {
                    btn.textContent = "Error";
                    btn.disabled = false;
                }
            });
        });
    });
});
</script>
</body>
</html>
