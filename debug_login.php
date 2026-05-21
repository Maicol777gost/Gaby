<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'conexion.php';

echo "<h3>Depuración de Base de Datos - Usuarios</h3>";

// 1. Mostrar las columnas de la tabla usuarios para ver si 'contraseña' tiene algún carácter extraño
$result = $conexion->query("SHOW COLUMNS FROM usuarios");
if ($result) {
    echo "<h4>Columnas en la tabla 'usuarios':</h4><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>Field: <strong>" . htmlspecialchars($row['Field']) . "</strong> (Type: " . htmlspecialchars($row['Type']) . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>Error al obtener columnas: " . $conexion->error . "</p>";
}

// 2. Listar usuarios y verificar contraseña
$result = $conexion->query("SELECT * FROM usuarios");
if ($result) {
    echo "<h4>Usuarios registrados:</h4>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Hash Contraseña</th><th>¿Verifica 'MaicolElmejor'?</th></tr>";
    while ($row = $result->fetch_assoc()) {
        // Encontrar la columna de la contraseña dinámicamente por si no se llama exactamente 'contraseña'
        $pass_col = isset($row['contraseña']) ? 'contraseña' : (isset($row['contrasena']) ? 'contrasena' : null);
        if (!$pass_col) {
            foreach ($row as $key => $val) {
                if (stripos($key, 'contra') !== false) {
                    $pass_col = $key;
                    break;
                }
            }
        }
        
        $hash = $row[$pass_col] ?? 'NO ENCONTRADA';
        $verificacion = password_verify('MaicolElmejor', $hash) ? "<span style='color:green;font-weight:bold;'>SÍ ✅</span>" : "<span style='color:red;font-weight:bold;'>NO ❌</span>";
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id_usuario'] ?? $row['id'] ?? '?') . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre'] ?? '?') . "</td>";
        echo "<td>" . htmlspecialchars($row['email'] ?? '?') . "</td>";
        echo "<td>" . htmlspecialchars($row['rol'] ?? '?') . "</td>";
        echo "<td><code style='font-size:11px;'>" . htmlspecialchars($hash) . "</code></td>";
        echo "<td>$verificacion</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Error al consultar usuarios: " . $conexion->error . "</p>";
}

$conexion->close();
?>
