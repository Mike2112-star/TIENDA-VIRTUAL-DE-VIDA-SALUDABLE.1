<?php
// Este archivo debe ser incluido en dashboard_admin.php.
require_once 'db_connect.php';

// Variables para almacenar datos de la sucursal a editar
$edit_mode = false;
$sucursal_to_edit = null;
$message = '';
$message_type = '';

// Se asume que $sucursales ya ha sido cargada por dashboard_admin.php
// con la consulta: "SELECT * FROM sucursales ORDER BY id_sucursal DESC"

// Lógica para AGREGAR, EDITAR/ACTUALIZAR, o ELIMINAR una sucursal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Lógica para ELIMINAR una sucursal ---
    if (isset($_POST['delete_sucursal'])) {
        $id_sucursal = $_POST['id_sucursal'];

        try {
            // 1. Verificar si la sucursal tiene visitas asociadas (Foreign Key constraint)
            $sql_check = "SELECT COUNT(*) FROM visitas WHERE sucursal_id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$id_sucursal]);
            $count = $stmt_check->fetchColumn();

            if ($count > 0) {
                $message = "❌ Error: No se puede eliminar la sucursal porque tiene **{$count}** visitas registradas. Elimine las visitas primero.";
                $message_type = 'danger';
            } else {
                // 2. Ejecutar la eliminación
                $sql = "DELETE FROM sucursales WHERE id_sucursal = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_sucursal]);
                $message = "✅ Sucursal eliminada con éxito.";
                $message_type = 'success';
                // Redireccionar para limpiar el formulario y recargar la lista
                header("Location: dashboard_admin.php?section=sucursales&message=" . urlencode($message) . "&message_type=success");
                exit();
            }
        } catch (PDOException $e) {
            $message = "❌ Error al eliminar: " . $e->getMessage();
            $message_type = 'danger';
        }

    } else { 
        // --- Lógica para AGREGAR o ACTUALIZAR ---
        // 1. Obtener y sanitizar datos comunes
        $direccion = $_POST['direccion'];
        $telefono = $_POST['telefono'];
        $nombre_encargado = $_POST['nombre_encargado'];
        
        // Lógica para ACTUALIZAR una sucursal existente
        if (isset($_POST['update_sucursal'])) {
            $id_sucursal = $_POST['id_sucursal'];
            
            try {
                $sql = "UPDATE sucursales SET direccion = ?, telefono = ?, nombre_encargado = ? WHERE id_sucursal = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$direccion, $telefono, $nombre_encargado, $id_sucursal]);
                $message = "✅ Sucursal actualizada con éxito.";
                $message_type = 'success';
                // Redireccionar para limpiar la URL y recargar la lista
                header("Location: dashboard_admin.php?section=sucursales&message=" . urlencode($message) . "&message_type=success");
                exit();

            } catch (PDOException $e) {
                $message = "❌ Error al actualizar la sucursal: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
        // Lógica para AGREGAR una nueva sucursal
        else if (isset($_POST['add_sucursal'])) {
            try {
                $sql = "INSERT INTO sucursales (direccion, telefono, nombre_encargado) 
                        VALUES (?, ?, ?)"; 
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$direccion, $telefono, $nombre_encargado]);
                $message = "✅ Sucursal en **{$direccion}** registrada con éxito.";
                $message_type = 'success';
                
                // Redireccionar para limpiar el formulario y recargar la lista
                header("Location: dashboard_admin.php?section=sucursales&message=" . urlencode($message) . "&message_type=success");
                exit();

            } catch (PDOException $e) {
                $message = "❌ Error al registrar: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Lógica para precargar datos si se está EDITANDO
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id_sucursal = $_GET['edit_id'];
    
    $sql = "SELECT * FROM sucursales WHERE id_sucursal = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_sucursal]);
    $sucursal_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sucursal_to_edit) {
        $message = "❌ Error: Sucursal no encontrada.";
        $message_type = 'danger';
        $edit_mode = false;
    }
}

// Manejo de mensajes pasados por la URL desde un redireccionamiento
if (isset($_GET['message']) && isset($_GET['message_type'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['message_type'];
}
?>

<h2 class="mb-4">Gestión de Sucursales</h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-5">
    <div class="card-header bg-success text-white">
        <h3 class="mb-0"><?php echo $edit_mode ? '✏️ Editar Sucursal (ID: ' . htmlspecialchars($sucursal_to_edit['id_sucursal']) . ')' : '➕ Registrar Nueva Sucursal'; ?></h3>
    </div>
    <div class="card-body">
        <form action="dashboard_admin.php?section=sucursales" method="POST">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id_sucursal" value="<?php echo htmlspecialchars($sucursal_to_edit['id_sucursal']); ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="direccion" class="form-label">Dirección</label>
                    <input type="text" id="direccion" name="direccion" class="form-control" required 
                           value="<?php echo $edit_mode ? htmlspecialchars($sucursal_to_edit['direccion']) : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" class="form-control" required 
                           value="<?php echo $edit_mode ? htmlspecialchars($sucursal_to_edit['telefono']) : ''; ?>">
                </div>
                <div class="col-md-12">
                    <label for="nombre_encargado" class="form-label">Nombre del Encargado</label>
                    <input type="text" id="nombre_encargado" name="nombre_encargado" class="form-control" required 
                           value="<?php echo $edit_mode ? htmlspecialchars($sucursal_to_edit['nombre_encargado']) : ''; ?>">
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <?php if ($edit_mode): ?>
                    <button type="submit" name="update_sucursal" class="btn btn-success">Actualizar Sucursal</button>
                    <a href="dashboard_admin.php?section=sucursales" class="btn btn-secondary">Cancelar Edición</a>
                <?php else: ?>
                    <button type="submit" name="add_sucursal" class="btn btn-primary">Registrar Sucursal</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<h3 class="mt-5 mb-3">Lista de Sucursales Registradas</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Encargado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (isset($sucursales) && is_array($sucursales)):
                foreach ($sucursales as $sucursal): 
            ?>
            <tr>
                <td><?php echo htmlspecialchars($sucursal['id_sucursal']); ?></td>
                <td><?php echo htmlspecialchars($sucursal['direccion']); ?></td>
                <td><?php echo htmlspecialchars($sucursal['telefono']); ?></td>
                <td><?php echo htmlspecialchars($sucursal['nombre_encargado']); ?></td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="dashboard_admin.php?section=sucursales&edit_id=<?php echo htmlspecialchars($sucursal['id_sucursal']); ?>" class="btn btn-sm btn-info text-white">Editar</a>
                        <form action="dashboard_admin.php?section=sucursales" method="POST" style="display:inline;">
                            <input type="hidden" name="id_sucursal" value="<?php echo htmlspecialchars($sucursal['id_sucursal']); ?>">
                            <button type="submit" name="delete_sucursal" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar esta sucursal? ATENCIÓN: Solo se eliminará si no tiene visitas asociadas.');">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php 
                endforeach; 
            else:
            ?>
            <tr>
                <td colspan="5" class="text-center">No hay sucursales registradas.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
