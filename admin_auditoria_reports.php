<?php
// Este archivo debe ser incluido en dashboard_admin.php.
// La conexión a la DB y la sesión ya están iniciadas.
?>

<h2 class="mb-4">Módulo de Reportes de Auditoría</h2>

<div class="card shadow-sm p-4">
    <div class="card-header bg-primary text-white mb-3">
        <h3 class="mb-0">Generación de Reportes en PDF</h3>
    </div>
    <div class="card-body">
        <p class="lead">Haga clic en el botón para generar el reporte de auditoría completo en formato PDF, el cual incluye:</p>
        
        <ul>
            <li>Número de auditores registrados.</li>
            <li>Total de visitas por auditor.</li>
            <li>Número de **sucursales únicas visitadas** por cada auditor.</li>
        </ul>

        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
            <a href="generate_auditoria_report.php" target="_blank" class="btn btn-danger btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-file-earmark-pdf-fill me-2" viewBox="0 0 16 16">
                    <path d="M5.523 12.424q.211-.178.43-.36l.757-.653v-.176l-.54-.482a2.3 2.3 0 0 1-.581-.78h-.006c.038-.052.091-.11.139-.175a1.88 1.88 0 0 1 .374-.462 2.32 2.32 0 0 1 .593-.306c.28-.087.568-.143.856-.143.235 0 .47.03.689.091.218.061.428.156.619.284.192.127.354.298.484.512a2.32 2.32 0 0 1 .331.642.723.723 0 0 1-.005.084.72.72 0 0 1-.1.218v.111l-.578.536a2.6 2.6 0 0 0-.583.784h-.006c.041.066.096.126.155.196.129.143.27.3.414.475.25.304.484.598.705.908v.23c-.156.124-.316.241-.476.354-.207.151-.43.284-.658.423-.228.139-.475.247-.724.341-.249.094-.51.157-.753.185-.244.028-.487.028-.711 0-.256-.037-.501-.102-.733-.19-.232-.087-.453-.2-.647-.35zM14 14V2a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM4 1C3.447 1 3 1.447 3 2v12c0 .553.447 1 1 1h8c.553 0 1-.447 1-1V2c0-.553-.447-1-1-1H4z"/>
                </svg>
                Generar Reporte Completo (PDF)
            </a>
        </div>
    </div>
</div>