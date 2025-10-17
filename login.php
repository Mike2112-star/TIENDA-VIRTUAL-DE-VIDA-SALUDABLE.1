<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // CORRECCIÓN: Se eliminó el prefijo "empresa." para que la consulta funcione
        $sql = "SELECT id, username, password_hash, user_type FROM login_menu WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_id'] = $user['id']; // Almacena el ID del usuario en la sesión

            // Redirigir según el tipo de usuario
            // Redirigir según username o tipo
switch (true) {
    case $user['user_type'] === 'admin':
        header("Location: dashboard_admin.php");
        break;
    case $user['username'] === 'auditoria':   // ← nuevo caso
        header("Location: dashboard_auditoria.php");
        break;
    case $user['user_type'] === 'ventas':
        header("Location: dashboard_sales.php");
        break;
    case $user['user_type'] === 'comprador':
        header("Location: dashboard_comprador.php");
        break;
    default:
        header("Location: index.html");
}
            exit();
        } else {
            // Si el usuario o contraseña son incorrectos
            $_SESSION['login_error'] = "Usuario o contraseña incorrectos.";
            header("Location: index.html");
            exit();
        }
    } catch (PDOException $e) {
        // Muestra un error detallado para depuración
        $_SESSION['login_error'] = "Error en la base de datos: " . $e->getMessage();
        header("Location: index.html");
        exit();
    }
} else {
    // Si se accede directamente sin POST, redirige al inicio
    header("Location: index.html");
    exit();
}
?>
