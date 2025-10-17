<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'comprador') {
    header("Location: index.html");
    exit();
}

if (empty($_SESSION['cart'])) {
    header("Location: dashboard_comprador.php?error=" . urlencode("❌ El carrito está vacío."));
    exit();
}

try {
    $pdo->beginTransaction();

    $user_id = $_SESSION['user_id'];
    $total = 0;

    foreach ($_SESSION['cart'] as $product_id => $item) {
        $total += $item['price'] * $item['quantity'];
    }

    $sql_pedido = "INSERT INTO pedidos (cliente_id, fecha, total, estado_pago) VALUES (?, NOW(), ?, 'pendiente_pago') RETURNING id";
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([$user_id, $total]);
    $pedido_id = $stmt_pedido->fetchColumn();

    $sql_detalles = "INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $sql_stock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
    $stmt_detalles = $pdo->prepare($sql_detalles);
    $stmt_stock = $pdo->prepare($sql_stock);

    foreach ($_SESSION['cart'] as $product_id => $item) {
        $stmt_detalles->execute([$pedido_id, $product_id, $item['quantity'], $item['price']]);
        $stmt_stock->execute([$item['quantity'], $product_id]);
    }

    $pdo->commit();
    unset($_SESSION['cart']);

    header("Location: payment_confirmation.php?pedido_id=" . $pedido_id . "&total=" . $total);
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: dashboard_comprador.php?error=" . urlencode("❌ Error al procesar el pedido: " . $e->getMessage()));
    exit();
}
