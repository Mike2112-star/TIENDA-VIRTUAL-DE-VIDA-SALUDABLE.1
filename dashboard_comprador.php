<?php
session_start();
// Si el usuario no es de tipo 'comprador', lo redirige al login
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'comprador') {
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos</title>
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

<header class="bg-primary text-white text-center p-3">
    <h1 class="mb-0">Catálogo de Productos</h1>
</header>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Menú</a>
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
                        🛒 Carrito <span class="badge bg-danger ms-1"><?php echo count($_SESSION['cart']); ?></span>
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
                echo '  <div class="card h-100 shadow-sm product-card">';
                echo '      <img src="' . htmlspecialchars($product['imagen_url']) . '" class="card-img-top" alt="' . htmlspecialchars($product['nombre']) . '">';
                echo '      <div class="card-body">';
                echo '          <h5 class="card-title">' . htmlspecialchars($product['nombre']) . '</h5>';
                echo '          <p class="card-text">Precio: $' . htmlspecialchars(number_format($product['precio'], 2)) . '</p>';
                echo '          <p class="card-text text-muted">Stock: ' . htmlspecialchars($product['stock']) . '</p>';
                echo '          <form method="POST" action="dashboard_comprador.php">';
                echo '              <input type="hidden" name="product_id" value="' . htmlspecialchars($product['id']) . '">';
                echo '              <button type="submit" name="add_to_cart" class="btn btn-primary w-100">Agregar al carrito</button>';
                echo '          </form>';
                echo '      </div>';
                echo '  </div>';
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
                    <tbody>
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
                <h3 class="text-end mt-3">Total: $<?php echo number_format($total_price, 2); ?></h3>
                <div class="d-flex justify-content-end mt-4">
                    <form action="process_checkout.php" method="POST">
                        <button type="submit" class="btn btn-success btn-lg">Finalizar Compra</button>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
