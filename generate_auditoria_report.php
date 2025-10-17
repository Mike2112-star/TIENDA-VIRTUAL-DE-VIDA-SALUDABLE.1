<?php
// Incluye la librería FPDF
require('fpdf186/fpdf.php');
// Incluye el archivo de conexión a la base de datos
require('db_connect.php');

// =========================================================================
// 1. CLASE PERSONALIZADA PARA EL PDF
// =========================================================================

class PDF extends FPDF
{
    // Cabecera de página
    function Header()
    {
        // Título del documento
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, utf8_decode('Reporte del Módulo de Auditoría'), 0, 1, 'C');
        
        // Subtítulo
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode('Fecha de Generación: ') . date('d/m/Y H:i:s'), 0, 1, 'C');
        $this->Ln(10); // Salto de línea
    }

    // Pie de página
    function Footer()
    {
        // Posición a 1.5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Títulos de sección
    function ChapterTitle($label)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(0, 8, utf8_decode($label), 0, 1, 'L', true);
        $this->Ln(4);
    }
}

// =========================================================================
// 2. CONSULTAS A LA BASE DE DATOS
// =========================================================================

try {
    // 1. Número de auditores registrados
    $sql_total_auditores = "SELECT COUNT(*) FROM auditor";
    $total_auditores = $pdo->query($sql_total_auditores)->fetchColumn();

    // 2. Total de visitas por auditor
    $sql_visitas_por_auditor = "
        SELECT 
            a.nombres || ' ' || a.apellidos AS nombre_auditor, 
            COUNT(v.id_visita) AS total_visitas
        FROM auditor a
        LEFT JOIN visitas v ON a.id_auditor = v.auditor_id
        GROUP BY 1
        ORDER BY total_visitas DESC
    ";
    $visitas_por_auditor = $pdo->query($sql_visitas_por_auditor)->fetchAll(PDO::FETCH_ASSOC);

    // 3. Número de sucursales únicas visitadas por cada auditor
    $sql_sucursales_por_auditor = "
        SELECT 
            a.nombres || ' ' || a.apellidos AS nombre_auditor, 
            COUNT(DISTINCT v.sucursal_id) AS total_sucursales_visitadas
        FROM auditor a
        LEFT JOIN visitas v ON a.id_auditor = v.auditor_id
        GROUP BY 1
        ORDER BY total_sucursales_visitadas DESC
    ";
    $sucursales_por_auditor = $pdo->query($sql_sucursales_por_auditor)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de base de datos al generar el reporte: " . $e->getMessage());
}


// =========================================================================
// 3. GENERACIÓN DEL PDF
// =========================================================================

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// --- SECCIÓN 1: Estadísticas Generales ---
$pdf->ChapterTitle('1. Estadísticas Generales');

// Total de Auditores
$pdf->SetFont('Arial', '', 12);
$pdf->SetFillColor(255, 255, 200);
$pdf->Cell(0, 8, utf8_decode('Número de Auditores Registrados: ') . $total_auditores, 1, 1, 'L', true);
$pdf->Ln(8);


// --- SECCIÓN 2: Total de Visitas por Auditor ---
$pdf->ChapterTitle('2. Total de Visitas Registradas por Auditor');

// Encabezados de tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(140, 7, utf8_decode('Auditor'), 1, 0, 'C', true);
$pdf->Cell(50, 7, utf8_decode('Total de Visitas'), 1, 1, 'C', true);

// Datos de la tabla
$pdf->SetFont('Arial', '', 10);
foreach ($visitas_por_auditor as $fila) {
    $pdf->Cell(140, 7, utf8_decode($fila['nombre_auditor']), 1, 0, 'L');
    $pdf->Cell(50, 7, $fila['total_visitas'], 1, 1, 'C');
}
$pdf->Ln(8);


// --- SECCIÓN 3: Sucursales Únicas Visitadas por Auditor ---
$pdf->ChapterTitle('3. Número de Sucursales Únicas Visitadas por Auditor');

// Encabezados de tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(140, 7, utf8_decode('Auditor'), 1, 0, 'C', true);
$pdf->Cell(50, 7, utf8_decode('Sucursales Únicas Visitadas'), 1, 1, 'C', true);

// Datos de la tabla
$pdf->SetFont('Arial', '', 10);
foreach ($sucursales_por_auditor as $fila) {
    $pdf->Cell(140, 7, utf8_decode($fila['nombre_auditor']), 1, 0, 'L');
    $pdf->Cell(50, 7, $fila['total_sucursales_visitadas'], 1, 1, 'C');
}
$pdf->Ln(10);


// Output (mostrar en el navegador)
$pdf->Output('I', 'Reporte_Auditoria_' . date('Ymd_His') . '.pdf');
?>