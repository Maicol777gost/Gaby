<?php
// ⚠️  SCRIPT DE USO ÚNICO — Elimínalo después de ejecutarlo
require 'conexion.php';

$pasos = [];
$errores = [];

// 1. Ver si la tabla existe
$res = $conexion->query("SHOW TABLES LIKE 'usuarios'");
if ($res && $res->num_rows > 0) {
    $pasos[] = "✅ La tabla <b>usuarios</b> ya existe.";

    // 2. Ver estructura actual
    $desc = $conexion->query("DESCRIBE usuarios");
    $cols = [];
    while ($row = $desc->fetch_assoc()) {
        $cols[$row['Field']] = $row;
    }

    // 3. Verificar si id_usuario tiene AUTO_INCREMENT
    if (isset($cols['id_usuario'])) {
        $extra = strtolower($cols['id_usuario']['Extra'] ?? '');
        $key   = strtolower($cols['id_usuario']['Key']   ?? '');
        if (strpos($extra, 'auto_increment') !== false) {
            $pasos[] = "✅ <b>id_usuario</b> ya tiene AUTO_INCREMENT. No se necesita corrección.";
        } else {
            // 4. Aplicar corrección
            // Primero eliminar PK si existe en otra columna
            $alter = "ALTER TABLE `usuarios` MODIFY COLUMN `id_usuario` INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id_usuario`)";
            // Si ya hay PK puede fallar, intentamos sin ADD PRIMARY KEY
            if (!$conexion->query($alter)) {
                $alter2 = "ALTER TABLE `usuarios` MODIFY COLUMN `id_usuario` INT NOT NULL AUTO_INCREMENT";
                if ($conexion->query($alter2)) {
                    $pasos[] = "✅ <b>id_usuario</b> corregido a AUTO_INCREMENT (sin recrear PK).";
                } else {
                    $errores[] = "❌ No se pudo corregir id_usuario: " . $conexion->error;
                }
            } else {
                $pasos[] = "✅ <b>id_usuario</b> corregido a AUTO_INCREMENT + PRIMARY KEY.";
            }
        }
    } else {
        $errores[] = "❌ La columna <b>id_usuario</b> no existe en la tabla.";
    }

    // 5. Mostrar estructura final
    $desc2 = $conexion->query("DESCRIBE usuarios");
    $tabla = "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;margin-top:16px;font-family:monospace'>
        <tr style='background:#f0f0f0'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($r = $desc2->fetch_assoc()) {
        $tabla .= "<tr><td>{$r['Field']}</td><td>{$r['Type']}</td><td>{$r['Null']}</td><td>{$r['Key']}</td><td>{$r['Default']}</td><td>{$r['Extra']}</td></tr>";
    }
    $tabla .= "</table>";

} else {
    // Tabla no existe: crearla completa
    $create = "CREATE TABLE `usuarios` (
        `id_usuario`  INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `nombre`      VARCHAR(150) NOT NULL,
        `email`       VARCHAR(200) NOT NULL UNIQUE,
        `contraseña`  VARCHAR(255) NOT NULL,
        `rol`         ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
        `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conexion->query($create)) {
        $pasos[] = "✅ Tabla <b>usuarios</b> creada desde cero con AUTO_INCREMENT.";
    } else {
        $errores[] = "❌ No se pudo crear la tabla: " . $conexion->error;
    }
    $tabla = "";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Fix DB — Fiordaliza Style</title>
<style>
  body { font-family: sans-serif; max-width: 720px; margin: 40px auto; padding: 20px; }
  h1   { color: #7a4ea3; }
  .ok  { background:#e8f5e9; color:#2e7d32; padding:10px 14px; border-radius:8px; margin:6px 0; }
  .err { background:#ffebee; color:#b71c1c; padding:10px 14px; border-radius:8px; margin:6px 0; }
  .warn{ background:#fff8e1; color:#e65100; padding:12px 14px; border-radius:8px; margin-top:24px; border:1px solid #ffb300; }
</style>
</head>
<body>
<h1>🔧 Reparación de BD — Fiordaliza Style</h1>

<?php foreach ($pasos as $p)   echo "<div class='ok'>$p</div>"; ?>
<?php foreach ($errores as $e) echo "<div class='err'>$e</div>"; ?>

<?php if (!empty($tabla)) echo "<h3>Estructura actual de <code>usuarios</code>:</h3>$tabla"; ?>

<div class="warn">
  ⚠️ <strong>Elimina este archivo del servidor</strong> una vez confirmado que el registro funciona.<br>
  Borra <code>fix_usuarios.php</code> del repositorio y vuelve a hacer deploy.
</div>
</body>
</html>
