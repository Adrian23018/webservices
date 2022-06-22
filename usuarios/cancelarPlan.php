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
	$result['cards'] = '';
	$result['error_cancelando'] = false;

	$stripe = new ClassIncStripe;
	// $customer = \Stripe\Customer::retrieve( 'cus_CwiWYLfJnlS2TS' );
	// var_dump($customer);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		// Buscamos consulta usuario
		$consulta = ukuGetPlanCustomer( $id );

		// Buscar planes que tenga 
		$sub_stripe_id = ukuGetPlanAnteriorId($id);

		// var_dump($sub_stripe_id);

		// Eliminar Plan anterior
		if ( $sub_stripe_id ) {
			try {
				$sub = \Stripe\Subscription::retrieve( $sub_stripe_id );
				$sub->cancel();

				$cancelado = true;
			} catch (Exception $e) {
				$result['error_cancelando'] = true;
			}

			// Eliminar de BD 
			if ($cancelado) {
				ukuDeletePlanAnteriorId( $id );
				ukuCustomerCanceledPlan( $id );
				$result["success"] = true;
			}
		}
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>