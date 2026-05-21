<?php
session_start();
include "conexion.php"; 

// 1. Consulta para Niños (Categoría 3)
$sql_productos = "SELECT * FROM productos WHERE id_categoria = 3 ORDER BY RAND()";
$resultado = mysqli_query($conexion, $sql_productos);

if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($conexion));
}

// 2. CONTADORES GLOBALES
$cantidad_carrito = 0;

if (isset($_SESSION['usuario_id'])) {
    $u_id = $_SESSION['usuario_id'];
    
    // Consultamos directamente la base de datos para contar los productos
    $sql_cart_count = "SELECT SUM(cantidad) as total FROM carrito WHERE id_usuario = $u_id";
    $res_cart_count = mysqli_query($conexion, $sql_cart_count);
    
    if ($res_cart_count) {
        $row_cart_count = mysqli_fetch_assoc($res_cart_count);
        // Si hay productos sumamos el total, de lo contrario queda en 0
        $cantidad_carrito = $row_cart_count['total'] ?? 0;
    }
}

$cantidad_favoritos = 0;
if (isset($_SESSION['usuario_id'])) {
    $u_id_fav = $_SESSION['usuario_id'];
    $sql_fav_count = "SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = $u_id_fav";
    $res_fav_count = mysqli_query($conexion, $sql_fav_count);
    $row_fav_count = mysqli_fetch_assoc($res_fav_count);
    $cantidad_favoritos = $row_fav_count['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ropa de Niños | D' Fiordaliza Style</title>
    <link rel="stylesheet" href="./styles.css?v=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.modal-personalizado{display:none;position:fixed;z-index:10000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);backdrop-filter:blur(5px)}
.modal-contenido{background:#fff;margin:12% auto;padding:35px;border-radius:25px;width:90%;max-width:380px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,0.3);position:relative;animation:zoomIn .3s ease}
@keyframes zoomIn{from{transform:scale(0.8);opacity:0}to{transform:scale(1);opacity:1}}
.cerrar-modal{position:absolute;right:20px;top:15px;font-size:25px;cursor:pointer;color:#bbb}
.modal-acciones{margin-top:25px;display:flex;flex-direction:column;gap:12px}
.btn-cancelar{background:none;border:none;color:#999;cursor:pointer;text-decoration:underline;font-size:14px}
.button.btn-primary{display:inline-block;padding:12px 20px;background:linear-gradient(135deg,#f6c1d4,#c9b6e4);color:#fff;border:none;border-radius:12px;font-weight:600;cursor:pointer;text-align:center;text-decoration:none}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;padding:20px 0}
.card{background:#fff;border-radius:18px;padding:15px;box-shadow:0 4px 15px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;transition:.3s;position:relative;box-sizing:border-box;border:1px solid #f9f9f9}
.card:hover{transform:translateY(-3px)}
.image-container{position:relative;width:100%;height:210px}
.product-img{width:100%;height:100%;object-fit:cover;border-radius:14px}
.card-body{display:flex;flex-direction:column;flex-grow:1;margin-top:12px;padding:0 4px}
.product-title{font-size:15px;margin:4px 0;font-weight:600;color:#333;line-height:1.3}
.price{font-size:16px;font-weight:700;color:#b84f4f;margin:4px 0 12px 0}
.buttons{margin-top:auto;width:100%;display:flex;gap:10px;align-items:center}
.buttons .btn-comprar{flex:1;padding:11px;border-radius:10px;font-size:15px;font-weight:600;text-align:center;box-sizing:border-box;background:#e2c2e6;color:#fff;text-decoration:none;border:none}
.favorito-btn{position:relative;border:none;background:#fbfbfb;color:#bbb;width:44px;height:44px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 3px 8px rgba(0,0,0,0.06);z-index:5;flex-shrink:0;border:1px solid #f0f0f0;transition:.3s}
.favorito-btn.activo{color:#e63946;background:#fff5f5;border-color:#f6c1d4}
.cart-icon,.fav-icon{position:relative;display:inline-block}
#cart-count,#fav-count{position:absolute;top:-5px;right:-10px;background:#f2b2b2;color:#fff;font-size:12px;font-weight:700;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center}
.menu{background:#fff;border-bottom:1px solid #eee;padding:10px 0}
.menu-links{display:flex;justify-content:center;gap:30px;flex-wrap:wrap;max-width:1200px;margin:0 auto;padding:0 20px}
.menu-links a{text-decoration:none;color:#555;font-weight:600;font-size:15px}
@media (max-width:768px){
  .header-main.container{width:90% !important;margin:0 auto !important;padding:10px 0 !important}
  .menu{padding:12px 0;background:#fff}
  .menu-links{display:flex;flex-direction:row;flex-wrap:nowrap;justify-content:flex-start;overflow-x:auto;gap:12px;padding:4px 15px;-webkit-overflow-scrolling:touch}
  .menu-links a{display:inline-block !important;font-size:14px;padding:8px 14px;background:#fff5f8;border-radius:15px;border:1px solid #f6c1d4;color:#704d66;white-space:nowrap}
  .container.productos{width:100% !important;max-width:100% !important;padding:0 !important;margin:0 !important}
  .grid{grid-template-columns:repeat(2,1fr);gap:10px !important;padding:10px !important}
  .card{padding:10px !important;border-radius:14px;position:relative}
  .image-container{height:135px;margin-bottom:5px}
  .product-title{font-size:13px;margin:2px 0;line-height:1.2}
  .price{font-size:14px;margin:2px 0 6px 0}
  .buttons{display:block !important;width:100%}
  .buttons .btn-comprar{padding:9px;font-size:13px;border-radius:7px;width:100%;display:block}
  .favorito-btn{position:absolute !important;top:8px !important;right:8px !important;width:32px !important;height:32px !important;font-size:14px !important;border-radius:50% !important;background:#fff !important;box-shadow:0 3px 8px rgba(0,0,0,0.12) !important;border:none !important}
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

<main class="container productos">
    <div class="grid">
        <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
            <?php while ($fila = mysqli_fetch_assoc($resultado)): 
                $es_favorito = false;
                if (isset($_SESSION['usuario_id'])) {
                    $id_u = (int) $_SESSION['usuario_id'];
                    $id_p = (int) $fila['id_producto'];
                    $check = mysqli_query($conexion, "SELECT id_favorito FROM favoritos WHERE id_usuario = $id_u AND id_producto = $id_p");
                    if ($check && mysqli_num_rows($check) > 0) { $es_favorito = true; }
                }
            ?>
                <div class="card">
                    <a href="detalle.php?id=<?php echo $fila['id_producto']; ?>" class="product-link" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%;">
                        <div class="image-container">
                            <img class="product-img" src="imagenes/<?php echo htmlspecialchars($fila['imagen']); ?>" alt="<?php echo htmlspecialchars($fila['nombre_producto']); ?>">
                        </div>
                        <div class="card-body">
                            <h2 class="product-title"><?php echo htmlspecialchars($fila['nombre_producto']); ?></h2>
                            <p class="price">RD$ <?php echo number_format($fila['precio'], 2); ?></p>
                        </div>
                    </a>

                    <div class="buttons">
                        <a href="detalle.php?id=<?php echo $fila['id_producto']; ?>" class="btn-comprar">Comprar</a>
                        
                        <button type="button" class="favorito-btn <?php echo $es_favorito ? 'activo' : ''; ?>" 
                                onclick="<?php echo isset($_SESSION['usuario_id']) ? "gestionarFavorito(this, " . (int)$fila['id_producto'] . ")" : "abrirModal()"; ?>">
                            <i class="<?php echo $es_favorito ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-products">No hay ropa de niños registrada.</p>
        <?php endif; ?>
    </div>
</main>

<footer class="footer" style="text-align: center; padding: 20px 0; color: #666;">
    <p>D' Fiordaliza Style • Santiago, RD</p>
    <div class="footer-bottom">&copy; 2026 Todos los derechos reservados.</div>
</footer>

<script>
// 1. CONTROL DEL MODAL
function abrirModal() { 
    var modalLogin = document.getElementById("modalLogin");
    if (modalLogin) { modalLogin.style.display = "block"; } 
}

function cerrarModal() { 
    var modalLogin = document.getElementById("modalLogin");
    if (modalLogin) { modalLogin.style.display = "none"; } 
}

window.addEventListener("click", function(event) {
    var modalLogin = document.getElementById("modalLogin");
    if (event.target === modalLogin) { cerrarModal(); }
});

// 2. GESTIÓN DINÁMICA DE FAVORITOS Y CARRITO
document.addEventListener("DOMContentLoaded", function() {
    var favCountSpan = document.getElementById("fav-count");
    var cartCountSpan = document.getElementById("cart-count");

    // Función para los favoritos
    window.gestionarFavorito = function(elemento, idProducto) {
        var icono = elemento.querySelector('i');
        if (!icono) return;

        fetch("procesar_favorito.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id_producto: idProducto })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.status === 'added') {
                icono.classList.replace('fa-regular', 'fa-solid');
                elemento.classList.add('activo');
                if (favCountSpan) {
                    favCountSpan.textContent = (parseInt(favCountSpan.textContent) || 0) + 1;
                }
            } else if (data.status === 'removed') {
                icono.classList.replace('fa-solid', 'fa-regular');
                elemento.classList.remove('activo');
                if (favCountSpan) {
                    favCountSpan.textContent = Math.max(0, (parseInt(favCountSpan.textContent) || 0) - 1);
                }
            } else if (data.status === 'not_logged') {
                abrirModal();
            }
        })
        .catch(function(err) { console.error("Error en favoritos:", err); });
    };

    // Función para el carrito
    window.actualizarContadorCarrito = function() {
        if (cartCountSpan) {
            // Añadimos un parámetro de tiempo (?t=...) para que el navegador no use datos viejos
            fetch('obtener_carrito.php?t=' + new Date().getTime())
                .then(function(response) { 
                    if (response.ok) return response.text();
                    throw new Error('No encontrado');
                })
                .then(function(cantidad) {
                    cartCountSpan.textContent = cantidad.trim();
                })
                .catch(function(error) { 
                    console.warn("No se pudo actualizar el carrito:", error.message); 
                });
        }
    };

    // --- EL TRUCO PARA CUANDO SE VA HACIA ATRÁS ---
    // El evento 'pageshow' detecta si la página se cargó desde la memoria caché al retroceder
    window.addEventListener('pageshow', function(event) {
        // Si event.persisted es true, significa que el usuario usó el botón atrás
        actualizarContadorCarrito();
    });

    // También lo ejecuta la primera vez que carga la página
    actualizarContadorCarrito();

    // Sincroniza cada 5 segundos por si acaso
    setInterval(actualizarContadorCarrito, 5000);
});
</script>
</body>
</html>
