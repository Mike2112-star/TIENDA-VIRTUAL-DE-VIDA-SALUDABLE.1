<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'comprador' || !isset($_GET['pedido_id'])) {
    header("Location: dashboard_comprador.php");
    exit();
}

$pedido_id = $_GET['pedido_id'];
$total = $_GET['total'] ?? 0;

// URL de la API de Google Charts para generar el QR
$payment_info = "Pedido ID: {$pedido_id}, Total: {$total}";
$qr_code_url = "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=" . urlencode($payment_info);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pago</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h2 class="text-center mb-0">¡Gracias por tu compra!</h2>
            </div>
            <div class="card-body text-center">
                <p class="lead">Tu pedido **#<?php echo htmlspecialchars($pedido_id); ?>** ha sido creado con éxito.</p>
                <p class="h4">Total a pagar: $<?php echo number_format($total, 2); ?></p>

                <div class="alert alert-info mt-4" role="alert">
                    <p>Escanea este código QR para realizar el pago y sube el comprobante a continuación.</p>
                    <img src="<?php echo htmlspecialchars($qr_code_url); ?>" alt="Código QR de Pago" class="img-fluid my-3">
                </div>

                <div class="mt-4">
                    <h3>Subir Comprobante de Pago</h3>
                    <form action="upload_payment.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="pedido_id" value="<?php echo htmlspecialchars($pedido_id); ?>">
                        <div class="mb-3">
                            <label for="comprobante" class="form-label">Archivo del Comprobante (PDF o Imagen):</label>
                            <input type="file" class="form-control" id="comprobante" name="comprobante" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Subir Comprobante</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
