<?php
// VentasActualizar_ValidacionAdmin.php

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de logs
date_default_timezone_set('America/Lima');
ini_set("log_errors", 1);
ini_set("error_log", "../log/log_VentasModificarController.log");

// Incluir las clases necesarias
require_once '../class/Ventas.class.php';
require_once '../class/VentasModificaciones_HistorialBitacora.class.php';
require_once '../class/Productos.class.php';
require_once '../class/MovimientoAlmacen.class.php';
require_once '../class/Produccion.class.php';

// Validación de sesión del usuario
if (!isset($_SESSION['usuario'])) {
    error_log("Acceso denegado: Usuario no autenticado.");
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada. Inicia sesión nuevamente.']);
    exit;
}

// Inicialización de respuesta por defecto
$response = ['status' => 'error', 'message' => 'Error en el proceso'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar que los datos esperados están presentes
        if (!isset($_POST['cabecera']) || !isset($_POST['detalles']) || !isset($_POST['TipoDocumento'])) {
            error_log("Error: Faltan parámetros en la solicitud.");
            throw new Exception("Datos incompletos. Verifique la solicitud.");
        }

        // Decodificar JSON recibido
        $cabecera = json_decode($_POST['cabecera'], true);
        $detalles = json_decode($_POST['detalles'], true);
        $TipoDocumento = intval($_POST['TipoDocumento']);

        if (!$cabecera || !$detalles) {
            error_log("Error: Datos JSON malformateados.");
            throw new Exception("Formato de datos incorrecto.");
        }

        // Instanciar clases
        $ventasModificaciones = new VentasModificaciones_Inmediata();
        $productos = new Productos();
        $movimientoAlmacen = new MovimientoAlmacen();
        $produccion = new Produccion();
        $ventasModificacionesHistorial = new VentasModificaciones();



        // Obtener ID de la cabecera de venta
        $idVentaCabecera = intval($cabecera['idVentaCabecera']);

        if ($idVentaCabecera <= 0) {
            error_log("Error: ID de venta inválido.");
            throw new Exception("ID de venta no válido.");
        }

        // 1️⃣ **Respaldar la venta en historial antes de modificar**
        $ventasModificacionesHistorial->RespaldaVentaCabecera($idVentaCabecera, "Modificación inmediata");

        // 2️⃣ **Revertir stock y eliminar detalles antiguos**
        $detallesAnteriores = $ventasModificaciones->getDetallesAnteriores($idVentaCabecera);
        
        foreach ($detallesAnteriores as $detOld) {
            $codigoProducto = $detOld['idProducto'];
            $cantidadAnterior = intval($detOld['cantidad_ventadetalle']);
            
            // Devolver stock del producto eliminado
            $productos->SumarStockProducto($codigoProducto, $cantidadAnterior);

            // Anular movimientos anteriores en el almacén
            $movimientoAlmacen->AnularMovimiento($idVentaCabecera, $codigoProducto, 'SALIDA', $cantidadAnterior);
        }

        // Eliminar los detalles antiguos
        $ventasModificaciones->EliminarDetallesVenta($idVentaCabecera);

        // 3️⃣ **Registrar nuevos detalles**
        foreach ($detalles as $detalle) {
            $codigoProducto = $detalle['articulo'];
            $cantidadNueva = intval($detalle['cantidad']);
            $precioUnitario = floatval($detalle['precioUnitario']);
            $precioDescuento = floatval($detalle['precioDescuento']);
            $totalFila = floatval($detalle['totalTabla']);

            // Insertar nuevo detalle
            $ventasModificaciones->InsertarNuevoDetalle(
                $idVentaCabecera, 
                $codigoProducto, 
                $cantidadNueva, 
                $precioUnitario, 
                $precioDescuento, 
                $totalFila
            );

            // Actualizar stock
            $productos->RestarStockProductos($codigoProducto, $cantidadNueva);

            // Registrar movimiento en el almacén
            $movimientoAlmacen->RegistrarMovimientoAlmacen($idVentaCabecera, $codigoProducto, 'SALIDA', $cantidadNueva);
        }

        // 4️⃣ **Actualizar la cabecera con los nuevos datos**
        $ventasModificaciones->ActualizarCabeceraVenta($idVentaCabecera, $cabecera);

        // 5️⃣ **Confirmar transacción y devolver éxito**
        $response['status'] = 'success';
        $response['message'] = 'Venta modificada correctamente.';
    } else {
        error_log("Solicitud inválida: Método incorrecto.");
        throw new Exception("Método de solicitud no permitido.");
    }
} catch (Exception $e) {
    error_log("Error en VentasModificarController: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

// Enviar respuesta JSON
echo json_encode($response);
