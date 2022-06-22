<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);
	
	error_reporting(E_ALL);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
 		$en_id = $_POST['en_id'];
 		$firma = $_POST['firma'];

 		// Crear PDF sin Firma
 		if (!$firma) {
 			crearConfirmacionPagoSalario($en_id, $_conection, false);
 		}else{
 			$firmaEmp = $_POST['imagenGuardar'];
 			crearConfirmacionPagoSalario($en_id, $_conection, $firmaEmp);
 		}

 		// Enviar Correo
 		$pathFile = '../../imagenes-contenidos/confirmaciones-pagos/confirmacion-pago-'.$en_id.'.pdf';

 		// Enviar Correo con Contrato.
 		$sqlUsuario = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s",
 			GetSQLValueString($id, "double")
 		);
 		$rs_sqlUsuario = mysqli_query($conexion, $sqlUsuario);
 		$row_sqlUsuario = mysqli_fetch_assoc($rs_sqlUsuario);

 		// Traer nombre del empleado
 		$sqlEmpleados =  sprintf("SELECT * FROM tbl_empleados_notificaciones 
 										INNER JOIN tbl_empleados ON emp_id=en_emp_id 
 										WHERE en_id=%s AND en_usu_id=%s",
 			GetSQLValueString($en_id, "double"),
 			GetSQLValueString($id, "double")
 		);
 		$rs_sqlEmpleados = mysqli_query($_conection->connect(), $sqlEmpleados);
 		$row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados);

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


 		if ($_POST['terminacion'] != 'terminacion') {
	 		$asunto = utf8_decode('Confirmación Pago '. utf8_encode($row_sqlEmpleados['emp_do_nombre']));
			$mail->Subject = "=?ISO-8859-1?B?".base64_encode($asunto)."=?=";
 			$mail->MsgHTML(include("templates/confirmacion-pago.php"));
 			$mail->AddAttachment($pathFile, 'comprobante-salario-'.$en_id.'.pdf');
 		}else{
 			$asunto = utf8_decode('Terminación Contrato '. utf8_encode($row_sqlEmpleados['emp_do_nombre']));
			$mail->Subject = "=?ISO-8859-1?B?".base64_encode($asunto)."=?=";
 			$mail->MsgHTML(include("templates/terminacion-contrato.php"));
 		}

 		if( $mail->Send() ){
 			$sqlEmpNotiEstado =  sprintf("UPDATE tbl_empleados_notificaciones SET en_estado=%s WHERE en_id=%s AND en_usu_id=%s",
 				2,
 				GetSQLValueString($en_id, "double"),
 				GetSQLValueString($id, "double")
 			);
 			$rs_sqlEmpNotiEstado = mysqli_query($_conection->connect(), $sqlEmpNotiEstado);
 		}

		$result["success"] = true;

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>
