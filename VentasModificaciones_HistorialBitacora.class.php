<?php
require_once "ConexionBD.class.php";
require_once "AccesoBD.class.php";

class VentasModificaciones {
    private $cn;

    function __construct() {
        try {
            $con = ConexionBD::CadenaCN();
            $this->cn = AccesoBD::ConexionBD($con);
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Inserta la cabecera actual en el historial, asignando la nueva versión
    public function RespaldaVentaCabecera($idVentaCabecera, $motivo) {
        $sql = '';
        try {
            $idVentaCabecera = intval($idVentaCabecera);

            // Consultar versión máxima
            $sqlVersion = "SELECT MAX(version_documento) AS maxVersion
                           FROM VentaCabecera_Historial
                           WHERE idVentaCabecera = $idVentaCabecera";
            $result = AccesoBD::Consultar($this->cn, $sqlVersion);
            $maxVersion = (!empty($result) && $result[0]['maxVersion'] !== null)
                          ? (int)$result[0]['maxVersion']
                          : 0;
            $newVersion = $maxVersion + 1;

            // Usuario de modificación (session)
            $usuario_modificacion = isset($_SESSION['usuario']) ? (int)$_SESSION['usuario'] : 0;

            $sql = "INSERT INTO VentaCabecera_Historial (
                        idVentaCabecera,
                        fecha_modificacion,
                        version_documento,
                        idEmisor,
                        idVendedor,
                        TipoDocumento,
                        TipoDescuento,
                        idCliente,
                        valventa_ventacabecera,
                        igv_ventacabecera,
                        total_ventacabecera,
                        estado_ventacabecera,
                        comentario_ventacabecera,
                        usuario_modificacion,
                        motivo
                    )
                    SELECT
                        idVentaCabecera,
                        NOW(),
                        $newVersion,
                        idEmisor,
                        idVendedor,
                        TipoDocumento,
                        TipoDescuento,
                        idCliente,
                        valventa_ventacabecera,
                        igv_ventacabecera,
                        total_ventacabecera,
                        estado_ventacabecera,
                        comentario_ventacabecera,
                        $usuario_modificacion,
                        '$motivo'
                    FROM VentaCabecera
                    WHERE idVentaCabecera = $idVentaCabecera";

            $idInsertado = AccesoBD::Insertar($this->cn, $sql);
            if (!$idInsertado) {
                throw new \Exception("La inserción en VentaCabecera_Historial devolvió false");
            }
            return $idInsertado;
        } catch (Exception $e) {
            error_log("Error en RespaldaVentaCabecera: " . $e->getMessage());
            throw $e;
        }
    }

    // Inserta el detalle actual en el historial (por cada producto) con versionado
    public function RespaldaVentaDetalle($idVentaCabecera, $idProducto, $motivo) {
        $sql = '';
        try {
            $idVentaCabecera = intval($idVentaCabecera);
            // Escapar el idProducto
            $idProducto = $this->cn->real_escape_string($idProducto);
            
            // Consulta la versión máxima actual para este detalle
            $sqlVersion = "SELECT MAX(version_documento) AS maxVersion 
                           FROM VentaDetalle_Historial 
                           WHERE idVentaCabecera = $idVentaCabecera AND idProducto = '$idProducto'";
            $result = AccesoBD::Consultar($this->cn, $sqlVersion);
            $maxVersion = (isset($result[0]['maxVersion']) && $result[0]['maxVersion'] !== null) ? intval($result[0]['maxVersion']) : 0;
            $newVersion = $maxVersion + 1;
            
            // Obtener el usuario de modificación desde la sesión
            $usuario_modificacion = isset($_SESSION['usuario']) ? intval($_SESSION['usuario']) : 0;
            
            error_log("RespaldaVentaDetalle: idVentaCabecera = $idVentaCabecera, idProducto = $idProducto, maxVersion = $maxVersion, newVersion = $newVersion");
            
            $sql = "INSERT INTO VentaDetalle_Historial (
                        idVentaCabecera, 
                        idProducto, 
                        cantidad_ventadetalle, 
                        preciounitario_ventadetalle, 
                        precioDescuento_ventadetalle, 
                        total_ventadetalle, 
                        fecha_modificacion, 
                        usuario_modificacion, 
                        motivo,
                        version_documento
                    )
                    SELECT 
                        idVentaCabecera, 
                        idProducto, 
                        cantidad_ventadetalle, 
                        preciounitario__ventadetalle, 
                        precioDescuento_ventadetalle, 
                        total_ventadetalle, 
                        NOW(), 
                        $usuario_modificacion,
                        '$motivo',
                        $newVersion
                    FROM VentaDetalle 
                    WHERE idVentaCabecera = $idVentaCabecera AND idProducto = '$idProducto'";
                    
            error_log("RespaldaVentaDetalle - Query: $sql");
            
            $idInsertado = AccesoBD::Insertar($this->cn, $sql);
            if (!$idInsertado) {
                throw new Exception("La inserción en VentaDetalle_Historial devolvió false");
            }
            return $idInsertado;
        } catch (Exception $e) {
            $mensaje = "Fecha: " . date("Y-m-d H:i:s") . "\n" .
                       "Archivo: " . $e->getFile() . "\n" .
                       "Línea: " . $e->getLine() . "\n" .
                       "SQL: " . (!empty($sql) ? $sql : 'N/A') . "\n" .
                       "Mensaje: " . $e->getMessage() . "\n\n";
            error_log($mensaje, 3, "../log/error_log_VentasModificaciones.log");
            throw $e;
        }
    }

    // Actualiza la tabla madre VentaCabecera a partir de la información respaldada en historial
    public function ActualizarVentaCabeceraDesdeBitacora($idValidacion) {
        $sql = '';
        try {
            $sql = "UPDATE VentaCabecera vc
                    JOIN VentaCabecera_Bitacora vcb ON vc.idVentaCabecera = vcb.idVentaCabecera
                    SET 
                        vc.version_documento       = vcb.version_documento, 
                        vc.idEmisor                = vcb.idEmisor,
                        vc.idVendedor              = vcb.idVendedor,
                        vc.TipoDocumento           = vcb.TipoDocumento,
                        vc.TipoDescuento           = vcb.TipoDescuento,
                        vc.idCliente               = vcb.idCliente,
                        vc.valventa_ventacabecera  = vcb.valventa_ventacabecera,
                        vc.igv_ventacabecera       = vcb.igv_ventacabecera,
                        vc.total_ventacabecera     = vcb.total_ventacabecera,
                        vc.estado_ventacabecera    = vcb.estado_ventacabecera,
                        vc.comentario_ventacabecera= vcb.comentario_ventacabecera,
                        vc.usuario_modificacion    = vcb.usuario_modificacion
                    WHERE vcb.idValidacion = $idValidacion";
            AccesoBD::OtroSQL($this->cn, $sql);
            return true;
        } catch (Exception $e) {
            $mensaje = "Fecha: " . date("Y-m-d H:i:s") . "\n" .
                       "Archivo: " . $e->getFile() . "\n" .
                       "Línea: " . $e->getLine() . "\n" .
                       "SQL: " . (!empty($sql) ? $sql : 'N/A') . "\n" .
                       "Mensaje: " . $e->getMessage() . "\n\n";
            error_log($mensaje, 3, "../log/error_log_VentasModificaciones.log");
            throw $e;
        }
    }

    // Elimina los registros de respaldo (bitácora) una vez que se ha aplicado la modificación
    public function LimpiarBitacoraVentaCabecera($idValidacion) {
        $sql = '';
        try {
            $sql = "DELETE FROM VentaCabecera_Bitacora WHERE idValidacion = $idValidacion";
            AccesoBD::OtroSQL($this->cn, $sql);
            return true;
        } catch (Exception $e) {
            $mensaje = "Fecha: " . date("Y-m-d H:i:s") . "\n" .
                       "Archivo: " . $e->getFile() . "\n" .
                       "Línea: " . $e->getLine() . "\n" .
                       "SQL: " . (!empty($sql) ? $sql : 'N/A') . "\n" .
                       "Mensaje: " . $e->getMessage() . "\n\n";
            error_log($mensaje, 3, "../log/error_log_VentasModificaciones.log");
            throw $e;
        }
    }

    // Actualiza la tabla madre VentaDetalle a partir de la información respaldada en historial
    public function ActualizarVentaDetalleDesdeBitacora($idValidacion) {
        $sql = '';
        try {
            $sql = "UPDATE VentaDetalle vd
                    JOIN VentaDetalle_Historial vdh ON vd.idVentaDetalle = vdh.idVentaDetalle
                    SET 
                        vd.cantidad_ventadetalle      = vdh.cantidad_ventadetalle,
                        vd.preciounitario_ventadetalle = vdh.preciounitario_ventadetalle,
                        vd.precioDescuento_ventadetalle = vdh.precioDescuento_ventadetalle,
                        vd.total_ventadetalle         = vdh.total_ventadetalle,
                        vd.usuario_modificacion       = vdh.usuario_modificacion
                    WHERE vdh.idValidacion = $idValidacion";
            AccesoBD::OtroSQL($this->cn, $sql);
            return true;
        } catch (Exception $e) {
            $mensaje = "Fecha: " . date("Y-m-d H:i:s") . "\n" .
                       "Archivo: " . $e->getFile() . "\n" .
                       "Línea: " . $e->getLine() . "\n" .
                       "SQL: " . (!empty($sql) ? $sql : 'N/A') . "\n" .
                       "Mensaje: " . $e->getMessage() . "\n\n";
            error_log($mensaje, 3, "../log/error_log_VentasModificaciones.log");
            throw $e;
        }
    }

    // Elimina los registros de respaldo de detalles
    public function LimpiarBitacoraVentaDetalle($idValidacion) {
        $sql = '';
        try {
            $sql = "DELETE FROM VentaDetalle_Historial WHERE idValidacion = $idValidacion";
            AccesoBD::OtroSQL($this->cn, $sql);
            return true;
        } catch (Exception $e) {
            $mensaje = "Fecha: " . date("Y-m-d H:i:s") . "\n" .
                       "Archivo: " . $e->getFile() . "\n" .
                       "Línea: " . $e->getLine() . "\n" .
                       "SQL: " . (!empty($sql) ? $sql : 'N/A') . "\n" .
                       "Mensaje: " . $e->getMessage() . "\n\n";
            error_log($mensaje, 3, "../log/error_log_VentasModificaciones.log");
            throw $e;
        }
    }

    // Método para procesar la aprobación (en este caso, se usa en el flujo de "Enviar Solicitud")
    public function ProcesarAprobacion($idValidacion, $motivo) {
        $sql = '';
        try {
            mysqli_autocommit($this->cn, false);

            // Respalda cabecera
            $sqlVC = "SELECT DISTINCT idVentaCabecera FROM VentaCabecera_Bitacora WHERE idValidacion = $idValidacion";
            $listaVC = AccesoBD::Consultar($this->cn, $sqlVC);
            if (!empty($listaVC)) {
                foreach ($listaVC as $rowVC) {
                    $this->RespaldaVentaCabecera($rowVC['idVentaCabecera'], $motivo);
                }
            }

            // Respalda detalle
            $sqlVD = "SELECT DISTINCT idVentaCabecera, idProducto FROM VentaDetalle_Bitacora WHERE idValidacion = $idValidacion";
            $listaVD = AccesoBD::Consultar($this->cn, $sqlVD);
            if (!empty($listaVD)) {
                foreach ($listaVD as $rowVD) {
                    $this->RespaldaVentaDetalle($rowVD['idVentaCabecera'], $rowVD['idProducto'], $motivo);
                }
            }

            // Actualiza tablas madres a partir del respaldo
            $this->ActualizarVentaCabeceraDesdeBitacora($idValidacion);
            $this->ActualizarVentaDetalleDesdeBitacora($idValidacion);

            // Limpia los registros de respaldo
            $this->LimpiarBitacoraVentaCabecera($idValidacion);
            $this->LimpiarBitacoraVentaDetalle($idValidacion);

            mysqli_commit($this->cn);
            mysqli_autocommit($this->cn, true);
            return true;
        } catch (Exception $e) {
            mysqli_rollback($this->cn);
            mysqli_autocommit($this->cn, true);
            $mensaje = "Fecha: " . date("Y-m-d H:i:s") . "\n" .
                       "Archivo: " . $e->getFile() . "\n" .
                       "Línea: " . $e->getLine() . "\n" .
                       "SQL: " . (!empty($sql) ? $sql : 'N/A') . "\n" .
                       "Mensaje: " . $e->getMessage() . "\n\n";
            error_log($mensaje, 3, "../log/error_log_VentasModificaciones.log");
            throw $e;
        }
    }
}
?>
