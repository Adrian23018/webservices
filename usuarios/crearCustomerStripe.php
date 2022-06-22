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

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		$result['id'] = $id;
		$sqlCustomer = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s",
			GetSQLValueString($id, "text")
		);
		$rs_sqlCustomer = mysqli_query($_conection->connect(), $sqlCustomer);
		$row_sqlCustomer = mysqli_fetch_assoc($rs_sqlCustomer);

		// create customer id 

		// STRIPE SAVE PLAN
		$stripe = new ClassIncStripe;
		if ( $row_sqlCustomer['usu_stripe_id'] ) {
			$result['existe'] = $row_sqlCustomer['usu_stripe_id'];
			if ( !$stripe->existCustomer( $row_sqlCustomer['usu_stripe_id'] ) ) {
				// crear
				$crear = true;
			}
		}else{
			$result['existe'] = 'crear';
			$crear = true;
		}

		if ($crear) {
			$customer = $stripe->createCustomer( $row_sqlCustomer['usu_email'] );

			$customer_id = is_array( $customer ) ? $customer['id'] : $customer->id;

			$sqlStripe = sprintf("	UPDATE tbl_usuarios 
									SET
										usu_stripe_id=%s
									WHERE 
										usu_id=%s",
					GetSQLValueString($customer_id,"text"),
					GetSQLValueString($id,"int")
			);
			$rs_sqlStripe = mysqli_query($_conection->connect(), $sqlStripe);
			if ( $rs_sqlStripe ) {
                $result['existe'] = $customer_id;
				$result["success"] = true;
			}
		}

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>