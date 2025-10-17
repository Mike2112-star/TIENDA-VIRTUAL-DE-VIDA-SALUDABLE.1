<?php
require 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    try {
        $pdo->beginTransaction();

        // Inserta en la tabla de clientes si el rol es comprador
        if ($user_type === 'comprador') {
            $sql_cliente = "INSERT INTO clientes (nombre, apellido) VALUES (?, ?)";
            $stmt_cliente = $pdo->prepare($sql_cliente);
            $stmt_cliente->execute([$nombre, $apellido]);

            $cliente_id = $pdo->lastInsertId('clientes_id_seq');
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql_usuario = "INSERT INTO login_menu (username, password_hash, user_type) VALUES (?, ?, ?)";
        $stmt_usuario = $pdo->prepare($sql_usuario);
        $stmt_usuario->execute([$username, $password_hash, $user_type]);

        $pdo->commit();
        $message = "✅ Usuario **{$username}** registrado con éxito. Ahora puedes iniciar sesión.";

    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23505) { 
            $message = "❌ Error: El nombre de usuario '{$username}' ya existe. Por favor, elige otro.";
        } else {
            $message = "❌ Error al registrar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #388e3c;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
        .register-card {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
        }
        .register-header {
            background-color: #4CAF50;
            color: #ffffff;
            padding: 15px 0;
            border-radius: 10px 10px 0 0;
            margin: -40px -40px 30px -40px;
            text-align: center;
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            font-size: 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }
        .btn-primary {
            background-color: #4CAF50;
            border-color: #4CAF50;
            border-radius: 8px;
            padding: 12px 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #43A047;
            border-color: #43A047;
        }
        .btn-secondary {
            background-color: #81C784;
            border-color: #81C784;
            border-radius: 8px;
            padding: 12px 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .btn-secondary:hover {
            background-color: #66BB6A;
            border-color: #66BB6A;
        }
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center my-5">
    <div class="register-card">
        <div class="register-header">Registrarse</div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label for="nombre" class="form-label visually-hidden">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Nombre" required>
                </div>
                <div class="mb-3">
                    <label for="apellido" class="form-label visually-hidden">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" class="form-control" placeholder="Apellido" required>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label visually-hidden">Usuario:</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Usuario" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label visually-hidden">Contraseña:</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Contraseña" required>
                </div>
                <div class="mb-3">
                    <label for="user_type" class="form-label visually-hidden">Tipo de Usuario:</label>
                    <select id="user_type" name="user_type" class="form-select" required>
                        <option value="admin">Administrativo</option>
                        <option value="ventas">Ventas</option>
                        <option value="comprador">Comprador</option>
                    </select>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Registrar</button>
                    <a href="index.html" class="btn btn-secondary mt-2">Volver al Login</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
