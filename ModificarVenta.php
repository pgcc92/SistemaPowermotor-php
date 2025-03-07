<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
  // Si no ha iniciado sesión, redirigir al inicio de sesión u otra página
  header("Location:../index.php");
  exit();
}

// Obtener el nombre del usuario de la sesión
$numeroDocumentoUsuario = $_SESSION['usuario'];

// Obtener los datos adicionales del usuario si están disponibles
if (isset($_SESSION['NombresApellidos'])) {
  $nombresApellidos = $_SESSION['NombresApellidos'];
}
if (isset($_SESSION['idTipoUsuario'])) {
  $idTipoUsuario = $_SESSION['idTipoUsuario'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modificar Venta | Sistema Web</title>

  <!-- Select2 -->
  <!-- <link rel="stylesheet" href="../plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css"> -->
  <?php include "../lib/topLink.php"; ?>
</head>

<style>
  /* div.dataTables_wrapper div.dataTables_filter input {
  margin-left: 0.5em;
  display: inline-block;
  width: 300px;
} */

  .codigoRow {
    display: block;
    float: none;
    text-align: center;
    font-size: 18pt;
  }

  .cantidadRow {
    width: 200px;

  }

  .precioRow {
    width: 300px;


  }

  .descuentoRow {
    width: 200px;
    display: block;
    float: none;
  }

  .subtotalRow {
    width: 200px;
    float: right;
  }
</style>


<body class="sidebar-mini dark-mode text-sm" style="height: auto;">
  <div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
      <img class="animation__wobble" src="../dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60">
    </div>

    <?php include "../ext/navbar.php"; ?>
    <!-- Main Sidebar Container -->
    <?php include "../ext/SidebarMenu.php";  ?>


    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="card-title">Módulo de Actualización de Documentos</h1>
            </div><!-- /.col -->

          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content-header -->

      <section class="content">
        <div class="container-fluid">

          <div class="row">
            <div class="col-12">
              <!-- Default box -->
              <div class="card card-primary card-outline">
                <div class="card-body">
                  <!-- <form role="form" enctype="multipart/form-data" id="frmDetalleDocumento" name="frmDetalleDocumento"> -->
                  <div class="row">

                    <div class="col-sm-2 input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                      </div>
                      <input type="text" class="form-control" style="text-align: center;" id="inputPedido" name="inputPedido" placeholder="Número documento">
                      <div class="input-group-append">
                        <button class="btn bg-gradient-warning" type="button" id="btnBuscarPedido"><i class="fas fa-search"></i></button>
                      </div>
                    </div>

                    <div class="col-sm-2 input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                      </div>
                      <input type="text" class="form-control" placeholder="Fecha" style="text-align: center;" value="<?php echo date("d-m-Y"); ?>" disabled>
                    </div>

                    <div class="col-sm-2 input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                      </div>
                      <select class="custom-select" name="cboEmisor" id="cboEmisor">
                        <option value="" selected disabled>Emisor</option>
                        <?php
                        require_once("../class/Empresa.class.php");
                        $obj = new Empresas();
                        $lista = $obj->ListarEmpresasVenta();
                        foreach ($lista as $campo) {
                          $idEmpresa = $campo["idEmpresa"];
                          $RazonSocial_Empresa = $campo["RazonSocial_Empresa"];
                        ?>
                          <option value="<?php echo $idEmpresa; ?>"><?php echo $RazonSocial_Empresa; ?></option>
                        <?php } ?>
                      </select>
                    </div>

                    <div class="col-sm-2 input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-file-alt"></i></span>
                      </div>
                      <select class="custom-select" name="cboTipoDocumento" id="cboTipoDocumento">
                        <option value="" selected disabled>Documento a emitir</option>
                        <?php
                        require_once("../class/TipoDocumentoVenta.class.php");
                        $obj = new TipoDocumentoVenta();
                        $lista = $obj->ListarTipoDocumentoVenta();
                        foreach ($lista as $campo) {
                          $idTipo = $campo["idTipo"];
                          $NombreDocumentoVentas = $campo["NombreDocumentoVentas"];
                        ?>

                          <option value="<?php echo $idTipo; ?>"><?php echo $NombreDocumentoVentas; ?></option>
                        <?php } ?>
                      </select>
                    </div>

                    <div class="col-sm-2 input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                      </div>
                      <select class="custom-select" id="cboVendedor">
                        <option value="" selected disabled>Vendedor</option>
                        <?php
                        require_once("../class/Usuarios.class.php");
                        $obj = new Usuarios();
                        $lista = $obj->ListarVendedores();
                        foreach ($lista as $campo) {
                          $idUsuario = $campo["idUsuario"];
                          $NombresApellidos = $campo["NombresApellidos"];
                        ?>

                          <option value="<?php echo $idUsuario; ?>"><?php echo $NombresApellidos; ?></option>
                        <?php }
                        ?>
                      </select>
                    </div>

                    <div class="col-sm-2 input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-percent"></i></span>
                      </div>
                      <select class="custom-select" name="cboDescuento" id="cboDescuento">
                        <option value="" selected disabled>Descuento</option>
                        <?php
                        require_once("../class/Descuentos.class.php");
                        $obj = new Descuentos();
                        $lista = $obj->ListarDescuentos();
                        foreach ($lista as $campo) {
                          $idDescuento = $campo["idDescuento"];
                          $NombreDescuento = $campo["NombreDescuento"];
                          $FactorDescuento = $campo["FactorDescuento"];
                        ?>

                          <option value="<?php echo $FactorDescuento; ?>"><?php echo $NombreDescuento; ?></option>
                        <?php } ?>
                      </select>
                    </div>


                  </div>

                  <div class="row">

                    <div class="col-sm-2 input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control" style="text-align: center;" id="txtCodigoCliente" name="txtCodigoCliente" placeholder="Codigo Cliente">
                      <div class="input-group-append">
                        <button class="btn bg-gradient-warning" type="button" data-toggle="modal" data-target="#ListadoCliente"><i class="fas fa-search" data-toggle="modal" data-target="#ListadoCliente"></i></button>
                      </div>
                    </div>

                    <div class="col-sm-2 input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-file-invoice"></i></span>
                      </div>
                      <input type="text" style="text-align: center;" id="txtNumeroDocumento" name="txtNumeroDocumento" class="form-control" placeholder="Número Documento" disabled>
                    </div>

                    <div class="col-sm-8 input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                      </div>
                      <input type="text" id="txtRazonSocial" name="txtRazonSocial" class="form-control" placeholder="Razón Social" disabled>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-sm-12 input-group mb-3">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                      </div>
                      <input type="text" id="txtDireccion" name="txtDireccion" class="form-control" placeholder="Domicilio Fiscal - Distrito - Provincia - Departamento" disabled>
                    </div>
                  </div>

                  <div class="row align-items-stretch mb-3"> <!-- Asegura que ambos ocupen el mismo alto -->
                    <!-- Botón "Agregar Productos" (col-10 en grandes, col-9 en medianas, col-12 en móviles) -->
                    <div class="col-lg-10 col-md-9 col-12 d-flex align-items-center">
                      <div class="form-group w-100 m-0">
                        <button type="button" class="btn btn-block bg-gradient-primary btn-sm" data-toggle="modal" data-target="#AgregarProductos"><i class="fa fa-plus" data-toggle="modal" data-target="#AgregarProductos"></i> Agregar Producto</button>
                        </button>
                      </div>
                    </div>

                    <!-- Número amarillo (col-2 en grandes, col-3 en medianas, col-12 en móviles) -->
                    <div class="col-lg-2 col-md-3 col-12 d-flex align-items-center">
                      <span id="CantidadTabla" class="bg-warning d-flex align-items-center justify-content-center w-100 h-100 py-2 rounded fw-bold text-center" style="font-size: 1rem;">
                        0
                      </span>
                    </div>
                  </div>

                  <!-- /.card-header -->
                  <div class="row">
                    <div class="col-lg-12 table-responsive">
                      <div class="table-responsive">
                        <table id="tabla_pedido" class="table table-striped display">
                          <!-- <table id="TablaProductos" class="table table-striped"> -->
                          <thead>
                            <tr style="text-align: center;">
                              <th class="col-sm-1"></th>
                              <th class="col-sm-2">CODIGO</th>
                              <th class="col-sm-1">CANTIDAD</th>
                              <th class="col-sm-2">PRECIO LISTA</th>
                              <th class="col-sm-2">PRECIO UNIT DSTO.</th>
                              <th class="col-sm-2">TOTAL</th>
                            </tr>
                          </thead>
                          <tbody>


                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-sm-9">
                      <div class="row">
                        <div class="col-sm-12">
                          <div class="form-group">
                            <textarea class="form-control" rows="6" cols="100" id="txtObservaciones" name="txtObservaciones" placeholder="Observaciones de pagos u otros referentes" oninput="this.value = this.value.toUpperCase()"></textarea>
                          </div>
                        </div>
                        <!-- <div class="col-sm-2">
                          <label for="txt_subtotal">Estado del documento </label>
                          <div class="form-group">
                            <input id="PedidoConfirmado" name="PedidoConfirmado" type="checkbox" checked data-toggle="toggle" data-on="CONFIRMADO" data-off="EN ESPERA" data-onstyle="primary" data-offstyle="danger" data-width="140">
                          </div>
                        </div> -->
                        <!-- <div class="row float-right">
                      <div class="col-lg-1 float-right">
                        <div class="form-group">
                          <input id="ckIVA" type="checkbox" checked data-toggle="toggle" data-on="CON IVA" data-off="SIN IVA" data-onstyle="success" data-offstyle="warning">
                        </div>
                      </div>
                      </div> -->


                        <!-- <button type="button" class="btn bg-gradient-danger btn-sm" onclick="estadoCheck();"><i class="fa fa-hand-holding-usd"></i>&nbsp; estado Check</button> -->
                      </div>
                    </div>

                    <div class="col-sm-3">
                      <div class="row mt-4">
                        <div class="row">

                          <div class="col-sm-12 input-group mb-3">
                            <div class="input-group-prepend">
                              <span for="txt_subtotal" class="input-group-text  ">Valor Venta</span>
                            </div>
                            <input type="text" style="text-align: right;" class="form-control" id="txt_subtotal" name="txt_subtotal" placeholder="" readonly>
                          </div>

                          <div class="col-sm-12 input-group mb-3">
                            <div class="input-group-prepend">
                              <span class="input-group-text">I.G.V. (18%)</span>
                            </div>
                            <input type="text" style="text-align: right;" class="form-control" id="txt_igv" name="txt_igv" placeholder="" readonly>
                          </div>

                          <div class="col-sm-12 input-group mb-3">
                            <div class="input-group-prepend">
                              <span class="input-group-text">Importe Total</span>
                            </div>
                            <input type="text" style="text-align: right;" class="form-control" id="txt_total" name="txt_total" placeholder="" readonly>
                          </div>

                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-sm-12">
                  <div class="form-group">
                    <button type="button" id="btnRealizarModificacion" class="btn btn-block bg-warning color-palette btn-sm"><i class="fa fa-save"></i>&nbsp; Realizar Modificación</button>
                    <!-- <button type="button" class="btn btn-block bg-gradient-danger btn-sm" data-toggle="modal" data-target="#modalImpresion"> imprimir</button> -->
                  </div>
                </div>
              </div>
              <!-- </form> -->
            </div>
          </div>
        </div>
        <!--/. container-fluid -->
      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->


    <div class="modal fade" id="ListadoCliente">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Listado de Clientes</h4>
          </div>
          <div class="modal-body">
            <div class="card-body table-responsive p-0">
              <table id="TableClientes" class="table table-hover text-nowrap">
                <thead>
                  <tr style="text-align: center;">
                    <th></th>
                    <th>CODIGO </th>
                    <th>CLIENTE Y/O RAZON SOCIAL: </th>
                    <th>Nº DOCUMENTO: </th>
                    <th>UBICACIÓN: </th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  require_once("../class/Clientes.class.php");
                  $obj = new Clientes();
                  $lista = $obj->ListarModuloCliente();
                  foreach ($lista as $campo) {
                    $idClientes = $campo["idCliente"];
                    $TipoDocumento = $campo["DescripcionTipoDocumentoClientes"];
                    $NumeroDocumento = $campo["NumeroDocumento_Cliente"];
                    $RazonSocial_Cliente = $campo["RazonSocial_Cliente"];
                    $direccion = $campo["Direccion_Cliente"];
                    $Comuna_Cliente = $campo["Distrito_Cliente"] . " - " . $campo["Provincia_Cliente"];
                    $CiudadRegion_Cliente = $campo["Departamento_Cliente"];
                    $Contacto_Cliente = $campo["Contacto_Cliente"];
                    $Celular_Cliente = $campo["Celular_Cliente"];
                    $Celular2_Cliente = $campo["Celular2_Cliente"];
                    $Email_Cliente = $campo["Email_Cliente"];
                  ?>
                    <tr>
                      <td class="project-actions" style="text-align: center;">
                        <a class="btn bg-gradient-success btn-sm" onclick="SeleccionarUsuario('<?php echo $idClientes ?>')">
                          <i class="fas fa-check"></i>
                        </a>
                      </td>
                      <td style="text-align: center;"><?php echo $idClientes; ?></td>
                      <td><?php echo $RazonSocial_Cliente; ?></td>
                      <td style="text-align: center;"><?php echo $TipoDocumento . ":  " . $NumeroDocumento; ?></td>
                      <td style="text-align: center;"><?php echo $direccion . "<br> " . $Comuna_Cliente . " - " . $CiudadRegion_Cliente ?></td>
                    </tr>
                  <?php } ?>

                </tbody>
              </table>
            </div>


          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-default" onclick="LimpiarFormulario()" data-dismiss="modal">Cerrar</button>
            <!-- <button type="button" id="RegistrarNuevoCliente" onclick="RegistrarNuevoCliente()" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
            <button type="button" id="ModificarCliente" style="display: none;" onclick="ModificarCliente()" class="btn btn-warning"><i class="fas fa-edit"></i> Modificar</button> -->
          </div>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->


    <div class="modal fade" id="AgregarProductos">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Agregar Productos</h5>
          </div>
          <div class="modal-body">
            <form id="frmDetalleProducto" role="form" enctype="multipart/form-data" name="frmDetalleProducto">
              <div class="row">
                <div class="col-sm-12">
                  <div class="form-group">
                    <select class="select2" id="cboProductos" name="cboProductos" multiple="multiple" data-placeholder="Ingrese el código del producto..." autofocus>
                      <?php
                      require_once("../class/Productos.class.php");
                      $obj = new Productos();
                      $lista = $obj->ListarProductosVentas();
                      foreach ($lista as $campo) {
                        $idProductos = $campo["Codigo"];
                        $idReferencia = $campo["CodigoReferencia"];
                        $CategoriaProducto = $campo["DescripcionCategoriaProductos"];
                        $Combustible = $campo["Combustible"];
                        $Marca = $campo["Marca"];
                        $Modelo = $campo["Modelo"];
                        $Motor = $campo["Motor"];
                        $Cilindrada = $campo["Cilindrada"];
                        $Stock_Productos = $campo["Stock_Productos"];
                        $PrecioNacional_Productos = $campo["PrecioNacional_Productos"];
                      ?>
                        <option id="<?php echo $idProductos; ?>" value="<?php echo $idProductos; ?>"><?php echo $idProductos . " | " . $idReferencia . " | " . $CategoriaProducto . " | "  . $Combustible . " | " . $Marca . " " . $Modelo . " | " . $Motor . " | " . $Cilindrada; ?></option>
                      <?php }
                      ?>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-sm-2">
                  <div class="form-group">
                    <label>Código Producto</label>
                    <input type="text" class="form-control" id="txtCodigoProducto" name="txtCodigoProducto" placeholder="" style="text-align: center;" disabled>
                  </div>
                </div>
                <div class="col-sm-2">
                  <div class="form-group">
                    <label>Referencia</label>
                    <input type="text" class="form-control" id="inputCodigoReferencia" name="inputCodigoReferencia" placeholder="" style="text-align: center;" disabled>
                  </div>
                </div>
                <div class="col-sm-2">
                  <div class="form-group">
                    <label>Stock</label>
                    <input type="text" class="form-control" id="txtStock" name="txtStock" placeholder="" style="text-align: center;" disabled>
                  </div>
                </div>
                <div class="col-sm-2">
                  <div class="form-group">
                    <label>¿Suspendido?</label>
                    <input type="text" class="form-control" id="inputSuspendido" name="inputSuspendido" style="text-align: center;" placeholder="" disabled>
                  </div>
                </div>
                <div class="col-sm-2">
                  <div class="form-group">
                    <label>Precio de Lista</label>
                    <input type="text" class="form-control" id="PrecioVenta" name="PrecioVenta" style="text-align: center;" placeholder="">
                  </div>
                </div>
                <div class="col-sm-2">
                  <div class="form-group">
                    <label>Cantidad</label>
                    <input type="text" class="form-control" id="Cantidad" name="Cantidad" style="text-align: center;" placeholder="" required>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <!-- <button type="button" class="btn btn-default" onclick="LimpiarFormulario()" data-dismiss="modal">Cerrar</button> -->
            <button type="button" id="AgregarNuevoProducto" class="btn btn-block bg-gradient-success btn-sm"><i class="fas fa-plus"></i> &nbsp;&nbsp; Agregar más productos</button>
          </div>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->

    <div class="modal fade" id="editarElementoModal" tabindex="-1" aria-labelledby="editarElementoModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editarElementoModalLabel">Editar Elemento del Pedido</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <!-- Puedes quitar el <form> si ya no necesitas el submit -->
            <div class="form-group">
              <label for="inputArticuloEditar">Código del Producto</label>
              <input type="text" class="form-control" id="inputArticuloEditar" readonly>
            </div>
            <div class="form-group">
              <label for="inputCantidadEditar">Cantidad</label>
              <input type="number" class="form-control" id="inputCantidadEditar" required>
            </div>
            <div class="form-group">
              <label for="inputPrecioEditar">Precio Lista Unitario</label>
              <input type="number" step="0.001" class="form-control" id="inputPrecioEditar" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" id="btnGuardarCambiosEdit" class="btn btn-primary">Guardar Cambios</button>
          </div>
        </div>
      </div>
    </div>


    <!-- INICIO DE MODAL IMPRESION -->

    <!-- Button trigger modal -->

    <!-- Modal -->
    <div class="modal fade" id="modalImpresion" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalCenterTitle">Seleccione tipo de Impresion</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <button type="button" class="btn btn-block bg-gradient-info btn-sm" onclick="Imprimir();"><i class="fas fa-file-pdf" onclick=""></i> &nbsp; Formato A4</button>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <button type="button" class="btn btn-block bg-gradient-info btn-sm" onclick="Imprimir();" disabled><i class="fas fa-file-pdf" onclick=""></i> &nbsp; Formato Carta </button>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <button type="button" class="btn btn-block bg-gradient-info btn-sm" onclick="Imprimir();" disabled><i class="fas fa-file-pdf" onclick=""></i> &nbsp; Formato Ticket 80mm</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FIN DE MODAL IMPRESION -->

    <!-- Modal de Validación -->
    
    <d<div class="modal fade" id="modalValidacion" tabindex="-1" role="dialog" aria-labelledby="modalValidacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalValidacionLabel">Autorización de Validación</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Contenedor centrado para los botones -->
        <div class="text-center mb-3">
          <div class="btn-group" role="group" aria-label="Opciones de validación">
            <button type="button" id="btnClaveAdmin" class="btn btn-warning mr-2">
              <i class="fa-solid fa-key"></i> &nbsp;Clave Administrador
            </button>
            <button type="button" id="btnEnviarSolicitud" class="btn btn-info">
              <i class="fa-regular fa-paper-plane"></i> &nbsp; Enviar Solicitud
            </button>
          </div>
        </div>

        <!-- Contenedores de campos dinámicos -->
        <div id="divClaveAdmin" style="display: none;">
          <label for="txtClaveAdmin">Clave Administrador:</label>
          <input type="password" id="txtClaveAdmin" class="form-control" placeholder="Ingrese la clave">
        </div>
        <div id="divEnviarSolicitud" style="display: none;">
          <label for="txtMotivo">Motivo de la Validación:</label>
          <textarea id="txtMotivo" class="form-control" rows="3" placeholder="Ingrese el motivo"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" id="btnConfirmarValidacion" class="btn btn-primary">Confirmar</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>





    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
      <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->

    <?php include "../ext/footer.php"; ?>
  </div>


  <?php include "../lib/botScript.php"; ?>
  <!-- Ventas -->
  <script language="javascript" src="../js/VentasModificar.js"></script>

  <script>
    // Asumiendo que $idTipoUsuario viene de la sesión y se inyecta en JavaScript
    var idTipoUsuario = <?php echo $idTipoUsuario; ?>;
    if (idTipoUsuario === 1 || idTipoUsuario === 3) {
      Swal.fire({
        icon: 'info',
        title: 'Atención',
        html: '<span style="color: white;">Para proceder con la modificación de un documento, es necesaria la autenticación de un administrador registrado. Los cambios se aplicarán una vez que el administrador los valide.</span>',
        confirmButtonText: 'Entendido'
      });
    }


    $(function() {
      //Initialize Select2 Elements
      $('.select2').select2()

      //Initialize Select2 Elements
      $('.select2bs4').select2({
        theme: 'bootstrap4'
      })
    });

    $(function() {
      $("#TableClientes").DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "paging": true,
        "ordering": false,
        "language": idioma_espanol,
        "info": true,
        "buttons": ["excel"]
      }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    });



    var idioma_espanol = {
      "sProcessing": "Procesando...",
      "sLengthMenu": "Mostrar _MENU_ registros",
      "sZeroRecords": "No se encontraron resultados",
      "sEmptyTable": "No existen datos registrados",
      "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
      "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
      "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
      "sInfoPostFix": "",
      "sSearch": "Buscar:",
      "sUrl": "",
      "sInfoThousands": ",",
      "sLoadingRecords": "Cargando...",
      "oPaginate": {
        "sFirst": "Primero",
        "sLast": "Último",
        "sNext": "Siguiente",
        "sPrevious": "Anterior"
      },
      "oAria": {
        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
      },
      "buttons": {
        "copy": "Copiar",
        "colvis": "Visibilidad",
        "print": "Imprimir"
      }
    }

    $(document).ready(function() {
      $('[data-toggle="tooltip"]').tooltip();
    });
  </script>



</body>

</html>