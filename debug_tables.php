<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'conexion.php';

echo "<h3>Depuración de Tablas en la Base de Datos</h3>";
echo "Base de datos conectada: <strong>" . htmlspecialchars($db) . "</strong><br><br>";

$result = $conexion->query("SHOW TABLES");
if ($result) {
    echo "<h4>Tablas encontradas:</h4>";
    if ($result->num_rows > 0) {
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . htmlspecialchars($row[0]) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange;'>⚠️ La base de datos está completamente vacía (no hay tablas).</p>";
    }
} else {
    echo "<p style='color:red;'>Error al ejecutar SHOW TABLES: " . $conexion->error . "</p>";
}

$conexion->close();
?>
