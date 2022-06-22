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
		

		$planSql = ukuGetPlan($_POST['planId']);
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
		
		$consultaApple = ukuGetPlanCustomerAndroid( $id );

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
		
		if( $consultaApple['app_receipt'] ){
			// Guardar en BD
			$sqlSubs = sprintf( " UPDATE tbl_android_pay SET 
								app_plan_id=%s, 
								app_status=%s,
								app_plan_id_android=%s, 
								app_transaction_id=%s, 
								app_receipt=%s
							WHERE  app_usu_id=%s",
				GetSQLValueString( $_POST['planId'] , "text" ),
				GetSQLValueString( 'success' , "text" ),
				GetSQLValueString( $_POST['planIdAndroid'] , "text" ),
				GetSQLValueString( $_POST['transactionId'] , "text" ),
				GetSQLValueString( $_POST['receipt'] , "text" ),
				GetSQLValueString( $id , "text" )
			);
			$rs_sqlSubs = mysqli_query($_conection->connect(), $sqlSubs);
		}else{
			// Guardar en BD
			$sqlSubs = sprintf( " INSERT INTO tbl_android_pay (
												app_usu_id, 
												app_plan_id, 
												app_status, 
												app_plan_id_android, 
												app_transaction_id, 
												app_receipt
											) VALUES ( %s, %s, %s, %s, %s, %s ) ",
				GetSQLValueString( $id , "text" ),
				GetSQLValueString( $_POST['planId'] , "text" ),
				GetSQLValueString( 'success' , "text" ),
				GetSQLValueString( $_POST['planIdAndroid'] , "text" ),
				GetSQLValueString( $_POST['transactionId'] , "text" ),
				GetSQLValueString( $_POST['receipt'] , "text" )
			);
			$rs_sqlSubs = mysqli_query($_conection->connect(), $sqlSubs);
		}
		if ( $rs_sqlSubs ) {
			$result["success"] = true;
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