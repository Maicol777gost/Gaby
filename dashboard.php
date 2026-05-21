<?php
session_start();
require 'conexion.php';

/* ===== SEGURIDAD: Si no hay sesión, vuelve al login ===== */
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = (int)$_SESSION['usuario_id'];
$nombre = $_SESSION['usuario_nombre'] ?? "Usuario";
$email = $_SESSION['usuario_email'] ?? "";

// Obtener correo si no está en sesión
if (empty($email)) {
    $stmt = $conexion->prepare("SELECT email FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $email = $row['email'];
        $_SESSION['usuario_email'] = $email;
    }
    $stmt->close();
}

// Estadísticas rápidas del cliente
$stmt_pedidos = $conexion->prepare("SELECT COUNT(*) as total FROM pedidos WHERE id_usuario = ?");
$stmt_pedidos->bind_param("i", $id_usuario);
$stmt_pedidos->execute();
$total_pedidos = $stmt_pedidos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_pedidos->close();

$stmt_fav = $conexion->prepare("SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = ?");
$stmt_fav->bind_param("i", $id_usuario);
$stmt_fav->execute();
$total_favoritos = $stmt_fav->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_fav->close();

// Obtener los últimos 5 pedidos
$stmt_ultimos = $conexion->prepare("SELECT id_pedido, fecha_pedido, total, estado FROM pedidos WHERE id_usuario = ? ORDER BY fecha_pedido DESC LIMIT 5");
$stmt_ultimos->bind_param("i", $id_usuario);
$stmt_ultimos->execute();
$pedidos_recientes = $stmt_ultimos->get_result();

$mostrar_bienvenida = isset($_SESSION['mensaje_bienvenida']);
$tipo_entrada = $_SESSION['tipo_entrada'] ?? '';

// Limpiar mensaje para futuras recargas
if ($mostrar_bienvenida) {
    unset($_SESSION['mensaje_bienvenida']);
    unset($_SESSION['tipo_entrada']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | Fiordaliza Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #f6c1d4;
            --primary-dark: #e8a0ba;
            --secondary: #c9b6e4;
            --secondary-dark: #a890c8;
            --bg: #f8f9fa;
            --text: #333;
            --text-light: #666;
            --white: #ffffff;
            --shadow: 0 10px 30px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        body { 
            margin: 0; 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg); 
            color: var(--text);
            min-height: 100vh;
        }

        /* NAVBAR SENCILLO */
        .navbar {
            background: var(--white);
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }
        .navbar .logo {
            font-size: 22px;
            font-weight: 800;
            color: var(--secondary-dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar .btn-volver {
            padding: 8px 20px;
            background: var(--primary);
            color: var(--white);
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
        }
        .navbar .btn-volver:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* CONTENEDOR PRINCIPAL */
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* MENSAJE DE BIENVENIDA TOAST */
        .toast-bienvenida {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow);
            animation: slideDown 0.5s ease forwards;
        }
        .toast-bienvenida i { font-size: 40px; }
        .toast-text h2 { margin: 0 0 5px 0; font-size: 20px; font-weight: 700; }
        .toast-text p { margin: 0; opacity: 0.9; font-size: 15px; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* GRID DEL DASHBOARD */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        /* TARJETA DE PERFIL */
        .card {
            background: var(--white);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow);
        }
        .profile-card { text-align: center; }
        .avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
            box-shadow: 0 5px 15px rgba(201, 182, 228, 0.5);
        }
        .profile-card h3 { margin: 0; font-size: 22px; color: var(--text); }
        .profile-card p { color: var(--text-light); margin: 5px 0 25px; font-size: 14px; }
        
        .stats {
            display: flex;
            justify-content: space-around;
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-bottom: 25px;
        }
        .stat-item h4 { margin: 0; font-size: 24px; color: var(--secondary-dark); }
        .stat-item span { font-size: 12px; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px; }

        .btn-logout {
            display: block;
            width: 100%;
            padding: 12px;
            background: #fff0f0;
            color: #e63946;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        .btn-logout:hover { background: #ffe5e5; }

        /* SECCIÓN DE PEDIDOS */
        .orders-card h3 {
            margin: 0 0 20px 0;
            font-size: 20px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-list { display: flex; flex-direction: column; gap: 15px; }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border: 1px solid #eee;
            border-radius: 12px;
            transition: var(--transition);
        }
        .order-item:hover { border-color: var(--secondary); background: #fdfcff; }
        
        .order-info { display: flex; align-items: center; gap: 15px; }
        .order-icon {
            width: 45px; height: 45px;
            border-radius: 10px;
            background: #f0f4f8;
            color: var(--secondary-dark);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .order-details h4 { margin: 0 0 3px 0; font-size: 15px; color: var(--text); }
        .order-details p { margin: 0; font-size: 13px; color: var(--text-light); }
        
        .order-status { text-align: right; }
        .order-status .price { display: block; font-weight: 700; color: var(--text); margin-bottom: 5px; }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-pendiente { background: #fff3cd; color: #856404; }
        .badge-enviado   { background: #cce5ff; color: #004085; }
        .badge-entregado { background: #d4edda; color: #155724; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }
        .empty-state i { font-size: 50px; color: #e2e8f0; margin-bottom: 15px; }
        .empty-state p { margin-bottom: 20px; }
        .empty-state a {
            display: inline-block;
            padding: 10px 25px;
            background: var(--secondary);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">
            <i class="fa-solid fa-crown" style="color: var(--primary);"></i> D' Fiordaliza
        </a>
        <a href="index.php" class="btn-volver"><i class="fa-solid fa-store"></i> Ir a la Tienda</a>
    </nav>

    <div class="container">
        
        <?php if ($mostrar_bienvenida): ?>
            <div class="toast-bienvenida">
                <?php if ($tipo_entrada === 'registro'): ?>
                    <i class="fa-solid fa-face-grin-stars"></i>
                    <div class="toast-text">
                        <h2>¡Bienvenida a la Familia!</h2>
                        <p>Tu cuenta ha sido creada. Prepárate para descubrir tu estilo único.</p>
                    </div>
                <?php else: ?>
                    <i class="fa-solid fa-hand-sparkles"></i>
                    <div class="toast-text">
                        <h2>¡Qué alegría verte de nuevo!</h2>
                        <p>Hola <?php echo htmlspecialchars(explode(' ', $nombre)[0]); ?>, nos encanta tenerte de vuelta.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- COLUMNA 1: PERFIL -->
            <div class="card profile-card">
                <div class="avatar">
                    <?php echo strtoupper(substr($nombre, 0, 1)); ?>
                </div>
                <h3><?php echo htmlspecialchars($nombre); ?></h3>
                <p><?php echo htmlspecialchars($email); ?></p>
                
                <div class="stats">
                    <div class="stat-item">
                        <h4><?php echo $total_pedidos; ?></h4>
                        <span>Pedidos</span>
                    </div>
                    <div class="stat-item">
                        <h4><?php echo $total_favoritos; ?></h4>
                        <span>Favoritos</span>
                    </div>
                </div>

                <a href="favoritos.php" class="btn-logout" style="background: #f0f4f8; color: var(--secondary-dark); margin-bottom: 10px;">
                    <i class="fa-solid fa-heart"></i> Mis Favoritos
                </a>
                <a href="logout.php" class="btn-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar Sesión
                </a>
            </div>

            <!-- COLUMNA 2: PEDIDOS RECIENTES -->
            <div class="card orders-card">
                <h3><i class="fa-solid fa-box-open" style="color: var(--secondary-dark);"></i> Mis Pedidos Recientes</h3>
                
                <?php if ($pedidos_recientes && $pedidos_recientes->num_rows > 0): ?>
                    <div class="order-list">
                        <?php while($pedido = $pedidos_recientes->fetch_assoc()): 
                            $badge_class = 'badge-pendiente';
                            if ($pedido['estado'] == 'enviado') $badge_class = 'badge-enviado';
                            if ($pedido['estado'] == 'entregado') $badge_class = 'badge-entregado';
                            
                            $icono = 'fa-clock';
                            if ($pedido['estado'] == 'enviado') $icono = 'fa-truck-fast';
                            if ($pedido['estado'] == 'entregado') $icono = 'fa-check-double';
                        ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <div class="order-icon">
                                        <i class="fa-solid <?php echo $icono; ?>"></i>
                                    </div>
                                    <div class="order-details">
                                        <h4>Pedido #<?php echo str_pad($pedido['id_pedido'], 4, '0', STR_PAD_LEFT); ?></h4>
                                        <p><?php echo date('d M Y, h:i A', strtotime($pedido['fecha_pedido'])); ?></p>
                                    </div>
                                </div>
                                <div class="order-status">
                                    <span class="price">RD$ <?php echo number_format($pedido['total'], 2); ?></span>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo strtoupper($pedido['estado']); ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <p>Aún no has realizado ninguna compra.</p>
                        <a href="index.php">Empezar a comprar</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</body>
</html>