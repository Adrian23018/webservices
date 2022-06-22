<?php
	// Headers App
	require("../_functions/headers_options.php");
	
	session_start();
	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');
	//require_once 'config.php';

	//$result['success'] = false;

	$_POST = json_decode(file_get_contents('php://input'), true);
	
	$result["success"] = false;
	$result['cards'] = '';
	$result['code_error'] = '';
	$result['message_error'] = '';
	//Ingresar Tarjeta en BD
	$stripe = new ClassIncStripe;
	
	// $_POST['tarjeta']
	// $_POST['mes']
	// $_POST['fecha']
	// $_POST['cvc']
	// $_POST['currency']
	// $_POST['name']

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	$sqlCustomer = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s",
		GetSQLValueString($id, "text")
	);
	$rs_sqlCustomer = mysqli_query($_conection->connect(), $sqlCustomer);
	$row_sqlCustomer = mysqli_fetch_assoc($rs_sqlCustomer);

	if ( $row_sqlCustomer['usu_stripe_id'] ) {
		$customer_id = $row_sqlCustomer['usu_stripe_id'];
	}else{
		$customer = $stripe->createCustomer( $row_sqlCustomer['usu_email'] );
		$customer_id = is_array( $customer ) ? $customer['id'] : $customer->id;
	}

	if ($customer_id) {
		try {

			$sqlCards = sprintf("SELECT * FROM tbl_stripe_cards WHERE sca_id=%s",
				GetSQLValueString($_POST['card_id'], "text")
			);
			$rs_sqlCards = mysqli_query($_conection->connect(), $sqlCards);
			$row_sqlCards = mysqli_fetch_assoc($rs_sqlCards);

			$customer = \Stripe\Customer::retrieve($customer_id);
			$card = $customer->sources->retrieve($row_sqlCards['sca_card']);
			$card->exp_month = $_POST['mes'];
			$card->exp_year = $_POST['fecha'];
			$card->name = $_POST['nombrepropietario'];
			$card->save();

			$flagSuccess = true;
		} catch (Exception $e) {
			$err = $e->getJsonBody();
			// var_dump($e);
			$result['code_error'] = $err['error']['code'];
			$result['message_error'] = $erroresStripe[$err['error']['code']];

			$flagSuccess = false;
		}

		if ($flagSuccess) {
			$sqlCard = sprintf("UPDATE tbl_stripe_cards SET sca_exp_month=%s, sca_exp_year=%s, sca_name=%s WHERE sca_id=%s",
				GetSQLValueString($_POST['mes'], "text"),
				GetSQLValueString($_POST['fecha'], "text"),
				GetSQLValueString(utf8_decode($_POST['nombrepropietario']), "text"),
				GetSQLValueString($_POST['card_id'], "text")
			);
			$rs_sqlCard = mysqli_query($_conection->connect(), $sqlCard);
			if ($rs_sqlCard) {
				# code...
				$result["success"] = true;
				$result['cards'] = ukuGetTarjetas( $id );
			}
		}
	}

	$response->result = $result;
	echo json_encode($response);
?>