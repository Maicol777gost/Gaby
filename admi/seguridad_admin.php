<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Si no es admin, lo devolvemos a la tienda pública
    header("Location: ../index.php");
    exit();
}
?>
