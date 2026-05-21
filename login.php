<?php
session_start();
require 'conexion.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    if (!$conexion->connect_error) {
        // 1. Consulta preparada (usamos alias contrasena para evitar problemas con la ñ)
        $stmt = $conexion->prepare("SELECT id_usuario, nombre, contraseña AS contrasena, rol FROM usuarios WHERE email = ? LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {
                $usuario = $resultado->fetch_assoc();

                // 2. VALIDACIÓN SEGURA con password_verify
                if (password_verify($contrasena, $usuario['contrasena'])) {

                    // DATOS DE SESIÓN
                    $_SESSION['usuario_id'] = $usuario['id_usuario'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['rol'] = $usuario['rol'] ?? 'cliente';

                    // --- SEÑALES PARA EL INDEX ---
                    $_SESSION['mensaje_bienvenida'] = true;
                    $_SESSION['tipo_entrada'] = 'login'; 

                    // Redirección por rol
                    if ($_SESSION['rol'] === 'admin') {
                        header("Location: admi/dashboard.php");
                    } else {
                        header("Location: index.php"); 
                    }
                    exit();

                } else {
                    $error = "Contraseña incorrecta";
                }

            } else {
                $error = "Usuario no encontrado";
            }
            $stmt->close();
        } else {
            $error = "Error en la consulta";
        }

    } else {
        $error = "Error de conexión: " . $conexion->connect_error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar Sesión | Fiordaliza Style</title>

<style>
body {
  min-height: 100vh;
  margin: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  background: linear-gradient(135deg, #f6c1d4, #c9b6e4);
  font-family: Arial, sans-serif;
}

.container {
  width: 100%;
  max-width: 400px;
  padding: 40px;
  border-radius: 20px;
  background: white;
  box-shadow: 0 20px 40px rgba(83, 80, 80, 0.88);
  text-align: center;
}

.error {
  background: #ffe5e5;
  color: #b00020;
  padding: 10px;
  border-radius: 10px;
  margin-bottom: 15px;
  font-size: 14px;
}

.form {
  display: grid;
  gap: 15px;
}

.form input {
  padding: 14px;
  font-size: 16px;
  border-radius: 12px;
  border: 1px solid #f6c1d4;
  outline: none;
}

.form button {
  padding: 14px;
  font-size: 17px;
  border-radius: 12px;
  font-weight: bold;
  border: none;
  cursor: pointer;
  color: white;
  background: linear-gradient(90deg, #f6c1d4, #c9b6e4);
  transition: 0.3s;
}

.form button:hover {
  transform: scale(1.03);
}

a {
  display: block;
  margin-top: 15px;
  text-decoration: none;
  color: #7a4ea3;
  font-weight: 600;
  font-size: 14px;
}

/* =========================================================
   📱 AJUSTES RESPONSIVOS EN MÓVIL
   ========================================================= */
@media (max-width: 480px) {
  body {
    padding: 15px; /* Evita que el contenedor toque los bordes de la pantalla */
    align-items: center; /* Mantiene el formulario centrado verticalmente */
  }

  .container {
    padding: 25px 20px; /* Reducimos el padding para que no ocupe tanto espacio en el celular */
    border-radius: 16px; /* Un redondeado un poco más sutil para pantallas pequeñas */
  }

  .form input, 
  .form button {
    padding: 12px; /* Reducimos un chin el relleno de los campos y el botón */
    font-size: 15px; /* Ajustamos el tamaño de la letra para que quepa bien */
  }
}
</style>
</head>

<body>

<div class="container">
    <h2>Iniciar Sesión</h2>

    <?php if (!empty($error)) : ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="form">
        <input type="email" name="email" placeholder="Correo electrónico" required>
        <input type="password" name="contrasena" placeholder="Contraseña" required>
        <button type="submit">Entrar</button>
    </form>

    <a href="registro.php">¿No tienes cuenta? Regístrate</a>
</div>

</body>
</html>