<?php
// Este archivo se incluye en dashboard_admin.php, por lo que la conexión a la DB y la sesión ya están iniciadas.
require_once 'db_connect.php';

// --- Lógica para la sección de INFORMES DE VENTAS ---
$report_data = [];
$most_sold_products = [];
$report_type = $_GET['report_type'] ?? 'daily';
$report_date = $_GET['report_date'] ?? date('Y-m-d');
$report_year = $_GET['report_year'] ?? date('Y');

// Construir la consulta SQL para el informe de ventas por fecha
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
        $sql_report = "SELECT EXTRACT(YEAR FROM fecha) AS fecha, SUM(total) AS total FROM pedidos WHERE EXTRACT(YEAR FROM fecha) = ? GROUP BY EXTRACT(YEAR FROM fecha) ORDER BY EXTRACT(YEAR FROM fecha)";
        $stmt_report = $pdo->prepare($sql_report);
        $stmt_report->execute([$report_year]);
        break;
}

$report_data = $stmt_report->fetchAll(PDO::FETCH_ASSOC);

// Lógica para encontrar los productos más vendidos
$sql_most_sold = "SELECT p.nombre, SUM(dp.cantidad) AS total_vendido FROM detalles_pedido dp JOIN productos p ON dp.producto_id = p.id JOIN pedidos ped ON dp.pedido_id = ped.id WHERE EXTRACT(YEAR FROM ped.fecha) = ? GROUP BY p.nombre ORDER BY total_vendido DESC LIMIT 5";
$stmt_most_sold = $pdo->prepare($sql_most_sold);
$stmt_most_sold->execute([$report_year]);
$most_sold_products = $stmt_most_sold->fetchAll(PDO::FETCH_ASSOC);

?>

<h2 class="mb-4">Informes de Ventas</h2>

<div class="card p-4 shadow-sm">
    <h4 class="mb-3">Filtrar Ventas</h4>
    <form action="dashboard_admin.php" method="GET" class="mb-4">
        <input type="hidden" name="section" value="reports">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="report_type" class="form-label">Filtrar por:</label>
                <select name="report_type" id="report_type" class="form-select">
                    <option value="daily" <?php echo ($report_type == 'daily') ? 'selected' : ''; ?>>Día</option>
                    <option value="monthly" <?php echo ($report_type == 'monthly') ? 'selected' : ''; ?>>Mes</option>
                    <option value="yearly" <?php echo ($report_type == 'yearly') ? 'selected' : ''; ?>>Año</option>
                </select>
            </div>
            <div class="col-md-3" id="date-picker">
                <label for="report_date" class="form-label">Seleccionar fecha:</label>
                <input type="date" name="report_date" id="report_date" class="form-control" value="<?php echo htmlspecialchars($report_date); ?>">
            </div>
            <div class="col-md-3" id="year-picker" style="display:none;">
                <label for="report_year" class="form-label">Seleccionar año:</label>
                <input type="number" name="report_year" id="report_year" class="form-control" value="<?php echo htmlspecialchars($report_year); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Generar Informe</button>
            </div>
        </div>
    </form>

    <h4 class="mt-4">Gráfico de Ventas</h4>
    <canvas id="salesChart"></canvas>

    <h4 class="mt-5">Top 5 Productos Más Vendidos (Año <?php echo htmlspecialchars($report_year); ?>)</h4>
    <canvas id="topProductsChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Lógica para mostrar/ocultar los selectores de fecha/año
    document.addEventListener('DOMContentLoaded', function() {
        const reportType = document.getElementById('report_type');
        const datePicker = document.getElementById('date-picker');
        const yearPicker = document.getElementById('year-picker');

        function toggleDatePicker() {
            if (reportType.value === 'daily') {
                datePicker.style.display = 'block';
                yearPicker.style.display = 'none';
            } else {
                datePicker.style.display = 'none';
                yearPicker.style.display = 'block';
            }
        }

        reportType.addEventListener('change', toggleDatePicker);
        toggleDatePicker();
    });

    // Datos para el gráfico de ventas
    const reportData = <?php echo json_encode($report_data); ?>;
    const labels = reportData.map(item => item.fecha);
    const data = reportData.map(item => item.total);

    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ventas Totales ($)',
                data: data,
                backgroundColor: 'rgba(76, 175, 80, 0.2)',
                borderColor: 'rgba(76, 175, 80, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Ventas Totales ($)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Periodo'
                    }
                }
            },
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Datos para el gráfico de productos más vendidos
    const topProductsData = <?php echo json_encode($most_sold_products); ?>;
    const productLabels = topProductsData.map(item => item.nombre);
    const productSales = topProductsData.map(item => item.total_vendido);

    const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
    const topProductsChart = new Chart(topProductsCtx, {
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
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Unidades Vendidas'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Productos'
                    }
                }
            },
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>