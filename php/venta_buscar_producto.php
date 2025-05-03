<?php
require_once "main.php";

$busqueda = isset($_GET['busqueda']) ? limpiar_cadena($_GET['busqueda']) : '';

if($busqueda != "") {
    $conexion = conexion();
    
    $consulta = "SELECT producto_id, producto_codigo, producto_nombre, producto_precio, producto_stock 
                 FROM producto 
                 WHERE (producto_codigo LIKE ? OR producto_nombre LIKE ?) 
                 AND producto_stock > 0 
                 LIMIT 10";
    
    $stmt = $conexion->prepare($consulta);
    $stmt->execute(["%$busqueda%", "%$busqueda%"]);
    
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $conexion = null;
    
    header('Content-Type: application/json');
    echo json_encode($productos);
} else {
    echo json_encode([]);
}