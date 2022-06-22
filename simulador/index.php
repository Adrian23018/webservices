<?php
	// Headers App
	require("../_functions/headers_options.php");
	
	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();
	//require_once 'config.php';

	$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);

	$array_dias['Monday'] = 1;
	$array_dias['Tuesday'] = 2;
	$array_dias['Wednesday'] = 3;
	$array_dias['Thursday'] = 4;
	$array_dias['Friday'] = 5;
	$array_dias['Saturday'] = 6;
	$array_dias['Sunday'] = 7;

	$result["dayText"] = $array_dias[date('l', strtotime($fechaSimulacion))];
	list($yearHoy, $mesHoy, $diaHoy) = explode('-',$fechaSimulacion);

	$result["day"] = $diaHoy;
	$result["month"] = $mesHoy;
	$result["year"] = $yearHoy;

	$response->result = $result;
	echo json_encode($response);
?>