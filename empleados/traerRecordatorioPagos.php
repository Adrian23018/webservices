<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
 		$en_id = $_POST['en_id'];

		$sqlNotificacion =  sprintf("SELECT * FROM tbl_empleados_notificaciones 
										INNER JOIN tbl_empleados ON emp_id=en_emp_id 
										WHERE en_id=%s AND en_usu_id=%s",
			GetSQLValueString($en_id, "double"),
			GetSQLValueString($id, "double")
		);
		$rs_sqlNotificacion = mysqli_query($_conection->connect(), $sqlNotificacion);
		$arrayNotificaciones = array();
		$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);
		list($yearHoy, $mesHoy, $diaHoy) = explode('-',$fechaSimulacion);

		while( $row_sqlNotificacion = mysqli_fetch_assoc($rs_sqlNotificacion) ){
			$result["valor"] = $row_sqlNotificacion["en_valor"];
			
			$result["ss"] = $result["se"] = $result["ssp"] = $result["sep"] = 0;
			if( $row_sqlNotificacion['emp_contribuciones'] == 'si'){
				$result["ss"] = $row_sqlNotificacion["en_ss"];
				$result["se"] = $row_sqlNotificacion["en_se"];
				$result["ssp"] = round($result["valor"] * 12.25 / 100, 2);
				$result["sep"] = round($result["valor"] * 1.5 / 100, 2);
			}
			
			$result["xiii"] = $row_sqlNotificacion["en_xiii"];
			$result["xiii_ss"] = $row_sqlNotificacion["en_xiii_ss"];
			$result["xiii_total"] = $row_sqlNotificacion["en_xiii_total"];

			$result["descuento"] = $row_sqlNotificacion["en_descuento"];

			$result["au_numeros"] = $row_sqlNotificacion["en_au_numeros"];
			$result["au_descuento"] = $row_sqlNotificacion["en_au_descuento"];
			$result["vac_descuento"] = $row_sqlNotificacion["en_vac_descuento"];

			$result["total"] = $row_sqlNotificacion["en_total"];
			$result["fecha_empieza"] = $row_sqlNotificacion["en_fecha_empieza"];
			$result["fecha"] = $row_sqlNotificacion["en_fecha"];
			if ( $row_sqlNotificacion["en_fecha_empieza"] == $row_sqlNotificacion["en_fecha"] ) {
				list($year, $month, $day) = explode("-", $row_sqlNotificacion["en_fecha_empieza"]);
				$result["fechaMostrar"] = $day . " de " . $arrayMesesGlobal[(int)$month] . " de " . $year;
			}else{
				list($yearE, $monthE, $dayE) = explode("-", $row_sqlNotificacion["en_fecha_empieza"]);
				list($yearT, $monthT, $dayT) = explode("-", $row_sqlNotificacion["en_fecha"]);
				if ( $monthE == $monthT ) {
					$result["fechaMostrar"] = $dayE . " al " . $dayT . " de " . $arrayMesesGlobal[(int)$monthT] . " de " . $yearT;
				}elseif ( $yearE == $yearT ) {
					$result["fechaMostrar"] = $dayE . " de " . $arrayMesesGlobal[(int)$monthE] . " al " . $dayT . " de " . $arrayMesesGlobal[(int)$monthT] . " de " . $yearT;
				}else{
					$result["fechaMostrar"] = $dayE . " de " . $arrayMesesGlobal[(int)$monthE] . " de " . $yearE . " al " . $dayT . " de " . $arrayMesesGlobal[(int)$monthT] . " de " . $yearT;
				}
			}
			$result["do_nombre"] = utf8_encode($row_sqlNotificacion['emp_do_nombre']);
			$result["dias_vacaciones"] = diferenciaDias($result["fecha_empieza"], $result["fecha"]) + 1;
			// crearConfirmacionPagoSalario($row_sqlNotificacion["en_id"], $_conection);
		}

		$result["success"] = true;

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>
