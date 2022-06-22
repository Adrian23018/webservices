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
		//	Condiciones de trabajo usada.
		$emp_id = $_POST["emp_id"];
		$firma = $_POST['firma'];
		$fechaSimulacion = simuladorTiempo($fechaActual, $fechaReferencia);

		// Crear PDF sin Firma
		if ($firma) {
			$firmaEmp = $_POST['imagenGuardar'];
		}

		$sqlEmpleado = sprintf("SELECT * FROM tbl_empleados WHERE emp_id=%s",
			GetSQLValueString($emp_id, "double")
		);
		$rs_sqlEmpleado = mysqli_query($_conection->connect(), $sqlEmpleado);
		$row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado);

		//Revisar si existe el prestamo
		$sqlPrest = sprintf("SELECT * FROM tbl_prestamos WHERE pr_emp_id=%s",
			GetSQLValueString($emp_id, "double")
		);
		$rs_sqlPrest = mysqli_query($_conection->connect(), $sqlPrest);
		$row_sqlPrest = mysqli_fetch_assoc($rs_sqlPrest);

		if ($_POST['monto'] <= 0) {
			// $error['true'] = 'El monto debe ser mayor que 0';
			$result['error'] = 1;
		}

		if ($_POST['cuotas'] <= 0) {
			// $error['true'] = 'Las cuotas deben ser mayor que 0';
			$result['error'] = 2;
		}

		if (!$result['error']) {
			if (!$row_sqlPrest['pr_id']) {
				$sqlPrestamos = sprintf("INSERT INTO tbl_prestamos (pr_usu_id,pr_emp_id,pr_monto,pr_cuotas_tipo,pr_cuotas,pr_fecha) VALUES (%s,%s,%s,%s,%s,%s) ",
						GetSQLValueString($id, "double"),
						GetSQLValueString($emp_id, "double"),
						GetSQLValueString(utf8_decode($_POST['monto']), "double"),
						GetSQLValueString(utf8_decode($_POST['cuota']['id']), "double"),
						GetSQLValueString(utf8_decode($_POST['cuotas']), "double"),
						GetSQLValueString($fechaSimulacion, "date")
				);
				$rs_sqlPrestamos = mysqli_query($conexion, $sqlPrestamos);
			}else{
				$sqlPrestamos = sprintf("UPDATE tbl_prestamos 
											SET pr_monto=%s,pr_cuotas_tipo=%s,pr_cuotas=%s,pr_fecha=%s 
											WHERE pr_emp_id=%s ",
						GetSQLValueString(utf8_decode($_POST['monto']), "double"),
						GetSQLValueString(utf8_decode($_POST['cuota']['id']), "double"),
						GetSQLValueString(utf8_decode($_POST['cuotas']), "double"),
						GetSQLValueString($fechaSimulacion, "date"),
						GetSQLValueString($emp_id, "double")
				);
				$rs_sqlPrestamos = mysqli_query($conexion, $sqlPrestamos);
			}
		}
		
		if ($rs_sqlPrestamos) {
			// Enviar Correo con Contrato.
			$sqlUsuario = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s",
				GetSQLValueString($id, "double")
			);
			$rs_sqlUsuario = mysqli_query($conexion, $sqlUsuario);
			$row_sqlUsuario = mysqli_fetch_assoc($rs_sqlUsuario);

			// Crear Comprobante
			$pr_id = mysqli_insert_id($conexion);
			crearComprobantePrestamoAdelanto(	
										1, 
										$pr_id,
										utf8_encode($row_sqlEmpleado['emp_do_nombre']), 
										$_POST['monto'], 
										$_POST['cuota']['id'], 
										$_POST['cuotas'], 
										$fechaSimulacion, 
										$firmaEmp, 
										$_conection
									);

			// Enviar Correo
			$pathFile = '../../imagenes-contenidos/confirmaciones-adelanto-o-prestamo/confirmacion-adelanto-o-prestamo-'.$pr_id.'.pdf';
			
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
			$mail->AddAttachment($pathFile, 'confirmacion-adelanto-o-prestamo-'.$pr_id.'.pdf');
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