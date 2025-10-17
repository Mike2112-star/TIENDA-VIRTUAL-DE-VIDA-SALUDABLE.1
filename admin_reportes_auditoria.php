<?php
// Este archivo debe ser incluido en dashboard_admin.php.
require_once 'db_connect.php';

// Variables para los datos del reporte
$auditores_count = 0;
$sucursales_count = 0;
$visitas_count = 0;
$message = '';
$message_type = '';

// Lógica para contar los datos necesarios
try {
    // 1. Número de auditores registrados
    $sql_auditors = "SELECT COUNT(id_auditor) FROM auditor";
    $auditores_count = $pdo->query($sql_auditors)->fetchColumn();

    // 2. Número de sucursales
    $sql_sucursales = "SELECT COUNT(id_sucursal) FROM sucursales";
    $sucursales_count = $pdo->query($sql_sucursales)->fetchColumn();
    
    // 3. Total de visitas
    $sql_visitas = "SELECT COUNT(id_visita) FROM visitas";
    $visitas_count = $pdo->query($sql_visitas)->fetchColumn();

} catch (PDOException $e) {
    $message = "❌ Error al cargar datos para el reporte: " . $e->getMessage();
    $message_type = 'danger';
}

// Lógica para generar PDF (Implementación detallada necesaria)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_pdf'])) {
    // Aquí iría la lógica completa de FPDF que no está incluida en este esqueleto.
    $message = "⚠️ La lógica de generación de PDF con FPDF no está implementada en este esqueleto.";
    $message_type = 'warning';
}

?>

<h2 class="mb-4">Generación de Reportes de Auditoría (PDF)</h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Resumen Rápido</h3>
            </div>
            <div class="card-body">
                <p>Número de Auditores Registrados: <span class="badge bg-success fs-5"><?php echo $auditores_count; ?></span></p>
                <p>Número de Sucursales Registradas: <span class="badge bg-success fs-5"><?php echo $sucursales_count; ?></span></p>
                <p>Total de Visitas Registradas: <span class="badge bg-success fs-5"><?php echo $visitas_count; ?></span></p>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-warning text-dark">
                <h3 class="mb-0">Generar Reporte Detallado</h3>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <p>Este reporte incluirá los datos solicitados:</p>
                    <ul>
                        <li>Total de auditores.</li>
                        <li>Total de sucursales por auditor.</li>
                        <li>Total de visitas por auditor.</li>
                    </ul>
                </div>
                <form action="dashboard_admin.php?section=reportes_auditoria" method="POST" class="mt-3">
                    <button type="submit" name="generate_pdf" class="btn btn-danger w-100">Generar PDF de Auditoría</button>
                </form>
            </div>
        </div>
    </div>
</div>