<?php 
session_start();
include("conexion.php"); 

/* =========================================================
   1. PROCESAR ENVÍO DE MENSAJE (Solo si hay sesión)
   ========================================================= */
if (isset($_POST['enviar'])) {
    if (isset($_SESSION['usuario_id'])) {
        $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
        $email  = mysqli_real_escape_string($conexion, $_POST['email']);
        $msg    = mysqli_real_escape_string($conexion, $_POST['mensaje']);

        if (!empty($nombre) && !empty($email) && !empty($msg)) {
            $consulta = "INSERT INTO mensajes (nombre, email, mensaje) 
                         VALUES ('$nombre', '$email', '$msg')";
            $resultado = mysqli_query($conexion, $consulta);

            if ($resultado) {
                header("Location: contacto.php?ok=1");
                exit();
            }
        }
    }
}

/* =========================================================
   2. CONTADORES (Carrito y Favoritos)
   ========================================================= */
$cantidad_carrito = 0;
if(isset($_SESSION['carrito'])){
    foreach($_SESSION['carrito'] as $item){ $cantidad_carrito += $item['cantidad']; }
}

$cantidad_favoritos = 0;
if (isset($_SESSION['usuario_id'])) {
    $u_id_fav = $_SESSION['usuario_id'];
    $sql_fav_count = "SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = $u_id_fav";
    $res_fav_count = mysqli_query($conexion, $sql_fav_count);
    $row_fav_count = mysqli_fetch_assoc($res_fav_count);
    $cantidad_favoritos = $row_fav_count['total'];
}

// Datos para autocompletar
$u_nombre = $_SESSION['usuario_nombre'] ?? "";
$u_email = $_SESSION['usuario_email'] ?? "";
$solo_lectura = isset($_SESSION['usuario_id']) ? "readonly" : "";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactos | D' Fiordaliza Style</title>
    <link rel="stylesheet" href="./styles.css?v=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
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
        .btn-modal-login { 
            display: block; padding: 12px; background: linear-gradient(135deg, #f6c1d4, #c9b6e4); 
            color: white; border-radius: 12px; text-decoration: none; font-weight: bold;
        }
        .btn-cancelar { background: none; border: none; color: #999; cursor: pointer; text-decoration: underline; font-size: 14px; }

        /* --- ESTILOS DE CONTACTO PARA COMPUTADORA --- */
        .section-title { text-align: center; margin: 30px 0; font-size: 28px; font-weight: 700; }
        .contact-layout { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; margin-top: 20px; align-items: stretch; }
        .contact-left { display: flex; flex-direction: column; gap: 20px; }
        .contact-info { background: white; padding: 20px; border-radius: 15px; box-shadow: var(--shadow); }
        .whatsapp-btn { display: inline-block; margin-top: 10px; padding: 10px 15px; background: #25D366; color: white; border-radius: 8px; text-decoration: none; }
        .contact-form { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.08); display: flex; flex-direction: column; gap: 18px; }
        .contact-form input, .contact-form textarea { padding: 14px; border-radius: 10px; border: 1px solid #ddd; outline: none; font-size: 14px; width: 100%; box-sizing: border-box; }
        .btn-submit { background: linear-gradient(135deg, #f6c1d4, #c9b6e4); color: white; border: none; padding: 14px; border-radius: 10px; cursor: pointer; font-weight: 600; width: 100%; }

        .map-box { border-radius: 15px; overflow: hidden; box-shadow: var(--shadow); height: 100%; display: flex; }
        .map-box iframe { width: 100%; height: 100%; min-height: 450px; border: none; }

        .menu { background: #fff; border-bottom: 1px solid #eee; padding: 10px 0; }
        .menu-links { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .menu-links a { text-decoration: none; color: #555; font-weight: 600; font-size: 15px; transition: all 0.3s ease; white-space: nowrap; }
        .menu-links a:hover { color: #f6c1d4; }

        /* =========================================================
            📱 AJUSTES EXCLUSIVOS DE CELULAR (MAX-WIDTH: 768px)
           ========================================================= */
        @media (max-width: 768px) {
            .section-title { font-size: 22px; margin: 20px 0; }
            .contact-layout { grid-template-columns: 1fr; gap: 20px; padding: 10px; }
            .contact-info { padding: 15px; border-radius: 12px; }
            .contact-form { padding: 20px; border-radius: 15px; }
            
            /* Ajustar mapa en celulares para que no sea gigante */
            .map-box { height: 300px; min-height: 300px; }
            .map-box iframe { min-height: 300px; height: 100%; }

            /* Menú deslizable en celulares */
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
        <span class="cerrar-modal" onclick="cerrarModal()">×</span>
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

<main class="container">
    <h2 class="section-title">Información de Contacto</h2>

    <div class="contact-layout">
        <div class="contact-left">
            <div class="contact-info">
                <h3>D' Fiordaliza Style</h3>
                <p><strong>Dirección:</strong> Ensanchez Libertad, calle 7, Santiago</p>
                <p><strong>Teléfono:</strong> 829-674-5204</p>
                <p><strong>Correo:</strong> fiordalizareyes2525@email.com</p>
                <a class="whatsapp-btn" href="https://wa.me/18296745204" target="_blank">💬 WhatsApp</a>
            </div>

            <?php if(isset($_GET['ok'])): ?>
                <p style="color:#2ecc71; font-weight:bold; text-align:center; background:#e6f9ec; padding:10px; border-radius:10px;">
                   ✅ Mensaje enviado correctamente
                </p>
            <?php endif; ?>

            <form class="contact-form" method="POST" action="contacto.php">
                <input type="text" name="nombre" placeholder="Tu nombre" value="<?php echo $u_nombre; ?>" <?php echo $solo_lectura; ?> required>
                <input type="email" name="email" placeholder="Tu correo" value="<?php echo $u_email; ?>" required>
                <textarea name="mensaje" placeholder="Escribe tu mensaje..." required></textarea>

                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <button type="submit" name="enviar" class="btn-submit">Enviar ✉️</button>
                <?php else: ?>
                    <button type="button" class="btn-submit" onclick="abrirModal()">Enviar ✉️</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="map-box">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3756.315882310939!2d-70.73113972386221!3d19.486311438914393!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8eb1c52cbb843bcb%3A0x8c5700b5afe1a167!2sD%60%20Fiordaliza%20Style!5e0!3m2!1ses!2sdo!4v1712176100000!5m2!1ses!2sdo" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>
</main>

<footer class="footer">
    <p>Gabriela Morán Vargas • 2026 • D' Fiordaliza Style • Santiago, RD</p>
    <div class="footer-bottom">&copy; 2026 Todos los derechos reservados.</div>
</footer>

<script>
function abrirModal() {
    document.getElementById("modalLogin").style.display = "block";
}
function cerrarModal() {
    document.getElementById("modalLogin").style.display = "none";
}
window.onclick = function(event) {
    if (event.target == document.getElementById("modalLogin")) cerrarModal();
}
</script>

</body>
</html>
