<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);
	$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		// $id
		if ($_POST['emp_id']) {
			//Editar
			$sqlEmpleado = sprintf("UPDATE tbl_empleados SET 
						emp_contribuciones=%s,
						emp_promedio=%s,
						emp_anho1=%s,
						emp_anho1_valor=%s,
						emp_anho2=%s,
						emp_anho2_valor=%s,
						emp_anho3=%s,
						emp_anho3_valor=%s,
						emp_anho4=%s,
						emp_anho4_valor=%s,
						emp_anho5=%s,
						emp_anho5_valor=%s,
						emp_vacaciones=%s,
						emp_dias=%s,
						emp_estado=%s,
						emp_fecha_creacion=%s
					WHERE 
						emp_usu_id=%s AND 
						emp_id=%s
				",
					GetSQLValueString(utf8_decode($_POST['caja_contribuciones']), "text"),
					GetSQLValueString(utf8_decode($_POST['caja_promedio_salarial']), "text"),
					GetSQLValueString(utf8_decode($_POST['anho1']), "double"),
					GetSQLValueString(utf8_decode($_POST['anho_uno']), "double"),
					GetSQLValueString(utf8_decode($_POST['anho2']), "double"),
					GetSQLValueString(utf8_decode($_POST['anho_dos']), "double"),
					GetSQLValueString(utf8_decode($_POST['anho3']), "double"),
					GetSQLValueString(utf8_decode($_POST['anho_tres']), "double"),
					GetSQLValueString(utf8_decode($_POST['anho4']), "double"),
					GetSQLValueString(utf8_decode($_POST['anho_cuatro']), "double"),
					GetSQLValueString(utf8_decode($_POST['anho5']), "double"),
					GetSQLValueString(utf8_decode($_POST['anho_cinco']), "double"),
					GetSQLValueString(utf8_decode($_POST['vacaciones']), "text"),
					GetSQLValueString(utf8_decode($_POST['dias_vacaciones']), "double"),
					GetSQLValueString(utf8_decode($_POST['paso']), "int"),
					GetSQLValueString($fechaSimulacion, "date"),
					GetSQLValueString($id, "double"),
					GetSQLValueString($_POST["emp_id"], "double")
			);
			$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
			if ($rs_sqlEmpleado) {
				if ($_POST['paso'] == 3) {
					//Crear PDF.
					$sqlEmpleados =  sprintf("SELECT `emp_id`, `emp_contrato`, `emp_do_nombre` FROM tbl_empleados WHERE emp_id=%s ",
						GetSQLValueString($_POST["emp_id"], "double")
					);
					$rs_sqlEmpleados = mysqli_query($conexion, $sqlEmpleados);
					$row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados);

					if ($row_sqlEmpleados['emp_contrato'] == 'definido') {
						crearFunctionContratoDefinido($_POST["emp_id"], $_conection);
					}else{
						crearFunctionContratoIndefinido($_POST["emp_id"], $_conection);
					}
					$emp_do_nombreCam = CamellizarConGuiones(utf8_encode($row_sqlEmpleados["emp_do_nombre"]));

					//Archivo Contrato
					$sql = 	sprintf("SELECT * FROM a_tbl_pagina WHERE pag_id=1");
					$rs_sql = mysqli_query($conexion, $sql);
					$row_pagina = mysqli_fetch_assoc($rs_sql);
					$nombreadministrador = explode("|",utf8_encode($row_pagina["pag_titulo"]));
					$nombreadministrador = $nombreadministrador[1];
					$logoadministador =  $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global._carpetaAdministrador."/img/".utf8_encode($row_pagina["pag_logo2"]).'?cache=2';

					$pathFile = '../../imagenes-contenidos/empleados/'.$_POST["emp_id"].'/contrato-laboral-'.$emp_do_nombreCam.'.pdf';

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
					
					$mail->Subject = utf8_encode('Contrato Laboral');
					$mail->MsgHTML(include("templates/registrarEmpleado.php"));
					$mail->AddAttachment($pathFile, 'contrato-laboral-'.$emp_do_nombreCam.'.pdf');
					$mail->Send();
				}

				//Enviar PDF al usuario
				$result["success"] = true;
			}
		}

		// 



	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>