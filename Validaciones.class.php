<?php
require_once "ConexionBD.class.php";
require_once "AccesoBD.class.php";

class Validaciones {
    
    private $cn;
	
	function __construct(){
		try{
			$con = ConexionBD::CadenaCN();
			$this->cn = AccesoBD::ConexionBD($con);
		} catch(Exception $e){
			throw $e;
		}
	}

    /**
     * Registra la solicitud de validación enviada por el operario.
     * 
     * Se insertan en la tabla:
     * - TipoDocumento: ID del tipo de documento que se está modificando.
     * - NumeroDocumento: idVentaCabecera (número del documento o cabecera de venta).
     * - id_Solicitante: ID del usuario solicitante, obtenido desde la sesión.
     * - id_admin: se deja NULL, ya que se completará cuando el administrador apruebe.
     * - MotivoSolicitud: texto ingresado en el área de validación.
     * - fecha_actualizacion: se inserta NULL para que quede en blanco.
     * - comentario: se deja como cadena vacía.
     * - estado: se fija en 'Pendiente'.
     * 
     * @param int    $TipoDocumento    ID del tipo de documento.
     * @param string $NumeroDocumento  idVentaCabecera.
     * @param int    $id_Solicitante   ID del usuario (obtenido de la sesión).
     * @param string $MotivoSolicitud  Motivo de la validación.
     * @return mixed  ID insertado o false en caso de error.
     */
    public function RegistrarValidacion_EnvioSolicitud_VentasModificar($TipoDocumento, $NumeroDocumento, $id_Solicitante, $MotivoSolicitud)
	{
		$sql = "INSERT INTO ValidacionesAdmin 
                (TipoDocumento, NumeroDocumento, id_Solicitante, id_admin, MotivoSolicitud, fecha_actualizacion, comentario, estado)
                VALUES (
                    $TipoDocumento, 
                    '$NumeroDocumento', 
                    $id_Solicitante, 
                    NULL,
                    '$MotivoSolicitud',
                    NULL,
                    '',
                    'Pendiente'
                )";
		$id  = AccesoBD::Insertar($this->cn, $sql);
		return $id;
	}
}
?>
