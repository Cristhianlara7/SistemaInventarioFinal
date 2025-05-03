<?php
require_once "main.php";
require_once "../inc/session_start.php";

header('Content-Type: application/json');

try {
    $json = file_get_contents('php://input');
    $carrito = json_decode($json, true);
    
    if(json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error en el formato de datos recibidos');
    }
    
    if(empty($carrito)) {
        throw new Exception('El carrito está vacío');
    }
    
    $conexion = conexion();
    $conexion->beginTransaction();
    
    // Crear la venta
    $venta_codigo = date('YmdHis');
    $venta_total = array_sum(array_column($carrito, 'total'));
    
    $stmt = $conexion->prepare("INSERT INTO venta (venta_codigo, venta_fecha, venta_total, usuario_id) VALUES (?, NOW(), ?, ?)");
    $stmt->execute([$venta_codigo, $venta_total, $_SESSION['id']]);
    
    $venta_id = $conexion->lastInsertId();
    
    // Array para almacenar productos con stock bajo
    $productos_stock_bajo = [];
    
    // Procesar cada item del carrito
    foreach($carrito as $item) {
        // Verificar stock disponible
        $stmt = $conexion->prepare("SELECT producto_stock, producto_nombre FROM producto WHERE producto_id = ?");
        $stmt->execute([$item['producto_id']]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$producto) {
            throw new Exception('Producto no encontrado: ' . $item['nombre']);
        }
        
        if($producto['producto_stock'] < $item['cantidad']) {
            throw new Exception('Stock insuficiente para el producto: ' . $item['nombre']);
        }
        
        // Insertar detalle de venta
        $stmt = $conexion->prepare("INSERT INTO venta_detalle (venta_id, producto_id, detalle_cantidad, detalle_precio, detalle_total) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $venta_id,
            $item['producto_id'],
            $item['cantidad'],
            $item['precio'],
            $item['total']
        ]);
        
        // Actualizar stock
        $nuevo_stock = $producto['producto_stock'] - $item['cantidad'];
        $stmt = $conexion->prepare("UPDATE producto SET producto_stock = ? WHERE producto_id = ?");
        $stmt->execute([$nuevo_stock, $item['producto_id']]);
        
        // Verificar si el nuevo stock es bajo
        if($nuevo_stock < 10) {
            $productos_stock_bajo[] = [
                'nombre' => $producto['producto_nombre'],
                'stock' => $nuevo_stock
            ];
        }
    }
    
    $conexion->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Venta procesada correctamente',
        'venta_id' => $venta_id,
        'alerta_stock' => !empty($productos_stock_bajo),
        'productos_stock_bajo' => $productos_stock_bajo
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if(isset($conexion)) {
        $conexion->rollBack();
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}