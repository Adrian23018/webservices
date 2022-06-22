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
	$result['error'] = false;
	$result['errorEmpleados'] = false;
	$result['cards'] = '';

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
			
		// Buscar planes que tenga 
		$sub_stripe_id = ukuGetPlanAnteriorId($id);

		$cus_stripe_id = ukuGetCustomerId($id);
		$canceled_stripe = ukuGetCustomerCanceled($id);
		

		$planSql = ukuGetPlan($_POST['plan_id']);
		// $spl_stripe_id = ukuGetPlanId($_POST['plan_id']);
		$spl_stripe_id = $planSql['spl_stripe_id'];
		$spl_min = $planSql['spl_min'];
		$spl_max = $planSql['spl_max'];

		$empleadosActivos = 0;
		$sqlEmpleados = sprintf(" SELECT * FROM tbl_empleados WHERE emp_usu_id=%s ",
			GetSQLValueString($id, "text")
		);
		$rs_sqlEmpleados = mysqli_query($_conection->connect(), $sqlEmpleados);
		$arrayActivos = array(1,2);
		while ( $row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados) ) {
			if ( in_array($arrayActivos, $row_sqlEmpleados['emp_estado']) ) {
			}elseif( $row_sqlEmpleados['emp_estado'] == 3 ){
				$empleadosActivos += 1;
			}
		}
		// $empleadosActivos

		$stripe = new ClassIncStripe;

		// Si empleados activos es mayor al máximo del plan
		if ( $empleadosActivos > $spl_max ) {
			$result['errorEmpleados'] = true;
		}

		if ( !$result['errorEmpleados'] ) {
			try {
				$trial_from_plan = false;
				if( !$sub_stripe_id && !$canceled_stripe ){
					$trial_from_plan = true;
				}

				$subscription = \Stripe\Subscription::create(array(
				  "customer" => $cus_stripe_id,
				  "items" => array(
				    array(
				      "plan" => $spl_stripe_id,
				    ),
				  ),
				  "trial_from_plan" => $trial_from_plan
				));
			} catch (Exception $e) {
				// var_dump($e);
				$result['error'] = true;
			}

			if ( is_array( $subscription ) ) {
				$sub_id = $subscription['id'];
				$sub_object = $subscription["object"];
				$sub_current_period_end = $subscription["current_period_end"];
				$sub_current_period_end_date = date('Y-m-d', $sub_current_period_end);
	  			$sub_current_period_start = $subscription["current_period_start"];
				$sub_current_period_start_date = date('Y-m-d', $sub_current_period_start);
	  			$sub_status = $subscription["status"];
			}else{
				$sub_id = $subscription->id;
				$sub_object = $subscription->object;
				$sub_current_period_end = $subscription->current_period_end;
				$sub_current_period_end_date = date('Y-m-d', $sub_current_period_end);
	  			$sub_current_period_start = $subscription->current_period_start;
				$sub_current_period_start_date = date('Y-m-d', $sub_current_period_start);
	  			$sub_status = $subscription->status;
			}

			if ( $sub_id ) {
				// Si se suscribe bien cancelar los planes anteriores
				// Eliminar Plan anterior
				if ( $sub_stripe_id ) {
					try {
						$sub = \Stripe\Subscription::retrieve( $sub_stripe_id );
						$sub->cancel();
					} catch (Exception $e) {
						$result['error_cancelando'] = true;
					}

					// Eliminar de BD 
					ukuDeletePlanAnteriorId( $id );
				}


				// Guardar en BD
				$sqlSubs = sprintf( " INSERT INTO tbl_stripe_subscriptions (
													ss_stripe_id, 
													ss_usu_id, 
													ss_spl_id, 
													ss_status, 
													ss_period_start, 
													ss_period_start_date, 
													ss_period_end,
													ss_period_end_date
												) VALUES ( %s, %s, %s, %s, %s, %s, %s, %s ) ",
					GetSQLValueString( $sub_id , "text" ),
					GetSQLValueString( $id , "text" ),
					GetSQLValueString( $_POST['plan_id'] , "text" ),
					GetSQLValueString( $sub_status , "text" ),
					GetSQLValueString( $sub_current_period_start , "text" ),
					GetSQLValueString( $sub_current_period_start_date , "text" ),
					GetSQLValueString( $sub_current_period_end , "text" ),
					GetSQLValueString( $sub_current_period_end_date , "text" )
				);
				$rs_sqlSubs = mysqli_query($_conection->connect(), $sqlSubs);
				if ( $rs_sqlSubs ) {
					$result["success"] = true;
				}
			}else{
				$result["error"] = true;
			}
		}
		// trialing
		// active
		// all
		// past_due
		// unpaid
		// canceled

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>