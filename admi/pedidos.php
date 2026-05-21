<?php
require 'seguridad_admin.php';
require '../conexion.php';

// PROCESAR CAMBIO DE ESTADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado') {
    $id_pedido = $_POST['id_pedido'];
    $nuevo_estado = $_POST['estado'];
    
    $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
    $stmt->bind_param("si", $nuevo_estado, $id_pedido);
    $stmt->execute();
    
    header("Location: pedidos.php?success=1");
    exit();
}

// OBTENER PEDIDOS CON DATOS DE USUARIO Y DIRECCIÓN (Usando subconsultas para evitar error ONLY_FULL_GROUP_BY)
$sql = "SELECT p.id_pedido, p.fecha_pedido, p.total, p.estado, u.nombre, u.email,
               (SELECT d.telefono_contacto FROM direcciones d WHERE d.id_usuario = p.id_usuario ORDER BY d.id_direccion DESC LIMIT 1) AS telefono_contacto,
               (SELECT d.ciudad FROM direcciones d WHERE d.id_usuario = p.id_usuario ORDER BY d.id_direccion DESC LIMIT 1) AS ciudad,
               (SELECT d.calle_numero FROM direcciones d WHERE d.id_usuario = p.id_usuario ORDER BY d.id_direccion DESC LIMIT 1) AS calle_numero
        FROM pedidos p 
        LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario 
        ORDER BY p.fecha_pedido DESC";
$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --primary: #c9b6e4; --primary-dark: #7a4ea3; --accent: #f6c1d4; --bg: #f4f7f6; --card-bg: #ffffff; --text-main: #2c3e50; --sidebar-bg: #2c3e50; --transition: all 0.3s ease; }
        body { display: flex; font-family: 'Inter', sans-serif; margin: 0; background-color: var(--bg); color: var(--text-main); min-height: 100vh; }
        
        /* SIDEBAR */
        .sidebar { 
            width: 250px; background-color: var(--sidebar-bg); color: white; padding: 20px 0; 
            box-shadow: 4px 0 15px rgba(0,0,0,0.1); position: fixed; height: 100vh; 
            overflow-y: auto; transition: var(--transition); z-index: 1000;
        }
        .sidebar h2 { text-align: center; font-weight: 800; color: var(--accent); margin-bottom: 30px; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 15px 25px; transition: var(--transition); border-left: 4px solid transparent; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background-color: rgba(255,255,255,0.05); color: white; border-left: 4px solid var(--accent); }
        
        /* CONTENIDO */
        .contenido { margin-left: 250px; flex-grow: 1; padding: 40px; max-width: calc(100% - 250px); transition: var(--transition); }
        .header-content { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: var(--card-bg); padding: 20px 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        .header-content h1 { margin: 0; font-size: 24px; font-weight: 800; color: var(--primary-dark); }
        
        /* TABLA */
        .table-container { background: var(--card-bg); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 850px; }
        th, td { padding: 18px 25px; text-align: left; border-bottom: 1px solid #f1f5f9; }
        th { background-color: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        td { font-size: 14px; color: #334155; vertical-align: middle; }
        tr:hover td { background-color: #f8fafc; }
        
        .badge { padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: 800; text-transform: uppercase; display: inline-block; }
        .badge-pendiente { background: #fef08a; color: #854d0e; }
        .badge-enviado { background: #bae6fd; color: #0369a1; }
        .badge-entregado { background: #bbf7d0; color: #166534; }
        
        .form-select { padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; font-family: inherit; font-size: 13px; font-weight: 600; }
        .btn-update { padding: 8px 12px; border-radius: 8px; border: none; background: var(--accent); color: var(--primary-dark); font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-update:hover { background: var(--primary); color: white; }

        /* MÓVIL ELEMENTOS */
        .mobile-toggle {
            display: none; position: fixed; top: 15px; right: 15px; z-index: 1100;
            background: var(--primary-dark); color: white; border: none; padding: 12px;
            border-radius: 10px; cursor: pointer; font-size: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* MEDIA QUERIES */
        @media (max-width: 992px) {
            .mobile-toggle { display: block; }
            .sidebar { left: -250px; }
            .sidebar.active { left: 0; }
            .contenido { margin-left: 0; max-width: 100%; padding: 80px 15px 20px 15px; }
            .header-content { text-align: center; justify-content: center; }
            .form-select { width: 100px; font-size: 11px; }
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
            <li><a href="dashboard.php"><i class="fa-solid fa-house"></i> Inicio</a></li>
            <li><a href="productos.php"><i class="fa-solid fa-box-open"></i> Productos</a></li>
            <li><a href="pedidos.php" class="active"><i class="fa-solid fa-cart-shopping"></i> Pedidos</a></li>
            <li><a href="mensajes.php"><i class="fa-solid fa-envelope"></i> Mensajes</a></li>
            <li><a href="usuarios.php"><i class="fa-solid fa-users"></i> Usuarios</a></li>
            <li><a href="configuracion.php"><i class="fa-solid fa-sliders"></i> Configuración</a></li>
            <li style="margin-top: 30px;"><a href="../index.php"><i class="fa-solid fa-globe"></i> Ver Web Pública</a></li>
            <li><a href="../logout.php" style="color: #ffb3b3;"><i class="fa-solid fa-right-from-bracket"></i> Salir</a></li>
        </ul>
    </div>

    <div class="contenido">
        <div class="header-content">
            <h1>Gestión de Pedidos</h1>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID / Fecha</th>
                        <th>Cliente</th>
                        <th>Dirección / Tel</th>
                        <th>Total (RD$)</th>
                        <th>Estado Actual</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($resultado && $resultado->num_rows > 0): ?>
                        <?php while($row = $resultado->fetch_assoc()): 
                            $badgeClass = 'badge-pendiente';
                            if ($row['estado'] == 'enviado') $badgeClass = 'badge-enviado';
                            if ($row['estado'] == 'entregado') $badgeClass = 'badge-entregado';
                        ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $row['id_pedido']; ?></strong><br>
                                    <span style="font-size: 12px; color: #94a3b8;"><?php echo date('d/m/Y H:i', strtotime($row['fecha_pedido'])); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['nombre'] ?? 'Desconocido'); ?></strong><br>
                                    <span style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($row['email'] ?? ''); ?></span>
                                </td>
                                <td style="font-size: 13px;">
                                    <?php echo htmlspecialchars($row['ciudad'] ?? '') . ", " . htmlspecialchars($row['calle_numero'] ?? ''); ?><br>
                                    <i class="fa-solid fa-phone" style="font-size: 10px; color: #94a3b8;"></i> <?php echo htmlspecialchars($row['telefono_contacto'] ?? 'N/A'); ?>
                                </td>
                                <td style="font-weight: 800; color: #10b981;">$<?php echo number_format($row['total'], 2); ?></td>
                                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo strtoupper($row['estado']); ?></span></td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="accion" value="cambiar_estado">
                                        <input type="hidden" name="id_pedido" value="<?php echo $row['id_pedido']; ?>">
                                        <select name="estado" class="form-select">
                                            <option value="pendiente" <?php if($row['estado']=='pendiente') echo 'selected'; ?>>Pendiente</option>
                                            <option value="enviado" <?php if($row['estado']=='enviado') echo 'selected'; ?>>Enviado</option>
                                            <option value="entregado" <?php if($row['estado']=='entregado') echo 'selected'; ?>>Entregado</option>
                                        </select>
                                        <button type="submit" class="btn-update"><i class="fa-solid fa-check"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">No hay pedidos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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