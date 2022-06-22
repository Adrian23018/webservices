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
		}

		$result["success"] = true;

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>
