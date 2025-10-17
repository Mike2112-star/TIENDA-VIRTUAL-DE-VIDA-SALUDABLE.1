<?php
// Este archivo se incluye en dashboard_admin.php, por lo que la conexión a la DB y la sesión ya están iniciadas.
require_once 'db_connect.php';

// Lógica para procesar la devolución
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_devolucion'])) {
    $detalles_pedido_id = $_POST['detalles_pedido_id'];
    $cantidad = $_POST['cantidad'];
    $razon = $_POST['razon'];

    try {
        // Iniciar la transacción
        $pdo->beginTransaction();

        // 1. Obtener el ID del producto del detalle del pedido
        $sql_select = "SELECT producto_id, cantidad FROM detalles_pedido WHERE id = ?";
        $stmt_select = $pdo->prepare($sql_select);
        $stmt_select->execute([$detalles_pedido_id]);
        $detalle_pedido = $stmt_select->fetch(PDO::FETCH_ASSOC);

        if (!$detalle_pedido) {
            $message = "❌ Error: El ID de detalle de pedido no existe.";
            $pdo->rollBack();
        } elseif ($cantidad > $detalle_pedido['cantidad']) {
            $message = "❌ Error: La cantidad a devolver ({$cantidad}) no puede ser mayor que la cantidad vendida ({$detalle_pedido['cantidad']}).";
            $pdo->rollBack();
        } else {
            // 2. Inserción en la tabla de devoluciones
            $sql_insert_devolucion = "INSERT INTO devoluciones (detalles_pedido_id, cantidad, razon) VALUES (?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert_devolucion);
            $stmt_insert->execute([$detalles_pedido_id, $cantidad, $razon]);

            // 3. Actualizar el stock del producto
            $sql_update_stock = "UPDATE productos SET stock = stock + ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update_stock);
            $stmt_update->execute([$cantidad, $detalle_pedido['producto_id']]);

            // Confirmar la transacción
            $pdo->commit();
            $message = "✅ Devolución registrada con éxito. El stock del producto ha sido actualizado.";
        }
    } catch (PDOException $e) {
        // En caso de error, revertir la transacción
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "❌ Error al registrar la devolución: " . $e->getMessage();
    }
}
?>

<h2 class="mb-4">Gestión de Devoluciones</h2>

<?php if (isset($message)): ?>
    <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>" role="alert">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0">Registrar una Devolución</h3>
    </div>
    <div class="card-body">
        <p class="mb-3">Para registrar una devolución, ingrese el ID del detalle del pedido (tabla `detalles_pedido`).</p>
        <form action="dashboard_admin.php?section=returns" method="POST">
            <div class="mb-3">
                <label for="detalles_pedido_id" class="form-label">ID de Detalle de Pedido:</label>
                <input type="number" id="detalles_pedido_id" name="detalles_pedido_id" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="cantidad" class="form-label">Cantidad a devolver:</label>
                <input type="number" id="cantidad" name="cantidad" class="form-control" min="1" required>
            </div>
            <div class="mb-3">
                <label for="razon" class="form-label">Razón de la devolución:</label>
                <textarea id="razon" name="razon" rows="3" class="form-control"></textarea>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="registrar_devolucion" class="btn btn-primary">Registrar Devolución</button>
            </div>
        </form>
    </div>
</div>