<?php
	// Headers App
	require("../_functions/headers_options.php");
	
	// session_start();
	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	//require_once 'config.php';

	//$result['success'] = false;

	$_POST = json_decode(file_get_contents('php://input'), true);
	
	$result["success"] = false;

	// $stripe = new ClassIncStripe;
	// $customer = \Stripe\Customer::retrieve( 'cus_CwiWYLfJnlS2TS' );
	// var_dump($customer);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		// Response Cards
		$result['cards'] = ukuGetTarjetas( $id );
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>