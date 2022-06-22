<?php
	// Headers App
	require("../_functions/headers_options.php");
	
	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');
	//require_once 'config.php';

	//$result['success'] = false;

	$_POST = json_decode(file_get_contents('php://input'), true);
	
	$result["success"] = false;
	$result['code_error'] = '';
	$result['message_error'] = '';
	if (!validar_tarjeta($_POST["tarjeta"])) {
		$error=true;
		$result["error"] = 1;
	}

	if (!$error) {
		//Ingresar Tarjeta en BD
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

		$sqlCards = sprintf("SELECT COUNT(*) AS cont_cards FROM tbl_stripe_cards WHERE sca_usu_id=%s",
			GetSQLValueString($id, "text")
		);
		$rs_sqlCards = mysqli_query($_conection->connect(), $sqlCards);
		$row_sqlCards = mysqli_fetch_assoc($rs_sqlCards);
		$cont_cards = 0;
		if (!$row_sqlCards['cont_cards']) {
			$cont_cards = 1;
		}

		$stripe = new ClassIncStripe;
		if ( $row_sqlCustomer['usu_stripe_id'] ) {
			$customer_id = $row_sqlCustomer['usu_stripe_id'];
		}else{
			$customer = $stripe->createCustomer( $row_sqlCustomer['usu_email'] );
			$customer_id = is_array( $customer ) ? $customer['id'] : $customer->id;
		}

		if ($customer_id) {
			try {
				$token = \Stripe\Token::create(array(
				  "card" => array(
				    "number" => $_POST['tarjeta'],
				    "exp_month" => $_POST['mes'],
				    "exp_year" => $_POST['fecha'],
				    "cvc" => $_POST['cvc'],
				    "name" => $_POST['nombrepropietario']
				  )
				));

				$customer = \Stripe\Customer::retrieve( $customer_id );
				$card = $customer->sources->create(
					array(
						"source" => $token['id'],
					)
				);
				$flagSuccess = true;
			} catch (Exception $e) {
				$err = $e->getJsonBody();
				$result['code_error'] = $err['error']['code'];
				$result['message_error'] = $erroresStripe[$err['error']['code']];

				$flagSuccess = false;
			}

			if ($flagSuccess) {
				$sqlCard = sprintf("INSERT INTO tbl_stripe_cards (sca_usu_id, sca_token, sca_card, sca_franquicia, sca_last4, sca_exp_month, sca_exp_year, sca_name, sca_default ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
					GetSQLValueString($id, "text"),
					GetSQLValueString($token['id'], "text"),
					GetSQLValueString($card['id'], "text"),
					GetSQLValueString($card['brand'], "text"),
					GetSQLValueString($card['last4'], "text"),
					GetSQLValueString($card['exp_month'], "text"),
					GetSQLValueString($card['exp_year'], "text"),
					GetSQLValueString(utf8_decode($card['name']), "text"),
					GetSQLValueString($cont_cards, "int")
				);
				$rs_sqlCard = mysqli_query($_conection->connect(), $sqlCard);
				if ($rs_sqlCard) {
					# code...
					$result["success"] = true;
				}
			}
		}

	}


	$response->result = $result;
	echo json_encode($response);
?>