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
		$sqlHorasExtras = sprintf("INSERT INTO tbl_horasextras (he_usu_id,he_emp_id,he_diurna,he_nocturna,he_diurnadom,he_nocturnadom) VALUES (%s,%s,%s,%s,%s,%s) ",
				GetSQLValueString($id, "double"),
				GetSQLValueString($_POST["emp_id"], "double"),
				GetSQLValueString(utf8_decode($_POST['diurna']), "int"),
				GetSQLValueString(utf8_decode($_POST['nocturna']), "int"),
				GetSQLValueString(utf8_decode($_POST['diurnadom']), "int"),
				GetSQLValueString(utf8_decode($_POST['nocturnadom']), "int")
		);
		$rs_sqlHorasExtras = mysqli_query($conexion, $sqlHorasExtras);
		if ($rs_sqlHorasExtras) {
			$result["success"] = true;
		}
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>