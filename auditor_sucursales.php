<h2 class="mb-4">Catálogo de Sucursales</h2>

<div class="card shadow-sm">
    <div class="card-header bg-auditoria">
        <h3 class="mb-0">Sucursales a Nivel Nacional</h3>
    </div>
    <div class="card-body">
        <p class="text-muted">Información de contacto y ubicación de todas las sucursales registradas.</p>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Encargado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // $sucursales se cargó en dashboard_auditoria.php
                    if (isset($sucursales) && !empty($sucursales)):
                        foreach ($sucursales as $sucursal): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sucursal['id_sucursal']); ?></td>
                        <td><?php echo htmlspecialchars($sucursal['direccion']); ?></td>
                        <td><?php echo htmlspecialchars($sucursal['telefono']); ?></td>
                        <td><?php echo htmlspecialchars($sucursal['nombre_encargado']); ?></td>
                    </tr>
                    <?php 
                        endforeach; 
                    else:
                    ?>
                    <tr>
                        <td colspan="4" class="text-center">No hay sucursales registradas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>