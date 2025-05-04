<div class="container is-fluid mb-6">
    <h1 class="title">Ventas</h1>
    <h2 class="subtitle">Nueva venta</h2>
</div>


<div class="box mt-5">
    <h2 class="title is-4">Datos del Cliente</h2>
    <div class="columns is-multiline">
        <div class="column is-6">
            <div class="field">
                <label class="label">Tipo de Identificación</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select id="tipoIdentificacion">
                            <option value="13">Cédula de Ciudadanía</option>
                            <option value="31">NIT</option>
                            <option value="41">Pasaporte</option>
                            <option value="42">Documento Extranjero</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Número de Identificación *</label>
                <div class="control">
                    <input class="input" type="text" id="numeroIdentificacion" required>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Nombre/Razón Social *</label>
                <div class="control">
                    <input class="input" type="text" id="razonSocial" required>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Dirección *</label>
                <div class="control">
                    <input class="input" type="text" id="direccion" required>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Ciudad</label>
                <div class="control">
                    <input class="input" type="text" id="ciudad">
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Teléfono</label>
                <div class="control">
                    <input class="input" type="text" id="telefono">
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="field">
                <label class="label">Email</label>
                <div class="control">
                    <input class="input" type="email" id="email">
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-6 pt-6">
    <div class="columns">
        <div class="column">
            <div class="field">
                <label class="label">Buscar Producto (Por código o nombre)</label>
                <div class="control">
                    <input class="input" type="text" id="buscar_producto" placeholder="Ingrese código o nombre del producto">
                </div>
            </div>

            <div id="resultados_busqueda" class="mt-3"></div>
        </div>
    
</div>

        <div class="column">
            <h3 class="title is-4">Carrito de Venta</h3>
            <div id="carrito_venta">
                <table class="table is-fullwidth">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Total</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="items_carrito">
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="has-text-right"><strong>Total:</strong></td>
                            <td id="total_venta">$0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="field is-grouped is-grouped-right">
                    <p class="control">
                        <button class="button is-success" id="procesar_venta">
                            Procesar Venta
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let carrito = [];
    const buscarProducto = document.getElementById('buscar_producto');
    const resultadosBusqueda = document.getElementById('resultados_busqueda');
    const itemsCarrito = document.getElementById('items_carrito');
    const totalVenta = document.getElementById('total_venta');
    
    buscarProducto.addEventListener('keyup', function() {
        if(this.value.length >= 2) {
            fetch(`./php/venta_buscar_producto.php?busqueda=${this.value}`)
            .then(response => response.json())
            .then(data => {
                mostrarResultados(data);
            });
        } else {
            resultadosBusqueda.innerHTML = '';
        }
    });

    function mostrarResultados(productos) {
        resultadosBusqueda.innerHTML = '';
        productos.forEach(producto => {
            const div = document.createElement('div');
            div.className = 'box';
            div.innerHTML = `
                <article class="media">
                    <div class="media-content">
                        <div class="content">
                            <p>
                                <strong>${producto.producto_nombre}</strong><br>
                                <small>Código: ${producto.producto_codigo}</small><br>
                                <small>Precio: $${producto.producto_precio}</small><br>
                                <small>Stock: ${producto.producto_stock}</small>
                            </p>
                        </div>
                        <div class="field has-addons">
                            <div class="control">
                                <input class="input" type="number" min="0" step="1" max="${producto.producto_stock}" value="0" 
                                    id="cantidad_${producto.producto_id}"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value < 0) this.value = 0;"
                                    onkeydown="return event.keyCode !== 190 && event.keyCode !== 188 && event.keyCode !== 110"
                                >
                            </div>
                            <div class="control">
                                <button class="button is-info" onclick="agregarAlCarrito(${JSON.stringify(producto).replace(/"/g, '&quot;')})">
                                    Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            `;
            resultadosBusqueda.appendChild(div);
        });
    }

    window.agregarAlCarrito = function(producto) {
        const cantidadInput = document.getElementById(`cantidad_${producto.producto_id}`);
        const cantidad = Math.floor(Math.abs(parseInt(cantidadInput.value)));
        
        if(isNaN(cantidad) || cantidad === 0) {
            alert('Por favor ingrese una cantidad válida (número entero mayor a 0)');
            cantidadInput.value = 0;
            return;
        }
        
        if(cantidad > producto.producto_stock) {
            alert('No hay suficiente stock disponible');
            cantidadInput.value = 0;
            return;
        }
        
        const itemExistente = carrito.find(item => item.producto_id === producto.producto_id);
        
        if(itemExistente) {
            if(itemExistente.cantidad + cantidad > producto.producto_stock) {
                alert('No hay suficiente stock disponible');
                return;
            }
            itemExistente.cantidad += cantidad;
            itemExistente.total = itemExistente.cantidad * itemExistente.precio;
        } else {
            carrito.push({
                producto_id: producto.producto_id,
                nombre: producto.producto_nombre,
                precio: parseFloat(producto.producto_precio),
                cantidad: cantidad,
                total: cantidad * parseFloat(producto.producto_precio)
            });
        }
        
        actualizarCarrito();
    }

    function actualizarCarrito() {
        itemsCarrito.innerHTML = '';
        let total = 0;
        
        carrito.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.nombre}</td>
                <td>${item.cantidad}</td>
                <td>$${item.precio}</td>
                <td>$${item.total}</td>
                <td>
                    <button class="button is-danger is-small" onclick="eliminarItem(${index})">
                        Eliminar
                    </button>
                </td>
            `;
            itemsCarrito.appendChild(tr);
            total += item.total;
        });
        
        totalVenta.textContent = `$${total}`;
    }

    window.eliminarItem = function(index) {
        carrito.splice(index, 1);
        actualizarCarrito();
    }

    // Reemplazar el evento click existente con la nueva función
    document.getElementById('procesar_venta').addEventListener('click', function() {
        if(carrito.length === 0) {
            alert('El carrito está vacío');
            return;
        }

        // Validar campos requeridos del cliente
        const numeroIdentificacion = document.getElementById('numeroIdentificacion').value;
        const razonSocial = document.getElementById('razonSocial').value;
        const direccion = document.getElementById('direccion').value;

        if (!numeroIdentificacion || !razonSocial || !direccion) {
            alert('Por favor complete los campos obligatorios del cliente');
            return;
        }

        // Preparar datos del cliente
        const datosCliente = {
            tipoIdentificacion: document.getElementById('tipoIdentificacion').value,
            numeroIdentificacion: numeroIdentificacion,
            razonSocial: razonSocial,
            direccion: direccion,
            ciudad: document.getElementById('ciudad').value,
            telefono: document.getElementById('telefono').value,
            email: document.getElementById('email').value
        };

        // Enviar datos de venta
        fetch('./php/venta_procesar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                carrito: carrito,
                cliente: datosCliente
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                alert('Venta procesada correctamente');
                
                if(data.alerta_stock && data.productos_stock_bajo.length > 0) {
                    let mensaje = 'ALERTA: Los siguientes productos tienen stock bajo:\n\n';
                    data.productos_stock_bajo.forEach(producto => {
                        mensaje += `- ${producto.nombre}: ${producto.stock} unidades\n`;
                    });
                    mensaje += '\nSe recomienda realizar un nuevo pedido de estos productos.';
                    alert(mensaje);
                }
                
                carrito = [];
                actualizarCarrito();
                
                // Redirigir a la factura
                if (data.factura && data.factura.url) {
                    window.location.href = data.factura.url;
                }
            } else {
                alert('Error al procesar la venta: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error al procesar la venta: ' + error.message);
            console.error('Error:', error);
        });
    });
});

</script>