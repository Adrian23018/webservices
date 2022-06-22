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
    
    $conexion = $_conection->connect();
    
			
    list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
	    $asunto = $_POST['asunto'];
	    $mensaje = $_POST['mensaje'];
	    
	    if( $asunto && $mensaje ){
            $sql = 	"SELECT * FROM a_tbl_pagina WHERE pag_id=1";
        	$rs_sql = mysqli_query($conexion, $sql);
        	$row_pagina = mysqli_fetch_assoc($rs_sql);
        	$logoadministador =  $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global._carpetaAdministrador."/img/".$row_pagina["pag_logo2"].'?cache=2';
        	
	        //Enviar Correo con Contrato.
			$sqlUsuario = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s",
				GetSQLValueString($id, "double")
			);
			$rs_sqlUsuario = mysqli_query($conexion, $sqlUsuario);
			$row_sqlUsuario = mysqli_fetch_assoc($rs_sqlUsuario);
			
			require_once("../../admin_uku/phpMailer/class.phpmailer.php");
	        $mail=new PHPMailer();
			$mail->CharSet='UTF-8';
			//Correo al que se envia el mensaje
			if ($_SERVER['HTTP_HOST'] == 'localhost') {
				$mail->SetFrom($row_sqlUsuario["usu_email"]);
			}else{
				$mail->SetFrom('soporte@ukumanager.com', 'Uku');
			}
			$mail->AddAddress('soporte@ukumanager.com');
			//Configuraci贸n Correo
			$mail->isSMTP();
			$mail->Host = 'smtp.gmail.com';
			$mail->SMTPAuth = true;
			$mail->Username = 'soporte@ukumanager.com';
			$mail->Password = 'nzfyvxmyyxzbdjkg';
			$mail->Port = '587';
			$mail->SMTPSecure = "tls";
			
			$mail->Subject = utf8_encode('Contactenos Uku');
			$mail->MsgHTML(include("templates/contactenos.php"));
			if(!$mail->Send()){
			    $result['error'] = 2;
	            $result['message_error'] = 'No se ha podido enviar el mensaje, intentélo más tarde...';
			}else{
			    $result["success"] = true;
			}
	    }else{
	        $result['error'] = 1;
	        $result['message_error'] = 'Los campos asunto y mensaje son obligatorios';
	    }
	}else{
		$result['error'] = -100;
	}
	
	$response->result = $result;
	echo json_encode($response);
?>
