<?php
session_start();
require 'conexion.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validar que no estén vacíos
    if (!empty($_POST["nombre"]) && !empty($_POST["email"]) && !empty($_POST["contrasena"])) {

        $nombre = trim($_POST["nombre"]);
        $email = trim($_POST["email"]);
        $contrasena = $_POST["contrasena"];

        // 🔒 Encriptar contraseña
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

        // 🔍 Verificar si el correo ya existe
        $checkEmail = $conexion->prepare("SELECT email FROM usuarios WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $res = $checkEmail->get_result();

        if ($res->num_rows > 0) {
            $error = "❌ Este correo ya está registrado.";
        } else {
            // ✅ Insertar usuario
            $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, contraseña) VALUES (?, ?, ?)");
            
            if ($stmt) {
                $stmt->bind_param("sss", $nombre, $email, $contrasena_hash);

                if ($stmt->execute()) {
                    // Guardamos los datos para el mensaje de bienvenida
                    $_SESSION['usuario_id'] = $conexion->insert_id;
                    $_SESSION['usuario_nombre'] = $nombre;
                    $_SESSION['usuario_email'] = $email;
                    $_SESSION['mensaje_bienvenida'] = true;
                    $_SESSION['tipo_entrada'] = 'registro'; // IMPORTANTE: Señal de registro
                    $_SESSION['rol'] = 'cliente'; // FIX: Asignar el rol al registrarse
                    
                    // Migrar carrito de sesión (invitado) a la base de datos al registrarse (defensivo)
                    try {
                        if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
                            foreach ($_SESSION['carrito'] as $id_prod => $item) {
                                $id_prod = (int)$id_prod;
                                $cant = (int)$item['cantidad'];
                                if ($cant > 0) {
                                    $ins = $conexion->prepare("INSERT INTO carrito (id_usuario, id_producto, cantidad) VALUES (?, ?, ?)");
                                    $ins->bind_param("iii", $_SESSION['usuario_id'], $id_prod, $cant);
                                    $ins->execute();
                                    $ins->close();
                                }
                            }
                        }

                        // Cargar el carrito completo consolidado desde la base de datos a la sesión
                        $_SESSION['carrito'] = [];
                        $load_cart = $conexion->prepare("SELECT c.id_producto, c.cantidad, p.nombre_producto, p.precio, p.imagen FROM carrito c JOIN productos p ON c.id_producto = p.id_producto WHERE c.id_usuario = ?");
                        $load_cart->bind_param("i", $_SESSION['usuario_id']);
                        $load_cart->execute();
                        $res_load = $load_cart->get_result();
                        while ($row = $res_load->fetch_assoc()) {
                            $_SESSION['carrito'][$row['id_producto']] = [
                                "nombre" => $row['nombre_producto'],
                                "precio" => $row['precio'],
                                "imagen" => $row['imagen'],
                                "cantidad" => $row['cantidad']
                            ];
                        }
                        $load_cart->close();
                    } catch (Throwable $e) {
                        // En caso de que la tabla 'carrito' no exista o falle, dejamos el carrito en sesión
                        // y evitamos tumbar el registro exitoso.
                    }

                    header("Location: index.php");
                    exit();
                } else {
                    $error = "❌ Error al registrar.";
                }
                $stmt->close();
            } else {
                $error = "❌ Error en la consulta.";
            }
        }
        $checkEmail->close();

    } else {
        $error = "⚠️ Completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro | Fiordaliza Style</title>
    <link rel="stylesheet" href="./styles.css?v=1">


<style>
/* =========================
FONDO SOLO PARA CONTACTO
========================= */
.contact-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f6c1d4, #c9b6e4);
  display: flex;
  justify-content: center;
  align-items: center;
}

/* =========================
CONTENEDOR
========================= */
.contact-container {
  width: 100%;
  max-width: 500px;
  padding: 40px;
  border-radius: 20px;
  background: white;
  box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

/* =========================
TÍTULO
========================= */
.section-title {
  text-align: center;
  margin-bottom: 20px;
}

/* =========================
FORMULARIO
========================= */
.contact-form {
  display: grid;
  gap: 15px;
}

/* INPUTS */
.contact-form input,
.contact-form textarea {
  padding: 14px;
  font-size: 15px;
  border-radius: 12px;
  border: 1px solid #f6c1d4;
  outline: none;
  transition: 0.3s;
}

/* EFECTO FOCUS */
.contact-form input:focus,
.contact-form textarea:focus {
  border-color: #c9b6e4;
  box-shadow: 0 0 0 3px rgba(201,182,228,0.2);
}

/* TEXTAREA */
.contact-form textarea {
  min-height: 120px;
  resize: none;
}

/* BOTÓN */
.btn-submit {
  padding: 14px;
  font-size: 16px;
  border-radius: 12px;
  font-weight: bold;
  border: none;
  cursor: pointer;
  color: white;
  background: linear-gradient(135deg, #f6c1d4, #c9b6e4);
  transition: 0.3s;
}

/* HOVER */
.btn-submit:hover {
  transform: scale(1.03);
}

/* MENSAJE ERROR */
.error {
  background: #ffe5e5;
  color: #b00020;
  padding: 10px;
  border-radius: 10px;
  margin-bottom: 10px;
  font-size: 14px;
}

a {
  display: block;
  margin-top: 15px;
  text-decoration: none;
  color: #7a4ea3;
  font-weight: 600;
  font-size: 14px;
  text-align: center;
}
/* ===================================================
   RESPONSIVO PARA CELULARES (Pantallas de 768px o menos)
   =================================================== */
@media (max-width: 768px) {
  .contact-page {
    /* Permite que el contenido haga scroll si es más alto que la pantalla */
    align-items: flex-start;
    padding: 20px 15px;
    min-height: 100vh;
  }

  .contact-container {
    padding: 25px 20px; /* Reducimos el espacio interno */
    border-radius: 16px; /* Bordes ligeramente más suaves para móviles */
    box-shadow: 0 10px 25px rgba(0,0,0,0.1); /* Sombra más sutil */
  }

  .section-title {
    font-size: 1.5rem; /* Ajusta el título para que no se vea gigante */
    margin-bottom: 15px;
  }

  .contact-form {
    gap: 12px; /* Un poco menos de separación entre campos */
  }

  /* Ajustes en los inputs para pantallas táctiles */
  .contact-form input,
  .contact-form textarea {
    padding: 12px;
    font-size: 16px; /* Evita que iOS haga zoom automático al hacer foco */
  }

  .contact-form textarea {
    min-height: 100px; /* Un poco más bajo para no empujar todo hacia abajo */
  }

  .btn-submit {
    padding: 12px;
    font-size: 15px;
  }

  /* Desactivamos el efecto hover en pantallas táctiles ya que no hay cursor */
  .btn-submit:hover {
    transform: none;
  }
  
  /* Efecto activo (cuando el usuario presiona el botón en el celular) */
  .btn-submit:active {
    transform: scale(0.98);
  }
}
</style>
</head>
<body class="contact-page">

<div class="contact-container">
    <h2 class="section-title">Crear Cuenta</h2>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="contact-form">
        <input type="text" name="nombre" placeholder="Nombre completo" required>
        <input type="email" name="email" placeholder="Correo electrónico" required>
        <input type="password" name="contrasena" placeholder="Contraseña" required>
        <button type="submit" class="btn-submit">Registrarse</button>
    </form>

    <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
</div>

</body>
</html>