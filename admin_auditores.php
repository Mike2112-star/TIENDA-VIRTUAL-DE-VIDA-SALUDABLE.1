<?php
// Este archivo debe ser incluido en dashboard_admin.php.
require_once 'db_connect.php';

// Variables para almacenar datos del auditor a editar
$edit_mode = false;
$auditor_to_edit = null;
$message = '';
$message_type = '';

// Se asume que $auditores ya ha sido cargada por dashboard_admin.php
// con la consulta: "SELECT * FROM auditor ORDER BY id_auditor DESC"

// Lógica para AGREGAR, EDITAR/ACTUALIZAR, o ELIMINAR un auditor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Lógica para ELIMINAR un auditor ---
    if (isset($_POST['delete_auditor'])) {
        $id_auditor = $_POST['id_auditor'];

        try {
            // 1. Verificar si el auditor tiene visitas asociadas (Foreign Key constraint)
            $sql_check = "SELECT COUNT(*) FROM visitas WHERE auditor_id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$id_auditor]);
            $count = $stmt_check->fetchColumn();

            if ($count > 0) {
                $message = "❌ Error: No se puede eliminar el auditor porque tiene **{$count}** visitas registradas. Elimine las visitas primero.";
                $message_type = 'danger';
            } else {
                // 2. Ejecutar la eliminación
                $sql = "DELETE FROM auditor WHERE id_auditor = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_auditor]);
                $message = "✅ Auditor eliminado con éxito.";
                $message_type = 'success';
                // Redireccionar para limpiar el formulario y recargar la lista
                header("Location: dashboard_admin.php?section=auditores&message=" . urlencode($message) . "&message_type=success");
                exit();
            }
        } catch (PDOException $e) {
            $message = "❌ Error al eliminar: " . $e->getMessage();
            $message_type = 'danger';
        }

    } else { 
        // --- Lógica para AGREGAR o ACTUALIZAR ---
        // 1. Obtener y sanitizar datos comunes
        $nro_documento = $_POST['nro_documento'];
        $nombres = $_POST['nombres'];
        $apellidos = $_POST['apellidos'];
        $celular = $_POST['celular'];
        $correo = $_POST['correo'];
        $direccion = $_POST['direccion'];
        $fecha_ingreso = $_POST['fecha_ingreso'] ?? null; // Solo se usa en actualización
        
        // Lógica para ACTUALIZAR un auditor existente
        if (isset($_POST['update_auditor'])) {
            $id_auditor = $_POST['id_auditor'];
            
            try {
                // Se incluye fecha_ingreso en la actualización, asumiendo que el usuario puede cambiarla en el formulario
                $sql = "UPDATE auditor SET nro_documento = ?, nombres = ?, apellidos = ?, celular = ?, correo = ?, direccion = ?, fecha_ingreso = ? WHERE id_auditor = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nro_documento, $nombres, $apellidos, $celular, $correo, $direccion, $fecha_ingreso, $id_auditor]);
                $message = "✅ Auditor actualizado con éxito.";
                $message_type = 'success';
                // Redireccionar para limpiar la URL y recargar la lista
                header("Location: dashboard_admin.php?section=auditores&message=" . urlencode($message) . "&message_type=success");
                exit();

            } catch (PDOException $e) {
                $message = "❌ Error al actualizar el auditor: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
        // Lógica para AGREGAR un nuevo auditor
        else if (isset($_POST['add_auditor'])) {
            try {
                // Se usa NOW() para la fecha de registro y se le da el nombre fecha_ingreso
                $sql = "INSERT INTO auditor (nro_documento, nombres, apellidos, celular, correo, direccion, fecha_ingreso) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())"; 
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nro_documento, $nombres, $apellidos, $celular, $correo, $direccion]);
                $message = "✅ Auditor **{$nombres} {$apellidos}** registrado con éxito.";
                $message_type = 'success';
                
                // Redireccionar para limpiar el formulario y recargar la lista
                header("Location: dashboard_admin.php?section=auditores&message=" . urlencode($message) . "&message_type=success");
                exit();

            } catch (PDOException $e) {
                // Error 23505 (violación de restricción única)
                if ($e->getCode() == 23505) { 
                    $message = "❌ Error: El número de documento o el correo ya existen. Por favor, verifica los datos.";
                } else {
                    $message = "❌ Error al registrar: " . $e->getMessage();
                }
                $message_type = 'danger';
            }
        }
    }
}

// Lógica para precargar datos si se está EDITANDO
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id_auditor = $_GET['edit_id'];
    
    $sql = "SELECT * FROM auditor WHERE id_auditor = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_auditor]);
    $auditor_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$auditor_to_edit) {
        $message = "❌ Error: Auditor no encontrado.";
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

<h2 class="mb-4">Gestión de Auditores</h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-5">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0"><?php echo $edit_mode ? '✏️ Editar Auditor (ID: ' . htmlspecialchars($auditor_to_edit['id_auditor']) . ')' : '➕ Registrar Nuevo Auditor'; ?></h3>
    </div>
    <div class="card-body">
        <form action="dashboard_admin.php?section=auditores" method="POST">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id_auditor" value="<?php echo htmlspecialchars($auditor_to_edit['id_auditor']); ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="nro_documento" class="form-label">Nro. Documento</label>
                    <input type="number" id="nro_documento" name="nro_documento" class="form-control" required 
                           value="<?php echo $edit_mode ? htmlspecialchars($auditor_to_edit['nro_documento']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="nombres" class="form-label">Nombres</label>
                    <input type="text" id="nombres" name="nombres" class="form-control" required 
                           value="<?php echo $edit_mode ? htmlspecialchars($auditor_to_edit['nombres']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="apellidos" class="form-label">Apellidos</label>
                    <input type="text" id="apellidos" name="apellidos" class="form-control" required 
                           value="<?php echo $edit_mode ? htmlspecialchars($auditor_to_edit['apellidos']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="celular" class="form-label">Celular</label>
                    <input type="number" id="celular" name="celular" class="form-control" required 
                           value="<?php echo $edit_mode ? htmlspecialchars($auditor_to_edit['celular']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="correo" class="form-label">Correo</label>
                    <input type="email" id="correo" name="correo" class="form-control" required 
                           value="<?php echo $edit_mode ? htmlspecialchars($auditor_to_edit['correo']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="direccion" class="form-label">Dirección</label>
                    <input type="text" id="direccion" name="direccion" class="form-control" required 
                           value="<?php echo $edit_mode ? htmlspecialchars($auditor_to_edit['direccion']) : ''; ?>">
                </div>
                
                <?php if ($edit_mode): 
                    // Se usa substr para formatear la fecha a YYYY-MM-DD para el input type="date"
                    $fecha_value = substr($auditor_to_edit['fecha_ingreso'], 0, 10);
                ?>
                    <div class="col-md-4">
                        <label for="fecha_ingreso" class="form-label">Fecha de Ingreso</label>
                        <input type="date" id="fecha_ingreso" name="fecha_ingreso" class="form-control" required 
                               value="<?php echo htmlspecialchars($fecha_value); ?>">
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <?php if ($edit_mode): ?>
                    <button type="submit" name="update_auditor" class="btn btn-success">Actualizar Auditor</button>
                    <a href="dashboard_admin.php?section=auditores" class="btn btn-secondary">Cancelar Edición</a>
                <?php else: ?>
                    <button type="submit" name="add_auditor" class="btn btn-primary">Registrar Auditor</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<h3 class="mt-5 mb-3">Lista de Auditores</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Documento</th>
                <th>Nombre Completo</th>
                <th>Celular</th>
                <th>Correo</th>
                <th>Dirección</th>
                <th>F. Ingreso</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (isset($auditores) && is_array($auditores)):
                foreach ($auditores as $auditor): 
            ?>
            <tr>
                <td><?php echo htmlspecialchars($auditor['id_auditor']); ?></td>
                <td><?php echo htmlspecialchars($auditor['nro_documento']); ?></td>
                <td><?php echo htmlspecialchars($auditor['nombres'] . ' ' . $auditor['apellidos']); ?></td>
                <td><?php echo htmlspecialchars($auditor['celular']); ?></td>
                <td><?php echo htmlspecialchars($auditor['correo']); ?></td>
                <td><?php echo htmlspecialchars($auditor['direccion']); ?></td>
                <td><?php echo htmlspecialchars(substr($auditor['fecha_ingreso'], 0, 10)); ?></td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="dashboard_admin.php?section=auditores&edit_id=<?php echo htmlspecialchars($auditor['id_auditor']); ?>" class="btn btn-sm btn-info text-white">Editar</a>
                        <form action="dashboard_admin.php?section=auditores" method="POST" style="display:inline;">
                            <input type="hidden" name="id_auditor" value="<?php echo htmlspecialchars($auditor['id_auditor']); ?>">
                            <button type="submit" name="delete_auditor" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar a este auditor? ATENCIÓN: Solo se eliminará si no tiene visitas asociadas.');">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php 
                endforeach; 
            else:
            ?>
            <tr>
                <td colspan="8" class="text-center">No hay auditores registrados.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>