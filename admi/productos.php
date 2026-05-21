<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'seguridad_admin.php';
require '../conexion.php';

// --- PROCESAR FORMULARIO (Añadir / Editar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
        $id_producto = $_POST['id_producto'] ?? null;
        $nombre = $_POST['nombre_producto'];
        $descripcion = $_POST['descripcion']; 
        $precio = $_POST['precio'];
        $id_categoria = $_POST['id_categoria'];
        $imagen_nombre = $_POST['imagen_actual'] ?? 'default.jpg';

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['imagen']['tmp_name'];
            $name = time() . '_' . basename($_FILES['imagen']['name']);
            $ruta_destino = "../imagenes/" . $name;
            
            if (move_uploaded_file($tmp_name, $ruta_destino)) {
                $imagen_nombre = $name;
            }
        }

        if (!empty($id_producto)) {
            $stmt = $conexion->prepare("UPDATE productos SET nombre_producto = ?, descripcion = ?, precio = ?, id_categoria = ?, imagen = ? WHERE id_producto = ?");
            $stmt->bind_param("ssdisi", $nombre, $descripcion, $precio, $id_categoria, $imagen_nombre, $id_producto);
            $stmt->execute();
        } else {
            $stmt = $conexion->prepare("INSERT INTO productos (nombre_producto, descripcion, precio, id_categoria, imagen) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdis", $nombre, $descripcion, $precio, $id_categoria, $imagen_nombre);
            $stmt->execute();
        }
        header("Location: productos.php?success=1");
        exit();
    }

    if (isset($_POST['accion']) && $_POST['accion'] === 'borrar') {
        $id_producto = $_POST['id_producto'];
        
        // Primero borramos de detalles_pedido para evitar el error de Foreign Key
        $stmt_fk = $conexion->prepare("DELETE FROM detalles_pedido WHERE id_producto = ?");
        $stmt_fk->bind_param("i", $id_producto);
        $stmt_fk->execute();

        $stmt = $conexion->prepare("DELETE FROM productos WHERE id_producto = ?");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        header("Location: productos.php?deleted=1");
        exit();
    }
}

$resultado = $conexion->query("SELECT p.*, c.nombre_categoria FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id_categoria ORDER BY p.id_producto DESC");

// Obtener categorías para el selector del formulario
$categorias_res = $conexion->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY id_categoria ASC");
$categorias = [];
if ($categorias_res) {
    while ($cat = $categorias_res->fetch_assoc()) {
        $categorias[] = $cat;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Productos | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary: #c9b6e4; --primary-dark: #7a4ea3; --accent: #f6c1d4;
            --bg: #f4f7f6; --card-bg: #ffffff; --text-main: #2c3e50;
            --sidebar-bg: #2c3e50; --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body { display: flex; font-family: 'Inter', sans-serif; margin: 0; background-color: var(--bg); color: var(--text-main); min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: 250px; background-color: var(--sidebar-bg); color: white; padding: 20px 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1); position: fixed; height: 100vh; overflow-y: auto;
            transition: var(--transition); z-index: 1000;
        }
        .sidebar h2 { text-align: center; font-weight: 800; color: var(--accent); margin-bottom: 30px; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { color: rgba(255,255,255,0.7); text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 15px 25px; transition: var(--transition); border-left: 4px solid transparent; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background-color: rgba(255,255,255,0.05); color: white; border-left: 4px solid var(--accent); }

        /* CONTENIDO */
        .contenido { margin-left: 250px; flex-grow: 1; padding: 40px; max-width: calc(100% - 250px); transition: var(--transition); }
        .header-content { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: var(--card-bg); padding: 20px 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        .header-content h1 { margin: 0; font-size: 24px; font-weight: 800; color: var(--primary-dark); }

        .btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; gap: 8px; font-size: 14px; }
        .btn-primary { background: linear-gradient(135deg, var(--accent), var(--primary)); color: white; box-shadow: 0 4px 15px rgba(201, 182, 228, 0.4); }
        .btn-danger { background: #ffe5e5; color: #e63946; }
        .btn-edit { background: #e0f2fe; color: #0284c7; }

        /* TABLA */
        .table-container { background: var(--card-bg); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 18px 25px; text-align: left; border-bottom: 1px solid #f1f5f9; }
        th { background-color: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        .product-img-mini { width: 50px; height: 50px; object-fit: cover; border-radius: 10px; }

        /* MODAL */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1100; opacity: 0; transition: opacity 0.3s ease; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-box { background: var(--card-bg); padding: 35px; border-radius: 24px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; transform: translateY(20px); transition: var(--transition); }
        .modal-overlay.active .modal-box { transform: translateY(0); }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; }

        /* MÓVIL ELEMENTOS */
        .mobile-toggle { display: none; position: fixed; top: 15px; right: 15px; z-index: 1200; background: var(--primary-dark); color: white; border: none; padding: 12px; border-radius: 10px; cursor: pointer; font-size: 20px; }

        @media (max-width: 992px) {
            .mobile-toggle { display: block; }
            .sidebar { left: -250px; }
            .sidebar.active { left: 0; }
            .contenido { margin-left: 0; max-width: 100%; padding: 80px 15px 20px 15px; }
            .header-content { flex-direction: column; gap: 15px; text-align: center; }
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
            <li><a href="productos.php" class="active"><i class="fa-solid fa-box-open"></i> Productos</a></li>
            <li><a href="pedidos.php"><i class="fa-solid fa-cart-shopping"></i> Pedidos</a></li>
            <li><a href="mensajes.php"><i class="fa-solid fa-envelope"></i> Mensajes</a></li>
            <li><a href="usuarios.php"><i class="fa-solid fa-users"></i> Usuarios</a></li>
            <li style="margin-top: 30px;"><a href="../index.php"><i class="fa-solid fa-globe"></i> Ver Web Pública</a></li>
            <li><a href="../logout.php" style="color: #ffb3b3;"><i class="fa-solid fa-right-from-bracket"></i> Salir</a></li>
        </ul>
    </div>

    <div class="contenido">
        <div class="header-content">
            <h1>Gestión de Catálogo</h1>
            <button class="btn btn-primary" onclick="abrirModal()">
                <i class="fa-solid fa-plus"></i> Nuevo Producto
            </button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>IMG</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Categoría</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($resultado && $resultado->num_rows > 0): ?>
                        <?php while($row = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td><img src="../imagenes/<?php echo htmlspecialchars($row['imagen']); ?>" class="product-img-mini"></td>
                                <td><strong><?php echo htmlspecialchars($row['nombre_producto']); ?></strong></td>
                                <td style="color: #10b981;">$<?php echo number_format($row['precio'], 2); ?></td>
                                <td><span style="background:#f1f5f9; padding:4px 8px; border-radius:6px; font-size:12px;"><?php echo $row['nombre_categoria'] ?? 'Cat: '.$row['id_categoria']; ?></span></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-edit" onclick='editarProducto(<?php echo json_encode($row); ?>)'><i class="fa-solid fa-pen"></i></button>
                                        <form action="productos.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar producto?');">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="id_producto" value="<?php echo $row['id_producto']; ?>">
                                            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-overlay" id="modalProducto">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Producto</h2>
                <button class="close-btn" onclick="cerrarModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" enctype="multipart/form-data" action="productos.php">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id_producto" id="form_id">
                <input type="hidden" name="imagen_actual" id="form_imagen_actual">
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre_producto" id="form_nombre" class="form-control" required></div>
                <div class="form-group"><label>Descripción</label><textarea name="descripcion" id="form_descripcion" class="form-control" rows="3"></textarea></div>
                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex:1;"><label>Precio</label><input type="number" step="0.01" name="precio" id="form_precio" class="form-control" required></div>
                    <div class="form-group" style="flex:1;">
                        <label>Categoría</label>
                        <select name="id_categoria" id="form_categoria" class="form-control" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id_categoria']; ?>"><?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Imagen</label><input type="file" name="imagen" class="form-control"></div>
                <div style="text-align: right;"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            const icon = document.getElementById('menuIcon');
            sidebar.classList.toggle('active');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-xmark');
        }

        const modal = document.getElementById('modalProducto');
        function abrirModal() {
            document.getElementById('modalTitle').innerText = 'Nuevo Producto';
            document.getElementById('form_id').value = '';
            document.getElementById('form_nombre').value = '';
            document.getElementById('form_descripcion').value = '';
            document.getElementById('form_precio').value = '';
            document.getElementById('form_categoria').value = '';
            modal.classList.add('active');
        }

        function editarProducto(prod) {
            document.getElementById('modalTitle').innerText = 'Editar Producto';
            document.getElementById('form_id').value = prod.id_producto;
            document.getElementById('form_nombre').value = prod.nombre_producto;
            document.getElementById('form_descripcion').value = prod.descripcion || '';
            document.getElementById('form_precio').value = prod.precio;
            document.getElementById('form_categoria').value = prod.id_categoria;
            document.getElementById('form_imagen_actual').value = prod.imagen;
            modal.classList.add('active');
        }

        function cerrarModal() { modal.classList.remove('active'); }
    </script>
</body>
</html>