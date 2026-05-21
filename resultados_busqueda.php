<?php
session_start(); 
include 'conexion.php'; 

// 1. Capturamos lo que el usuario escribió
$busqueda = isset($_GET['termino']) ? mysqli_real_escape_string($conexion, $_GET['termino']) : '';

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
    <title>Resultados para: <?php echo htmlspecialchars($busqueda); ?></title>
    <link rel="stylesheet" href="./styles.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* --- ESTILOS DE LA GALERÍA --- */
        .galeria-productos { 
            display: grid; 
            grid-template-columns: repeat(5, 1fr); 
            gap: 25px; 
            padding: 30px 0; 
        }

        .tarjeta-producto, .card { 
            display: flex; 
            flex-direction: column; 
            height: 100%; 
            width: 100%; 
            background: white; 
            border-radius: 15px; 
            padding: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            transition: 0.3s ease; 
            text-decoration: none; 
            color: inherit;
            position: relative; 
            box-sizing: border-box;
        }

        .tarjeta-producto:hover, .card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 6px 15px rgba(0,0,0,0.1); 
        }

        .image-container {
            position: relative;
            width: 100%;
            height: 180px;
        }

        .product-img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            border-radius: 10px; 
            transition: 0.3s ease; 
        }

        .card-body {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            margin-top: 10px;
        }

        .product-title { 
            font-size: 15px; 
            margin: 5px 0; 
            line-height: 1.3; 
            color: #333; 
            font-weight: 600;
        }

        .precio, .price { 
            font-size: 16px; 
            font-weight: 800; 
            color: #b33333; 
            margin: 5px 0 12px 0; 
        }

        .buttons {
            margin-top: auto;
            width: 100%;
        }

        .button.btn-primary { 
            display: block; 
            padding: 10px 15px; 
            background: linear-gradient(135deg, #f6c1d4, #c9b6e4); 
            color: #fff; 
            border: none; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            text-align: center; 
            text-decoration: none;
            font-size: 14px;
            transition: opacity 0.2s;
        }

        .button.btn-primary:hover {
            opacity: 0.9;
        }

        .favorito-btn { 
            position: absolute;
            top: 18px;
            right: 18px;
            border: none; 
            background: rgba(255, 255, 255, 0.9); 
            color: #bbb;
            width: 34px;
            height: 34px;
            border-radius: 50%; 
            cursor: pointer; 
            transition: 0.3s ease; 
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            z-index: 5;
        }

        .favorito-btn.activo { 
            background: #ffe5e5; 
            color: #e63946; 
        }

        /* --- ESTILOS DEL MODAL --- */
        .modal-personalizado {
            display: none; 
            position: fixed;
            z-index: 10000;
            left: 0; 
            top: 0;
            width: 100%; 
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            /* Agregado para centrar mejor */
            align-items: center;
            justify-content: center;
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

        @keyframes zoomIn {
            from {transform: scale(0.8); opacity: 0;}
            to {transform: scale(1); opacity: 1;}
        }

        .cerrar-modal { 
            position: absolute; 
            right: 20px; 
            top: 15px; 
            font-size: 25px; 
            cursor: pointer; 
            color: #bbb; 
        }

        .modal-acciones { 
            margin-top: 25px; 
            display: flex; 
            flex-direction: column; 
            gap: 12px; 
        }

        .btn-cancelar { 
            background: none; 
            border: none; 
            color: #999; 
            cursor: pointer; 
            text-decoration: underline; 
            font-size: 14px; 
        }

        /* --- NAVEGACIÓN --- */
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

        /* --- RESPONSIVE --- */
        @media (max-width: 1024px) { 
            .galeria-productos { grid-template-columns: repeat(3, 1fr); } 
        }

        @media (max-width: 768px) {
            .menu-links {
                justify-content: flex-start;
                overflow-x: auto;
                gap: 12px;
                padding: 4px 15px;
                flex-wrap: nowrap;
            }
            .menu-links a {
                padding: 8px 14px;
                background: #fff5f8;
                border-radius: 15px;
                border: 1px solid #f6c1d4;
            }
        }

        /* --- AJUSTES MÓVIL Y MODAL --- */
        @media (max-width: 600px) { 
            /* Arreglo del Modal para Celular */
            .modal-personalizado {
                display: none; /* Se cambia a flex en JS si prefieres, pero mantengo tu lógica */
                padding-top: 20%; 
            }
            .modal-contenido {
                margin: 0 auto; /* Quitamos el 12% que lo bajaba mucho */
                width: 85%;
                padding: 25px 20px;
                border-radius: 20px;
            }
            #modalIcono { font-size: 40px !important; }
            #modalTitulo { font-size: 18px; }
            #modalMensaje { font-size: 14px; }

            /* Grid de productos */
            .galeria-productos { 
                grid-template-columns: repeat(2, 1fr); 
                gap: 12px;
                padding: 10px 5px;
            } 

            .card { padding: 8px; }
            .product-img { height: 150px; }
            .product-title { font-size: 13px; }
            .price { font-size: 14px; }
            .button { font-size: 12px; padding: 8px; }
            
            .favorito-btn {
                top: 10px;
                right: 10px;
                width: 30px;
                height: 30px;
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
                <input type="text" name="termino" placeholder="¿Qué estás buscando?" value="<?php echo htmlspecialchars($busqueda); ?>">
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
    <h2 style="margin-top:20px; color: #333;">Resultados de búsqueda para: "<?php echo htmlspecialchars($busqueda); ?>"</h2>

    <div class="galeria-productos">
    <?php
    if ($busqueda != "") {
        $sql = "SELECT * FROM productos WHERE nombre_producto LIKE '%$busqueda%' OR descripcion LIKE '%$busqueda%'";
        $resultado = mysqli_query($conexion, $sql);

        if ($resultado && mysqli_num_rows($resultado) > 0) {
            while ($fila = mysqli_fetch_assoc($resultado)): 
                $es_favorito = false;
                if (isset($_SESSION['usuario_id'])) {
                    $id_u = (int) $_SESSION['usuario_id'];
                    $id_p = (int) $fila['id_producto'];
                    $check = mysqli_query($conexion, "SELECT id_favorito FROM favoritos WHERE id_usuario = $id_u AND id_producto = $id_p");
                    if ($check && mysqli_num_rows($check) > 0) { 
                        $es_favorito = true; 
                    }
                }
                ?>
                
                <div class="card"> 
                    <button class="favorito-btn <?php echo $es_favorito ? 'activo' : ''; ?>" 
                            onclick="<?php echo isset($_SESSION['usuario_id']) ? "gestionarFavorito(this, {$fila['id_producto']})" : "abrirModal()" ?>">
                        <i class="<?php echo $es_favorito ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                    </button>

                    <a href="detalle.php?id=<?php echo $fila['id_producto']; ?>" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%;">
                        <div class="image-container">
                            <img src="imagenes/<?php echo htmlspecialchars($fila['imagen']); ?>" class="product-img" alt="<?php echo htmlspecialchars($fila['nombre_producto']); ?>">
                        </div>
                        
                        <div class="card-body">
                            <h3 class="product-title"><?php echo htmlspecialchars($fila['nombre_producto']); ?></h3>
                            <p class="price">RD$ <?php echo number_format($fila['precio'], 2); ?></p>
                        </div>
                    </a>

                    <div class="buttons">
                        <a href="detalle.php?id=<?php echo $fila['id_producto']; ?>" class="button btn-primary">Comprar</a>
                    </div>
                </div>

            <?php endwhile; 
        } else {
            echo "<div style='grid-column: 1 / -1; text-align: center; padding: 50px;'>
                    <i class='fa-solid fa-magnifying-glass' style='font-size: 40px; color: #ccc; margin-bottom: 15px;'></i>
                    <p class='error'>No encontramos resultados para '<strong>" . htmlspecialchars($busqueda) . "</strong>'.</p>
                    <a href='index.php' style='color: #7a4ea3; text-decoration: underline;'>Ver todos los productos</a>
                  </div>";
        }
    } else {
        echo "<p style='grid-column: 1 / -1; text-align: center; padding: 50px;'>Por favor, escribe algo en el buscador.</p>";
    }
    ?>
    </div>
</main>

<script>
function abrirModal() { 
    const modal = document.getElementById("modalLogin");
    modal.style.display = "block"; 
    // Opcional: forzar centrado si el CSS no fuera suficiente
    modal.style.display = "flex"; 
}
function cerrarModal() { document.getElementById("modalLogin").style.display = "none"; }
window.onclick = function(event) { if (event.target == document.getElementById("modalLogin")) cerrarModal(); }

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
                if(favCountSpan) favCountSpan.textContent = (parseInt(favCountSpan.textContent) || 0) + 1;
            } else if (data.status === 'removed') {
                icono.classList.replace('fa-solid', 'fa-regular');
                elemento.classList.remove('activo');
                if(favCountSpan) favCountSpan.textContent = Math.max(0, (parseInt(favCountSpan.textContent) || 0) - 1);
            }
        })
        .catch(err => console.error("Error:", err));
    }
});
</script>

</body>
</html>