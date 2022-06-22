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
	$validacion = true;
	$id = 44;
	if ($validacion) {
		// Buscamos consulta usuario
		$consulta = ukuGetPlanCustomer( $id );

		$stripe = new ClassIncStripe;

		// Actualizar si se ha pasado la fecha
		$sub_current_period_end = $consulta["ss_period_end"];
		$sub_current_period_end_date = $consulta["ss_period_end_date"];
		$sub_current_period_start = $consulta["ss_period_start"];
		$sub_current_period_start_date = $consulta["ss_period_start_date"];
		$sub_status = $consulta["ss_status"];

		//  || $consulta['ss_status'] != 'active' 
		if ( ( time() > $consulta['ss_period_end'] || $consulta['ss_status'] != 'active' ) ) {
			// Revisar actualizacion
			try {
				$subscription = \Stripe\Subscription::retrieve( $consulta['ss_stripe_id'] );

				$sub_current_period_end = $subscription["current_period_end"];
				$sub_current_period_end_date = date('Y-m-d', $sub_current_period_end);
	  			$sub_current_period_start = $subscription["current_period_start"];
				$sub_current_period_start_date = date('Y-m-d', $sub_current_period_start);
	  			$sub_status = $subscription["status"];

	  			$sqlSubs = sprintf( " UPDATE tbl_stripe_subscriptions SET 
	  												ss_status=%s, 
	  												ss_period_start=%s, 
	  												ss_period_start_date=%s, 
	  												ss_period_end=%s,
	  												ss_period_end_date=%s
	  											WHERE ss_id=%s ",
	  				GetSQLValueString( $sub_status , "text" ),
	  				GetSQLValueString( $sub_current_period_start , "text" ),
	  				GetSQLValueString( $sub_current_period_start_date , "text" ),
	  				GetSQLValueString( $sub_current_period_end , "text" ),
	  				GetSQLValueString( $sub_current_period_end_date , "text" ),
	  				GetSQLValueString( $consulta['ss_id'] , "text" )
	  			);
	  			$rs_sqlSubs = mysqli_query($_conection->connect(), $sqlSubs);
				
			} catch (Exception $e) {
				// $result['error_cancelando'] = true;
			}

			// $consulta['spl_max'];
			// $sub_current_period_end;
			// $sub_current_period_end_date;
			// $sub_current_period_start;
			// $sub_current_period_start_date;
			// $sub_status;
		}

		// Convertir fecha final en 
		list($anho, $mes, $dia) = explode("-", $consulta['ss_period_end_date']);
		$mesLetras = $arrayMesesGlobal[(int)$mes];

		$result['empleados'] = 0;
		$result['estado'] = '';

		// Pagos Android Stripe
		if ($consulta['ss_id']) {
			$result['empleados'] = $consulta['spl_max'];
			$result['plan'] = $consulta['spl_slug_name'];
			$result['descripcion'] = $consulta['spl_description'];
			$result['fecha'] = $dia . ' de ' . $mesLetras . ' del ' . $anho;

			switch ($sub_status) {
				case 'active':
					$result['estado'] = 'activo';
					break;

				case 'trialing':
					$result['estado'] = 'pruebas';
					break;

				case 'past_due':
					$result['estado'] = 'vencido';
					break;

				case 'canceled':
					$result['estado'] = 'cancelada';
					break;

				case 'unpaid':
					$result['estado'] = 'no pagada';
					break;
				
				default:
					$result['estado'] = $sub_status;
					break;
			}
		}
		
		// Pagos InAppPurchase
		// Buscamos consulta usuario Pagos iOS
		$consultaApple = ukuGetPlanCustomerApple( $id );
		//var_dump($consultaApple);
		if( $result['estado']!='activo' && $result['estado']!='pruebas' && $consultaApple['app_receipt'] ){
			$data = array("password" => _Password_Apple_Pay, "receipt-data" => $consultaApple['app_receipt'] );
			$data_string = json_encode($data);
			
			// 
			//Trial Sandbox
			/*$appleLinkVerify = 'https://sandbox.itunes.apple.com/verifyReceipt';
	
			$ch = curl_init($appleLinkVerify);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string))
			);
			$resultCurl = curl_exec($ch);
			$resultApple = json_decode($resultCurl);
			
			if( $resultApple->receipt ){
				foreach( $resultApple->receipt->in_app as $nameApple => $valorItem ){
					if( $valorItem->transaction_id == $consultaApple['app_transaction_id'] ){
					    $fechaExpira = explode(" ", $valorItem->expires_date)[0];
						if( time() > strtotime($fechaExpira . " +1 Day") ){
							$result['estado'] = 'vencido';
						}else{
							$result['estado'] = 'activo';
						}

						$result['empleados'] = $consultaApple['spl_max'];
						$result['plan'] = $consultaApple['spl_slug_name'];
						$result['descripcion'] = $consultaApple['spl_description'];
						list($anho, $mes, $dia) = explode("-", $fechaExpira);
						$mesLetras = $arrayMesesGlobal[(int)$mes];
						$result['fecha'] = $dia . ' de ' . $mesLetras . ' del ' . $anho;
					}
				}
			}*/
			
	        //No Sandbox		
			$appleLinkVerify = 'https://buy.itunes.apple.com/verifyReceipt';
			
			$ch = curl_init($appleLinkVerify);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string))
			);
			$resultCurl = curl_exec($ch);
			$resultApple = json_decode($resultCurl);
			
			if( $resultApple->receipt ){
				foreach( $resultApple->receipt->in_app as $nameApple => $valorItem ){
					if( $valorItem->transaction_id == $consultaApple['app_transaction_id'] ){
					    $fechaExpira = explode(" ", $valorItem->expires_date)[0];
						if( time() > strtotime($fechaExpira . " +1 Day") ){
							$result['estado'] = 'vencido';
						}else{
							$result['estado'] = 'activo';
						}

						$result['empleados'] = $consultaApple['spl_max'];
						$result['plan'] = $consultaApple['spl_slug_name'];
						$result['descripcion'] = $consultaApple['spl_description'];
						list($anho, $mes, $dia) = explode("-", $fechaExpira);
						$mesLetras = $arrayMesesGlobal[(int)$mes];
						$result['fecha'] = $dia . ' de ' . $mesLetras . ' del ' . $anho;
					}
				}
			}
		}

		// Enviamos informacion de los empleados
		$empleados = array();
		$empleados['creados'] = $empleados['activos'] = $empleados['liquidados'] = 0;

		$sqlEmpleados = sprintf(" SELECT * FROM tbl_empleados WHERE emp_usu_id=%s ",
			GetSQLValueString($id, "text")
		);
		$rs_sqlEmpleados = mysqli_query($_conection->connect(), $sqlEmpleados);
		$arrayActivos = array(1,2);
		while ( $row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados) ) {
			if ( in_array($arrayActivos, $row_sqlEmpleados['emp_estado']) ) {
				$empleados['creados'] += 1;
			}elseif( $row_sqlEmpleados['emp_estado'] == 3 ){
				$empleados['activos'] += 1;
			}else{
				$empleados['liquidados'] += 1;
			}
		}

		$result['adm_empleados'] = $empleados;

		// Prueba
		// $result['empleados'] = 0;
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>