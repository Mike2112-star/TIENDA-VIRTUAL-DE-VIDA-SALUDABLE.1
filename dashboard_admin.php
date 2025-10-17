<?php
// Incluye el archivo de conexión a la base de datos
require 'db_connect.php';

// Inicia la sesión
session_start();

// Verifica si el usuario ha iniciado sesión y es un administrador
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.html");
    exit();
}

// Determina la sección actual del panel. El valor por defecto es 'users'.
$section = $_GET['section'] ?? 'users';
$message = '';
$message_type = '';

// Define una lista blanca de secciones permitidas
// MODIFICACIÓN 1: Se incluyen las secciones de auditoría
$allowed_sections = ['users', 'products', 'reports', 'returns', 'payments', 'auditores', 'sucursales', 'visitas', 'auditoria_reports'];

// Verifica si la sección solicitada está en la lista blanca. Si no, se usa 'users' por defecto.
if (!in_array($section, $allowed_sections)) {
    $section = 'users';
}

try {
    // Lógica para cargar los datos de las secciones
    // Se cargan todos los datos aquí para que estén disponibles en los archivos incluidos
    switch ($section) {
        case 'users':
            $sql = "SELECT id, username, user_type FROM login_menu";
            $stmt = $pdo->query($sql);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'products':
            $sql = "SELECT * FROM productos";
            $stmt = $pdo->query($sql);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'reports':
            // Lógica para informes de ventas (Tu código original)
            $report_data = [];
            $most_sold_products = [];
            $report_type = $_GET['report_type'] ?? 'daily';
            $report_date = $_GET['report_date'] ?? date('Y-m-d');
            $report_year = $_GET['report_year'] ?? date('Y');

            // Consulta para ingresos totales
            $sql_report = "";
            switch ($report_type) {
                case 'daily':
                    $sql_report = "SELECT DATE(fecha) AS fecha, SUM(total) AS total FROM pedidos WHERE DATE(fecha) = ? GROUP BY fecha ORDER BY fecha";
                    $stmt_report = $pdo->prepare($sql_report);
                    $stmt_report->execute([$report_date]);
                    break;
                case 'monthly':
                    $sql_report = "SELECT TO_CHAR(fecha, 'YYYY-MM') AS fecha, SUM(total) AS total FROM pedidos WHERE EXTRACT(YEAR FROM fecha) = ? GROUP BY TO_CHAR(fecha, 'YYYY-MM') ORDER BY TO_CHAR(fecha, 'YYYY-MM')";
                    $stmt_report = $pdo->prepare($sql_report);
                    $stmt_report->execute([$report_year]);
                    break;
                case 'yearly':
                    $sql_report = "SELECT EXTRACT(YEAR FROM fecha) AS fecha, SUM(total) AS total FROM pedidos GROUP BY EXTRACT(YEAR FROM fecha) ORDER BY fecha";
                    $stmt_report = $pdo->query($sql_report);
                    break;
            }
            $report_data = $stmt_report->fetchAll(PDO::FETCH_ASSOC);

            // Consulta para productos más vendidos
            $sql_most_sold = "SELECT p.nombre, SUM(dp.cantidad) AS total_vendido
                                FROM detalles_pedido dp
                                JOIN productos p ON dp.producto_id = p.id
                                GROUP BY p.nombre
                                ORDER BY total_vendido DESC
                                LIMIT 5";
            $stmt_most_sold = $pdo->query($sql_most_sold);
            $most_sold_products = $stmt_most_sold->fetchAll(PDO::FETCH_ASSOC);

            break;

        case 'payments':
            $sql = "SELECT id, fecha, total, comprobante_pago_url, estado_pago FROM pedidos WHERE estado_pago IN ('pagado', 'confirmado_admin') ORDER BY fecha DESC";
            $stmt = $pdo->query($sql);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'returns':
            // Tu código original no carga datos aquí, el archivo admin_returns.php maneja el registro.
            break;
            
        // --- MODIFICACIÓN 2: Lógica para cargar datos de Auditoría ---
        case 'auditores':
            $sql = "SELECT * FROM auditor ORDER BY id_auditor DESC";
            $stmt = $pdo->query($sql);
            $auditores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'sucursales':
            $sql = "SELECT * FROM sucursales ORDER BY id_sucursal DESC";
            $stmt = $pdo->query($sql);
            $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'visitas':
            // Consulta compleja que une las 3 tablas para la lista principal de visitas
            $sql = "SELECT 
                        v.id_visita, v.fecha_visita, v.hallazgos,
                        a.id_auditor, a.nombres || ' ' || a.apellidos AS nombre_auditor,
                        s.id_sucursal, s.direccion AS direccion_sucursal
                    FROM visitas v
                    JOIN auditor a ON v.auditor_id = a.id_auditor
                    JOIN sucursales s ON v.sucursal_id = s.id_sucursal
                    ORDER BY v.fecha_visita DESC";
            $stmt = $pdo->query($sql);
            $visitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Carga de listas de FK para los SELECT del formulario de Visitas (Si el archivo admin_visitas.php lo requiere)
            $auditores_list = $pdo->query("SELECT id_auditor, nombres, apellidos FROM auditor ORDER BY nombres")->fetchAll(PDO::FETCH_ASSOC);
            $sucursales_list = $pdo->query("SELECT id_sucursal, direccion FROM sucursales ORDER BY direccion")->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'auditoria_reports':
            // No carga datos aquí, el archivo admin_auditoria_reports.php se conecta directamente.
            break;
        // --- FIN MODIFICACIÓN 2 ---

        default:
            break;
    }
} catch (PDOException $e) {
    // Si hay un error de conexión o consulta
    $message = "Error en la base de datos: " . $e->getMessage();
    $message_type = 'danger';
}

// Lógica para incluir el archivo de contenido de la sección
$content_file = "admin_{$section}.php";
// Si el archivo de la sección no existe, por seguridad se redirige a 'users'
if (!file_exists($content_file)) {
    // Para las secciones de auditoría, se necesita un archivo específico:
    if ($section === 'auditoria_reports') {
        $content_file = "admin_auditoria_reports.php"; // El archivo de reportes que crearemos
    } else {
        $content_file = "admin_users.php";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: -15rem;
            transition: margin .25s ease-out;
        }
        #sidebar-wrapper .sidebar-heading {
            padding: 1.5rem 1.25rem;
            font-size: 1.2rem;
        }
        #sidebar-wrapper .list-group {
            width: 15rem;
        }
        #page-content-wrapper {
            min-width: 100vw;
        }
        .sidebar-heading { background-color: #388e3c; color: white; }
        .list-group-item.active { background-color: #2e7d32; border-color: #2e7d32; }
        .list-group-item:hover { background-color: #f0f0f0; }
        @media (min-width: 768px) {
            #sidebar-wrapper {
                margin-left: 0;
            }
            #page-content-wrapper {
                min-width: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <div class="border-end bg-white" id="sidebar-wrapper">
        <div class="sidebar-heading border-bottom">Admin Panel: **<?php echo htmlspecialchars($_SESSION['username']); ?>**</div>
        <div class="list-group list-group-flush">
            <a href="dashboard_admin.php?section=users" class="list-group-item list-group-item-action p-3 <?php echo ($section === 'users') ? 'active' : ''; ?>">Gestión de Usuarios</a>
            <a href="dashboard_admin.php?section=products" class="list-group-item list-group-item-action p-3 <?php echo ($section === 'products') ? 'active' : ''; ?>">Gestión de Productos</a>
            <a href="dashboard_admin.php?section=payments" class="list-group-item list-group-item-action p-3 <?php echo ($section === 'payments') ? 'active' : ''; ?>">Gestión de Pagos</a>
            <a href="dashboard_admin.php?section=returns" class="list-group-item list-group-item-action p-3 <?php echo ($section === 'returns') ? 'active' : ''; ?>">Devoluciones</a>
            <a href="dashboard_admin.php?section=reports" class="list-group-item list-group-item-action p-3 <?php echo ($section === 'reports') ? 'active' : ''; ?>">Informes de Ventas</a>
            
            <div class="list-group-item list-group-item-action p-3 bg-light text-primary fw-bold">MÓDULO DE AUDITORÍA</div>
            <a href="dashboard_admin.php?section=auditores" class="list-group-item list-group-item-action p-3 <?php echo ($section === 'auditores') ? 'active' : ''; ?>">Gestión de Auditores</a>
            <a href="dashboard_admin.php?section=sucursales" class="list-group-item list-group-item-action p-3 <?php echo ($section === 'sucursales') ? 'active' : ''; ?>">Gestión de Sucursales</a>
            <a href="dashboard_admin.php?section=visitas" class="list-group-item list-group-item-action p-3 <?php echo ($section === 'visitas') ? 'active' : ''; ?>">Registro de Visitas</a>
            <a href="dashboard_admin.php?section=auditoria_reports" class="list-group-item list-group-item-action p-3 <?php echo ($section === 'auditoria_reports') ? 'active' : ''; ?>">Reportes de Auditoría</a>
            <a href="logout.php" class="list-group-item list-group-item-action p-3 text-danger mt-3">Cerrar Sesión</a>
        </div>
    </div>
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
            <div class="container-fluid">
                <button class="btn btn-primary" id="sidebarToggle">☰</button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                        <li class="nav-item active">
                            <a class="nav-link" href="#">Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php
            // Incluir el contenido específico de la sección
            if (file_exists($content_file)) {
                require $content_file;
            } else {
                echo '<div class="alert alert-danger">❌ Error: No se encontró el módulo de contenido para: ' . htmlspecialchars($section) . '</div>';
            }
            ?>
        </div>
    </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Script para alternar el sidebar en pantallas pequeñas
    document.getElementById('sidebarToggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('wrapper').classList.toggle('toggled');
    });

    // Lógica de gráficos (Solo se ejecuta si estás en la sección 'reports')
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        const reportData = <?php echo json_encode($report_data); ?>;
        const labels = reportData.map(item => item.fecha);
        const totals = reportData.map(item => item.total);

        new Chart(revenueCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ingresos Totales ($)',
                    data: totals,
                    borderColor: 'rgba(56, 142, 60, 1)',
                    backgroundColor: 'rgba(56, 142, 60, 0.2)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                scales: { y: { beginAtZero: true } },
                responsive: true,
                plugins: { legend: { display: false } }
            }
        });
    }

    const topProductsCtx = document.getElementById('topProductsChart');
    if (topProductsCtx) {
        const topProductsData = <?php echo json_encode($most_sold_products); ?>;
        const productLabels = topProductsData.map(item => item.nombre);
        const productSales = topProductsData.map(item => item.total_vendido);

        new Chart(topProductsCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: productLabels,
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: productSales,
                    backgroundColor: 'rgba(56, 142, 60, 0.8)',
                    borderColor: 'rgba(56, 142, 60, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Unidades Vendidas' } },
                    x: { title: { display: true, text: 'Productos' } }
                },
                responsive: true,
                plugins: { legend: { display: false } }
            }
        });
    }
</script>
</body>
</html>