$(document).ready(function () {
  let pedidoActual = null; // Variable para almacenar el número de pedido actual

  // Inicializar DataTable
  const tablaPedido = $("#tabla_pedido").DataTable({
    language: idioma_espanol,
    columns: [
      {
        data: null,
        render: function (data, type, row) {
          return (
            '<button class="editar btn btn-success" data-articulo="' +
            row.articulo +
            '" data-cantidad="' +
            row.cantidad +
            '" data-precio="' +
            row.precioUnitario +
            '"><i class="fas fa-pen"></i></button>' +
            '<button class="eliminar btn btn-danger" data-articulo="' +
            row.articulo +
            '"><i class="fas fa-times-circle"></i></button>'
          );
        },
      },
      { data: "articulo" },
      { data: "cantidad" },
      { data: "precioUnitario" },
      { data: "precioDescuento" },
      { data: "totalTabla" },
    ],
  });

  tablaPedido.on("draw", function () {
    $('[data-toggle="tooltip"]').tooltip();
  });

  // Eventos para buscar pedido, agregar y eliminar (ya existentes)
  $("#btnBuscarPedido").click(function () {
    const numeroPedido = $("#inputPedido").val();
    if (numeroPedido !== "") {
      buscarPedido(numeroPedido);
    } else {
      alert("Ingrese un número de pedido válido.");
    }
  });

  $("#inputPedido").on("keypress", function (event) {
    if (event.key === "Enter") {
      event.preventDefault();
      $("#btnBuscarPedido").click();
    }
  });

  function mostrarPedido(pedido) {
    if (!pedido || !pedido.venta_cabecera || !pedido.venta_detalle) {
      console.log("Pedido no encontrado o la respuesta es incorrecta.");
      return;
    }
    const ventaCabecera = pedido.venta_cabecera;
    const ventaDetalle = pedido.venta_detalle;

    $("#numeroPedido").text(
      `Número de Pedido: ${ventaCabecera["idVentaCabecera"]}`
    );

    // Llenar campos del encabezado
    $("#cboEmisor").val(ventaCabecera.idEmisor);
    $("#cboTipoDocumento").val(ventaCabecera.TipoDocumento);
    $("#cboVendedor").val(ventaCabecera.idVendedor);
    $("#cboDescuento").val(ventaCabecera.TipoDescuento);
    $("#txtCodigoCliente").val(ventaCabecera.idCliente);
    $("#cboTipoDocumentoCliente").val(ventaCabecera.DocumentoCliente);
    $("#txtNumeroDocumento").val(ventaCabecera.NumeroDocumento_Cliente);
    $("#txtRazonSocial").val(ventaCabecera.RazonSocial_Cliente);
    $("#txtDireccion").val(ventaCabecera.Ubicacion_Cliente);
    $("#txt_subtotal").val(ventaCabecera.valventa_ventacabecera);
    $("#txt_igv").val(ventaCabecera.igv_ventacabecera);
    $("#txt_total").val(ventaCabecera.total_ventacabecera);
    var ObservacionCodificada = decodeURIComponent(
      ventaCabecera.comentario_ventacabecera
    );
    $("#txtObservaciones").val(ObservacionCodificada);

    // Limpiar la tabla y agregar el detalle
    tablaPedido.clear();
    ventaDetalle.forEach((elemento) => {
      const precioDescuento = elemento["precioDescuento_ventadetalle"];
      const totalTabla = elemento["total_ventadetalle"];
      tablaPedido.row
        .add({
          articulo: elemento["idProducto"],
          cantidad: elemento["cantidad_ventadetalle"],
          precioUnitario: elemento["preciounitario__ventadetalle"],
          precioDescuento: precioDescuento,
          totalTabla: totalTabla,
        })
        .node()
        .classList.add("pedido-fila");
    });
    tablaPedido.draw();

    $("#CantidadTabla").empty();
    $("#CantidadTabla").text("Productos agregados: " + ventaDetalle.length);

    var descuento = parseFloat($("#cboDescuento").val());
    if (isNaN(descuento)) descuento = 1;
    updateTableData(descuento);
  }

  function buscarPedido(numeroPedido) {
    $.ajax({
      url: "../function/Ventas_Buscar.php",
      type: "POST",
      data: { pedido_id: numeroPedido },
      dataType: "json",
      success: function (response) {
        if (!response || !response.venta_cabecera || !response.venta_detalle) {
          Swal.fire({
            icon: "error",
            title: "Pedido no encontrado",
            text: "Por favor, verifique o ingrese un pedido válido.",
          });
        } else {
          mostrarPedido(response);
        }
      },
      error: function (xhr, status, error) {
        console.error("Error en la solicitud AJAX:", error);
      },
    });
  }

  $("#tabla_pedido").on("click", ".eliminar", function () {
    // Obtenemos la fila completa del botón clickeado
    const row = tablaPedido.row($(this).closest("tr"));
    const data = row.data();

    // Extraer el código y la cantidad del producto
    const articulo = data.articulo;
    const cantidad = data.cantidad;

    // Mostrar el diálogo de confirmación con SweetAlert
    Swal.fire({
      title: "Confirmar eliminación",
      html: `<span style="color: white;">¿Está seguro de eliminar el producto <strong>${articulo}</strong> con cantidad <strong>${cantidad}</strong>?</span>`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (result.isConfirmed) {
        // Eliminar la fila del DataTable de forma dinámica
        row.remove().draw(false);

        // Recalcular los totales y actualizar el contador de productos
        calculateTotals();
        $("#CantidadTabla").text(
          "Productos agregados: " + tablaPedido.data().count()
        );

        // Mostrar un mensaje de éxito
        Swal.fire({
          title: "Eliminado",
          html: `<span style="color: white;">Producto <strong>${articulo}</strong> eliminado (cantidad: <strong>${cantidad}</strong>).</span>`,
          icon: "success",
          timer: 2000,
          showConfirmButton: false,
        });
      }
    });
  });

  // Actualiza la tabla y recalcula los totales al cambiar el descuento
  $("#cboDescuento").on("change", () => {
    updateTableAndTotals();
  });

  function updateTableAndTotals() {
    // Obtener el valor numérico del descuento; si no es válido, se usa 1 (sin descuento)
    const descuento = parseFloat($("#cboDescuento").val()) || 1;
    updateTableData(descuento);
  }

  function updateTableData(descuento) {
    // Obtener los datos actuales del DataTable
    const rows = tablaPedido.rows().data();
    rows.each((row, index) => {
      const cantidad = row.cantidad;
      const precio = row.precioUnitario;
      // Calcular el precio con descuento y el total de la fila
      const precioDescuento = precio * descuento;
      const total = cantidad * precioDescuento;
      // Actualizar las celdas correspondientes (índices 4 y 5)
      tablaPedido
        .cell(index, 4)
        .data((Math.round(precioDescuento * 1000) / 1000).toFixed(3));
      tablaPedido
        .cell(index, 5)
        .data((Math.round(total * 100) / 100).toFixed(2));
    });
    // Redibujar la tabla sin reiniciar la paginación
    tablaPedido.draw(false);
    // Recalcular y actualizar los totales generales
    calculateTotals();
  }

  function calculateTotals() {
    // Extraemos los datos del DataTable y generamos un arreglo con la propiedad totalFormateado
    var cartData = tablaPedido
      .rows()
      .data()
      .toArray()
      .map((row) => {
        return { totalFormateado: row.totalTabla };
      });

    // Sumar los totales de cada producto
    var total = cartData.reduce(
      (sum, product) => sum + parseFloat(product.totalFormateado),
      0
    );

    // Calcular subtotal e IGV (suponiendo un 18% de IGV)
    var subtotal = total / 1.18;
    var igv = total - subtotal;

    // Actualizar los campos con los valores calculados
    $("#txt_subtotal").val(subtotal.toFixed(2));
    $("#txt_igv").val(igv.toFixed(2));
    $("#txt_total").val(total.toFixed(2));
  }

  // --- Sección de Edición local de un elemento del DataTable ---
  // Al hacer clic en el botón de editar, se abre el modal con los datos actuales
  $("#tabla_pedido tbody").on("click", ".editar", function () {
    const articulo = $(this).data("articulo");
    const cantidad = $(this).data("cantidad");
    const precioUnitario = $(this).data("precio");
    $("#inputArticuloEditar").val(articulo);
    $("#inputCantidadEditar").val(cantidad);
    $("#inputPrecioEditar").val(precioUnitario);
    $("#editarElementoModal").modal("show");
  });

  // Función que actualiza localmente la fila del DataTable
  function actualizarElementoLocal(articulo, cantidad, precio) {
    var descuento = parseFloat($("#cboDescuento").val());
    if (isNaN(descuento)) {
      descuento = 1;
    }
    var precioDescuento = (precio * descuento).toFixed(3);
    var totalTabla = (cantidad * precioDescuento).toFixed(2);

    // Buscar la fila cuyo "articulo" coincida
    var filaEncontrada = false;
    tablaPedido.rows().every(function () {
      var data = this.data();
      if (data.articulo === articulo) {
        // Actualizar los datos de la fila
        this.data({
          articulo: articulo,
          cantidad: cantidad,
          precioUnitario: precio.toFixed(3),
          precioDescuento: precioDescuento,
          totalTabla: totalTabla,
        });
        filaEncontrada = true;
      }
    });

    if (!filaEncontrada) {
      Swal.fire("Error", "No se encontró el producto en la tabla.", "error");
    }

    tablaPedido.draw(false);
    calculateTotals();
    $("#CantidadTabla").text(
      "Productos agregados: " + tablaPedido.data().count()
    );
  }

  // Manejar clic en el botón de guardar cambios del modal de edición
  $("#btnGuardarCambiosEdit").click(function () {
    const articulo = $("#inputArticuloEditar").val();
    const cantidad = parseInt($("#inputCantidadEditar").val());
    const precio = parseFloat($("#inputPrecioEditar").val());
    if (isNaN(cantidad) || isNaN(precio)) {
      Swal.fire("Error", "Verifique que los valores sean numéricos.", "error");
      return;
    }
    actualizarElementoLocal(articulo, cantidad, precio);
    $("#editarElementoModal").modal("hide");
  });
  // --- Fin de sección de Edición local ---

  // Función para agregar un nuevo elemento al pedido
  function agregarElementoPedido(articulo, cantidad, precio) {
    const articulosExistentes = tablaPedido.data().pluck("articulo").toArray();
    if (articulosExistentes.includes(articulo)) {
      alert("El Producto ya existe en el pedido.");
      return;
    }
    var descuento = parseFloat($("#cboDescuento").val());
    if (isNaN(descuento)) descuento = 1;
    const precioDescuento = (precio * descuento).toFixed(3);
    const totalTabla = (cantidad * precioDescuento).toFixed(2);
    var newRow = {
      articulo: articulo,
      cantidad: cantidad,
      precioUnitario: precio.toFixed(3),
      precioDescuento: precioDescuento,
      totalTabla: totalTabla,
    };
    tablaPedido.row.add(newRow).draw();
    calculateTotals();
    $("#CantidadTabla").text(
      "Productos agregados: " + tablaPedido.data().count()
    );
    // Limpiar campos del modal de productos
    $("#txtCodigoProducto").val("");
    $("#Cantidad").val("");
    $("#PrecioVenta").val("");
    $("#txtStock").val("");
    $("#inputCodigoReferencia").val("");
    $("#inputSuspendido").val("");
    $("#cboProductos").val(null).trigger("change");
    $(".select2-search__field")[0].focus();
  }

  // Manejar clic en el botón "Agregar" del modal de productos
  $("#AgregarNuevoProducto").click(function () {
    const articulo = $("#txtCodigoProducto").val();
    const cantidad = parseInt($("#Cantidad").val());
    const precio = parseFloat($("#PrecioVenta").val());
    if (cantidad <= 0) {
      alert("Ingrese una cantidad válida (mayor que 0).");
      return;
    }
    if (precio <= 0 || isNaN(precio)) {
      alert("Ingrese un precio válido (mayor que 0).");
      return;
    }
    agregarElementoPedido(articulo, cantidad, precio);
  });

  // Al hacer clic en btnRealizarModificacion se abre el modal
  $("#btnRealizarModificacion").on("click", function () {
    // Limpia y oculta ambos contenedores para evitar residuos de interacciones anteriores
    $("#divClaveAdmin").hide();
    $("#divEnviarSolicitud").hide();
    $("#txtClaveAdmin").val("");
    $("#txtMotivo").val("");
    // Abre el modal
    $("#modalValidacion").modal("show");
  });

  // Inicialmente, ambos botones se habilitan
  $("#btnClaveAdmin, #btnEnviarSolicitud").prop("disabled", false);

  // Al hacer clic en "Clave Administrador"
  $("#btnClaveAdmin").on("click", function () {
    if ($(this).prop("disabled")) return;
    $(this).prop("disabled", true);
    $("#btnEnviarSolicitud").prop("disabled", false);

    // Mostrar el campo para ingresar la clave y ocultar el motivo
    $("#divClaveAdmin").show();
    $("#divEnviarSolicitud").hide();
  });

  // Al hacer clic en "Enviar Solicitud"
  $("#btnEnviarSolicitud").on("click", function () {
    if ($(this).prop("disabled")) return;
    $(this).prop("disabled", true);
    $("#btnClaveAdmin").prop("disabled", false);

    // Mostrar el campo para ingresar el motivo y ocultar la clave
    $("#divEnviarSolicitud").show();
    $("#divClaveAdmin").hide();
  });

  // Función para recopilar los datos modificados de la cabecera
  function getCabeceraData() {
    return {
      idVentaCabecera: $("#inputPedido").val(),
      idEmisor: $("#cboEmisor").val(),
      TipoDocumento: $("#cboTipoDocumento").val(),
      idVendedor: $("#cboVendedor").val(),
      TipoDescuento: $("#cboDescuento").val(),
      idCliente: $("#txtCodigoCliente").val(),
      valventa_ventacabecera: $("#txt_subtotal").val(),
      igv_ventacabecera: $("#txt_igv").val(),
      total_ventacabecera: $("#txt_total").val(),
      comentario_ventacabecera: encodeURIComponent(
        $("#txtObservaciones").val()
      ),
    };
  }

  // Función para obtener el detalle (las filas del DataTable)
  function getDetalleData() {
    return tablaPedido.rows().data().toArray();
  }

  // Al hacer clic en "Confirmar del modal"
  $("#btnConfirmarValidacion").on("click", function () {
    // Si la opción seleccionada es "Clave Administrador"
    if ($("#btnClaveAdmin").prop("disabled")) {
      var clave = $("#txtClaveAdmin").val().trim();
      if (clave === "") {
        Swal.fire(
          "Error",
          "Por favor, ingrese la clave del administrador",
          "error"
        );
        return;
      }
      // Enviar la clave por AJAX para validarla
      $.ajax({
        url: "../function/VentasActualizar_ValidacionAdmin.php",
        type: "POST",
        data: { clave: clave },
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            Swal.fire("Éxito", response.message, "success");
            // Si además se han capturado modificaciones, se pueden enviar:
            var cabeceraData = getCabeceraData();
            var detalleData = getDetalleData();
            // Ahora se envían junto con la clave validada
            $.ajax({
              url: "../function/VentasActualizar_ValidacionAdmin.php",
              type: "POST",
              data: {
                clave: clave,
                TipoDocumento: cabeceraData.TipoDocumento,
                cabecera: JSON.stringify(cabeceraData),
                detalles: JSON.stringify(detalleData),
              },
              dataType: "json",
              success: function (res) {
                if (res.status === "success") {
                  Swal.fire(
                    "Éxito",
                    "Modificación realizada y respaldada correctamente.",
                    "success"
                  );
                  // Actualizar la interfaz según corresponda
                } else {
                  Swal.fire("Error", res.message, "error");
                }
              },
              error: function () {
                Swal.fire(
                  "Error",
                  "Error al procesar la modificación inmediata.",
                  "error"
                );
              },
            });
          } else {
            Swal.fire("Error", response.message, "error");
          }
        },
        error: function () {
          Swal.fire("Error", "Error al validar la clave.", "error");
        },
      });
    }
    // Si la opción seleccionada es "Enviar Solicitud"
    else if ($("#btnEnviarSolicitud").prop("disabled")) {
      // Si la opción seleccionada es "Enviar Solicitud"
      if ($("#btnEnviarSolicitud").prop("disabled")) {
        var motivo = $("#txtMotivo").val().trim();
        if (motivo === "") {
          Swal.fire(
            "Error",
            "Por favor, ingrese el motivo de validación",
            "error"
          );
          return;
        }

        // Recopilar datos de la cabecera y del detalle
        var cabeceraData = getCabeceraData();
        var detalleData = getDetalleData();

        // Enviar los datos por AJAX
        $.ajax({
          url: "../function/VentasActualizar_EnviarValidacionAdmin.php", // Nuevo endpoint para registrar en la bitácora
          type: "POST",
          data: {
            MotivoSolicitud: motivo,
            cabecera: JSON.stringify(cabeceraData),
            detalles: JSON.stringify(detalleData),
          },
          dataType: "json",
          success: function (response) {
            if (response.status === "success") {
              Swal.fire("Éxito", response.message, "success");
              // Aquí puedes proceder a limpiar o recargar el formulario si es necesario
            } else {
              Swal.fire("Error", response.message, "error");
            }
          },
          error: function () {
            Swal.fire("Error", "Error al procesar la solicitud.", "error");
          },
        });
      }
      // Si no se ha seleccionado ninguna opción, se muestra un error
      else {
        Swal.fire(
          "Error",
          "Debe seleccionar una opción de validación",
          "error"
        );
      }
      // Aquí se pueden continuar con las acciones correspondientes para esta opción
    } else {
      Swal.fire("Error", "Debe seleccionar una opción de validación", "error");
    }
  });

  // Manejar clic en el botón "Realizar Modificación"
  // $("#btnRealizarModificacion").click(function () {
  //   const idPedido = $("#inputPedido").val();
  //   const idEmisor = $("#cboEmisor").val();
  //   const TipoDocumento = $("#cboTipoDocumento").val();
  //   const Vendedor = $("#cboVendedor").val();
  //   const Descuento = $("#cboDescuento").val();
  //   const idCliente = $("#txtCodigoCliente").val();
  //   const Subtotal = $("#txt_subtotal").val();
  //   const IGV = $("#txt_igv").val();
  //   const Total = $("#txt_total").val();
  //   const Comentarios = encodeURIComponent($("#txtObservaciones").val());
  //   const detalles = tablaPedido.rows().data().toArray();
  //   $.ajax({
  //     url: "../function/Ventas_EditarVentaCabecera.php",
  //     type: "POST",
  //     data: {
  //       numeroPedido: idPedido,
  //       emisor: idEmisor,
  //       tipoDocumento: TipoDocumento,
  //       idVendedor: Vendedor,
  //       descuento: Descuento,
  //       idCliente: idCliente,
  //       Subtotal: Subtotal,
  //       igv: IGV,
  //       Total: Total,
  //       Comentarios: Comentarios,
  //       detalles: detalles,
  //     },
  //     dataType: "json",
  //     success: function (response) {
  //       Swal.fire({
  //         position: "center",
  //         icon: "success",
  //         title: response.message,
  //         showConfirmButton: false,
  //         timerProgressBar: false,
  //         timer: 3000,
  //       });
  //       $("#modalImpresion").modal("show");
  //     },
  //     error: function (xhr, status, error) {
  //       console.error("Error en la solicitud AJAX:", error);
  //     },
  //   });
  // });
});
// FIN DE $(document).ready()

// Para cargar datos en el modal de productos
$("#cboProductos").on("change", function () {
  var idProductos = $(this).children(":selected").attr("id");
  $.ajax({
    type: "GET",
    url: "../function/Productos_BuscarVentas.php",
    data: { codigoProducto: idProductos },
    datatype: "JSON",
    success: function (respuesta) {
      $("#txtCodigoProducto").val(respuesta.idProductos);
      $("#PrecioVenta").val(respuesta.PrecioNacional);
      $("#txtStock").val(respuesta.Stock);
      $("#inputCodigoReferencia").val(respuesta.CodigoReferencia);
      var suspendido = respuesta.Suspendido;
      if (suspendido === "NO") {
        $("#inputSuspendido").css("color", "#0FEB82");
      } else if (suspendido === "SI") {
        $("#inputSuspendido").css("color", "red");
      }
      $("#inputSuspendido").val(respuesta.Suspendido);
    },
  });
});

function SeleccionarUsuario(idusuario) {
  $.ajax({
    type: "POST",
    url: "../function/Cliente_Buscar.php",
    data: { id_usuario: idusuario },
    datatype: "JSON",
    success: function (respuesta) {
      $("#txtCodigoCliente").val(respuesta[0].idClientes);
      $("#cboTipoDocumentoCliente").val(respuesta[0].idTipoDocumentos_Clientes);
      $("#txtNumeroDocumento").val(respuesta[0].NumeroDocumento_Clientes);
      $("#txtRazonSocial").val(respuesta[0].RazonSocial_Clientes);
      $("#txtDireccion").val(
        respuesta[0].Direccion_Clientes +
          " - " +
          respuesta[0].Distrito_Cliente +
          " - " +
          respuesta[0].Provincia_Cliente +
          " - " +
          respuesta[0].Departamento_Cliente
      );
      $("#ListadoCliente").modal("hide");
    },
  });
}

function Imprimir() {
  var numeroDocumento = $("#inputPedido").val();
  window.open(
    "../reportes/ReporteVentaA4.php?numeroDocumento=" + numeroDocumento,
    ""
  );
  setTimeout(function () {
    document.location.reload();
  }, 5000);
}
