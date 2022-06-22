<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);

	list($dia,$mes,$anho) = explode("/", $_POST['fecha_ausencia']);
	$fecha_ausencia = $anho.'-'.$mes.'-'.$dia;

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		$sqlAusencia = sprintf("INSERT INTO `tbl_ausencias`(`au_usu_id`, `au_emp_id`, `au_fecha_ausencia`, `au_justificada`) VALUES (%s,%s,%s,%s) ",
				GetSQLValueString($id, "double"),
				GetSQLValueString($_POST["emp_id"], "double"),
				GetSQLValueString(utf8_decode($fecha_ausencia), "date"),
				GetSQLValueString(utf8_decode($_POST['justificada']['id']), "double")
		);
		$rs_sqlAusencia = mysqli_query($conexion, $sqlAusencia);
		if ($rs_sqlAusencia) {
			$result["success"] = true;
		}
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>