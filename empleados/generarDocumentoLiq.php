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
 		//$firma = $_POST['firma'];
 		$fechaSimulacion = simuladorTiempo($fechaActual, $fechaReferencia);

 		// Enviar Correo
 		$pathFile = '../../imagenes-contenidos/documentos-liquidacion/documento-de-liquidacion-'.$_POST['emp_id'].'.pdf';


 		// Traer nombre del empleado
 		$sqlEmpleados =  sprintf("SELECT * FROM tbl_empleados WHERE emp_id=%s",
 			GetSQLValueString($_POST["emp_id"], "double")
 		);
 		$rs_sqlEmpleados = mysqli_query($_conection->connect(), $sqlEmpleados);
 		$row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados);
 		$fechaCondRelacion = utf8_encode($row_sqlEmpleados["emp_cond_fecha_relacion"]);

 		$fechainicial = new DateTime($fechaCondRelacion);
 		$fechafinal = new DateTime($fechaSimulacion);
 		$diferencia = $fechainicial->diff($fechafinal);

 		require_once("../../admin_uku/phpMailer/class.phpmailer.php");

 		// Crear PDF sin Firma

 		if ($firma) {
 			$firmaEmp = $_POST['imagenGuardar'];
 		}
 		crearConfirmacionLiquidacion($_POST["emp_id"], $_conection, $_POST, $row_sqlEmpleados, $firmaEmp);

 		$mail=new PHPMailer();

 		// Documentos de liquidación
 		if ( $_POST["tipo_id"] == 1 ) {
 			//Renuncia	
 			$pathFileRenuncia = '../../imagenes-contenidos/documentos-liquidacion/carta-de-renuncia-'.$_POST['emp_id'].'.pdf';
 			crearCartaRenuncia($_POST["emp_id"], $_conection, $_POST, $row_sqlEmpleados, $firmaEmp);
 			$mail->AddAttachment($pathFileRenuncia, 'carta-de-renuncia-'.$_POST['emp_id'].'.pdf');
 		}elseif ( $_POST["tipo_id"] == 2 ) {
 			//Despido Justificado
 			// if ($diferencia->y < 2) {
 				// $pathFileDespido = '../../imagenes-contenidos/documentos-liquidacion/despido-menos-dos-'.$_POST['emp_id'].'.pdf';
 				// crearDespidoDosAnhos($_POST["emp_id"], $_conection, $_POST, $row_sqlEmpleados, $firmaEmp);
 				// 
 				// $mail->AddAttachment($pathFileDespido, 'despido-menos-dos-'.$_POST['emp_id'].'.pdf');
 			// }
 			$mensajeDespidoJustificado = 'Debe preparar la carta de despido con las justificantes y notificar al trabajador dicha carta. <br>';

 		}elseif ( $_POST["tipo_id"] == 3 ) {
 			//Despido Injustificado
 			// if ($diferencia->y < 2) {
 				$pathFileDespido = '../../imagenes-contenidos/documentos-liquidacion/despido-menos-dos-'.$_POST['emp_id'].'.pdf';
 				crearDespidoDosAnhos($_POST["emp_id"], $_conection, $_POST, $row_sqlEmpleados, $firmaEmp);
 				// 
 				$mail->AddAttachment($pathFileDespido, 'despido-menos-dos-'.$_POST['emp_id'].'.pdf');
 			// }
 		}elseif ( $_POST["tipo_id"] == 4 ) {
 			//Mutuo Acuerdo
 			$pathFileAcuerdo = '../../imagenes-contenidos/documentos-liquidacion/mutuo-acuerdo-'.$_POST['emp_id'].'.pdf';
 			crearMutuoAcuerdo($_POST["emp_id"], $_conection, $_POST, $row_sqlEmpleados, $firmaEmp);
 			$mail->AddAttachment($pathFileAcuerdo, 'mutuo-acuerdo-'.$_POST['emp_id'].'.pdf');
 		}elseif ( $_POST["tipo_id"] == 5 ) {
 			//Expiración del contrato
 			$pathFileExpiracion = '../../imagenes-contenidos/documentos-liquidacion/expiracion-contrato-'.$_POST['emp_id'].'.pdf';
 			$fechaLiquidar = $_POST['fecha_liquidar'];
 			// $_POST['fecha_liquidar'] = '';
 			// emp_tipo_definido
 			// emp_cond_fecha_relacion
 			crearExpiracionContrato($_POST["emp_id"], $_conection, $_POST, $row_sqlEmpleados, $firmaEmp);
 			$_POST['fecha_liquidar'] = $fechaLiquidar;
 			$mail->AddAttachment($pathFileExpiracion, 'expiracion-contrato-'.$_POST['emp_id'].'.pdf');
 		}

 		// Enviar Correo con Contrato.
 		$sqlUsuario = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s",
 			GetSQLValueString($id, "double")
 		);
 		$rs_sqlUsuario = mysqli_query($conexion, $sqlUsuario);
 		$row_sqlUsuario = mysqli_fetch_assoc($rs_sqlUsuario);

 		//Archivo Contrato
 		$sql = 	sprintf("SELECT * FROM a_tbl_pagina WHERE pag_id=1");
 		$rs_sql = mysqli_query($conexion, $sql);
 		$row_pagina = mysqli_fetch_assoc($rs_sql);
 		$nombreadministrador = explode("|",utf8_encode($row_pagina["pag_titulo"]));
 		$nombreadministrador = $nombreadministrador[1];
 		$logoadministador =  $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global._carpetaAdministrador."/img/".utf8_encode($row_pagina["pag_logo2"]).'?cache=2';

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
 		
 		$asunto = utf8_decode('Liquidación '. utf8_encode($row_sqlEmpleados['emp_do_nombre']));
		$mail->Subject = "=?ISO-8859-1?B?".base64_encode($asunto)."=?=";

 		$mail->MsgHTML(include("templates/documento-liquidacion.php"));
 		$mail->AddAttachment($pathFile, 'documento-de-liquidacion-'.$_POST['emp_id'].'.pdf');
 		if( $mail->Send() ){
 			$sqlEmpleado = sprintf("UPDATE tbl_empleados SET emp_estado=4, emp_fecha_liquidacion=%s, emp_motivo_liquidacion=%s WHERE emp_id=%s",
 				GetSQLValueString($_POST['fecha_liquidar'],"date"),
 				GetSQLValueString(utf8_decode($_POST['tipo']),"text"),
 				GetSQLValueString($_POST['emp_id'],"double")
 			);
 			$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);

 			$sqlEmpleadoNot = sprintf("UPDATE tbl_empleados_notificaciones SET en_estado=2 WHERE en_emp_id=%s",
 				GetSQLValueString($_POST['emp_id'],"double")
 			);
 			$rs_sqlEmpleadoNot = mysqli_query($conexion, $sqlEmpleadoNot);

 		}

		$result["success"] = true;

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>
