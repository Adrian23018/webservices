<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);

	$patronTexto = "/^[A-Z][a-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ ]+$/i";
	$patronTextoNumeros = "/^[A-Za-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ0-9#_.,\- ]+$/i";
	$patronNumeros = "/^[0-9]+$/i";

	if (!$_POST['nombre'] || !preg_match( $patronTexto, trim($_POST['nombre']) )) {
		$error=true;
		$result["error"] = 1;
	}

	if (!$_POST['domicilio'] || !preg_match( $patronTextoNumeros, trim($_POST['domicilio'])) ) {
		$error = true;
		$result["error"] = 2;
	}

	if (!$_POST['no_identidad'] || !preg_match( $patronTextoNumeros, trim($_POST['no_identidad']) )) {
		$error = true;
		$result["error"] = 3;
	}

	if (!$_POST['edad'] || !preg_match( $patronNumeros, trim($_POST['edad']) )) {
		$error = true;
		$result["error"] = 4;
	}

	if (!$error) {
		$result["success"] = true;
	}

	$response->result = $result;
	echo json_encode($response);
?>