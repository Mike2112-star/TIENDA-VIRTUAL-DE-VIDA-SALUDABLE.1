<h2 class="mb-4">Visitas de Auditoría Registradas</h2>

<div class="card shadow-sm">
    <div class="card-header bg-auditoria">
        <h3 class="mb-0">Historial de Auditorías</h3>
    </div>
    <div class="card-body">
        <p class="text-muted">Lista de todas las visitas realizadas por cualquier auditor a las sucursales.</p>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Auditor</th>
                        <th>Sucursal</th>
                        <th>Fecha Visita</th>
                        <th>Hallazgos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // $visitas se cargó en dashboard_auditoria.php
                    if (isset($visitas) && !empty($visitas)):
                        foreach ($visitas as $visita): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($visita['id_visita']); ?></td>
                        <td><?php echo htmlspecialchars($visita['auditor_nombres'] . ' ' . $visita['auditor_apellidos']); ?></td>
                        <td><?php echo htmlspecialchars($visita['sucursal_direccion']); ?></td>
                        <td><?php echo htmlspecialchars(substr($visita['fecha_visita'], 0, 10)); ?></td>
                        <td><?php echo htmlspecialchars($visita['hallazgos']); ?></td>
                    </tr>
                    <?php 
                        endforeach; 
                    else:
                    ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay visitas registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>