<?php
// Este archivo debe ser incluido en dashboard_admin.php.
require_once 'db_connect.php';

// Variables
$edit_mode = false;
$visita_to_edit = null;
$message = '';
$message_type = '';

// --- 1. Cargar datos necesarios para los SELECTs ---
try {
    // Auditores
    $sql_auditores = "SELECT id_auditor, nombres, apellidos FROM auditor ORDER BY apellidos, nombres";
    $stmt_auditores = $pdo->query($sql_auditores);
    $auditores_list = $stmt_auditores->fetchAll(PDO::FETCH_ASSOC);

    // Sucursales
    $sql_sucursales = "SELECT id_sucursal, direccion FROM sucursales ORDER BY direccion";
    $stmt_sucursales = $pdo->query($sql_sucursales);
    $sucursales_list = $stmt_sucursales->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar datos base: " . $e->getMessage());
}


// Lógica para AGREGAR, EDITAR/ACTUALIZAR, o ELIMINAR una visita
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Lógica para ELIMINAR una visita ---
    if (isset($_POST['delete_visita'])) {
        $id_visita = $_POST['id_visita'];

        try {
            $sql = "DELETE FROM visitas WHERE id_visita = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_visita]);
            $message = "✅ Visita eliminada con éxito.";
            $message_type = 'success';
            // Redireccionar para recargar la lista
            header("Location: dashboard_admin.php?section=visitas&message=" . urlencode($message) . "&message_type=success");
            exit();
        } catch (PDOException $e) {
            $message = "❌ Error al eliminar: " . $e->getMessage();
            $message_type = 'danger';
        }

    } else { 
        // --- Lógica para AGREGAR o ACTUALIZAR ---
        // 1. Obtener y sanitizar datos comunes
        $auditor_id = $_POST['auditor_id'];
        $sucursal_id = $_POST['sucursal_id'];
        $fecha_visita = $_POST['fecha_visita'];
        $hallazgos = $_POST['hallazgos'];
        
        // Lógica para ACTUALIZAR una visita existente
        if (isset($_POST['update_visita'])) {
            $id_visita = $_POST['id_visita'];
            
            try {
                $sql = "UPDATE visitas SET auditor_id = ?, sucursal_id = ?, fecha_visita = ?, hallazgos = ? WHERE id_visita = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$auditor_id, $sucursal_id, $fecha_visita, $hallazgos, $id_visita]);
                $message = "✅ Visita actualizada con éxito.";
                $message_type = 'success';
                // Redireccionar para limpiar la URL y recargar la lista
                header("Location: dashboard_admin.php?section=visitas&message=" . urlencode($message) . "&message_type=success");
                exit();

            } catch (PDOException $e) {
                $message = "❌ Error al actualizar la visita: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
        // Lógica para AGREGAR una nueva visita
        else if (isset($_POST['add_visita'])) {
            try {
                $sql = "INSERT INTO visitas (auditor_id, sucursal_id, fecha_visita, hallazgos) 
                        VALUES (?, ?, ?, ?)"; 
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$auditor_id, $sucursal_id, $fecha_visita, $hallazgos]);
                $message = "✅ Visita registrada con éxito.";
                $message_type = 'success';
                
                // Redireccionar para limpiar el formulario y recargar la lista
                header("Location: dashboard_admin.php?section=visitas&message=" . urlencode($message) . "&message_type=success");
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
    $id_visita = $_GET['edit_id'];
    
    $sql = "SELECT * FROM visitas WHERE id_visita = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_visita]);
    $visita_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$visita_to_edit) {
        $message = "❌ Error: Visita no encontrada.";
        $message_type = 'danger';
        $edit_mode = false;
    }
}

// Lógica para obtener el LISTADO COMPLETO de visitas con nombres
try {
    $sql_listado = "
        SELECT 
            v.id_visita,
            v.fecha_visita,
            v.hallazgos,
            a.nombres AS auditor_nombres,
            a.apellidos AS auditor_apellidos,
            s.direccion AS sucursal_direccion
        FROM 
            visitas v
        JOIN 
            auditor a ON v.auditor_id = a.id_auditor
        JOIN 
            sucursales s ON v.sucursal_id = s.id_sucursal
        ORDER BY 
            v.fecha_visita DESC
    ";
    $stmt_listado = $pdo->query($sql_listado);
    $visitas = $stmt_listado->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la tabla 'visitas' aún no existe, esto evitará el error fatal.
    $visitas = []; 
    $message = "⚠️ Error al cargar la lista de visitas. Asegúrate de que las tablas `visitas`, `auditor` y `sucursales` existen. " . $e->getMessage();
    $message_type = 'warning';
}


// Manejo de mensajes pasados por la URL desde un redireccionamiento
if (isset($_GET['message']) && isset($_GET['message_type'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['message_type'];
}
?>

<h2 class="mb-4">Gestión de Visitas de Auditoría</h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-5">
    <div class="card-header bg-warning text-dark">
        <h3 class="mb-0"><?php echo $edit_mode ? '✏️ Editar Visita (ID: ' . htmlspecialchars($visita_to_edit['id_visita']) . ')' : '➕ Registrar Nueva Visita'; ?></h3>
    </div>
    <div class="card-body">
        <form action="dashboard_admin.php?section=visitas" method="POST">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id_visita" value="<?php echo htmlspecialchars($visita_to_edit['id_visita']); ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="auditor_id" class="form-label">Auditor Asignado</label>
                    <select id="auditor_id" name="auditor_id" class="form-select" required>
                        <option value="">Seleccione un Auditor</option>
                        <?php foreach ($auditores_list as $auditor): ?>
                            <option value="<?php echo htmlspecialchars($auditor['id_auditor']); ?>"
                                <?php 
                                if ($edit_mode && $auditor['id_auditor'] == $visita_to_edit['auditor_id']) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                <?php echo htmlspecialchars($auditor['nombres'] . ' ' . $auditor['apellidos']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="sucursal_id" class="form-label">Sucursal Visitada</label>
                    <select id="sucursal_id" name="sucursal_id" class="form-select" required>
                        <option value="">Seleccione una Sucursal</option>
                        <?php foreach ($sucursales_list as $sucursal): ?>
                            <option value="<?php echo htmlspecialchars($sucursal['id_sucursal']); ?>"
                                <?php 
                                if ($edit_mode && $sucursal['id_sucursal'] == $visita_to_edit['sucursal_id']) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                <?php echo htmlspecialchars($sucursal['direccion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="fecha_visita" class="form-label">Fecha de Visita</label>
                    <input type="date" id="fecha_visita" name="fecha_visita" class="form-control" required 
                           value="<?php echo $edit_mode ? htmlspecialchars(substr($visita_to_edit['fecha_visita'], 0, 10)) : date('Y-m-d'); ?>">
                </div>
                <div class="col-md-8">
                    <label for="hallazgos" class="form-label">Hallazgos (Resumen)</label>
                    <textarea id="hallazgos" name="hallazgos" rows="3" class="form-control" required><?php echo $edit_mode ? htmlspecialchars($visita_to_edit['hallazgos']) : ''; ?></textarea>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <?php if ($edit_mode): ?>
                    <button type="submit" name="update_visita" class="btn btn-success">Actualizar Visita</button>
                    <a href="dashboard_admin.php?section=visitas" class="btn btn-secondary">Cancelar Edición</a>
                <?php else: ?>
                    <button type="submit" name="add_visita" class="btn btn-primary">Registrar Visita</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<h3 class="mt-5 mb-3">Lista de Visitas Registradas</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Auditor</th>
                <th>Sucursal</th>
                <th>Fecha Visita</th>
                <th>Hallazgos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (!empty($visitas)):
                foreach ($visitas as $visita): 
            ?>
            <tr>
                <td><?php echo htmlspecialchars($visita['id_visita']); ?></td>
                <td><?php echo htmlspecialchars($visita['auditor_nombres'] . ' ' . $visita['auditor_apellidos']); ?></td>
                <td><?php echo htmlspecialchars($visita['sucursal_direccion']); ?></td>
                <td><?php echo htmlspecialchars(substr($visita['fecha_visita'], 0, 10)); ?></td>
                <td><?php echo htmlspecialchars($visita['hallazgos']); ?></td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="dashboard_admin.php?section=visitas&edit_id=<?php echo htmlspecialchars($visita['id_visita']); ?>" class="btn btn-sm btn-info text-white">Editar</a>
                        <form action="dashboard_admin.php?section=visitas" method="POST" style="display:inline;">
                            <input type="hidden" name="id_visita" value="<?php echo htmlspecialchars($visita['id_visita']); ?>">
                            <button type="submit" name="delete_visita" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar esta visita?');">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php 
                endforeach; 
            else:
            ?>
            <tr>
                <td colspan="6" class="text-center">No hay visitas registradas.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
