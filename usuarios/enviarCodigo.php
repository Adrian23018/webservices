<?php
	// Headers App
	require("../_functions/headers_options.php");
	
	session_start();
	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');
	//require_once 'config.php';

	$result['success'] = false;

	$_POST = json_decode(file_get_contents('php://input'), true);
	//Valores Requeridos (Datos Normales)
	$dataRequired = array('correo');
	foreach ($_POST as $key => $valorData) {
		if (in_array($key, $dataRequired)) {
			if (!$valorData) {
				$error=true;
				$result["error_campos"][$key] = true;
				$result["error_mensaje"][$key] = '';
			}
		}
	}

	$conexion = $_conection->connect();

	$sqlCorreo = sprintf("SELECT * FROM tbl_usuarios WHERE usu_email=%s AND usu_tipo='email' ",
				GetSQLValueString(utf8_decode($_POST['correo']), "text")
			);
	$rs_sqlCorreo = mysqli_query($conexion, $sqlCorreo);
	$row_sqlCorreo = mysqli_fetch_assoc($rs_sqlCorreo);
	if ($row_sqlCorreo["usu_id"]) {
		if ($errorInterno != 1) {
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
			$mail->AddAddress($row_sqlCorreo["usu_email"]);

			//Configuración Correo
			$mail->isSMTP();
			$mail->Host = 'smtp.gmail.com';
			$mail->SMTPAuth = true;
			$mail->Username = 'soporte@ukumanager.com';
			$mail->Password = 'nzfyvxmyyxzbdjkg';
			$mail->Port = '587';
			$mail->SMTPSecure = "tls";

			$codigo = '';
			for($i=0;$i<5;$i++){
			    $codigo .= rand(1,9);
			}

			// $mail->Subject = utf8_encode("Recuperar Clave");
			$asunto = utf8_decode('Recuperar Contraseña');
			$mail->Subject = "=?ISO-8859-1?B?".base64_encode($asunto)."=?=";

			//Cargar Template
			$mail->MsgHTML(include("templates/enviarCodigo.php"));
			if($mail->Send()){
				$sqlCodigo = sprintf("UPDATE tbl_usuarios SET usu_codigo_verificacion=%s WHERE usu_email=%s AND usu_tipo='email' ",
					GetSQLValueString($codigo,"text"),
					GetSQLValueString(utf8_decode($_POST['correo']), "text")
				);
				$rs_sqlCodigo = mysqli_query($conexion, $sqlCodigo);
				if ($rs_sqlCodigo) {
					$result["success"] = true;
				}else{
					$error=true;
					$result["error_campos"]['correo'] = true;
					$result["error_mensaje"]['correo'] = 'Se generó un error, por favor intente mas tarde.';
				}
			}else{
				$error=true;
				$result["error_campos"]['correo'] = true;
				$result["error_mensaje"]['correo'] = 'Se generó un error, por favor intente mas tarde.';		
			}
		}

	}else{
		$error=true;
		$result["error_campos"]['correo'] = true;
		$result["error_mensaje"]['correo'] = 'El correo no se encuentra registrado.';
	}


	$response->result = $result;
	echo json_encode($response);
?>