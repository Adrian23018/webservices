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
			
		// Si empleados activos es mayor al máximo del plan
		$consultaApple = ukuGetPlanCustomerApple( $id );
		
		if( $consultaApple['app_receipt'] ){
			
		}else{
		    $data = array("password" => _Password_Apple_Pay, "receipt-data" => $_POST['receipt'], "exclude-old-transactions" => true );
			$data_string = json_encode($data);
			
			//Production
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
			    $cRec = 0;
			    $dateReceipt;
			    $itemReceipt;
				foreach( $resultApple->receipt->in_app as $nameApple => $valorItem ){
				    if( $cRec === 0 ){
				        $itemReceipt = $valorItem;
				        $dateReceipt = $valorItem->purchase_date_ms;
				    }else{
				        if( $dateReceipt < $valorItem->purchase_date_ms){
				            $itemReceipt = $valorItem;
				            $dateReceipt = $valorItem->purchase_date_ms;
				        }
				    }
				    
				    $cRec++;
				}
			}
			
			$splStripeId = substr(str_replace("_","-",$itemReceipt->product_id),2);	
            $selectPlan = sprintf( "SELECT spl_id FROM tbl_stripe_plans WHERE spl_stripe_id=%s", GetSQLValueString( $splStripeId , "text" ) );
            $rs_sqlPlan = mysqli_query($_conection->connect(), $selectPlan);
            while ( $row_sqlPlan = mysqli_fetch_assoc($rs_sqlPlan) ) {
    			// Guardar en BD
    			$sqlSubs = sprintf( " INSERT INTO tbl_apple_pay (
    												app_usu_id, 
    												app_plan_id, 
    												app_status, 
    												app_plan_id_apple, 
    												app_transaction_id, 
    												app_receipt
    											) VALUES ( %s, %s, %s, %s, %s, %s ) ",
    				GetSQLValueString( $id , "text" ),
    				GetSQLValueString( $row_sqlPlan['spl_id'] , "text" ),
    				GetSQLValueString( 'success' , "text" ),
    				GetSQLValueString( $itemReceipt->product_id , "text" ),
    				GetSQLValueString( $itemReceipt->transaction_id , "text" ),
    				GetSQLValueString( $_POST['receipt'] , "text" )
    			);
    			$rs_sqlSubs = mysqli_query($_conection->connect(), $sqlSubs);
            }
			
		}
		
		$result["success"] = true;
		
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