<?php
// VentasModificaciones_Inmediata.class.php
require_once "ConexionBD.class.php";
require_once "AccesoBD.class.php";
require_once "Ventas.class.php";
require_once "VentasModificaciones_HistorialBitacora.class.php"; // Respaldo a Historial
require_once "Productos.class.php";
require_once "MovimientoAlmacen.class.php";
require_once "Produccion.class.php";

/**
 * Constantes para los tipos de documento
 */
if (!defined('ORDEN_COMPRA')) {
    define('ORDEN_COMPRA', 1);
}
if (!defined('DEVOLUCION')) {
    define('DEVOLUCION', 2);
}

class VentasModificaciones_Inmediata
{
    private $cn;
    private $objVentas;
    private $objVentasHistorial;
    private $objProductos;
    private $objMovimiento;
    private $objProduccion;

    public function __construct()
    {
        try {
            $con = ConexionBD::CadenaCN();
            $this->cn = AccesoBD::ConexionBD($con);

            $this->objVentas          = new Ventas();
            $this->objVentasHistorial = new VentasModificaciones(); // Maneja Historial
            $this->objProductos       = new Productos();
            $this->objMovimiento      = new MovimientoAlmacen();
            $this->objProduccion      = new Produccion();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Método principal que realiza la modificación inmediata:
     *  1) Respaldar cabecera y detalle anterior.
     *  2) Revertir stock y anular movimientos/producción anteriores.
     *  3) Eliminar los detalles antiguos.
     *  4) Insertar los nuevos detalles.
     *  5) Aplicar la nueva lógica de stock (registrar movimientos y/o producción).
     *  6) Commit final.
     */
    public function ProcesarModificacionInmediata(array $cabecera, array $detallesNuevos, int $TipoDocumento) {
        mysqli_autocommit($this->cn, false); // Iniciar la transacción
        try {
            $idVentaCabecera = (int)$cabecera['idVentaCabecera'];
    
            // 1) Respaldar venta anterior
            $this->objVentasHistorial->RespaldaVentaCabecera($idVentaCabecera, "Modificación inmediata");
            $detallesAnteriores = $this->getDetallesAnteriores($idVentaCabecera);
    
            // 2) Restaurar stock y anular movimientos previos
            foreach ($detallesAnteriores as $detOld) {
                $codigo = $detOld['idProducto'];
                $cantOld = (int)$detOld['cantidad_ventadetalle'];
    
                if ($TipoDocumento === ORDEN_COMPRA) {
                    $this->objProductos->SumarStockProducto($codigo, $cantOld);
                    $this->objMovimiento->AnularMovimiento($idVentaCabecera, $codigo, 'SALIDA', $cantOld);
                    $this->objProduccion->AnularProduccion($idVentaCabecera, $codigo);
                } elseif ($TipoDocumento === DEVOLUCION) {
                    $this->objProductos->RestarStockProductos($codigo, $cantOld);
                    $this->objMovimiento->AnularMovimiento($idVentaCabecera, $codigo, 'INGRESO', $cantOld);
                }
            }
    
            // 3) Eliminar los detalles antiguos
            $sqlDelete = "DELETE FROM VentaDetalle WHERE idVentaCabecera = $idVentaCabecera";
            AccesoBD::OtroSQL($this->cn, $sqlDelete);
    
            // 4) Insertar nuevos detalles
            foreach ($detallesNuevos as $detNew) {
                $codigo = $detNew['articulo'];
                $cantidad = (int)$detNew['cantidad'];
                $precioUnit = (float)$detNew['precioUnitario'];
                $precioDesc = (float)$detNew['precioDescuento'];
                $totalFila = (float)$detNew['totalTabla'];
    
                $sqlInsert = "INSERT INTO VentaDetalle (idVentaCabecera, idProducto, cantidad_ventadetalle, preciounitario__ventadetalle, precioDescuento_ventadetalle, total_ventadetalle) 
                              VALUES ($idVentaCabecera, '$codigo', $cantidad, '$precioUnit', '$precioDesc', '$totalFila')";
                AccesoBD::Insertar($this->cn, $sqlInsert);
            }
    
            // 5) Registrar nuevos movimientos y producción
            foreach ($detallesNuevos as $detNew) {
                $codigo = $detNew['articulo'];
                $cantidad = (int)$detNew['cantidad'];
    
                if ($TipoDocumento === ORDEN_COMPRA) {
                    $this->objProductos->RestarStockProductos($codigo, $cantidad);
                    $this->objMovimiento->RegistrarMovimientoAlmacen($idVentaCabecera, $codigo, 'SALIDA', $cantidad);
    
                    // Registrar en producción solo si pertenece a la categoría 1
                    if ($this->objProductos->ObtenerCategoriaProducto($codigo) == 1) {
                        $this->objProduccion->RegistrarPedidoProduccion($idVentaCabecera, $codigo, $cantidad);
                    }
                } elseif ($TipoDocumento === DEVOLUCION) {
                    $this->objProductos->SumarStockProducto($codigo, $cantidad);
                    $this->objMovimiento->RegistrarMovimientoAlmacen($idVentaCabecera, $codigo, 'INGRESO', $cantidad);
                }
            }
    
            mysqli_commit($this->cn);
            mysqli_autocommit($this->cn, true);
            return true;
        } catch (Exception $e) {
            mysqli_rollback($this->cn);
            mysqli_autocommit($this->cn, true);
            error_log("Error en modificación inmediata: " . $e->getMessage());
            return false;
        }
    }
    

    /**
     * Obtiene los detalles anteriores (los que existen actualmente en VentaDetalle)
     * para poder revertirlos.
     */
    public function getDetallesAnteriores(int $idVentaCabecera): array
    {
        try {
            $sql = "SELECT *
                    FROM VentaDetalle
                    WHERE idVentaCabecera = $idVentaCabecera";
            return AccesoBD::Consultar($this->cn, $sql);
        } catch (Exception $e) {
            error_log("Error en getDetallesAnteriores: " . $e->getMessage());
            throw $e;
        }
    }

    public function EliminarDetallesVenta($idVentaCabecera) {
        $sql = "DELETE FROM VentaDetalle WHERE idVentaCabecera = $idVentaCabecera";
        return AccesoBD::OtroSQL($this->cn, $sql);
    }

    public function InsertarNuevoDetalle($idVentaCabecera, $codigoProducto, $cantidad, $precioUnitario, $precioDescuento, $totalFila) {
        $sql = "INSERT INTO VentaDetalle (idVentaCabecera, idProducto, cantidad_ventadetalle, preciounitario__ventadetalle, precioDescuento_ventadetalle, total_ventadetalle) 
                VALUES ($idVentaCabecera, '$codigoProducto', $cantidad, '$precioUnitario', '$precioDescuento', '$totalFila')";
        return AccesoBD::Insertar($this->cn, $sql);
    }

    public function ActualizarCabeceraVenta($idVentaCabecera, $cabecera) {
        $sql = "UPDATE VentaCabecera SET 
                total_ventacabecera = {$cabecera['total_ventacabecera']}, 
                comentario_ventacabecera = '{$cabecera['comentario_ventacabecera']}'
                WHERE idVentaCabecera = $idVentaCabecera";
        return AccesoBD::OtroSQL($this->cn, $sql);
    }
    
}
