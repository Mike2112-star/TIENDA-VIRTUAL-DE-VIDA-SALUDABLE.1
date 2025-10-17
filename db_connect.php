<?php
// =========================================================================
// ARCHIVO: db_connect.php (CONEXIÓN PHP a COCKROACHDB CLOUD)
// =========================================================================

// Configuración de la conexión a CockroachDB
$host = 'richer-teddy-9558.jxf.gcp-us-east1.cockroachlabs.cloud'; // Tu host de CockroachDB Cloud
$port = 26257; 
$dbname = 'db_lixom'; // La base de datos que migramos
$user = 'lixom-audit'; // El usuario que creaste
$password = 'wBb7X8mJhGh2PpBz0L69Mg'; 

// ******************************************************************************
// PASO CLAVE: Agregar el identificador del clúster Serverless para evitar el error [7]
// ******************************************************************************
$cluster_name = 'richer-teddy-9558'; // <-- ASEGÚRATE DE QUE ESTE ES EL NOMBRE DE TU CLÚSTER

// RUTA ABSOLUTA al certificado ca.crt
// ¡IMPORTANTE! Debe ser la ruta COMPLETA y usar barras diagonales (/)
// Ejemplo para Windows: C:/Users/TuUsuario/AppData/Local/cockroach/certs/ca.crt
$ssl_cert_path = 'C:/cockroach-certs/ca.crt'; // Cambia esto según tu sistema

// Cadena de conexión DSN (Data Source Name) - LÍNEA CORREGIDA
// Se añade el parámetro 'options=--cluster=' para resolver el error: missing cluster identifier
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password;sslmode=verify-full;sslrootcert=$ssl_cert_path;options=--cluster=$cluster_name";

try {
    // 1. Crear una nueva instancia de PDO
    $pdo = new PDO($dsn);

    // 2. Establecer el modo de error de PDO a excepción
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Opcional: Mostrar un mensaje de éxito (para pruebas)
    // echo "¡Conexión a CockroachDB exitosa!"; 

} catch (PDOException $e) {
    // Si la conexión falla, se captura el error
    // El mensaje de error original aparece aquí.
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>