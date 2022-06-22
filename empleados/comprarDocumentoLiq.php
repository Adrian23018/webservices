<?php
	// Headers App
	require("../_functions/headers_options.php");
	// error_reporting(1);
	
	// session_start();
	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();
	//require_once 'config.php';

	$result['success'] = false;

	$_POST = json_decode(file_get_contents('php://input'), true);
	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		if (!$error) {
			$sqlUsuario = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s",
				GetSQLValueString($id, "text")
			);
			$rs_sqlUsuario = mysqli_query($_conection->connect(), $sqlUsuario);
			$row_sqlUsuario = mysqli_fetch_assoc($rs_sqlUsuario);

			$sqlHistorial = sprintf("INSERT INTO tbl_usuarios_historial 
										(
											uh_usu_id, 
											uh_total, 
											uh_estado, 
											uh_plan, 
											uh_documento, 
											uh_departamento, 
											uh_ciudad, 
											uh_direccion, 
											uh_telefono, 
											uh_tarjeta, 
											uh_cvc, 
											uh_mes, 
											uh_fecha
										) 
										VALUES
										(
											%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s
										)
										",
				GetSQLValueString($id, "double"),
				GetSQLValueString(30, "double"),
				GetSQLValueString('rechazada', "text"),
				GetSQLValueString($_POST["plan"]["id"], "double"),
				GetSQLValueString('liquidacion', "text"),
				GetSQLValueString($_POST["departamento"], "text"),
				GetSQLValueString($_POST["ciudad"], "text"),
				GetSQLValueString($_POST["direccion"], "text"),
				GetSQLValueString($_POST["telefono"], "double"),
				GetSQLValueString("tarjeta", "double"),
				// GetSQLValueString($_POST["tarjeta"], "double"),
				GetSQLValueString($_POST["cvc"], "double"),
				GetSQLValueString($_POST["mes"], "double"),
				GetSQLValueString($_POST["fecha"], "double")
			);
			$rs_sqlHistorial = mysqli_query($conexion, $sqlHistorial);

			$idH = mysqli_insert_id($conexion);

			// echo $sqlHistorial;
			if ($rs_sqlHistorial && $idH) {
				$description = $_POST['plan']['value'];
				$totalPagarTarjeta = 30;

				$ApiKey= ApiKey;
				$merchantId= merchantId;
				$currency= currency;
				$apiLogin = apiLogin;
				$accountId = accountId;
				$payuUrl = payuUrl;
				$test = test;

				$referenceCode= "tstapp_uku_".$idH;
				$tx_value= $totalPagarTarjeta;

				$signature = md5("$ApiKey~$merchantId~$referenceCode~$tx_value~$currency");

				$payu["id"] = $idH;
				$payu["signature"] = $signature;
				$payu["apiLogin"] = $apiLogin;
				$payu["apiKey"] = $ApiKey;
				$payu["accountId"] = $accountId;
				$payu["referenceCode"] = $referenceCode;
				$payu["tx_value"] = $tx_value;
				$payu["description"] = $description;
				if ($test) {
					$payu["name"] = 'APPROVED';
					if ($row_sqlUsuario['usu_nacionalidad'] == 'panama') {
						$accountId = "512326";
						$payu["accountId"] = $accountId;
						$currency = "USD";
						$signature = md5("$ApiKey~$merchantId~$referenceCode~$tx_value~$currency");
						$payu["signature"] = $signature;
					}
				}else{
					// if ($_POST['nombrepropietario']) {
					// 	$payu["name"] = $_POST['nombrepropietario'];
					// }
					if ($row_sqlUsuario['usu_nacionalidad'] == 'panama') {
						$currency = "USD";
						$signature = md5("$ApiKey~$merchantId~$referenceCode~$tx_value~$currency");
						$payu["signature"] = $signature;	
					}
				}

				$payu["test"] = $test;
				$payu["url"] = $payuUrl;
				$payu["email"] = utf8_encode($row_sqlUsuario["usu_email"]);
				$payu["celular"] = utf8_encode($_POST["telefono"]);
				$payu["nombres"] = utf8_encode($row_sqlUsuario["usu_nombres"].' '.$row_sqlUsuario["usu_apellidos"]);

				if ($row_sqlUsuario['usu_nacionalidad'] == 'colombia') {
					$city = "Colombia";
					$state = "Colombia";
					$country = "CO";
					$postalCode = "000000";
					$currency = "COP";
					$paymentCountry = "CO";
				}else{
					$city = "Panamá";
					$state = "Panamá";
					$country = "PA";
					$postalCode = $_POST["codigo"];
					
					$paymentCountry = "PA";
				}

				if ((int)$_POST["mes"] < 10) {
					$_POST["mes"] = '0'.(int)$_POST["mes"];
				}

				$postData = array(
				   "language" => "es",
				   "command" => "SUBMIT_TRANSACTION",
				   "merchant" => array(
				      "apiLogin" => $apiLogin,
				      "apiKey" => $ApiKey
				   ),
				   "transaction" => array(
				      "order" => array(
				         "accountId" => $accountId,
				         "referenceCode" => $referenceCode,
				         "description" => $description,
				         "language" => "es",
				         "notifyUrl" => "",
				         "signature" => $signature,
				         "shippingAddress" => array(
				            "country" => $country
				         ),
				         "buyer" => array(
				            "merchantBuyerId" => 1,
				            "fullName" => $payu["nombres"],
				            "emailAddress" => $payu["email"],
				            "dniNumber" => "",
				            "shippingAddress" => array(
				               "street1" => $_POST["direccion"],
				               "city" => $city,
					           "state" => $state,
					           "country" => $country,
					           "postalCode" => $postalCode,
				               "phone" => $_POST["telefono"]
				            )
				         ),
				         "additionalValues" => array(
				            "TX_VALUE" => array(
				               "value" => $tx_value,
				               "currency" => $currency
				            )
				         )
				      ),
				      "creditCard" => array(
				         "number" => $_POST["tarjeta"],
				         "securityCode" => $_POST["cvc"],
				         "expirationDate" => $_POST["fecha"]."/".$_POST["mes"],
				         "name" => $payu["name"]
				      ),
				      "type" => "AUTHORIZATION_AND_CAPTURE",
				      "paymentMethod" => $_POST["franquicia"],
				      "paymentCountry" => $paymentCountry
				   ),
				   "test" => $payu["test"]
				);

				// ,
		  //     "payer" => array(
				// "fullName" => $_POST["nombrepropietario"],
		  //     )

				// var_dump($postData);

				// Setup cURL
				$ch = curl_init($payu["url"]);
				curl_setopt_array($ch, array(
				    CURLOPT_POST => TRUE,
				    CURLOPT_RETURNTRANSFER => true,
				    CURLOPT_HTTPHEADER => array(
				        'Accept: application/json',
				        'Content-Type: application/json'
				    ),
				    CURLOPT_POSTFIELDS => json_encode($postData)
				));

				// Send the request
				$responseCurl = curl_exec($ch);

				// Check for errors
				if($response === FALSE){
				    die(curl_error($ch));
				}
				curl_close($ch);

				// Decode the response
				$responseData = json_decode($responseCurl, TRUE);
				// var_dump($responseData);

				$result["transactionId"] = $responseData["transactionResponse"]["transactionId"];
				if ($responseData["code"] == "ERROR") {
					$result["responseCode"] = 'ERROR';
					$deleteHistorial = true;
				}else{
					$result["responseCode"] = $responseData["transactionResponse"]["responseCode"];

					if ($responseData["transactionResponse"]["responseCode"]=='APPROVED' && $responseData["transactionResponse"]["state"] == 'APPROVED') {
						$result["success"] = true;

						//Guardamos transaccion
						$sqlUpdHistorial = sprintf("UPDATE tbl_usuarios_historial 
													SET
														uh_estado=%s, 
														uh_tr_orderid=%s,
														uh_tr_transactionid=%s,
														uh_tr_paymentnetworkresponsecode=%s,
														uh_tr_paymentnetworkresponseerrormessage=%s,
														uh_tr_trazabilitycode=%s,
														uh_tr_authorizationcode=%s,
														uh_tr_pendingreason=%s,
														uh_tr_responsecode=%s,
														uh_tr_errorcode=%s,
														uh_tr_responsemessage=%s,
														uh_tr_transactiondate=%s,
														uh_tr_transactiontime=%s,
														uh_tr_operationdate=%s,
														uh_tr_referencequestionnaire=%s,
														uh_tr_extraparameters=%s,
														uh_tr_additionalinfo=%s
													WHERE
														uh_id=%s
													",
							GetSQLValueString('aceptada', "text"),
							GetSQLValueString($responseData["transactionResponse"]["orderId"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["transactionId"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["paymentNetworkResponseCode"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["paymentNetworkResponseErrorMessage"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["trazabilityCode"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["authorizationCode"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["pendingReason"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["responseCode"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["errorCode"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["responseMessage"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["transactionDate"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["transactionTime"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["operationDate"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["referenceQuestionnaire"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["extraParameters"], "text"),
							GetSQLValueString($responseData["transactionResponse"]["additionalInfo"], "text"),
							GetSQLValueString($idH, "text")
						);
						$rs_sqlUpdHistorial = mysqli_query($conexion, $sqlUpdHistorial);

						//liquidación
						// crearConfirmacionPagoSalario($_POST['emp_id'], $_POST, $_conection);

						//Enviar Correo
						$sql = 	sprintf("SELECT * FROM a_tbl_pagina WHERE pag_id=1");
						$rs_sql = mysqli_query($conexion, $sql);
						$row_pagina = mysqli_fetch_assoc($rs_sql);
						$nombreadministrador = explode("|",utf8_encode($row_pagina["pag_titulo"]));
						$nombreadministrador = $nombreadministrador[1];
						$logoadministador =  $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global._carpetaAdministrador."/img/".utf8_encode($row_pagina["pag_logo2"]);

						$sqlRegistro = 	sprintf("SELECT * FROM a_tbl_mensajes WHERE msj_id=1");
						$rs_sqlRegistro = mysql_query($sqlRegistro, $_conection->connect());
						$row_sqlRegistro = mysql_fetch_assoc($rs_sqlRegistro);

						require_once("../../admin_uku/phpMailer/class.phpmailer.php");
						$mail=new PHPMailer();
						$mail->CharSet='UTF-8';
						//Correo al que se envia el mensaje
						if ($_SERVER['HTTP_HOST'] == 'localhost') {
							$mail->SetFrom($row_sqlUsuario["usu_email"]);
						}else{
							$mail->SetFrom('soporte@ukumanager.com', 'Uku');
						}
						$mail->AddAddress($row_sqlUsuario["usu_email"]);

						//Configuración Correo
						$mail->isSMTP();
						$mail->Host = 'smtp.gmail.com';
						$mail->SMTPAuth = true;
						$mail->Username = 'soporte@ukumanager.com';
						$mail->Password = 'nzfyvxmyyxzbdjkg';
						$mail->Port = '587';
						$mail->SMTPSecure = "tls";

						$asunto = utf8_decode('Comprobante de Pago Liquidación');
						$mail->Subject = "=?ISO-8859-1?B?".base64_encode($asunto)."=?=";

						//Cargar Template
						$mail->MsgHTML(include("templates/comprobanteLiquidacion.php"));
						$mail->Send();

						$sqlEmpleado = sprintf("UPDATE tbl_empleados SET emp_estado=4, emp_fecha_liquidacion=%s WHERE emp_id=%s",
							GetSQLValueString($_POST['fecha_liquidar'],"date"),
							GetSQLValueString($_POST['emp_id'],"double")
						);
						$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
						//Exportar PDF Liquidacion
					}else{
						$deleteHistorial = true;
					}
				}

				if ($deleteHistorial) {
					$sqlDelHistorial = sprintf("DELETE FROM tbl_usuarios_historial WHERE uh_id=%s",
						GetSQLValueString($idH, "text")
					);	
					$rs_sqlDelHistorial = mysqli_query($conexion, $sqlDelHistorial);
				}

			}else{
				$result["error"] = 4;
			}
		}
	}else{
		$result['error'] = 1;
	}
	


	$response->result = $result;
	echo json_encode($response);
?>