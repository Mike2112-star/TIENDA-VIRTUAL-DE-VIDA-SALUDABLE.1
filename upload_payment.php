<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'comprador' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard_comprador.php");
    exit();
}

$pedido_id = $_POST['pedido_id'];

if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
    $file_tmp_path = $_FILES['comprobante']['tmp_name'];
    $file_name = $_FILES['comprobante']['name'];
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $new_file_name = uniqid('comprobante_', true) . '.' . $file_extension;
    $upload_dir = 'uploads/comprobantes/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $dest_path = $upload_dir . $new_file_name;

    if (move_uploaded_file($file_tmp_path, $dest_path)) {
        try {
            $sql = "UPDATE pedidos SET comprobante_pago_url = ?, estado_pago = 'pagado' WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$dest_path, $pedido_id]);

            header("Location: dashboard_comprador.php?message=" . urlencode("✅ Comprobante subido con éxito. Tu pedido ahora está en revisión por el administrador."));
            exit();

        } catch (PDOException $e) {
            header("Location: payment_confirmation.php?pedido_id={$pedido_id}&error=" . urlencode("❌ Error al guardar la URL del comprobante: " . $e->getMessage()));
            exit();
        }
    } else {
        header("Location: payment_confirmation.php?pedido_id={$pedido_id}&error=" . urlencode("❌ Error al subir el archivo."));
        exit();
    }
} else {
    header("Location: payment_confirmation.php?pedido_id={$pedido_id}&error=" . urlencode("❌ No se seleccionó un archivo o hubo un error en la subida."));
    exit();
}

