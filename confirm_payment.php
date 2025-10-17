<?php
// Incluye tu archivo de conexión a la base de datos
require 'db_connect.php';

// Verifica si se ha pasado un ID de pedido
if (isset($_GET['id'])) {
    $pedido_id = $_GET['id'];

    try {
        // Prepara y ejecuta la consulta para actualizar el estado del pedido
        $sql = "UPDATE pedidos SET estado_pago = 'confirmado_admin' WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $pedido_id]);

        // Redirige de vuelta al panel de pagos del administrador
        header("Location: dashboard_admin.php?section=payments&success=pago_confirmado");
        exit();

    } catch (PDOException $e) {
        // Manejo de errores
        header("Location: dashboard_admin.php?section=payments&error=" . urlencode("Error al confirmar el pago: " . $e->getMessage()));
        exit();
    }
} else {
    // Si no se pasó un ID, redirige con un error
    header("Location: dashboard_admin.php?section=payments&error=id_invalido");
    exit();
}
?>
