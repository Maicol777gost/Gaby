<?php
/**
 * SETUP ADMIN - D' Fiordaliza Style
 * Ejecutar una sola vez en el navegador:
 * http://localhost/PROYECTOS/setup_admin.php
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 1. Usar la conexión dinámica configurada en conexion.php
    require 'conexion.php';
    $db = $conexion;

    // 2. Ejecutar el schema SQL completo
    $sql_file = __DIR__ . '/basededatos/fiordaliza_schema.sql';
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        
        // Remover CREATE DATABASE y USE para evitar fallos de permisos en hostings compartidos / Render
        $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS\s+`?[a-zA-Z0-9_-]+`?[^;]*;/i', '', $sql);
        $sql = preg_replace('/USE\s+`?[a-zA-Z0-9_-]+`?[^;]*;/i', '', $sql);
        
        // Ejecutar sentencias separadas
        $db->multi_query($sql);
        // Consumir todos los resultados para no bloquear
        do { if ($r = $db->store_result()) $r->free(); } while ($db->more_results() && $db->next_result());
        echo "<p style='color:green'>✅ Tablas creadas/verificadas correctamente desde el schema SQL.</p>";
    } else {
        echo "<p style='color:orange'>⚠️ No se encontró el archivo schema SQL. Procediendo sin él.</p>";
    }

    // 3. Generar hash seguro de la contraseña solicitada
    $hash = password_hash("MaicolElmejor", PASSWORD_BCRYPT);

    // 4. Verificar si el admin ya existe
    $check = $db->prepare("SELECT id_usuario, rol FROM usuarios WHERE email = ?");
    $check->bind_param("s", $email_admin);
    $email_admin = 'admin@fiordaliza.com';
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows === 0) {
        // Crear admin
        $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, contraseña, rol) VALUES ('Admin', ?, ?, 'admin')");
        $stmt->bind_param("ss", $email_admin, $hash);
        $stmt->execute();
        $stmt->close();
        echo "<p style='color:green'>✅ Usuario Admin creado.</p>";
    } else {
        // Actualizar contraseña y asegurar rol admin
        $stmt = $db->prepare("UPDATE usuarios SET contraseña = ?, rol = 'admin', nombre = 'Admin' WHERE email = ?");
        $stmt->bind_param("ss", $hash, $email_admin);
        $stmt->execute();
        $stmt->close();
        echo "<p style='color:blue'>🔄 Usuario Admin actualizado con nueva contraseña.</p>";
    }
    $check->close();
    $db->close();

    echo "
    <div style='font-family: sans-serif; max-width: 500px; margin: 40px auto; 
                padding: 30px; border-radius: 12px; background: #f0fff4; border: 2px solid #10b981;'>
        <h2 style='color: #10b981;'>✅ Configuración Completada</h2>
        <table style='width:100%; border-collapse: collapse; margin-top: 15px;'>
            <tr><td style='padding:8px; font-weight:bold;'>URL de Admin:</td>
                <td><a href='admi/dashboard.php'>admi/dashboard.php</a></td></tr>
            <tr style='background:#ecfdf5'><td style='padding:8px; font-weight:bold;'>Usuario (email):</td>
                <td>admin@fiordaliza.com</td></tr>
            <tr><td style='padding:8px; font-weight:bold;'>Contraseña:</td>
                <td><strong>MaicolElmejor</strong></td></tr>
            <tr style='background:#ecfdf5'><td style='padding:8px; font-weight:bold;'>Base de datos:</td>
                <td>fiordaliza</td></tr>
        </table>
        <p style='margin-top:20px; color:#6b7280; font-size:13px;'>
            ⚠️ Elimina este archivo (<code>setup_admin.php</code>) una vez configurado el sistema.
        </p>
        <a href='index.php' style='display:inline-block; margin-top:15px; padding:10px 25px; 
                background:#10b981; color:white; border-radius:8px; text-decoration:none; font-weight:bold;'>
            Ir a la Tienda →
        </a>
    </div>";

} catch (Exception $e) {
    echo "<div style='font-family:sans-serif; max-width:500px; margin:40px auto; padding:30px;
                border-radius:12px; background:#fff1f2; border:2px solid #e63946;'>
        <h2 style='color:#e63946;'>❌ Error durante la configuración</h2>
        <p><strong>Verifica que XAMPP esté activo</strong> (Apache + MySQL).</p>
        <p style='color:#666; font-size:13px;'>Detalle técnico: " . htmlspecialchars($e->getMessage()) . "</p>
    </div>";
}
?>
