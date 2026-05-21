<?php
// ⚠️  SCRIPT DE USO ÚNICO — Elimínalo después de ejecutarlo
require 'conexion.php';

$pasos  = [];
$errores = [];

// Desactivar excepciones para manejar errores manualmente
$conexion->report_mode = MYSQLI_REPORT_OFF;

// 1. ¿Existe la tabla?
$res = $conexion->query("SHOW TABLES LIKE 'usuarios'");
$existe = ($res && $res->num_rows > 0);

if ($existe) {
    $pasos[] = "✅ La tabla <b>usuarios</b> ya existe.";

    // 2. Revisar si id_usuario ya tiene AUTO_INCREMENT
    $desc  = $conexion->query("DESCRIBE usuarios");
    $cols  = [];
    while ($row = $desc->fetch_assoc()) $cols[$row['Field']] = $row;

    $extra = strtolower($cols['id_usuario']['Extra'] ?? '');
    if (strpos($extra, 'auto_increment') !== false) {
        $pasos[] = "✅ <b>id_usuario</b> ya tiene AUTO_INCREMENT — sin cambios necesarios.";
    } else {
        // 3. Renombrar tabla vieja
        if ($conexion->query("RENAME TABLE `usuarios` TO `usuarios_old`")) {
            $pasos[] = "✅ Tabla antigua renombrada a <b>usuarios_old</b>.";

            // 4. Crear tabla nueva correcta
            $ok = $conexion->query("CREATE TABLE `usuarios` (
                `id_usuario`  INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `nombre`      VARCHAR(150) NOT NULL,
                `email`       VARCHAR(200) NOT NULL UNIQUE,
                `contraseña`  VARCHAR(255) NOT NULL,
                `rol`         ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
                `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            if ($ok) {
                $pasos[] = "✅ Nueva tabla <b>usuarios</b> creada con AUTO_INCREMENT.";

                // 5. Migrar datos si existen columnas compatibles
                $migrar = $conexion->query("INSERT INTO `usuarios` (nombre, email, `contraseña`, rol)
                    SELECT nombre, email, `contraseña`,
                    IFNULL(rol, 'cliente')
                    FROM `usuarios_old`");

                if ($migrar) {
                    $n = $conexion->affected_rows;
                    $pasos[] = "✅ <b>$n usuario(s)</b> migrado(s) exitosamente.";
                    // Eliminar tabla vieja
                    $conexion->query("DROP TABLE IF EXISTS `usuarios_old`");
                    $pasos[] = "✅ Tabla antigua eliminada.";
                } else {
                    $errores[] = "⚠️ No se pudieron migrar datos: " . $conexion->error . " — la tabla nueva está vacía pero funcional.";
                }
            } else {
                $errores[] = "❌ No se pudo crear la tabla nueva: " . $conexion->error;
                // Revertir rename
                $conexion->query("RENAME TABLE `usuarios_old` TO `usuarios`");
                $errores[] = "↩️ Se restauró la tabla original.";
            }
        } else {
            $errores[] = "❌ No se pudo renombrar la tabla: " . $conexion->error;
        }
    }
} else {
    // Tabla no existe: crearla
    $ok = $conexion->query("CREATE TABLE `usuarios` (
        `id_usuario`  INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `nombre`      VARCHAR(150) NOT NULL,
        `email`       VARCHAR(200) NOT NULL UNIQUE,
        `contraseña`  VARCHAR(255) NOT NULL,
        `rol`         ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
        `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if ($ok) $pasos[] = "✅ Tabla <b>usuarios</b> creada desde cero con AUTO_INCREMENT.";
    else      $errores[] = "❌ Error al crear: " . $conexion->error;
}

// Mostrar estructura final
$tabla = "";
$desc2 = $conexion->query("DESCRIBE usuarios");
if ($desc2) {
    $tabla = "<table border='1' cellpadding='8' cellspacing='0'
        style='border-collapse:collapse;margin-top:16px;font-family:monospace;font-size:13px'>
        <tr style='background:#ede7f6'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($r = $desc2->fetch_assoc()) {
        $hl = ($r['Field'] === 'id_usuario') ? "background:#e8f5e9" : "";
        $tabla .= "<tr style='$hl'>
            <td>{$r['Field']}</td><td>{$r['Type']}</td><td>{$r['Null']}</td>
            <td>{$r['Key']}</td><td>{$r['Default']}</td><td>{$r['Extra']}</td></tr>";
    }
    $tabla .= "</table>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Fix BD — Fiordaliza Style</title>
<style>
  body  { font-family:sans-serif; max-width:750px; margin:40px auto; padding:20px; }
  h1    { color:#7a4ea3; }
  .ok   { background:#e8f5e9; color:#2e7d32; padding:10px 14px; border-radius:8px; margin:5px 0; }
  .err  { background:#ffebee; color:#b71c1c; padding:10px 14px; border-radius:8px; margin:5px 0; }
  .warn { background:#fff8e1; color:#e65100; padding:14px; border-radius:8px; margin-top:28px; border:1px solid #ffb300; }
</style>
</head>
<body>
<h1>🔧 Reparación de BD — Fiordaliza Style</h1>

<?php foreach ($pasos   as $p) echo "<div class='ok'>$p</div>"; ?>
<?php foreach ($errores as $e) echo "<div class='err'>$e</div>"; ?>

<?php if ($tabla): ?>
  <h3>Estructura actual de <code>usuarios</code>:</h3>
  <?= $tabla ?>
<?php endif; ?>

<div class="warn">
  ⚠️ <strong>Elimina este archivo del servidor</strong> cuando confirmes que el registro funciona.<br>
  Borra <code>fix_usuarios.php</code> del repositorio y haz deploy nuevamente.
</div>
</body>
</html>
