<?php
// Este archivo debe ser incluido en dashboard_admin.php.

// Se asume que $payments ya ha sido cargada por dashboard_admin.php
// con la siguiente consulta:
// "SELECT id, fecha, total, comprobante_pago_url, estado_pago FROM pedidos WHERE estado_pago IN ('pagado', 'confirmado_admin')"

// Lógica para mostrar mensajes de éxito o error
if (isset($_GET['success']) && $_GET['success'] === 'pago_confirmado') {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '✅ El pago ha sido confirmado con éxito.';
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '❌ ' . htmlspecialchars(urldecode($_GET['error']));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
?>

<h2 class="mb-4">Gestión de Pagos</h2>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Comprobante</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?php echo htmlspecialchars($payment['id']); ?></td>
                <td><?php echo htmlspecialchars($payment['fecha']); ?></td>
                <td>$<?php echo htmlspecialchars(number_format($payment['total'], 2)); ?></td>
                <td>
                    <?php if ($payment['comprobante_pago_url']): ?>
                        <a href="<?php echo htmlspecialchars($payment['comprobante_pago_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info">Ver</a>
                    <?php else: ?>
                        No disponible
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?php echo ($payment['estado_pago'] === 'pagado') ? 'bg-warning text-dark' : 'bg-success'; ?>">
                        <?php echo htmlspecialchars($payment['estado_pago']); ?>
                    </span>
                </td>
                <td>
                    <?php if ($payment['estado_pago'] === 'pagado'): ?>
                        <a href="confirm_payment.php?id=<?php echo htmlspecialchars($payment['id']); ?>" class="btn btn-sm btn-success me-2" onclick="return confirm('¿Estás seguro de que quieres confirmar este pago?');">Confirmar</a>
                    <?php endif; ?>
                    <?php if ($payment['estado_pago'] === 'confirmado_admin'): ?>
                        <a href="generate_invoice.php?pedido_id=<?php echo htmlspecialchars($payment['id']); ?>" target="_blank" class="btn btn-sm btn-primary">Generar Factura</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
