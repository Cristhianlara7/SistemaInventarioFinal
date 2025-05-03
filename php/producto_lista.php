<?php
	$inicio = ($pagina>0) ? (($pagina * $registros)-$registros) : 0;
	$tabla="";

	$campos="producto.producto_id,producto.producto_codigo,producto.producto_nombre,producto.producto_precio,producto.producto_stock,producto.producto_foto,producto.categoria_id,producto.usuario_id,categoria.categoria_id,categoria.categoria_nombre,usuario.usuario_id,usuario.usuario_nombre,usuario.usuario_apellido";

	if(isset($busqueda) && $busqueda!=""){

		$consulta_datos="SELECT $campos FROM producto INNER JOIN categoria ON producto.categoria_id=categoria.categoria_id INNER JOIN usuario ON producto.usuario_id=usuario.usuario_id WHERE producto.producto_codigo LIKE '%$busqueda%' OR producto.producto_nombre LIKE '%$busqueda%' ORDER BY producto.producto_nombre ASC LIMIT $inicio,$registros";

		$consulta_total="SELECT COUNT(producto_id) FROM producto WHERE producto_codigo LIKE '%$busqueda%' OR producto_nombre LIKE '%$busqueda%'";

	}elseif($categoria_id>0){

		$consulta_datos="SELECT $campos FROM producto INNER JOIN categoria ON producto.categoria_id=categoria.categoria_id INNER JOIN usuario ON producto.usuario_id=usuario.usuario_id WHERE producto.categoria_id='$categoria_id' ORDER BY producto.producto_nombre ASC LIMIT $inicio,$registros";

		$consulta_total="SELECT COUNT(producto_id) FROM producto WHERE categoria_id='$categoria_id'";

	}else{

		$consulta_datos="SELECT $campos FROM producto INNER JOIN categoria ON producto.categoria_id=categoria.categoria_id INNER JOIN usuario ON producto.usuario_id=usuario.usuario_id ORDER BY producto.producto_nombre ASC LIMIT $inicio,$registros";

		$consulta_total="SELECT COUNT(producto_id) FROM producto";

	}

	$conexion=conexion();

	$datos = $conexion->query($consulta_datos);
	$datos = $datos->fetchAll();

	$total = $conexion->query($consulta_total);
	$total = (int) $total->fetchColumn();

	$Npaginas =ceil($total/$registros);

	if($total>=1 && $pagina<=$Npaginas){
		$contador=$inicio+1;
		$pag_inicio=$inicio+1;
		
		$tabla.='
		<div class="columns is-multiline">';
		
		foreach($datos as $rows){
			$tabla.='
				<div class="column is-one-quarter">
				<div class="card">
					<div class="card-image">
						<figure class="image is-4by3">';
						if(is_file("./img/producto/".$rows['producto_foto'])){
							$tabla.='<img src="./img/producto/'.$rows['producto_foto'].'" alt="'.$rows['producto_nombre'].'">';
						}else{
							$tabla.='<img src="./img/producto.png" alt="Imagen por defecto">';
						}
			   $tabla.='</figure>
					</div>
					<div class="card-content">
						<div class="content">
							<p class="title is-5">'.$contador.' - '.$rows['producto_nombre'].'</p>
							<p class="subtitle is-6">
								<strong>CODIGO:</strong> '.$rows['producto_codigo'].'<br>
								<strong>PRECIO:</strong> $'.number_format((float)$rows['producto_precio'], 0, ',', '.').'<br>
								<strong>STOCK:</strong> '.$rows['producto_stock'].'<br>
								<strong>CATEGORIA:</strong> '.$rows['categoria_nombre'].'<br>
								<strong>REGISTRADO POR:</strong> '.$rows['usuario_nombre'].' '.$rows['usuario_apellido'].'
							</p>
						</div>
						<div class="buttons are-small is-centered">
							<a href="index.php?vista=product_img&product_id_up='.$rows['producto_id'].'" class="button is-link is-rounded">Imagen</a>
							<a href="index.php?vista=product_update&product_id_up='.$rows['producto_id'].'" class="button is-success is-rounded">Actualizar</a>
							<a href="'.$url.$pagina.'&product_id_del='.$rows['producto_id'].'" class="button is-danger is-rounded">Eliminar</a>
						</div>
					</div>
				</div>
			</div>';
            $contador++;
		}
		$tabla.='</div>';
		$pag_final=$contador-1;
	}else{
		if($total>=1){
			$tabla.='
				<p class="has-text-centered" >
					<a href="'.$url.'1" class="button is-link is-rounded is-small mt-4 mb-4">
						Haga clic acá para recargar el listado
					</a>
				</p>
			';
		}else{
			$tabla.='
				<p class="has-text-centered" >No hay registros en el sistema</p>
			';
		}
	}

	if($total>0 && $pagina<=$Npaginas){
		$tabla.='<p class="has-text-right">Mostrando productos <strong>'.$pag_inicio.'</strong> al <strong>'.$pag_final.'</strong> de un <strong>total de '.$total.'</strong></p>';
	}

	$conexion=null;
	echo $tabla;

	if($total>=1 && $pagina<=$Npaginas){
		echo paginador_tablas($pagina,$Npaginas,$url,7);
	}