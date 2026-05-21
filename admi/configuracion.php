<?php
require 'seguridad_admin.php';
require '../conexion.php';

// 1. AUTOCONFIGURACIÓN DE LA TABLA (Asegura que exista)
$conexion->query("CREATE TABLE IF NOT EXISTS configuracion_web (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT NOT NULL,
    descripcion VARCHAR(150) NOT NULL
)");

// Insertar valores por defecto si está vacía
$check_empty = $conexion->query("SELECT COUNT(*) as total FROM configuracion_web");
$row_empty = $check_empty->fetch_assoc();
if ($row_empty['total'] == 0) {
    $defaults = [
        ['titulo_bienvenida', "Descubre tu <span>estilo único</span>", 'Título principal en la página de inicio'],
        ['texto_bienvenida', "En D' Fiordaliza Style encontrarás moda moderna, elegante y diseñada para resaltar tu belleza en cada ocasión.", 'Texto secundario de bienvenida'],
        ['promesa_texto', "Más que una tienda, somos tu aliado de imagen en Santiago. Ofrecemos piezas seleccionadas bajo los más altos estándares de calidad para que tu única preocupación sea lucir espectacular.", 'Texto de la sección Nuestra Promesa'],
        ['telefono_contacto', "809-555-5555", 'Teléfono general de contacto'],
    ];
    $stmt = $conexion->prepare("INSERT INTO configuracion_web (clave, valor, descripcion) VALUES (?, ?, ?)");
    foreach ($defaults as $def) {
        $stmt->bind_param("sss", $def[0], $def[1], $def[2]);
        $stmt->execute();
    }
}

// 2. PROCESAR ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $stmt = $conexion->prepare("UPDATE configuracion_web SET valor = ? WHERE clave = ?");
    foreach ($_POST['config'] as $clave => $valor) {
        $valor_limpio = trim($valor);
        $stmt->bind_param("ss", $valor_limpio, $clave);
        $stmt->execute();
    }
    header("Location: configuracion.php?success=1");
    exit();
}

// 3. OBTENER CONFIGURACIONES
$configs = [];
$res = $conexion->query("SELECT * FROM configuracion_web");
while ($row = $res->fetch_assoc()) {
    $configs[$row['clave']] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de la Web | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary: #c9b6e4;
            --primary-dark: #7a4ea3;
            --accent: #f6c1d4;
            --bg: #f4f7f6;
            --card-bg: #ffffff;
            --text-main: #2c3e50;
            --sidebar-bg: #2c3e50;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body { display: flex; font-family: 'Inter', sans-serif; margin: 0; background-color: var(--bg); color: var(--text-main); min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: 250px; background-color: var(--sidebar-bg); color: white; padding: 20px 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1); position: fixed; height: 100vh; overflow-y: auto;
            transition: var(--transition); z-index: 1000;
        }
        .sidebar h2 { text-align: center; font-weight: 800; letter-spacing: 1px; color: var(--accent); margin-bottom: 30px; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a {
            color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center;
            gap: 12px; padding: 15px 25px; transition: var(--transition); border-left: 4px solid transparent;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active {
            background-color: rgba(255,255,255,0.05); color: white; border-left: 4px solid var(--accent);
        }

        /* MAIN CONTENT */
        .contenido { margin-left: 250px; flex-grow: 1; padding: 40px; max-width: calc(100% - 250px); transition: var(--transition); }

        .header-content {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;
            background: var(--card-bg); padding: 20px 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .header-content h1 { margin: 0; font-size: 24px; font-weight: 800; color: var(--primary-dark); }

        .btn {
            padding: 12px 25px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer;
            transition: var(--transition); display: inline-flex; align-items: center; gap: 8px; font-size: 15px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--primary)); color: white;
            box-shadow: 0 4px 15px rgba(201, 182, 228, 0.4);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(201, 182, 228, 0.6); }

        .config-container {
            background: var(--card-bg); border-radius: 16px; padding: 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        }

        .config-group {
            margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #f1f5f9;
        }
        .config-group:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        
        .config-group label {
            display: block; font-size: 15px; font-weight: 800; color: #334155; margin-bottom: 5px;
        }
        .config-group small {
            display: block; color: #94a3b8; font-size: 13px; margin-bottom: 12px;
        }
        
        .form-control {
            width: 100%; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 10px;
            font-size: 14px; font-family: inherit; transition: var(--transition); box-sizing: border-box;
            background: #f8fafc; color: #334155;
        }
        .form-control:focus {
            border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(201, 182, 228, 0.15); outline: none;
        }
        textarea.form-control { resize: vertical; min-height: 100px; line-height: 1.5; }

        /* MÓVIL ELEMENTOS */
        .mobile-toggle {
            display: none; position: fixed; top: 15px; right: 15px; z-index: 1100;
            background: var(--primary-dark); color: white; border: none; padding: 12px;
            border-radius: 10px; cursor: pointer; font-size: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* TOAST NOTIFICATION */
        .toast {
            position: fixed; bottom: 30px; right: 30px; background: white; padding: 15px 25px; border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px;
            transform: translateX(120%); transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 9999; border-left: 4px solid var(--accent);
        }
        .toast.show { transform: translateX(0); }

        /* MEDIA QUERIES */
        @media (max-width: 768px) {
            .mobile-toggle { display: block; }
            .sidebar { left: -250px; }
            .sidebar.active { left: 0; }
            .contenido { margin-left: 0; max-width: 100%; padding: 80px 15px 20px 15px; }
            .header-content { flex-direction: column; text-align: center; gap: 20px; padding: 20px; }
            .config-container { padding: 20px; }
            .btn { width: 100%; justify-content: center; }
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
            <li><a href="mensajes.php"><i class="fa-solid fa-envelope"></i> Mensajes</a></li>
            <li><a href="usuarios.php"><i class="fa-solid fa-users"></i> Usuarios</a></li>
            <li><a href="configuracion.php" class="active"><i class="fa-solid fa-sliders"></i> Configuración</a></li>
            <li style="margin-top: 30px;"><a href="../index.php"><i class="fa-solid fa-globe"></i> Ver Web Pública</a></li>
            <li><a href="../logout.php" style="color: #ffb3b3;"><i class="fa-solid fa-right-from-bracket"></i> Salir</a></li>
        </ul>
    </div>

    <div class="contenido">
        <div class="header-content">
            <div>
                <h1>Ajustes Generales</h1>
                <p style="color: #64748b; margin: 5px 0 0 0; font-size: 14px;">Modifica los textos de la tienda.</p>
            </div>
            <button form="configForm" type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
            </button>
        </div>

        <div class="config-container">
            <form id="configForm" method="POST">
                <input type="hidden" name="accion" value="actualizar">
                
                <?php foreach ($configs as $clave => $data): ?>
                    <div class="config-group">
                        <label><?php echo ucwords(str_replace('_', ' ', $clave)); ?></label>
                        <small><?php echo htmlspecialchars($data['descripcion']); ?></small>
                        
                        <?php if (strlen($data['valor']) > 60 || strpos($clave, 'texto') !== false || strpos($clave, 'promesa') !== false): ?>
                            <textarea name="config[<?php echo $clave; ?>]" class="form-control" required><?php echo htmlspecialchars($data['valor']); ?></textarea>
                        <?php else: ?>
                            <input type="text" name="config[<?php echo $clave; ?>]" class="form-control" value="<?php echo htmlspecialchars($data['valor']); ?>" required>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </form>
        </div>
    </div>

    <div class="toast" id="toastMsg">
        <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 24px;"></i>
        <div>
            <strong style="display:block; font-size:14px;">¡Guardado!</strong>
            <span style="font-size:13px; color:#64748b;">Parámetros actualizados con éxito.</span>
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

        // Cerrar menú al hacer clic fuera en móviles
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-toggle');
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('active');
                document.getElementById('menuIcon').classList.replace('fa-xmark', 'fa-bars');
            }
        });

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.has('success')) {
                const toast = document.getElementById('toastMsg');
                toast.classList.add('show');
                setTimeout(() => {
                    toast.classList.remove('show');
                    window.history.replaceState(null, '', window.location.pathname);
                }, 4000);
            }
        }
    </script>
</body>
</html>