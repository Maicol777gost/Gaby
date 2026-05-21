<?php
session_start();
include "conexion.php";
// 1. Reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Conexión y Sesión (Unificadas al principio)
 

// 3. Consulta para productos optimizada (Eliminando N+1 y usando JOIN)
$sql = "SELECT * FROM productos WHERE imagen NOT LIKE '%logo%' ORDER BY RAND()";
$resultado = mysqli_query($conexion, $sql);

if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($conexion));
}

// 4. CARGAR CONFIGURACIONES DINÁMICAS (Textos de la web)
$cfg = [
    'titulo_bienvenida' => "Descubre tu <span>estilo único</span>",
    'texto_bienvenida' => "En D' Fiordaliza Style encontrarás moda moderna, elegante y diseñada para resaltar tu belleza en cada ocasión.",
    'promesa_texto' => "Más que una tienda, somos tu aliado de imagen en Santiago. Ofrecemos piezas seleccionadas bajo los más altos estándares de calidad para que tu única preocupación sea lucir espectacular.",
    'telefono_contacto' => "809-555-5555"
];
try {
    $res_cfg = $conexion->query("SELECT clave, valor FROM configuracion_web");
    if ($res_cfg) {
        while ($row = $res_cfg->fetch_assoc()) {
            $cfg[$row['clave']] = $row['valor'];
        }
    }
} catch (Exception $e) {
    // Si la tabla no existe aún, usamos los defaults del array $cfg
}

// 2. CONTADORES GLOBALES
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
    $cantidad_favoritos = $row_fav_count['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio | D' Fiordaliza Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
:root { --bg:#fffaf6; --gradient:linear-gradient(135deg,#f4b4ce 0%,#c1a8e1 100%); --text:#333; --dark-purple:#704d66; --muted:#777; --accent:#7a4ea3; }
* { box-sizing: border-box; transition: 0.3s ease; }
body { margin: 0; font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); }
.container { width: 90%; max-width: 1400px; margin: auto; }

/* --- HEADER Y MARCA --- */
header { background: var(--gradient); color: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.header-main { display: flex; justify-content: space-between; align-items: center; padding: 30px 0; max-width: 1200px; margin: auto; width: 90%; }
.brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: #fff; }
.brand img { width: 55px; height: 55px; border-radius: 30%; background: #fff; }
.brand h1 { margin: 0; font-size: 24px; }
.brand p { margin: 0; font-size: 12px; }
.search-box { flex: 1; max-width: 400px; margin: 0 20px; }
.search-box input { width: 100%; padding: 10px 20px; border-radius: 25px; border: none; outline: none; }
.header-icons { display: flex; gap: 15px; font-size: 22px; align-items: center; }
.header-icons a { color: #fff; text-decoration: none; position: relative; }
.user-logged-container i { color: #fff !important; }
.user-logged-container a:hover i { color: #f6c1d4 !important; transform: scale(1.1); }

/* --- MENÚ DE NAVEGACIÓN --- */
.menu { background: #fff; border-bottom: 1px solid #eee; padding: 10px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.menu-links { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.menu-links a { text-decoration: none; color: #555; font-weight: 600; font-size: 15px; transition: all 0.3s ease; white-space: nowrap; flex-shrink: 0; }
.menu-links a:hover { color: #f6c1d4; }
#cart-count, #fav-count { position: absolute; top: -6px; right: -8px; background: #f2b2b2; color: #fff; font-size: 11px; font-weight: bold; padding: 3px 6px; border-radius: 50%; min-width: 18px; text-align: center; }

/* --- BIENVENIDA Y ANUNCIOS --- */
.bienvenida { height: 45vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
.contenido-bienvenida { width: 100%; max-width: 1400px; padding: 40px 80px; text-align: center; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-top: 2px solid rgba(255,255,255,0.4); border-bottom: 2px solid rgba(255,255,255,0.4); border-radius: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.bienvenida h1 { font-size: 2.5rem; color: #271c1c; margin-bottom: 15px; }
.bienvenida h1::after { content: ""; display: block; width: 200px; height: 3px; background: #f6c1d4; margin: 10px auto; }.grid-ads { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 60px; }
.card-ad { position: relative; background: #fff; border-radius: 30px; overflow: hidden; height: 450px; box-shadow: 0 15px 30px rgba(0,0,0,0.1); text-decoration: none; }
.card-ad:hover { transform: translateY(-10px); }
.card-ad .product-img { width: 100%; height: 100%; object-fit: cover; position: absolute; }
.card-ad:hover .product-img { transform: scale(1.1); }
.category-badge { position: absolute; top: 25px; left: 25px; background: #fff; color: var(--accent); padding: 10px 20px; border-radius: 50px; font-weight: 800; font-size: 11px; z-index: 3; text-transform: uppercase; }
.ad-overlay { position: absolute; bottom: 0; width: 100%; padding: 30px; background: linear-gradient(transparent, rgba(0,0,0,0.85)); color: #fff; }

/* --- PRODUCTOS Y CARDS --- */
.productos .grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 40px 25px; padding: 30px 0; }
.productos .card { background: #fff; border-radius: 15px; padding: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; flex-direction: column; height: 100%; }
.productos .card:hover { transform: translateY(-5px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
.product-link { text-decoration: none; color: inherit; display: block; }
.product-img { width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 10px; }
.product-title { font-size: 16px; margin: 10px 0 5px; color: #333; line-height: 1.2; }
.price { font-size: 18px; font-weight: 800; color: #b33333; margin: 10px 0; }
.buttons { margin-top: auto; display: flex; gap: 8px; padding-top: 10px; }
.button { flex: 1; padding: 10px 5px; border-radius: 8px; font-weight: 700; text-align: center; font-size: 13px; text-decoration: none; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.btn-primary { background: var(--gradient); color: #fff; border: none; }
.btn-outline { border: 1px solid #f6c1d4; color: var(--dark-purple); background: #fff; }
.favorito-btn { background: #f0f0f0; border: none; width: 48px; height: 48px; border-radius: 15px; font-size: 22px; color: #ccc; display: flex; align-items: center; justify-content: center; transition: 0.3s; cursor: pointer; }
.favorito-btn.activo { background: #ffe5e5; color: #e63946; }
.favorito-btn:hover { transform: scale(1.1); background: #ebebeb; }

/* --- OTROS COMPONENTES --- */
.about-box { background: var(--gradient); color: #fff; padding: 40px 20px; border-radius: 30px; text-align: center; margin: 20px 0; }
.btn-white { background: #fff; color: var(--accent); padding: 12px 30px; border-radius: 30px; text-decoration: none; font-weight: bold; display: inline-block; }
.welcome-toast { position: fixed; top: 30px; right: 30px; background: linear-gradient(135deg, #704d66, #a1819a); color: #fff; padding: 15px 25px; border-radius: 50px; z-index: 10000; display: flex; align-items: center; gap: 12px; animation: slideIn 0.5s ease-out, fadeOut 0.5s 4s forwards; }
.footer { background: var(--gradient); color: #fff; margin-top: 50px; padding: 20px; text-align: center; }

/* --- MODAL --- */
.modal-personalizado { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(5px); }
.modal-contenido { background-color: white; margin: 12% auto; padding: 35px; border-radius: 25px; width: 90%; max-width: 380px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3); position: relative; animation: zoomIn 0.3s ease; }
.cerrar-modal { position: absolute; right: 20px; top: 15px; font-size: 25px; cursor: pointer; color: #bbb; }
.modal-acciones { margin-top: 25px; display: flex; flex-direction: column; gap: 12px; }
.btn-cancelar { background: none; border: none; color: #999; cursor: pointer; text-decoration: underline; font-size: 14px; }
.button.btn-primary { display: inline-block; padding: 12px 20px; background: linear-gradient(135deg, #f6c1d4, #c9b6e4); color: #fff; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; }
@keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
/* --- RESPONSIVO --- */
@media (max-width: 900px) {
    .header-main { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; padding: 20px; }
    .search-box { order: 3; width: 100%; max-width: 100%; margin-top: 5px; }
    .search-box input { width: 100%; height: 45px; padding: 0 15px; border-radius: 12px; font-size: 15px; border: 1px solid #eee; background-color: #f5f5f5; }
    .menu { justify-content: flex-start; }
    .productos { padding: 20px 15px; }
    .productos .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }

    /* CAMBIO: fondo blanco en vez de amarillo */
    .contenido-bienvenida {
        background: #fff !important;
    }
.bienvenida { height: auto; padding: 40px 20px; text-align: center; }
.bienvenida h1 { font-size: 30px; line-height: 1.3; margin-bottom: 10px; }
.bienvenida h1::after { content: ""; display: block; width: 150px; height: 3px; background: #f6c1d4; margin: 10px auto; }
.bienvenida p { font-size: 16px; line-height: 1.6; }
.brand h1 { font-size: 26px; }
}

/* NUEVO: 3 columnas en tablet */
@media (max-width: 1024px) {
    .productos .grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .menu { padding: 12px 0; background: #fff; }
    .menu-links { display: flex; flex-direction: row; flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; gap: 12px; padding: 4px 15px; -webkit-overflow-scrolling: touch; }
    .menu-links a { display: inline-block !important; font-size: 14px; padding: 8px 14px; background: #fff5f8; border-radius: 15px; border: 1px solid #f6c1d4; color: #704d66; }
    .menu-links a:hover { background: #f6c1d4; color: white; }
}
@media (max-width: 600px) {
    body { overflow-x: hidden; }

    /* HEADER */
    .header-main {
        justify-content: flex-start;
        gap: 12px;
        padding: 12px;
    }

    .brand { justify-content: flex-start; }

    .brand h1 {
        font-size: 20px;
        text-align: left;
    }

    .search-box input { height: 40px; font-size: 14px; }

/* 🔥 PRODUCTOS BIEN ARREGLADOS */
.productos { padding: 10px 8px; }
.productos .grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
.productos .card { position: relative; padding: 6px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }

/* IMAGEN PERFECTA (CLAVE) */
.product-img { width: 100%; height: 140px; object-fit: cover; border-radius: 8px; margin-bottom: 6px; }

/* TEXTO MÁS LIMPIO */
.product-title { font-size: 13px; margin: 4px 0; line-height: 1.2; height: 32px; overflow: hidden; }
.price { font-size: 14px; margin: 4px 0; }

/* BOTONES MÁS PEQUEÑOS */
.buttons { gap: 5px; padding-top: 5px; }
.button { font-size: 11px; padding: 6px 4px; border-radius: 6px; }

/* FAVORITO ARRIBA EN LA ESQUINA */
.favorito-btn { position: absolute; top: 12px; right: 12px; width: 30px !important; height: 30px !important; font-size: 13px; display: flex; align-items: center; justify-content: center; z-index: 10; }

    /* BIENVENIDA */
    .bienvenida { padding: 25px 10px; }
    .bienvenida h1 { font-size: 22px; }
    .bienvenida p { font-size: 14px; }
}

@media (max-width: 430px) {
    /* CAMBIO: mantener 2 productos por fila */
    .productos .grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .brand h1 { font-size: 20px; }
    .bienvenida h1 { font-size: 22px; }
    .bienvenida p { font-size: 14px; line-height: 1.5; }
    .search-box input { font-size: 13px; }
}

@keyframes slideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
@keyframes fadeOut { from { opacity: 1; } to { opacity: 0; visibility: hidden; } }

</style>
</head>
<body>
<?php
// Mensaje flotante de bienvenida
if (isset($_SESSION['mensaje_bienvenida'])): ?>
    <div class="welcome-toast">
        <i class="fa-solid fa-wand-magic-sparkles"></i>
        <span>¡Hola, <?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nombre'])[0], ENT_QUOTES, 'UTF-8'); ?>! ✨ Bienvenido a D' Fiordaliza</span>
    </div>
    <?php unset($_SESSION['mensaje_bienvenida']); ?>
<?php endif; ?>

<div id="modalLogin" class="modal-personalizado" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);">
    <div class="modal-contenido" style="background: white; margin: 12% auto; padding: 35px; border-radius: 25px; width: 90%; max-width: 380px; text-align: center; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
        <span class="cerrar-modal" onclick="cerrarModal()" style="position: absolute; right: 20px; top: 15px; font-size: 25px; cursor: pointer; color: #bbb;">&times;</span>
        <i class="fa-solid fa-heart" style="font-size: 50px; color: #f4b4ce; margin-bottom: 15px;"></i>
        <h3 style="color: #704d66; font-family: 'Poppins', sans-serif;">¿Te encanta? ❤️</h3>
        <p style="font-family: 'Poppins', sans-serif; color: #666;">Inicia sesión para guardar este producto en tus favoritos y no perderlo de vista.</p>
        <div style="display:flex; flex-direction:column; gap:10px; margin-top:20px;">
            <a href="login.php" class="button btn-primary" style="padding: 15px; text-decoration: none;">Iniciar Sesión</a>
            <button onclick="cerrarModal()" style="background:none; border:none; text-decoration:underline; cursor:pointer; color:#999; font-family: 'Poppins', sans-serif;">Ahora no</button>
        </div>
    </div>
</div>


<header>
    <div class="header-main container">
        <a class="brand" href="logo.php">
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
                        <i class="fa-solid fa-circle-user" style="color: white;"></i>
                    </a>
                    <a href="logout.php" title="Cerrar Sesión">
                        <i class="fa-solid fa-right-from-bracket" style="color: white;"></i>
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php" title="Iniciar Sesión"><i class="fa-solid fa-user" style="color: white;"></i></a>
            <?php endif; ?>

            <a href="carrito.php" class="cart-icon">
                <i class="fa-solid fa-cart-shopping"></i>
                <span id="cart-count"><?php echo $cantidad_carrito; ?></span>
            </a>

            <a href="favoritos.php" class="fav-icon">
                <i class="fa-solid fa-heart"></i>
                <span id="fav-count"><?php echo $cantidad_favoritos; ?></span>
            </a>

            <a href="contacto.php">
                <i class="fa-solid fa-phone"></i>
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
    <section class="bienvenida">
        <div class="contenido-bienvenida">
            <h1><?php echo $cfg['titulo_bienvenida']; ?></h1>
            <p><?php echo htmlspecialchars($cfg['texto_bienvenida']); ?></p>
        </div>
    </section>

    <section class="grid-ads">
        <a href="damas.php" class="card-ad">
            <div class="category-badge">Colección Damas</div>
            <img id="img-damas" class="product-img" src="imagenes/default.jpg" alt="Producto damas">
            <div class="ad-overlay">
                <h3 id="title-damas">Producto</h3> 
                <span id="price-damas" style="color: white; font-weight: 800; font-size: 18px;"></span>
            </div>
        </a>

        <a href="caballeros.php" class="card-ad">
            <div class="category-badge">Línea Masculina</div>
            <img id="img-caballeros" class="product-img">
            <div class="ad-overlay">
                <h3 id="title-caballeros" style="margin:0; font-size: 24px;"></h3>
                <span id="price-caballeros" style="color: white; font-weight: 800; font-size: 18px;"></span>
            </div>
        </a>

        <a href="ninos.php" class="card-ad">
            <div class="category-badge">Moda Infantil</div>
            <img id="img-ninos" class="product-img">
            <div class="ad-overlay">
                <h3 id="title-ninos" style="margin:0; font-size: 24px;"></h3>
                <span id="price-ninos" style="color: white; font-weight: 800; font-size: 18px;"></span>
            </div>
        </a>
    </section>

    <section class="about-box">
        <h2>✨ Nuestra Promesa ✨</h2>
        <p style="font-size: 1.3em; max-width: 750px; margin: 25px auto; font-weight: 400;">
            <?php echo htmlspecialchars($cfg['promesa_texto']); ?>
        </p>
        <a href="nosotros.php" class="btn-white">Descubre nuestra historia</a>
    </section>
</main>

<main class="container productos">
    <div class="grid">
        <?php 
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            while($fila = mysqli_fetch_assoc($resultado)){ 
                $es_favorito = false;
                if (isset($_SESSION['usuario_id'])) {
                    $id_u = $_SESSION['usuario_id'];
                    $id_p = $fila['id_producto'];
                    $check = mysqli_query($conexion, "SELECT id_favorito FROM favoritos WHERE id_usuario = $id_u AND id_producto = $id_p");
                    if (mysqli_num_rows($check) > 0) { $es_favorito = true; }
                }
        ?>
            <div class="card">
                <a href="detalle.php?id=<?php echo $fila['id_producto']; ?>" class="product-link">
                    <img class="product-img" src="imagenes/<?php echo $fila['imagen']; ?>" alt="<?php echo $fila['nombre_producto']; ?>">
                    <div class="card-body">
                        <h3 class="product-title"><?php echo $fila['nombre_producto']; ?></h3>
                        <p class="price">RD$ <?php echo number_format($fila['precio'], 2); ?></p>
                    </div>
                </a>

                <div class="buttons">
                    <a href="detalle.php?id=<?php echo $fila['id_producto']; ?>" class="button btn-primary">Comprar</a> 
                    <button class="favorito-btn <?php echo $es_favorito ? 'activo' : ''; ?>" 
                            onclick="<?php echo isset($_SESSION['usuario_id']) ? "gestionarFavorito(this, {$fila['id_producto']})" : "abrirModal()"; ?>">
                        <i class="<?php echo $es_favorito ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                    </button>
                </div>
            </div> 
        <?php 
            } 
        } else {
            echo "<p class='no-products'>No hay productos registrados.</p>";
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
    const damas = [
        {img:"imagenes/vestido4.jpeg", title:"Vestido de Gala", price:"RD$2200"},
        {img:"imagenes/vestido2.jpg", title:"Elegancia Pura", price:"RD$1800"},
        {img:"imagenes/blusaamarilla2.jpg", title:"Blusa Moderna", price:"RD$780"},
        {img:"imagenes/camisamujer2.jpg", title:"Camisa Elegante", price:"RD$1200"},
        {img:"imagenes/conjumj6.jpeg", title:"Conjunto Playero", price:"RD$1900"},
        {img:"imagenes/falda2.jpg", title:"Falda Elegante", price:"RD$970"}
    ];
    const caballeros = [
        {img:"imagenes/pantalonhm6.jpeg", title:"Estilo Casual", price:"RD$1350"},
        {img:"imagenes/camisahm1.jpg", title:"Corte Ejecutivo", price:"RD$650"},
        {img:"imagenes/polohm5.jpeg", title:"Clasico Moderno", price:"RD$1200"},
        {img:"imagenes/tenishm2.jpg", title:"Tenis Casuales", price:"RD$2200"},
        {img:"imagenes/pantalonhm12.jpeg", title:"Estilo Casual", price:"RD$1250"},
        {img:"imagenes/hombre10.jpeg", title:"Conjunto Casual", price:"RD$1550"}
    ];
    const ninos = [
        {img:"imagenes/nino2.jpeg", title:"Conjunto Junior", price:"RD$1200"},
        {img:"imagenes/nina17.jpeg", title:"Vestido Sweet", price:"RD$1200"},
        {img:"imagenes/nina9.jpeg", title:"Conjunto Verde", price:"RD$1200"},
        {img:"imagenes/nino4.jpeg", title:"Conjunto Casual", price:"RD$1200"},
        {img:"imagenes/nina15.jpeg", title:"Conjunto de Flores", price:"RD$1200"},
        {img:"imagenes/conjuntodeniño1.jpg", title:"Conjunto Junior", price:"RD$560"}
    ];

    function slider(lista, imgId, titleId, priceId){
        let i=0;
        function cambiar(){
            const imgElement = document.getElementById(imgId);
            if(imgElement) {
                imgElement.src = lista[i].img;
                document.getElementById(titleId).innerText = lista[i].title;
                document.getElementById(priceId).innerText = lista[i].price;
                i=(i+1)%lista.length;
            }
        }
        cambiar();
        setInterval(cambiar, 3800);
    }

    slider(damas,"img-damas","title-damas","price-damas");
    slider(caballeros,"img-caballeros","title-caballeros","price-caballeros");
    slider(ninos,"img-ninos","title-ninos","price-ninos");

    function abrirModal() { document.getElementById("modalLogin").style.display = "block"; }
    function cerrarModal() { document.getElementById("modalLogin").style.display = "none"; }
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const favCountSpan = document.getElementById("fav-count");

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
                    favCountSpan.textContent = (parseInt(favCountSpan.textContent) || 0) + 1;
                }
            } else if (data.status === 'removed') {
                icono.classList.replace('fa-solid', 'fa-regular');
                elemento.classList.remove('activo');
                if(favCountSpan) {
                    favCountSpan.textContent = Math.max(0, (parseInt(favCountSpan.textContent) || 0) - 1);
                }
            }
        })
        .catch(err => console.error("Error en favoritos:", err));
    }
});
</script>
</body>
</html>