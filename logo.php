<?php 
// Es obligatorio iniciar sesión para poder leer los datos del usuario
session_start(); 
include("conexion.php");

$cantidad_favoritos = 0;
if (isset($_SESSION['usuario_id'])) {
    $u_id_fav = $_SESSION['usuario_id'];
    $sql_fav_count = "SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = $u_id_fav";
    $res_fav_count = mysqli_query($conexion, $sql_fav_count);
    $row_fav_count = mysqli_fetch_assoc($res_fav_count);
    $cantidad_favoritos = $row_fav_count['total'] ?? 0;
}

// Lógica de carrito para el contador
$cantidad_carrito = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidad_carrito += $item['cantidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo | D' Fiordaliza Style</title>

    <link rel="stylesheet" href="./styles.css?v=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
<style>
    /* --- ESTILOS DEL MODAL PARA ESTA PÁGINA --- */
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
</style>
</head>

<body>

<?php if (isset($_SESSION['mensaje_bienvenida'])): ?>
    <div class="welcome-toast">
        <div class="toast-content">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
            <span>¡Hola, <?php echo explode(' ', $_SESSION['usuario_nombre'])[0]; ?>! ✨</span>
        </div>
    </div>
    <?php unset($_SESSION['mensaje_bienvenida']); ?>
<?php endif; ?>

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

<main>
  <div class="logo-container" style="text-align: center; padding: 40px 20px;">
      <img src="imagenes/logo.jpeg" class="logo-img" style="max-width: 250px; height: auto; margin-bottom: 20px;">
      <h2>Logo Oficial de D' Fiordaliza Style</h2>
      <p>Este es el logo oficial de la tienda. Da clic derecho para descargar o compartir.</p>
  </div>
</main>

<footer class="footer">
    <p>Gabriela Morán Vargas • 2026 • D' Fiordaliza Style • Santiago, RD</p>
    
    </div>

    <div class="footer-bottom">
        &copy; 2026 Todos los derechos reservados.
    </div>
    </div>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const favCountSpan = document.getElementById("fav-count");
    const cartCountSpan = document.getElementById("cart-count");

    /* ❤️ FUNCIÓN DE FAVORITOS */
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
                if(favCountSpan) {
                    let actual = parseInt(favCountSpan.textContent) || 0;
                    favCountSpan.textContent = actual + 1;
                }
            } else if (data.status === 'removed') {
                icono.classList.replace('fa-solid', 'fa-regular');
                elemento.classList.remove('activo');
                if(favCountSpan) {
                    let actual = parseInt(favCountSpan.textContent) || 0;
                    favCountSpan.textContent = Math.max(0, actual - 1);
                }
            }
        })
        .catch(err => console.error("Error en favoritos:", err));
    }

    /* 🛒 LÓGICA DE CARRITO */
    document.querySelectorAll(".form-carrito").forEach(form => {
        form.addEventListener("submit", function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            const id = this.querySelector('input[name="id_producto"]').value;
            
            let datos = new FormData();
            datos.append("accion", "agregar");
            datos.append("id", id);

            btn.textContent = "⌛";
            
            fetch("carrito.php", { method: "POST", body: datos })
            .then(res => res.text())
            .then(res => {
                if (res.trim() === "ok") {
                    if(cartCountSpan) {
                        let actual = parseInt(cartCountSpan.textContent) || 0;
                        cartCountSpan.textContent = actual + 1;
                    }
                    btn.textContent = "✔";
                    btn.style.background = "#2e7d32";
                }
            });
        });
    });
});
</script>
</body>
</html>
