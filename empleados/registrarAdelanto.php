<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		$emp_id = $_POST["emp_id"];
		$firma = $_POST['firma'];
		$fechaSimulacion = simuladorTiempo($fechaActual, $fechaReferencia);

		// Crear PDF sin Firma
		if ($firma) {
			$firmaEmp = $_POST['imagenGuardar'];
		}

		//Revisar si existe el adelanto
		$sqlAdel = sprintf("SELECT * FROM tbl_adelantos WHERE ad_tipo=%s AND ad_emp_id=%s",
			GetSQLValueString($_POST['adelanto']['id'], "int"),
			GetSQLValueString($emp_id, "double")
		);
		$rs_sqlAdel = mysqli_query($_conection->connect(), $sqlAdel);
		$row_sqlAdel = mysqli_fetch_assoc($rs_sqlAdel);

		$sqlEmpleado = sprintf("SELECT * FROM tbl_empleados WHERE emp_id=%s",
			GetSQLValueString($emp_id, "double")
		);
		$rs_sqlEmpleado = mysqli_query($_conection->connect(), $sqlEmpleado);
		$row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado);

		if ($_POST['monto'] <= 0) {
			$result['error'] = 1;
		}

		if (!$result['error']) {
			if (!$row_sqlAdel['ad_id']) {
				$sqlAdelanto = sprintf("INSERT INTO tbl_adelantos (ad_usu_id,ad_emp_id,ad_monto,ad_tipo,ad_fecha) VALUES (%s,%s,%s,%s,%s) ",
						GetSQLValueString($id, "double"),
						GetSQLValueString($emp_id, "double"),
						GetSQLValueString(utf8_decode($_POST['monto']), "double"),
						GetSQLValueString(utf8_decode($_POST['adelanto']['id']), "double"),
						GetSQLValueString($fechaSimulacion, "date")
				);
				$rs_sqlAdelanto = mysqli_query($conexion, $sqlAdelanto);
			}else{
				$sqlAdelanto = sprintf("UPDATE tbl_adelantos 
											SET ad_monto=%s,ad_fecha=%s 
											WHERE ad_tipo=%s AND ad_emp_id=%s ",
						GetSQLValueString(utf8_decode($_POST['monto']), "double"),
						GetSQLValueString($fechaSimulacion, "date"),
						GetSQLValueString($_POST['adelanto']['id'], "int"),
						GetSQLValueString($emp_id, "double")
				);
				$rs_sqlAdelanto = mysqli_query($conexion, $sqlAdelanto);
			}
		}

		if ($rs_sqlAdelanto) {
			// Enviar Correo con Contrato.
			$sqlUsuario = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s",
				GetSQLValueString($id, "double")
			);
			$rs_sqlUsuario = mysqli_query($conexion, $sqlUsuario);
			$row_sqlUsuario = mysqli_fetch_assoc($rs_sqlUsuario);

			// Crear Comprobante
			$ad_id = mysqli_insert_id($conexion);
			crearComprobantePrestamoAdelanto(	
										2, 
										$ad_id,
										utf8_encode($row_sqlEmpleado['emp_do_nombre']), 
										$_POST['monto'], 
										utf8_decode($_POST['adelanto']['id']), 
										0, 
										$fechaSimulacion,
										$firmaEmp, 
										$_conection
									);

			// Enviar Correo
			$pathFile = '../../imagenes-contenidos/confirmaciones-adelanto-o-prestamo/confirmacion-adelanto-o-prestamo-'.$ad_id.'.pdf';
			
			//Archivo Contrato
			$sql = 	sprintf("SELECT * FROM a_tbl_pagina WHERE pag_id=1");
			$rs_sql = mysqli_query($conexion, $sql);
			$row_pagina = mysqli_fetch_assoc($rs_sql);
			$nombreadministrador = explode("|",utf8_encode($row_pagina["pag_titulo"]));
			$nombreadministrador = $nombreadministrador[1];
			$logoadministador =  $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global._carpetaAdministrador."/img/".utf8_encode($row_pagina["pag_logo2"]).'?cache=2';

			require_once("../../admin_uku/phpMailer/class.phpmailer.php");
			$mail=new PHPMailer();
			$mail->CharSet='UTF-8';
			//Correo al que se envia el mensaje
			if ($_SERVER['HTTP_HOST'] == 'localhost') {
				$mail->SetFrom($row_sqlCorreo["usu_email"]);
			}else{
				$mail->SetFrom('soporte@ukumanager.com', 'Uku');
			}
			$mail->AddAddress($row_sqlUsuario['usu_email']);

			//Configuración Correo
			$mail->isSMTP();
			$mail->Host = 'smtp.gmail.com';
			$mail->SMTPAuth = true;
			$mail->Username = 'soporte@ukumanager.com';
			$mail->Password = 'nzfyvxmyyxzbdjkg';
			$mail->Port = '587';
			$mail->SMTPSecure = "tls";
			
			$asunto = utf8_decode('Confirmación Adelanto o Préstamo '. utf8_encode($row_sqlEmpleado['emp_do_nombre']));
			$mail->Subject = "=?ISO-8859-1?B?".base64_encode($asunto)."=?=";

			$mail->MsgHTML(include("templates/confirmacion-adelanto-o-prestamo.php"));
			$mail->AddAttachment($pathFile, 'confirmacion-adelanto-o-prestamo-'.$ad_id.'.pdf');
			if( $mail->Send() ){
				// accion
			}
			$result["success"] = true;
		}
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>