<?php
session_start();
require 'db_connect.php';

// Verifica si la solicitud es de tipo POST y si se ha subido un archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['comprobante']) && isset($_POST['pedido_id'])) {
    $pedido_id = $_POST['pedido_id'];
    $file = $_FILES['comprobante'];
    
    // Directorio donde se guardarán los comprobantes
    $upload_dir = 'uploads/comprobantes/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generar un nombre de archivo único para evitar colisiones
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_file_name = uniqid('comprobante_', true) . '.' . $file_extension;
    $upload_path = $upload_dir . $new_file_name;
    
    // Mover el archivo subido al directorio
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        try {
            // Iniciar una transacción para garantizar la integridad
            $pdo->beginTransaction();

            // 1. Actualizar el estado del pedido y la URL del comprobante
            $sql_update_pedido = "UPDATE pedidos SET estado_pago = 'pagado', comprobante_pago_url = ? WHERE id = ?";
            $stmt_update_pedido = $pdo->prepare($sql_update_pedido);
            $stmt_update_pedido->execute([$upload_path, $pedido_id]);

            // 2. Disminuir el stock de los productos vendidos
            $sql_detalles = "SELECT producto_id, cantidad FROM detalles_pedido WHERE pedido_id = ?";
            $stmt_detalles = $pdo->prepare($sql_detalles);
            $stmt_detalles->execute([$pedido_id]);
            $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

            $sql_update_stock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
            $stmt_update_stock = $pdo->prepare($sql_update_stock);

            foreach ($detalles as $detalle) {
                $stmt_update_stock->execute([$detalle['cantidad'], $detalle['producto_id']]);
            }

            $pdo->commit();
            
            // Redirigir con mensaje de éxito
            header("Location: dashboard_sales.php?message=" . urlencode("✅ Venta registrada con éxito. ¡El comprobante ha sido subido!"));
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            // Si hay un error, redirigir con un mensaje de error
            header("Location: dashboard_sales.php?message=" . urlencode("❌ Error al procesar la venta: " . $e->getMessage()));
            exit();
        }
    } else {
        header("Location: dashboard_sales.php?message=" . urlencode("❌ Error al subir el archivo."));
        exit();
    }
} else {
    header("Location: dashboard_sales.php?message=" . urlencode("❌ Solicitud inválida."));
    exit();
}
?>
