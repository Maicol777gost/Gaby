<?php
require 'seguridad_admin.php';
require '../conexion.php';

// PROCESAR BORRADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'borrar') {
    $id = $_POST['id'];
    $stmt = $conexion->prepare("DELETE FROM mensajes WHERE id = ?");
    if($stmt){
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    header("Location: mensajes.php");
    exit();
}

// OBTENER MENSAJES
$mensajes = [];
try {
    $conexion->query("ALTER TABLE mensajes ADD COLUMN leido BOOLEAN DEFAULT 0");
} catch (Exception $e) {}

try {
    $res = $conexion->query("SELECT * FROM mensajes ORDER BY fecha DESC");
    if($res) {
        while($r = $res->fetch_assoc()) $mensajes[] = $r;
    }
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes Recibidos | Admin</title>
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
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 18px 25px; text-align: left; border-bottom: 1px solid #f1f5f9; }
        th { background-color: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        td { font-size: 14px; color: #334155; vertical-align: middle; }
        tr:hover td { background-color: #f8fafc; }
        
        .btn-danger { padding: 10px 15px; border-radius: 8px; border: none; background: #ffe5e5; color: #e63946; cursor: pointer; transition: 0.3s; }
        .btn-danger:hover { background: #ffcccc; }

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
            <li><a href="pedidos.php"><i class="fa-solid fa-cart-shopping"></i> Pedidos</a></li>
            <li><a href="mensajes.php" class="active"><i class="fa-solid fa-envelope"></i> Mensajes</a></li>
            <li><a href="usuarios.php"><i class="fa-solid fa-users"></i> Usuarios</a></li>
            <li><a href="configuracion.php"><i class="fa-solid fa-sliders"></i> Configuración</a></li>
            <li style="margin-top: 30px;"><a href="../index.php"><i class="fa-solid fa-globe"></i> Ver Web Pública</a></li>
            <li><a href="../logout.php" style="color: #ffb3b3;"><i class="fa-solid fa-right-from-bracket"></i> Salir</a></li>
        </ul>
    </div>

    <div class="contenido">
        <div class="header-content">
            <h1>Bandeja de Contacto</h1>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Remitente</th>
                        <th>Correo / Teléfono</th>
                        <th>Mensaje</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($mensajes) > 0): ?>
                        <?php foreach($mensajes as $row): ?>
                            <tr>
                                <td style="white-space: nowrap; font-size: 12px; color: #64748b;"><?php echo date('d/m/Y H:i', strtotime($row['fecha'] ?? 'now')); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                <td style="font-size: 13px;">
                                    <a href="mailto:<?php echo htmlspecialchars($row['email']??''); ?>" style="color:var(--primary-dark); text-decoration:none;"><?php echo htmlspecialchars($row['email']??''); ?></a><br>
                                    <span style="color: #94a3b8;"><i class="fa-solid fa-phone" style="font-size:10px;"></i> <?php echo htmlspecialchars($row['telefono'] ?? 'N/A'); ?></span>
                                </td>
                                <td style="font-style: italic; color: #475569; max-width: 300px;"><?php echo nl2br(htmlspecialchars($row['mensaje']??'')); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('¿Borrar mensaje?');">
                                        <input type="hidden" name="accion" value="borrar">
                                        <input type="hidden" name="id" value="<?php echo $row['id'] ?? $row['id_mensaje']; ?>">
                                        <button type="submit" class="btn-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:40px; color:#94a3b8;">Bandeja vacía. No hay mensajes nuevos.</td></tr>
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