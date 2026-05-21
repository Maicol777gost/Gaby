<?php
session_start();
include 'conexion.php';

/* 🛒 CONTADOR GLOBAL */
$cantidad_carrito = 0;

if(isset($_SESSION['carrito'])){
    foreach($_SESSION['carrito'] as $item){
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nosotros | D' Fiordaliza Style</title>

    <link rel="stylesheet" href="./styles.css?v=1">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root {
  --bg: #fffaf6; --card: #ffffff; --accent: #f6c1d4; --accent2: #c9b6e4;
  --gradient: linear-gradient(135deg, #f6c1d4, #c9b6e4);
  --text: #333; --muted: #777; --shadow: 0 10px 25px rgba(0,0,0,0.08);
}

* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
body { background: var(--bg); color: var(--text); }
.container { width: 90%; max-width: 1300px; margin: auto; }
main.container { margin-top: 40px; }

/* Layout & Video */
.hero-layout { display: grid; grid-template-columns: 1.2fr 1fr; gap: 25px; align-items: start; margin-bottom: 40px; }
.hero-left { display: flex; flex-direction: column; gap: 20px; }
.video-box { height: 620px; border-radius: 15px; overflow: hidden; cursor: pointer; box-shadow: var(--shadow); }
.video-box video { width: 100%; height: 100%; object-fit: cover; }
.video-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); justify-content: center; align-items: center; z-index: 9999; }
.video-modal video { width: 90%; max-width: 1000px; max-height: 80vh; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
.cerrar { position: absolute; top: 20px; right: 30px; font-size: 40px; color: white; cursor: pointer; }

/* Cards & Grid */
.info-card { background: var(--card); padding: 25px; border-radius: 15px; box-shadow: var(--shadow); margin-bottom: 20px; transition: 0.3s; }
.info-card:hover { transform: translateY(-5px); }
.hero-box { background: var(--gradient); color: white; }
.hero-box h2 { margin-bottom: 15px; }
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
.value-list { margin-top: 10px; padding-left: 20px; }
.value-list li { margin-bottom: 10px; }

/* Buttons & Contact */
.btn-primary { display: inline-block; padding: 12px 20px; background: var(--gradient); color: white; border-radius: 8px; text-decoration: none; margin-top: 10px; transition: 0.3s; font-weight: 600; text-align: center; }
.btn-primary:hover { opacity: 0.8; }
.contact { text-align: center; background: #f3e5f5; }

/* Header Icons Color Fix */
.header-icons a i { color: white !important; }

/* Footer */
.footer { background: var(--gradient); color: white; margin-top: 50px; padding: 20px 20px; text-align: center; }
.footer-bottom { text-align: center; margin-top: 15px; font-size: 13px; opacity: 0.8; border-top: 1px solid rgba(255,255,255,0.3); padding-top: 15px; }

/* --- ESTILOS DEL MODAL --- */
.modal-personalizado {
    display: none; position: fixed; z-index: 10000;
    left: 0; top: 0; width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.6); backdrop-filter: blur(5px);
}
.modal-contenido {
    background-color: white; margin: 12% auto; padding: 35px;
    border-radius: 25px; width: 90%; max-width: 380px; 
    text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3);
    position: relative; animation: zoomIn 0.3s ease;
}
@keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.cerrar-modal { position: absolute; right: 20px; top: 15px; font-size: 25px; cursor: pointer; color: #bbb; }
.modal-acciones { margin-top: 25px; display: flex; flex-direction: column; gap: 12px; }
.btn-cancelar { background: none; border: none; color: #999; cursor: pointer; text-decoration: underline; font-size: 14px; }

/* --- ESTILOS DEL MENÚ --- */
.menu { background: #fff; border-bottom: 1px solid #eee; padding: 10px 0; }
.menu-links { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.menu-links a { text-decoration: none; color: #555; font-weight: 600; font-size: 15px; transition: all 0.3s ease; white-space: nowrap; }
.menu-links a:hover { color: #f6c1d4; }

/* =========================================================
    📱 AJUSTES EXCLUSIVOS PARA TELÉFONOS (MAX-WIDTH: 768px)
   ========================================================= */
@media (max-width: 768px) {
    .hero-layout { grid-template-columns: 1fr; gap: 20px; }
    .video-box { height: 350px; }
    
    .menu { padding: 12px 0; background: #fff; }
    .menu-links { display: flex; flex-direction: row; flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; gap: 12px; padding: 4px 15px; -webkit-overflow-scrolling: touch; }
    .menu-links a { display: inline-block !important; font-size: 14px; padding: 8px 14px; background: #fff5f8; border-radius: 15px; border: 1px solid #f6c1d4; color: #704d66; }
    .menu-links a:hover { background: #f6c1d4; color: white; }
    
    .grid { grid-template-columns: 1fr; }
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
                <i class="fa-solid fa-cart-shopping icon" style="color: white;"></i>
                <span id="cart-count"><?php echo $cantidad_carrito; ?></span>
            </a>

            <a href="favoritos.php" class="fav-icon">
                <i class="fa-solid fa-heart icon" style="color: white;"></i>
                <span id="fav-count"><?php echo $cantidad_favoritos; ?></span>
            </a>

            <a href="contacto.php">
                <i class="fa-solid fa-phone icon" style="color: white;"></i>
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

<main class="container">

<section class="hero-layout">

    <div class="hero-left">
        <section class="info-card hero-box">
            <h2>✨ Quiénes Somos: Tu Estilo, Nuestra Pasión</h2>
            <p>En D' Fiordaliza Style somos tu destino de moda en Santiago.</p>
            <p>Ofrecemos ropa, calzado y accesorios para toda la familia.</p>
            <p>Te brindamos calidad, buen precio y atención cercana.</p>
        </section>

        <section class="info-card">
            <h3>💎 Nuestros Pilares</h3>
            <ul class="value-list">
                <li>Atención personalizada</li>
                <li>Variedad de estilos y tallas</li>
                <li>Calidad garantizada</li>
            </ul>
        </section>

        <section class="info-card">
            <h3>🛍️ Comodidad en tu Compra</h3>
            <ul class="value-list">
                <li>Envíos a domicilio</li>
                <li>Recojo en tienda</li>
                <li>Precios accesibles</li>
            </ul>
        </section>
    </div>

    <div class="video-box">
        <video autoplay muted loop playsinline>
            <source src="videos/video de promocion.mp4" type="video/mp4">
        </video>
    </div>

    <div class="video-modal" id="videoModal">
        <span class="cerrar">&times;</span>
        <video id="videoGrande" muted loop playsinline>
            <source src="videos/video de promocion.mp4" type="video/mp4">
        </video>
    </div>

</section>

    <div class="grid">
        <section class="info-card">
            <h3>💎 Visión</h3>
            <ul class="value-list">
                <li>Expandir nuestro catálogo de moda.</li>
                <li>Consolidarnos como tu tienda favorita.</li>
                <li>Innovar continuamente en tendencias.</li>
            </ul>
        </section>

        <section class="info-card">
            <h3>🛍️ Misión</h3>
            <ul class="value-list">
                <li>Facilitar el acceso a estilos modernos.</li>
                <li>Garantizar una experiencia de compra única.</li>
                <li>Apoyar la economía familiar con buenos precios.</li>
            </ul>
        </section>
    </div>

    <section class="info-card contact">
        <h3>📞 Contáctanos</h3>
        <p>Habla directamente con nuestra propietaria:</p>
        <p><strong>Fiordaliza Reyes • 829-674-5204</strong></p>
        <a class="btn-primary" href="contacto.php">¡Envíanos un mensaje!</a>
    </section>

</main>

<footer class="footer">
    <p>Gabriela Morán Vargas • 2026 • D' Fiordaliza Style • Santiago, RD</p>
    <div class="footer-bottom">
        &copy; 2026 Todos los derechos reservados.
    </div>
</footer>

<script>
const videoBox = document.querySelector(".video-box");
const modal = document.getElementById("videoModal");
const cerrar = document.querySelector(".cerrar");
const videoGrande = document.getElementById("videoGrande");

videoBox.addEventListener("click", () => {
    modal.style.display = "flex";
    videoGrande.currentTime = 0;
    videoGrande.muted = false;
    videoGrande.play();
});

cerrar.addEventListener("click", () => {
    modal.style.display = "none";
    videoGrande.pause();
    videoGrande.muted = true;
});

modal.addEventListener("click", (e) => {
    if (e.target === modal) {
        modal.style.display = "none";
        videoGrande.pause();
        videoGrande.muted = true;
    }
});
</script>
</body>
</html>
