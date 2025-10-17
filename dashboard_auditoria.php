<?php
// Incluye el archivo de conexión a la base de datos
require 'db_connect.php';

// Inicia la sesión
session_start();

// Verifica si el usuario ha iniciado sesión y es de tipo 'auditoria'
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'auditoria') {
    // Si no es el usuario 'auditoria', se le redirige al login.
    header("Location: index.html");
    exit();
}

$username = $_SESSION['username'];
$message = '';
$message_type = '';

// Determina la sección actual del panel. El valor por defecto es 'visitas'.
$section = $_GET['section'] ?? 'visitas';

// Define una lista blanca de secciones permitidas para el auditor
$allowed_sections = ['visitas', 'sucursales'];

// Verifica si la sección solicitada está en la lista blanca.
if (!in_array($section, $allowed_sections)) {
    $section = 'visitas';
}

try {
    // Lógica para cargar los datos de las secciones
    switch ($section) {
        case 'visitas':
            // Carga la lista de visitas con los nombres del auditor y la sucursal
            $sql_visitas = "
                SELECT 
                    v.id_visita,
                    v.fecha_visita,
                    v.hallazgos,
                    a.nombres AS auditor_nombres,
                    a.apellidos AS auditor_apellidos,
                    s.direccion AS sucursal_direccion
                FROM 
                    visitas v
                JOIN 
                    auditor a ON v.auditor_id = a.id_auditor
                JOIN 
                    sucursales s ON v.sucursal_id = s.id_sucursal
                ORDER BY 
                    v.fecha_visita DESC
            ";
            $stmt_visitas = $pdo->query($sql_visitas);
            $visitas = $stmt_visitas->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'sucursales':
            // Carga la lista de sucursales
            $sql_sucursales = "SELECT * FROM sucursales ORDER BY direccion ASC";
            $stmt_sucursales = $pdo->query($sql_sucursales);
            $sucursales = $stmt_sucursales->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
} catch (PDOException $e) {
    // Manejo de errores de base de datos
    $message = "❌ Error al cargar datos: " . $e->getMessage();
    $message_type = 'danger';
}

// Manejo de mensajes pasados por la URL
if (isset($_GET['message']) && isset($_GET['message_type'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['message_type'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Auditoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bs-primary: #198754; /* Verde de Bootstrap, similar al ejemplo */
            --bs-sidebar-bg: #212529; /* Color oscuro para la barra lateral */
        }
        body { background-color: #f8f9fa; }
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--bs-sidebar-bg);
            color: white;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .content {
            margin-left: 250px;
            padding: 30px;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.7);
        }
        .nav-link:hover, .nav-link.active {
            color: #fff;
            background-color: var(--bs-primary);
        }
        .header-top {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 10px 30px;
        }
        .card-header.bg-auditoria {
            background-color: #0d6efd !important; /* Azul para diferenciar un poco */
            color: white;
        }
    </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
    <h4 class="text-center mb-4 text-warning">Módulo Auditoría</h4>
    <p class="text-center mb-4 small">Bienvenido, **<?php echo htmlspecialchars($username); ?>**</p>
    
    <ul class="nav flex-column mb-auto">
        <li class="nav-item">
            <a class="nav-link <?php echo ($section === 'visitas') ? 'active' : ''; ?>" href="dashboard_auditoria.php?section=visitas">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-journal-check me-2" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M10.854 6.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 8.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                    <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z"/>
                    <path d="M1 5v.5a.5.5 0 0 0 1 0V5h.5a.5.5 0 0 0 0-1h-2a.5.5 0 0 0 0 1H1zm0 3v.5a.5.5 0 0 0 1 0V8h.5a.5.5 0 0 0 0-1h-2a.5.5 0 0 0 0 1H1zm0 3v.5a.5.5 0 0 0 1 0v-.5h.5a.5.5 0 0 0 0-1h-2a.5.5 0 0 0 0 1H1z"/>
                </svg>
                Visitas Registradas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($section === 'sucursales') ? 'active' : ''; ?>" href="dashboard_auditoria.php?section=sucursales">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-building me-2" viewBox="0 0 16 16">
                    <path d="M4 2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zM2 4a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H2zm12 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h1zM5.5 6a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm2 0a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zM10.5 6a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zM2 7a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1A.5.5 0 0 1 2 8V7zM12 7a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1A.5.5 0 0 1 12 8V7z"/>
                </svg>
                Sucursales (Catálogo)
            </a>
        </li>
    </ul>

    <hr>
    
    <div class="px-3 pb-3">
        <a href="logout.php" class="btn btn-outline-danger w-100">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right me-2" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9zm-2-9a.5.5 0 0 0-1 0v7a.5.5 0 0 0 1 0v-7z"/>
                <path fill-rule="evenodd" d="M12.5 13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2a1.5 1.5 0 0 1 1.5-1.5h8A1.5 1.5 0 0 1 14 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 3 12.5v-2a.5.5 0 0 1 1 0v2a.5.5 0 0 0 .5.5h8z"/>
            </svg>
            Cerrar Sesión
        </a>
    </div>
</div>

<div class="content">

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <h1 class="mb-4 text-primary">Panel del Auditor</h1>
    <hr>

    <?php 
    // Lógica para incluir el contenido de la sección seleccionada
    switch ($section) {
        case 'visitas':
            include 'auditor_visitas.php'; // Incluiremos este archivo en el siguiente paso
            break;
        case 'sucursales':
            include 'auditor_sucursales.php'; // Incluiremos este archivo en el siguiente paso
            break;
        default:
            echo '<div class="alert alert-info">Seleccione una opción del menú lateral.</div>';
            break;
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>