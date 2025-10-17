<?php
// Este archivo debe ser incluido en dashboard_admin.php.
require_once 'db_connect.php';

// Variables para almacenar datos del producto a editar
$edit_mode = false;
$product_to_edit = null;
$message = '';

// Lógica para AGREGAR o EDITAR/ACTUALIZAR un producto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lógica para ACTUALIZAR un producto existente
    if (isset($_POST['update_product'])) {
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        $categoria = $_POST['categoria'];

        $sql = "UPDATE productos SET nombre = ?, precio = ?, stock = ?, categoria = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $precio, $stock, $categoria, $id]);
        $message = "✅ Producto actualizado con éxito.";
    }
    // Lógica para AGREGAR un nuevo producto
    else if (isset($_POST['add_product'])) {
        $nombre = $_POST['nombre'];
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        $categoria = $_POST['categoria'];
        
        // Manejo de la imagen
        $imagen_url = 'images/' . basename($_FILES['imagen']['name']);
        move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen_url);

        $sql = "INSERT INTO productos (nombre, precio, stock, imagen_url, categoria) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $precio, $stock, $imagen_url, $categoria]);
        $message = "✅ Producto agregado con éxito.";
    }
    // Lógica para ELIMINAR un producto
    else if (isset($_POST['delete_product'])) {
        $id = $_POST['id'];
        $sql = "DELETE FROM productos WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $message = "✅ Producto eliminado con éxito.";
    }
}

// Lógica para CARGAR los datos del producto a editar
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = $_GET['edit_id'];
    $sql = "SELECT * FROM productos WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $product_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lógica para MOSTRAR la lista de productos
$sql = "SELECT * FROM productos ORDER BY id DESC";
$stmt = $pdo->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="mb-4">Gestión de Productos</h2>

<?php if ($message): ?>
    <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>" role="alert">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0"><?php echo $edit_mode ? 'Editar Producto' : 'Agregar Nuevo Producto'; ?></h3>
    </div>
    <div class="card-body">
        <form action="dashboard_admin.php?section=products<?php echo $edit_mode ? '&edit_id=' . htmlspecialchars($product_to_edit['id']) : ''; ?>" method="POST" enctype="multipart/form-data">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($product_to_edit['id']); ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre:</label>
                <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo $edit_mode ? htmlspecialchars($product_to_edit['nombre']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="precio" class="form-label">Precio:</label>
                <input type="number" step="0.01" id="precio" name="precio" class="form-control" value="<?php echo $edit_mode ? htmlspecialchars($product_to_edit['precio']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Stock:</label>
                <input type="number" id="stock" name="stock" class="form-control" value="<?php echo $edit_mode ? htmlspecialchars($product_to_edit['stock']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="categoria" class="form-label">Categoría:</label>
                <select id="categoria" name="categoria" class="form-select" required>
                    <option value="dama" <?php echo $edit_mode && $product_to_edit['categoria'] === 'dama' ? 'selected' : ''; ?>>Ropa de Dama</option>
                    <option value="caballero" <?php echo $edit_mode && $product_to_edit['categoria'] === 'caballero' ? 'selected' : ''; ?>>Ropa de Caballero</option>
                    <option value="salud" <?php echo $edit_mode && $product_to_edit['categoria'] === 'salud' ? 'selected' : ''; ?>>Vida Saludable</option>
                </select>
            </div>

            <?php if (!$edit_mode): ?>
                <div class="mb-3">
                    <label for="imagen" class="form-label">Imagen:</label>
                    <input type="file" id="imagen" name="imagen" class="form-control" required>
                </div>
            <?php endif; ?>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="<?php echo $edit_mode ? 'update_product' : 'add_product'; ?>" class="btn btn-primary">
                    <?php echo $edit_mode ? 'Actualizar Producto' : 'Guardar Producto'; ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="dashboard_admin.php?section=products" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<h3 class="mt-5 mb-3">Lista de Productos</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Categoría</th>
                <th>Imagen</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $producto): ?>
            <tr>
                <td><?php echo htmlspecialchars($producto['id']); ?></td>
                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                <td>$<?php echo htmlspecialchars(number_format($producto['precio'], 2)); ?></td>
                <td><?php echo htmlspecialchars($producto['stock']); ?></td>
                <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                <td><img src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" width="50" alt="Producto"></td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="dashboard_admin.php?section=products&edit_id=<?php echo htmlspecialchars($producto['id']); ?>" class="btn btn-sm btn-info text-white">Editar</a>
                        <form action="dashboard_admin.php?section=products" method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($producto['id']); ?>">
                            <button type="submit" name="delete_product" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?');">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>