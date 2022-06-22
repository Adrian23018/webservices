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

     	$sqlEmpNotiEstado =  sprintf("UPDATE tbl_empleados_notificaciones SET en_estado=%s WHERE en_id=%s AND en_usu_id=%s",
     		2,
     		GetSQLValueString($en_id, "double"),
     		GetSQLValueString($id, "double")
     	);
     	$rs_sqlEmpNotiEstado = mysqli_query($_conection->connect(), $sqlEmpNotiEstado);

		$result["success"] = true;

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>
