<?php
require_once "./php/main.php";

// Obtener ID de la venta
$venta_id = isset($_GET['venta_id']) ? (int)$_GET['venta_id'] : 0;

if($venta_id <= 0) {
    echo '
    <div class="notification is-danger">
        ID de venta no válido
    </div>
    ';
    exit;
}

$conexion = conexion();

// Obtener datos de la factura
$stmt = $conexion->prepare("
    SELECT fe.*, v.venta_codigo, v.venta_fecha, v.venta_total
    FROM factura_electronica fe
    INNER JOIN venta v ON fe.venta_id = v.venta_id
    WHERE fe.venta_id = ?
");
$stmt->execute([$venta_id]);
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$factura) {
    echo '
    <div class="notification is-danger">
        Factura no encontrada
    </div>
    ';
    exit;
}

// Obtener detalles de la venta
$stmt = $conexion->prepare("
    SELECT vd.*, p.producto_nombre
    FROM venta_detalle vd
    INNER JOIN producto p ON vd.producto_id = p.producto_id
    WHERE vd.venta_id = ?
");
$stmt->execute([$venta_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Decodificar contenido XML
$xml_data = json_decode($factura['xml_contenido'], true);
?>

<div class="container is-fluid mb-6">
    <h1 class="title">Factura Electrónica</h1>
    <h2 class="subtitle">Número: <?php echo $factura['numero_factura']; ?></h2>
</div>

<div class="container pb-6 pt-6">
    <div class="box">
        <!-- Información del Emisor -->
        <div class="columns">
            <div class="column is-6">
                <h3 class="title is-5">Datos del Emisor</h3>
                <p><strong>Razón Social:</strong> <?php echo $xml_data['Emisor']['RazonSocial']; ?></p>
                <p><strong>NIT:</strong> <?php echo $xml_data['Emisor']['NIT']; ?></p>
                <p><strong>Dirección:</strong> <?php echo $xml_data['Emisor']['DireccionFiscal']; ?></p>
                <p><strong>Ciudad:</strong> <?php echo $xml_data['Emisor']['Ciudad']; ?></p>
            </div>
            <div class="column is-6">
                <h3 class="title is-5">Datos del Cliente</h3>
                <p><strong>Razón Social:</strong> <?php echo $xml_data['Cliente']['RazonSocial']; ?></p>
                <p><strong>Identificación:</strong> <?php echo $xml_data['Cliente']['NumeroIdentificacion']; ?></p>
                <p><strong>Dirección:</strong> <?php echo $xml_data['Cliente']['Direccion']; ?></p>
                <p><strong>Ciudad:</strong> <?php echo $xml_data['Cliente']['Ciudad']; ?></p>
            </div>
        </div>

        <!-- Información de la Factura -->
        <div class="columns mt-5">
            <div class="column is-12">
                <h3 class="title is-5">Detalles de la Factura</h3>
                <div class="table-container">
                    <table class="table is-fullwidth is-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>IVA</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal_total = 0;
                            $iva_total = 0;
                            $total_total = 0;
                            
                            foreach($detalles as $detalle): 
                                $precio_sin_iva = round($detalle['detalle_precio'] / 1.19, 2);
                                $cantidad = $detalle['detalle_cantidad'];
                                $subtotal = $precio_sin_iva * $cantidad;
                                $iva = $subtotal * 0.19;
                                $total = $subtotal + $iva;
                                
                                $subtotal_total += $subtotal;
                                $iva_total += $iva;
                                $total_total += $total;
                            ?>
                            <tr>
                                <td><?php echo $detalle['producto_nombre']; ?></td>
                                <td><?php echo $cantidad; ?></td>
                                <td>$<?php echo number_format($precio_sin_iva, 0, ',', '.'); ?></td>
                                <td>$<?php echo number_format($iva, 0, ',', '.'); ?></td>
                                <td>$<?php echo number_format($total, 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"></td>
                                <td><strong>Subtotal:</strong></td>
                                <td>$<?php echo number_format($subtotal_total, 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3"></td>
                                <td><strong>IVA (19%):</strong></td>
                                <td>$<?php echo number_format($iva_total, 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3"></td>
                                <td><strong>Total:</strong></td>
                                <td>$<?php echo number_format($total_total, 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="columns mt-5">
            <div class="column is-12">
                <div class="field">
                    <p><strong>CUFE:</strong> <?php echo $factura['cufe']; ?></p>
                    <p><strong>Fecha de Emisión:</strong> <?php echo date('d/m/Y H:i:s', strtotime($factura['fecha_generacion'])); ?></p>
                    <p><strong>Estado:</strong> <span class="tag is-success"><?php echo $factura['estado']; ?></span></p>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="field is-grouped mt-5">
            <p class="control">
                <button class="button is-info" onclick="window.print()">
                    <span class="icon">
                        <i class="fas fa-print"></i>
                    </span>
                    <span>Imprimir</span>
                </button>
            </p>
            <p class="control">
                <a href="index.php?vista=sale_new" class="button is-link">
                    Nueva Venta
                </a>
            </p>
        </div>
    </div>
</div>