<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$username = $_SESSION['username'];
$user_type = $_SESSION['user_type'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Datos del Usuario</title>
</head>
<body>
    <h2>Datos de Usuario</h2>
    <p>Bienvenido, <?php echo htmlspecialchars($username); ?>!</p>
    <p>Tu tipo de usuario es: **<?php echo htmlspecialchars($user_type); ?>**</p>
    
    <h3>Tabla de Datos (Ejemplo)</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Campo</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Usuario</td>
                <td><?php echo htmlspecialchars($username); ?></td>
            </tr>
            <tr>
                <td>Tipo de Usuario</td>
                <td><?php echo htmlspecialchars($user_type); ?></td>
            </tr>
            </tbody>
    </table>
    <br>
    <a href="logout.php">Cerrar Sesión</a>
</body>
</html>