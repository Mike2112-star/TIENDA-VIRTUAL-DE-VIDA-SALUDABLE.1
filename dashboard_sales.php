<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'ventas') {
    header("Location: index.html");
    exit();
}
require 'db_connect.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Lógica para agregar productos al carrito con validación de stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];

    $sql = "SELECT id, nombre, precio, stock FROM productos WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $current_quantity_in_cart = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
        
        if ($product['stock'] > $current_quantity_in_cart) {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity']++;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'name' => $product['nombre'],
                    'price' => $product['precio'],
                    'quantity' => 1
                ];
            }
            $message = "✅ Producto agregado al carrito.";
        } else {
            $message = "❌ No hay suficiente stock para este producto.";
        }
    }
}

// Lógica para procesar la venta via AJAX (se ha movido a create_order.php)
// Se ha añadido la lógica para manejar el AJAX de creación del pedido en esta misma página para simplificar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_order') {
    header('Content-Type: application/json');
    if (empty($_SESSION['cart'])) {
        echo json_encode(['success' => false, 'message' => 'El carrito está vacío.']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $sql_user_id = "SELECT id FROM login_menu WHERE username = ?";
        $stmt_user_id = $pdo->prepare($sql_user_id);
        $stmt_user_id->execute([$_SESSION['username']]);
        $user_id = $stmt_user_id->fetchColumn();

        $total_venta = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_venta += $item['price'] * $item['quantity'];
        }

        $sql_pedido = "INSERT INTO pedidos (fecha, total, user_id, estado_pago) VALUES (NOW(), ?, ?, 'pendiente_pago')";
        $stmt_pedido = $pdo->prepare($sql_pedido);
        $stmt_pedido->execute([$total_venta, $user_id]);
        $pedido_id = $pdo->lastInsertId('pedidos_id_seq');

        $sql_detalle = "INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
        $stmt_detalle = $pdo->prepare($sql_detalle);

        foreach ($_SESSION['cart'] as $product_id => $item) {
            $stmt_detalle->execute([$pedido_id, $product_id, $item['quantity'], $item['price']]);
        }
        
        $pdo->commit();
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true, 'pedido_id' => $pedido_id, 'total' => $total_venta]);
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al crear el pedido: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .product-card img {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>

<header class="bg-success text-white text-center p-3">
    <h1 class="mb-0">Tienda de Ventas</h1>
</header>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Catálogo</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="?category=dama">Ropa de Dama</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="?category=caballero">Ropa de Caballero</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="?category=salud">Implementos de Vida Saludable</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#cartModal">
                        🛒 Carrito <span class="badge bg-danger ms-1 cart-count"><?php echo count($_SESSION['cart']); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Cerrar Sesión</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if (isset($message)): ?>
        <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>" role="alert">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <h2 class="mb-4 text-center">Catálogo de Productos</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php
        $category = isset($_GET['category']) ? $_GET['category'] : 'dama';
        $sql = "SELECT id, nombre, precio, imagen_url, stock FROM productos WHERE categoria = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$category]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($products) {
            foreach ($products as $product) {
                echo '<div class="col">';
                echo '    <div class="card h-100 shadow-sm product-card">';
                echo '        <img src="' . htmlspecialchars($product['imagen_url']) . '" class="card-img-top" alt="' . htmlspecialchars($product['nombre']) . '">';
                echo '        <div class="card-body">';
                echo '            <h5 class="card-title">' . htmlspecialchars($product['nombre']) . '</h5>';
                echo '            <p class="card-text">Precio: $' . htmlspecialchars(number_format($product['precio'], 2)) . '</p>';
                echo '            <p class="card-text text-muted">Stock: ' . htmlspecialchars($product['stock']) . '</p>';
                echo '            <form method="POST" action="dashboard_sales.php">';
                echo '                <input type="hidden" name="product_id" value="' . htmlspecialchars($product['id']) . '">';
                echo '                <button type="submit" name="add_to_cart" class="btn btn-primary w-100">Agregar al carrito</button>';
                echo '            </form>';
                echo '        </div>';
                echo '    </div>';
                echo '</div>';
            }
        } else {
            echo "<p class='text-center'>No hay productos en esta categoría.</p>";
        }
        ?>
    </div>
</div>

<div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cartModalLabel">Tu Carrito de Compras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items-table">
                        <?php
                        $total_price = 0;
                        foreach ($_SESSION['cart'] as $product_id => $item) {
                            $item_total = $item['price'] * $item['quantity'];
                            $total_price += $item_total;
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                            echo "<td>$" . htmlspecialchars(number_format($item['price'], 2)) . "</td>";
                            echo "<td>$" . htmlspecialchars(number_format($item_total, 2)) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <h3 class="text-end mt-3">Total: $<span id="total-amount"><?php echo number_format($total_price, 2); ?></span></h3>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="processOrderBtn">Finalizar Venta</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Finalizar Venta y Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4 class="text-center">Total a Pagar: $<span id="modal-total-amount">0.00</span></h4>
                <p class="text-center">Para finalizar tu compra, realiza el pago escaneando el siguiente código QR.</p>
                
                <div class="text-center">
                    <img src="images/qr_pago.png" alt="Código QR de Pago" style="width: 200px; height: 200px;">
                </div>

                <hr>

                <form id="upload-form" action="upload_comprobante.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="comprobante" class="form-label">Subir Comprobante de Pago:</label>
                        <input type="file" class="form-control" id="comprobante" name="comprobante" required>
                    </div>
                    <input type="hidden" id="pedido-id" name="pedido_id">
                    <button type="submit" class="btn btn-primary w-100">Subir Comprobante y Finalizar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Escucha el clic en el botón "Finalizar Venta" en el modal del carrito
    $('#processOrderBtn').on('click', function(e) {
        e.preventDefault();

        const totalAmount = parseFloat($('#total-amount').text());
        if (totalAmount > 0) {
            // Lógica para crear el pedido a través de AJAX
            $.ajax({
                type: 'POST',
                url: 'dashboard_sales.php',
                data: { action: 'create_order' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#modal-total-amount').text(response.total.toFixed(2));
                        $('#pedido-id').val(response.pedido_id);
                        
                        // Cierra el modal del carrito y abre el modal de pago
                        const cartModal = bootstrap.Modal.getInstance(document.getElementById('cartModal'));
                        cartModal.hide();
                        
                        const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
                        paymentModal.show();

                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Error al procesar el pedido. Por favor, inténtelo de nuevo.');
                }
            });
        } else {
            alert('El carrito de compras está vacío.');
        }
    });

    // Actualiza el contador del carrito y el contenido del modal del carrito
    // al abrirlo, en caso de que un producto haya sido agregado o quitado de la sesión
    // Nota: El carrito en esta versión se maneja por session en PHP
    // para una interacción más fluida, se podría usar AJAX también para agregar/quitar items
    $('#cartModal').on('show.bs.modal', function() {
        $.ajax({
            url: 'dashboard_sales.php',
            type: 'GET',
            dataType: 'html',
            success: function(data) {
                const newContent = $(data).find('#cart-items-table').html();
                const newTotal = $(data).find('#total-amount').text();
                const newCount = $(data).find('.cart-count').text();
                $('#cart-items-table').html(newContent);
                $('#total-amount').text(newTotal);
                $('.cart-count').text(newCount);
            }
        });
    });
});
</script>

</body>
</html>