<div class="container is-fluid mb-6">
    <h1 class="title">Ventas</h1>
    <h2 class="subtitle">Lista de ventas</h2>
</div>

<div class="container pb-6 pt-6">
    <?php
        require_once "./php/main.php";

        if(!isset($_GET['page'])){
            $pagina = 1;
        }else{
            $pagina = (int) $_GET['page'];
            if($pagina <= 1){
                $pagina = 1;
            }
        }

        $pagina = limpiar_cadena($pagina);
        $url = "index.php?vista=sale_list&page=";
        $registros = 15;
        $busqueda = "";

        # Conexión a la base de datos
        $conexion = conexion();
        
        # Contar el total de registros
        $total = $conexion->query("SELECT COUNT(*) FROM venta");
        $total = (int) $total->fetchColumn();
        
        # Paginador
        $Npaginas = ceil($total/$registros);
        
        $tabla = "";
        
        if($total >= 1 && $pagina <= $Npaginas){
            $contador = ($pagina-1) * $registros + 1;
            $pag_inicio = ($pagina-1) * $registros;
            
            $ventas = $conexion->prepare("
            SELECT v.*, u.usuario_nombre, u.usuario_apellido 
            FROM venta v 
            INNER JOIN usuario u ON v.usuario_id = u.usuario_id 
            ORDER BY v.venta_fecha DESC 
            LIMIT :inicio, :registros
        ");
        $ventas->bindValue(':inicio', $pag_inicio, PDO::PARAM_INT);
        $ventas->bindValue(':registros', $registros, PDO::PARAM_INT);
        $ventas->execute();
            
            $tabla .= '
            <div class="table-container">
                <table class="table is-bordered is-striped is-narrow is-hoverable is-fullwidth">
                    <thead>
                        <tr class="has-text-centered">
                            <th>#</th>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Vendedor</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
            ';
            
            foreach($ventas as $row){
                $tabla .= '
                    <tr class="has-text-centered">
                        <td>'.$contador.'</td>
                        <td>'.$row['venta_codigo'].'</td>
                        <td>'.$row['venta_fecha'].'</td>
                        <td>$'.number_format($row['venta_total'], 0, ',', '.').'</td>
                        <td>'.$row['usuario_nombre'].' '.$row['usuario_apellido'].'</td>
                        <td>
                            <a href="index.php?vista=sale_invoice&venta_id='.$row['venta_id'].'" class="button is-info is-rounded is-small">
                                Ver Factura
                            </a>
                        </td>
                    </tr>
                ';
                $contador++;
            }
            
            $tabla .= '</tbody></table></div>';
            
            $tabla .= paginador_tablas($pagina, $Npaginas, $url, 7);
        }else{
            if($total >= 1){
                $tabla .= '
                    <p class="has-text-centered">
                        <a href="'.$url.'1" class="button is-link is-rounded is-small mt-4 mb-4">
                            Haga clic acá para recargar el listado
                        </a>
                    </p>
                ';
            }else{
                $tabla .= '
                    <p class="has-text-centered">No hay registros en el sistema</p>
                ';
            }
        }

        echo $tabla;
        $conexion = null;
    ?>
</div>