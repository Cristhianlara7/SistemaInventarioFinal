<div class="container is-fluid mb-6">
    <h1 class="title">Ventas</h1>
    <h2 class="subtitle">Factura de venta</h2>
</div>

<div class="container pb-6 pt-6">
    <?php
        require_once "./php/main.php";
        
        $venta_id = isset($_GET['venta_id']) ? limpiar_cadena($_GET['venta_id']) : 0;
        
        $conexion = conexion();
        
        // Obtener datos de la venta
        $venta = $conexion->prepare("
            SELECT v.*, u.usuario_nombre, u.usuario_apellido 
            FROM venta v 
            INNER JOIN usuario u ON v.usuario_id = u.usuario_id 
            WHERE v.venta_id = ?
        ");
        $venta->execute([$venta_id]);
        $venta = $venta->fetch();
        
        if($venta) {
    ?>
    <div class="box">
        <div class="columns">
            <div class="column">
                <p><strong>Código de Venta:</strong> <?php echo $venta['venta_codigo']; ?></p>
                <p><strong>Fecha:</strong> <?php echo $venta['venta_fecha']; ?></p>
            </div>
            <div class="column">
                <p><strong>Vendedor:</strong> <?php echo $venta['usuario_nombre'].' '.$venta['usuario_apellido']; ?></p>
            </div>
        </div>
        
        <table class="table is-fullwidth mt-4">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>IVA</th>
                    <th>Subtotal</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $subtotal_general = 0;
                    $iva_general = 0;
                    $total_general = 0;
                    
                    $detalles = $conexion->prepare("
                        SELECT vd.*, p.producto_nombre, p.producto_iva 
                        FROM venta_detalle vd 
                        INNER JOIN producto p ON vd.producto_id = p.producto_id 
                        WHERE vd.venta_id = ?
                    ");
                    $detalles->execute([$venta_id]);
                    
                    foreach($detalles as $detalle) {
                        $precio_con_iva = $detalle['detalle_precio'];
                        $cantidad = $detalle['detalle_cantidad'];
                        $porcentaje_iva = $detalle['producto_iva'];
                        
                        $total = $precio_con_iva * $cantidad;
                        $subtotal = $total / (1 + ($porcentaje_iva/100));
                        $iva = $total - $subtotal;
                        
                        $subtotal_general += $subtotal;
                        $iva_general += $iva;
                        $total_general += $total;
                        
                        echo "
                        <tr>
                            <td>{$detalle['producto_nombre']}</td>
                            <td>{$detalle['detalle_cantidad']}</td>
                            <td>\$".number_format($detalle['detalle_precio'], 0, ',', '.')."</td>
                            <td>{$detalle['producto_iva']}%</td>
                            <td>\$".number_format($subtotal, 0, ',', '.')."</td>
                            <td>\$".number_format($total, 0, ',', '.')."</td>
                        </tr>
                        ";
                    }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="has-text-right"><strong>Subtotal:</strong></td>
                    <td colspan="2"><strong>$<?php echo number_format($subtotal_general, 0, ',', '.'); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="has-text-right"><strong>IVA Total:</strong></td>
                    <td colspan="2"><strong>$<?php echo number_format($iva_general, 0, ',', '.'); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="has-text-right"><strong>Total con IVA:</strong></td>
                    <td colspan="2"><strong>$<?php echo number_format($total_general, 0, ',', '.'); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="field is-grouped is-grouped-right mt-4">
            <p class="control">
                <button class="button is-info" onclick="window.print()">
                    Imprimir
                </button>
            </p>
            <p class="control">
                <a href="index.php?vista=sale_new" class="button is-link">
                    Nueva Venta
                </a>
            </p>
        </div>
    </div>
    <?php
        } else {
            echo '<p class="has-text-centered">No se encontró la venta solicitada</p>';
        }
        
        $conexion = null;
    ?>
</div>