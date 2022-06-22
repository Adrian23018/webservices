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

	// $stripe = new ClassIncStripe;
	// $customer = \Stripe\Customer::retrieve( 'cus_CwiWYLfJnlS2TS' );
	// var_dump($customer);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		$sqlCards = sprintf("SELECT * FROM tbl_stripe_cards INNER JOIN tbl_usuarios ON usu_id=sca_usu_id WHERE sca_usu_id=%s AND sca_id=%s",
			GetSQLValueString($id, "text"),
			GetSQLValueString($_POST['card_id'], "text")
		);
		$rs_sqlCards = mysqli_query($_conection->connect(), $sqlCards);
		$row_sqlCards = mysqli_fetch_assoc($rs_sqlCards);

		//Eliminar tarjeta de Stripe

		try {
			$stripe = new ClassIncStripe;

			$customer = \Stripe\Customer::retrieve( $row_sqlCards['usu_stripe_id'] );
			$customer->sources->retrieve( $row_sqlCards['sca_card'] )->delete();

		} catch (Exception $e) {
			$errorStripe = true;
		}

		if ( !$errorStripe ) {
			// Delete BD Card
			$delete = ukuDeleteTarjeta( $_POST['card_id'] );

			if ( $row_sqlCards['sca_default'] ) {
				// 
				$customer = \Stripe\Customer::retrieve( $row_sqlCards['usu_stripe_id'] );
				ukuEditTarjetaDefault($customer->default_source);
			}
		}
		
		$result['cards'] = ukuGetTarjetas( $id );
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>