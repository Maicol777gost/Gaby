<?php
require 'seguridad_admin.php';
require '../conexion.php';

// Consultas con manejo seguro
$res_pedidos = $conexion->query("SELECT COUNT(*) as total FROM pedidos");
$pedidos = $res_pedidos ? $res_pedidos->fetch_assoc()['total'] : 0;

$res_pedidos_pend = $conexion->query("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'");
$pedidos_pend = $res_pedidos_pend ? $res_pedidos_pend->fetch_assoc()['total'] : 0;

try {
    $f = $conexion->query("SELECT COUNT(*) as total FROM mensajes WHERE leido = 0");
    $mensajes = $f ? $f->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    $f2 = $conexion->query("SELECT COUNT(*) as total FROM mensajes");
    $mensajes = $f2 ? $f2->fetch_assoc()['total'] : 0;
}

$res_usuarios = $conexion->query("SELECT COUNT(*) as total FROM usuarios");
$usuarios = $res_usuarios ? $res_usuarios->fetch_assoc()['total'] : 0;

$res_productos = $conexion->query("SELECT COUNT(*) as total FROM productos WHERE id_categoria != 6");
$productos = $res_productos ? $res_productos->fetch_assoc()['total'] : 0;

// Ventas totales
$res_ventas = $conexion->query("SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE estado = 'entregado'");
$ventas = $res_ventas ? $res_ventas->fetch_assoc()['total'] : 0;

// Últimos 5 pedidos recientes
$ultimos_pedidos = $conexion->query("SELECT p.id_pedido, p.fecha_pedido, p.total, p.estado, u.nombre 
                                     FROM pedidos p 
                                     LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario 
                                     ORDER BY p.fecha_pedido DESC LIMIT 5");

$nombre_admin = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Fiordaliza Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary: #c9b6e4; --primary-dark: #7a4ea3; --accent: #f6c1d4;
            --bg: #f4f7f6; --card-bg: #ffffff; --text-main: #2c3e50;
            --sidebar-bg: #2c3e50; --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        body { display: flex; font-family: 'Inter', sans-serif; margin: 0; background: var(--bg); color: var(--text-main); min-height: 100vh; }

        /* SIDEBAR */
        .sidebar { 
            width: 250px; background: var(--sidebar-bg); color: white; padding: 20px 0; 
            box-shadow: 4px 0 15px rgba(0,0,0,0.1); position: fixed; height: 100vh; 
            overflow-y: auto; z-index: 1000; transition: var(--transition);
        }
        .sidebar h2 { text-align: center; font-weight: 800; color: var(--accent); margin-bottom: 30px; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 15px 25px; transition: var(--transition); border-left: 4px solid transparent; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: rgba(255,255,255,0.05); color: white; border-left: 4px solid var(--accent); }

        /* MAIN */
        .contenido { margin-left: 250px; flex-grow: 1; padding: 40px; transition: var(--transition); }

        /* MOBILE TOGGLE */
        .mobile-toggle {
            display: none; position: fixed; top: 15px; right: 15px; z-index: 1100;
            background: var(--primary-dark); color: white; border: none; padding: 12px;
            border-radius: 10px; cursor: pointer; font-size: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* WELCOME BAR */
        .welcome-bar {
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
            padding: 35px 40px; border-radius: 20px; color: white; margin-bottom: 35px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 40px rgba(201,182,228,0.3);
        }
        .welcome-bar h1 { margin: 0; font-size: 26px; font-weight: 800; }
        .welcome-bar p { margin: 5px 0 0; opacity: 0.85; font-size: 14px; }
        .welcome-bar .date { background: rgba(255,255,255,0.2); padding: 8px 18px; border-radius: 10px; font-weight: 600; font-size: 13px; }

        /* STATS GRID */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .stat-card {
            background: var(--card-bg); padding: 25px; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 18px;
            transition: var(--transition); border: 1px solid #f1f5f9;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .stat-icon {
            width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center;
            justify-content: center; font-size: 22px; flex-shrink: 0;
        }
        .stat-icon.purple { background: #ede9fe; color: #7c3aed; }
        .stat-icon.pink   { background: #fce7f3; color: #db2777; }
        .stat-icon.blue   { background: #dbeafe; color: #2563eb; }
        .stat-icon.green  { background: #d1fae5; color: #059669; }
        .stat-icon.amber  { background: #fef3c7; color: #d97706; }
        .stat-icon.red    { background: #fee2e2; color: #dc2626; }
        .stat-info h3 { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; }
        .stat-info span { font-size: 13px; color: #94a3b8; font-weight: 500; }

        /* RECENT TABLE */
        .section-card {
            background: var(--card-bg); border-radius: 16px; padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 20px; overflow-x: auto;
        }
        .section-card h2 { margin: 0 0 20px; font-size: 18px; color: var(--primary-dark); display: flex; align-items: center; gap: 10px; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 14px 20px; text-align: left; border-bottom: 1px solid #f1f5f9; }
        th { background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        td { font-size: 14px; color: #334155; }
        tr:hover td { background: #f8fafc; }
        .badge { padding: 5px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-pendiente { background: #fef08a; color: #854d0e; }
        .badge-enviado   { background: #bae6fd; color: #0369a1; }
        .badge-entregado { background: #bbf7d0; color: #166534; }

        /* QUICK ACTIONS */
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
        .quick-btn {
            display: flex; flex-direction: column; align-items: center; gap: 10px;
            padding: 25px 15px; border-radius: 16px; text-decoration: none; color: var(--text-main);
            background: var(--card-bg); border: 1px solid #f1f5f9; transition: var(--transition);
            box-shadow: 0 2px 10px rgba(0,0,0,0.02); text-align: center;
        }
        .quick-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); border-color: var(--accent); }
        .quick-btn i { font-size: 28px; color: var(--primary-dark); }
        .quick-btn span { font-size: 13px; font-weight: 600; }

        /* MEDIA QUERIES */
        @media (max-width: 992px) {
            .sidebar { left: -250px; }
            .sidebar.active { left: 0; }
            .contenido { margin-left: 0; padding: 20px; padding-top: 80px; }
            .mobile-toggle { display: block; }
            .welcome-bar { flex-direction: column; text-align: center; gap: 20px; padding: 25px; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .welcome-bar h1 { font-size: 22px; }
            .stat-card { padding: 15px; }
        }
    </style>
</head>
<body>
    <button class="mobile-toggle" onclick="toggleMenu()">
        <i class="fa-solid fa-bars" id="menuIcon"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <h2>Fiordaliza<br><span style="font-size: 14px; color: #fff; font-weight: 300;">Panel Admin</span></h2>
        <ul>
            <li><a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Inicio</a></li>
            <li><a href="productos.php"><i class="fa-solid fa-box-open"></i> Productos</a></li>
            <li><a href="pedidos.php"><i class="fa-solid fa-cart-shopping"></i> Pedidos</a></li>
            <li><a href="mensajes.php"><i class="fa-solid fa-envelope"></i> Mensajes</a></li>
            <li><a href="usuarios.php"><i class="fa-solid fa-users"></i> Usuarios</a></li>
            <li><a href="configuracion.php"><i class="fa-solid fa-sliders"></i> Configuración</a></li>
            <li style="margin-top: 30px;"><a href="../index.php"><i class="fa-solid fa-globe"></i> Ver Web Pública</a></li>
            <li><a href="../logout.php" style="color: #ffb3b3;"><i class="fa-solid fa-right-from-bracket"></i> Salir</a></li>
        </ul>
    </div>

    <div class="contenido">
        <div class="welcome-bar">
            <div>
                <h1>¡Hola, <?php echo $nombre_admin; ?>!</h1>
                <p>Bienvenido al panel de control de D' Fiordaliza Style</p>
            </div>
            <div class="date">
                <i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y'); ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-dollar-sign"></i></div>
                <div class="stat-info">
                    <h3>RD$<?php echo number_format($ventas, 0); ?></h3>
                    <span>Ventas Entregadas</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="stat-info">
                    <h3><?php echo $pedidos; ?></h3>
                    <span>Pedidos Totales</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?php echo $pedidos_pend; ?></h3>
                    <span>Pendientes</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pink"><i class="fa-solid fa-envelope"></i></div>
                <div class="stat-info">
                    <h3><?php echo $mensajes; ?></h3>
                    <span>Mensajes</span>
                </div>
            </div>
        </div>

        <div class="section-card">
            <h2><i class="fa-solid fa-bolt"></i> Acciones Rápidas</h2>
            <div class="quick-actions">
                <a href="productos.php" class="quick-btn">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>Nuevo Producto</span>
                </a>
                <a href="pedidos.php" class="quick-btn">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span>Ver Pedidos</span>
                </a>
                <a href="mensajes.php" class="quick-btn">
                    <i class="fa-solid fa-inbox"></i>
                    <span>Mensajes</span>
                </a>
                <a href="configuracion.php" class="quick-btn">
                    <i class="fa-solid fa-paint-roller"></i>
                    <span>Ajustes Web</span>
                </a>
            </div>
        </div>

        <div class="section-card">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Actividad Reciente</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($ultimos_pedidos && $ultimos_pedidos->num_rows > 0): ?>
                            <?php while($row = $ultimos_pedidos->fetch_assoc()):
                                $bc = 'badge-pendiente';
                                if ($row['estado'] == 'enviado') $bc = 'badge-enviado';
                                if ($row['estado'] == 'entregado') $bc = 'badge-entregado';
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $row['id_pedido']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nombre'] ?? 'Invitado'); ?></td>
                                    <td style="font-weight: 700; color: #059669;">RD$<?php echo number_format($row['total'], 2); ?></td>
                                    <td><span class="badge <?php echo $bc; ?>"><?php echo strtoupper($row['estado']); ?></span></td>
                                    <td style="color: #94a3b8; font-size: 13px;"><?php echo date('d/m/Y', strtotime($row['fecha_pedido'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding: 30px; color: #94a3b8;">No hay pedidos registrados aún.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            const icon = document.getElementById('menuIcon');
            sidebar.classList.toggle('active');
            
            if (sidebar.classList.contains('active')) {
                icon.classList.replace('fa-bars', 'fa-xmark');
            } else {
                icon.classList.replace('fa-xmark', 'fa-bars');
            }
        }

        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-toggle');
            if (window.innerWidth <= 992 && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('active');
                document.getElementById('menuIcon').classList.replace('fa-xmark', 'fa-bars');
            }
        });
    </script>
</body>
</html>