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

    <!-- Incluir Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Contenedor para las gráficas -->
    <div class="columns is-multiline mt-6">
        <!-- Gráfica de Ventas por Producto - Ahora ocupa todo el ancho -->
        <div class="column is-12">
            <div class="box" style="height: 25hv;">
                <h3 class="title is-5">Distribución de Ventas por Producto</h3>
                <canvas id="productosChart"></canvas>
            </div>
        </div>
        
        <!-- Gráficas de Categorías -->
        <div class="column is-6">
            <div class="box" style="height: 20hv;">
                <h3 class="title is-5" >Distribución de Stock por Categoría</h3>
                <canvas id="categoriasChart"></canvas>
            </div>
        </div>
          <!-- Gráfica de Stock Crítico -->
        <div class="column is-6">
            <div class="box" style="height: 20hv;">
                <h3 class="title is-5">Nivel de Stock Crítico</h3>
                <canvas id="stockGauge"></canvas>
            </div>
        </div>
    </div>

  
  

    <!-- Obtener datos para las gráficas -->
    <?php
        // Productos vendidos - Reejecutar la consulta
        $productos_vendidos->execute([$fecha_actual]);
        $productos_data = $productos_vendidos->fetchAll();
        
        // Datos de categorías
        $datos_categorias = $conexion->prepare("
            SELECT c.categoria_nombre, 
                   COUNT(p.producto_id) as total_productos
            FROM categoria c
            LEFT JOIN producto p ON c.categoria_id = p.categoria_id
            GROUP BY c.categoria_id
        ");
        $datos_categorias->execute();
        $categorias_data = $datos_categorias->fetchAll();
        
        // Stock bajo - Mejorar la información
        $stock_bajo_count = $conexion->prepare("
            SELECT COUNT(*) as total_critico,
                   (SELECT COUNT(*) FROM producto) as total_productos
            FROM producto
            WHERE producto_stock <= 5
        ");
        $stock_bajo_count->execute();
        $stock_info = $stock_bajo_count->fetch();
    ?>

    <script>
        // Configuración de la gráfica de productos con mejoras
        const productosData = {
            labels: [<?php 
                echo implode(',', array_map(function($item) {
                    return '"' . $item['producto_nombre'] . '"';
                }, $productos_data));
            ?>],
            datasets: [{
                label: 'Cantidad Vendida',
                data: [<?php 
                    echo implode(',', array_map(function($item) {
                        return $item['cantidad_total'];
                    }, $productos_data));
                ?>],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
            }]
        };

        // Configuración de la gráfica de categorías
        const categoriasData = {
            labels: [<?php 
                echo implode(',', array_map(function($item) {
                    return '"' . $item['categoria_nombre'] . '"';
                }, $categorias_data));
            ?>],
            datasets: [{
                data: [<?php 
                    echo implode(',', array_map(function($item) {
                        return $item['total_productos'];
                    }, $categorias_data));
                ?>],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
            }]
        };

        // Crear las gráficas
        new Chart(document.getElementById('productosChart'), {
            type: 'bar',
            data: productosData,
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Top 5 Productos Más Vendidos'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad Vendida'
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('categoriasChart'), {
            type: 'pie',
            data: categoriasData,
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: {
                        display: true,
                        text: 'Productos por Categoría'
                    }
                }
            }
        });

        // Mejorar la gráfica de stock crítico
        new Chart(document.getElementById('stockGauge'), {
            type: 'doughnut',
            data: {
                labels: ['Stock Crítico', 'Stock Normal'],
                datasets: [{
                    data: [
                        <?php echo $stock_info['total_critico']; ?>,
                        <?php echo $stock_info['total_productos'] - $stock_info['total_critico']; ?>
                    ],
                    backgroundColor: ['#FF6384', '#4BC0C0'],
                    circumference: 180,
                    rotation: 270
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Estado del Stock'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let total = <?php echo $stock_info['total_productos']; ?>;
                                let percentage = ((value * 100) / total).toFixed(1);
                                return `${label}: ${value} productos (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
    </div>

   
</div>

    <?php
        // Obtener resumen de ventas del día
        $ventas_mensuales = $conexion->prepare("
            SELECT 
                DATE_FORMAT(venta_fecha, '%Y-%m') as mes,
                COUNT(*) as total_ventas,
                SUM(venta_total) as total_ingresos
            FROM venta
            WHERE venta_fecha >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(venta_fecha, '%Y-%m')
            ORDER BY mes ASC
        ");
        $ventas_mensuales->execute();
        $historico_ventas = $ventas_mensuales->fetchAll();
    ?>

    <!-- Histórico de Ventas -->
    <div class="box mt-6">
        <h3 class="title is-4">Histórico de Ventas Mensuales</h3>
        <canvas id="historicoVentas"></canvas>
    </div>

    <script>
        // Configuración de la gráfica histórica
        const historicoData = {
            labels: [<?php 
                echo implode(',', array_map(function($item) {
                    $fecha = DateTime::createFromFormat('Y-m', $item['mes']);
                    return '"' . $fecha->format('M Y') . '"';
                }, $historico_ventas));
            ?>],
            datasets: [{
                label: 'Total Ventas',
                data: [<?php 
                    echo implode(',', array_map(function($item) {
                        return $item['total_ventas'];
                    }, $historico_ventas));
                ?>],
                borderColor: '#36A2EB',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Total Ingresos',
                data: [<?php 
                    echo implode(',', array_map(function($item) {
                        return $item['total_ingresos'];
                    }, $historico_ventas));
                ?>],
                borderColor: '#FF6384',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y2'
            }]
        };

        new Chart(document.getElementById('historicoVentas'), {
            type: 'line',
            data: historicoData,
            options: {
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Tendencia de Ventas Últimos 6 Meses'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 1) {
                                    return 'Ingresos: $' + new Intl.NumberFormat('es-CL').format(context.raw);
                                }
                                return 'Ventas: ' + context.raw;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Número de Ventas'
                        }
                    },
                    y2: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Total Ingresos ($)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    </script>

    <?php $conexion = null; ?>
</div>