<div class="container is-fluid mb-6">
    <h1 class="title">Dashboard de Reportes</h1>
    <h2 class="subtitle">Resumen de Ventas</h2>
</div>

<div class="container pb-6 pt-6">
    <?php
        require_once "./php/main.php";
        $conexion = conexion();
        
        // Obtener resumen de ventas del día
        $fecha_actual = date('Y-m-d');
        $ventas_dia = $conexion->prepare("
            SELECT COUNT(*) as total_ventas, 
                   SUM(venta_total) as total_ingresos,
                   AVG(venta_total) as promedio_venta
            FROM venta 
            WHERE DATE(venta_fecha) = ?
        ");
        $ventas_dia->execute([$fecha_actual]);
        $resumen_dia = $ventas_dia->fetch();

        // Obtener productos más vendidos
        $productos_vendidos = $conexion->prepare("
            SELECT p.producto_nombre, 
                   SUM(vd.detalle_cantidad) as cantidad_total,
                   SUM(vd.detalle_cantidad * vd.detalle_precio) as total_ventas
            FROM venta_detalle vd
            INNER JOIN producto p ON vd.producto_id = p.producto_id
            INNER JOIN venta v ON vd.venta_id = v.venta_id
            WHERE DATE(v.venta_fecha) = ?
            GROUP BY p.producto_id
            ORDER BY cantidad_total DESC
            LIMIT 5
        ");
        $productos_vendidos->execute([$fecha_actual]);
    ?>

    <!-- Resumen del Día -->
    <div class="columns is-multiline">
        <div class="column is-4">
            <div class="box has-text-centered">
                <p class="heading">Ventas Realizadas Hoy</p>
                <p class="title"><?php echo $resumen_dia['total_ventas']; ?></p>
            </div>
        </div>
        <div class="column is-4">
            <div class="box has-text-centered">
                <p class="heading">Total Ingresos Hoy</p>
                <p class="title">$<?php echo number_format($resumen_dia['total_ingresos'] ?? 0, 0, ',', '.'); ?></p>
            </div>
        </div>
        <div class="column is-4">
            <div class="box has-text-centered">
                <p class="heading">Promedio por Venta</p>
                <p class="title">$<?php echo number_format($resumen_dia['promedio_venta'] ?? 0, 0, ',', '.'); ?></p>
            </div>
        </div>
    </div>

    <!-- Productos Más Vendidos -->
    <div class="box mt-6">
        <h3 class="title is-4">Productos Más Vendidos Hoy</h3>
        <table class="table is-fullwidth">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad Vendida</th>
                    <th>Total Ventas</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach($productos_vendidos as $producto) {
                        echo "
                        <tr>
                            <td>{$producto['producto_nombre']}</td>
                            <td>{$producto['cantidad_total']}</td>
                            <td>$".number_format($producto['total_ventas'], 0, ',', '.')."</td>
                        </tr>
                        ";
                    }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Estado del Inventario -->
    <h2 class="subtitle mt-6">Estado del Inventario</h2>
    <?php
        // Productos con stock bajo
        $stock_bajo = $conexion->prepare("
            SELECT producto_nombre, producto_stock
            FROM producto
            WHERE producto_stock <= 5
            ORDER BY producto_stock ASC
            LIMIT 5
        ");
        $stock_bajo->execute();

        // Valor total del inventario
        $valor_inventario = $conexion->prepare("
            SELECT SUM(producto_stock * producto_precio) as valor_total
            FROM producto
        ");
        $valor_inventario->execute();
        $valor_total = $valor_inventario->fetch()['valor_total'];
    ?>

    <div class="columns">
        <div class="column is-6">
            <div class="box">
                <h3 class="title is-5">Productos con Stock Bajo</h3>
                <table class="table is-fullwidth">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Stock Actual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach($stock_bajo as $producto) {
                                echo "
                                <tr>
                                    <td>{$producto['producto_nombre']}</td>
                                    <td>{$producto['producto_stock']}</td>
                                </tr>
                                ";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="column is-6">
            <div class="box">
                <h3 class="title is-5">Valor Total del Inventario</h3>
                <p class="title is-2">$<?php echo number_format($valor_total, 0, ',', '.'); ?></p>
            </div>
        </div>
    </div>

    <?php $conexion = null; ?>
</div>