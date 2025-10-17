<?php
require('fpdf186/fpdf.php');
require('db_connect.php');

if (isset($_GET['pedido_id'])) {
    $pedido_id = $_GET['pedido_id'];

    try {
        $sql = "
            SELECT 
                p.id AS pedido_id, p.fecha, p.total, p.estado_pago,
                c.nombre AS cliente_nombre, c.apellido AS cliente_apellido, c.email AS cliente_email
            FROM 
                pedidos p
            JOIN 
                clientes c ON p.cliente_id = c.id
            WHERE 
                p.id = :pedido_id
            AND 
                p.estado_pago = 'confirmado_admin'
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pedido_id' => $pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            die("Error: El pedido no existe o el pago no ha sido confirmado por el administrador.");
        }

        $sql_productos = "
            SELECT 
                dp.cantidad, dp.precio_unitario, pr.nombre AS producto_nombre
            FROM 
                detalles_pedido dp
            JOIN 
                productos pr ON dp.producto_id = pr.id
            WHERE 
                dp.pedido_id = :pedido_id
        ";
        $stmt_productos = $pdo->prepare($sql_productos);
        $stmt_productos->execute([':pedido_id' => $pedido_id]);
        $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(190, 10, utf8_decode('Factura de Venta'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(190, 5, utf8_decode('Fecha: ') . date('d/m/Y', strtotime($pedido['fecha'])), 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(190, 5, 'Datos del Cliente', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(190, 5, utf8_decode('Nombre: ') . utf8_decode($pedido['cliente_nombre']) . ' ' . utf8_decode($pedido['cliente_apellido']), 0, 1);
        $pdf->Cell(190, 5, utf8_decode('Email: ') . utf8_decode($pedido['cliente_email']), 0, 1);
        $pdf->Ln(10);
        
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(110, 10, 'Producto', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Cantidad', 1, 0, 'C', true);
        $pdf->Cell(50, 10, 'Precio Unitario', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 12);
        foreach ($productos as $producto) {
            $pdf->Cell(110, 10, utf8_decode($producto['producto_nombre']), 1, 0, 'L');
            $pdf->Cell(30, 10, $producto['cantidad'], 1, 0, 'C');
            $pdf->Cell(50, 10, '$' . number_format($producto['precio_unitario'], 2), 1, 1, 'C');
        }

        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(190, 10, 'Total: $' . number_format($pedido['total'], 2), 0, 1, 'R');

        $pdf->Output();

    } catch (PDOException $e) {
        die("Error en la base de datos: " . $e->getMessage());
    }
} else {
    die("ID de pedido no especificado.");
}
?>
