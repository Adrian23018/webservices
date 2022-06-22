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
		

		try {
			$stripe = new ClassIncStripe;
			$cu = \Stripe\Customer::retrieve( $row_sqlCards['usu_stripe_id'] );
			$cu->default_source = $row_sqlCards['sca_card'];
			$cu->save();
		} catch (Exception $e) {
			
		}

		$sqlDefault = sprintf(" UPDATE tbl_stripe_cards SET sca_default=1 WHERE sca_id=%s ",
			GetSQLValueString($_POST['card_id'], "text")
		);
		$rs_sqlDefault = mysqli_query($_conection->connect(), $sqlDefault);

		$sqlNoDefault = sprintf(" UPDATE tbl_stripe_cards SET sca_default=0 WHERE sca_id!=%s ",
			GetSQLValueString($_POST['card_id'], "text")
		);
		$rs_sqlNoDefault = mysqli_query($_conection->connect(), $sqlNoDefault);

		$result['cards'] = ukuGetTarjetas( $id );
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>