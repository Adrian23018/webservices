<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);

	$patronTexto = "/^[A-Z][a-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ ]+$/i";
	$patronTextoNumeros = "/^[A-Za-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ0-9#_\- ]+$/i";
	$patronNumeros = "/^[0-9]+$/i";
	$patronSueldo = "/^[0-9.]+$/i";

	if ($_POST['dependiente']) {
		if (!preg_match( $patronTexto, trim($_POST['dependiente']) )) {
			// $error=true;
			// $result["error"] = 1;
		}
	}

	if (!preg_match( $patronSueldo, trim($_POST['sueldo']) )) {
		$error=true;
		$result["error"] = 2;
	}else if (substr_count($_POST['sueldo'], '.') > 1) {
		$error=true;
		$result["error"] = 2;
	}

	if (!$error) {
		$result["success"] = true;
	}

	$response->result = $result;
	echo json_encode($response);
?>