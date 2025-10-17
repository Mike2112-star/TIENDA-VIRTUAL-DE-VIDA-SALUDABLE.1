<?php
// Este archivo debe ser incluido en dashboard_admin.php.
require_once 'db_connect.php';

// Variable para mensajes de retroalimentación
$message = '';

// Lógica para registrar un nuevo usuario (sin cliente asociado para el admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    try {
        // Encriptar la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insertar el nuevo usuario en la tabla login_menu
        $sql = "INSERT INTO login_menu (username, password_hash, user_type) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $password_hash, $user_type]);
        $message = "✅ Usuario **{$username}** registrado con éxito.";

    } catch (PDOException $e) {
        if ($e->getCode() == 23505) { // Código de error para restricción UNIQUE
            $message = "❌ Error: El nombre de usuario '{$username}' ya existe. Por favor, elige otro.";
        } else {
            $message = "❌ Error al registrar: " . $e->getMessage();
        }
    }
}

// Lógica para eliminar un usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = $_POST['id'];

    // Evitar que el administrador se elimine a sí mismo
    if ($id == $_SESSION['user_id']) {
        $message = "❌ Error: No puedes eliminar tu propio usuario.";
    } else {
        try {
            $sql = "DELETE FROM login_menu WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $message = "✅ Usuario eliminado con éxito.";
        } catch (PDOException $e) {
            $message = "❌ Error al eliminar el usuario: " . $e->getMessage();
        }
    }
}

// Lógica para obtener la lista de usuarios
$sql = "SELECT id, username, user_type FROM login_menu ORDER BY id ASC";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="mb-4">Gestión de Usuarios</h2>

<?php if ($message): ?>
    <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>" role="alert">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0">Registrar Nuevo Usuario</h3>
    </div>
    <div class="card-body">
        <form action="dashboard_admin.php?section=users" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Usuario:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="user_type" class="form-label">Tipo de Usuario:</label>
                <select id="user_type" name="user_type" class="form-select" required>
                    <option value="admin">Administrativo</option>
                    <option value="ventas">Ventas</option>
                </select>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="register_user" class="btn btn-primary">Registrar Usuario</button>
            </div>
        </form>
    </div>
</div>

<h3 class="mt-5 mb-3">Lista de Usuarios Existentes</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Tipo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                <td>
                    <form action="dashboard_admin.php?section=users" method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar a este usuario?');">Eliminar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
