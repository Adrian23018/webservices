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
		$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);
		
		// Crear PDF sin Firma
		if ($firma) {
			$firmaEmp = $_POST['imagenGuardar'];
		}

		// Enviar Correo con Contrato.
		$sqlUsuario = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s",
			GetSQLValueString($id, "double")
		);
		$rs_sqlUsuario = mysqli_query($conexion, $sqlUsuario);
		$row_sqlUsuario = mysqli_fetch_assoc($rs_sqlUsuario);

		$sqlVariables = sprintf("SELECT * FROM tbl_variables_panama");
		$rs_sqlVariables = mysqli_query($_conection->connect(), $sqlVariables);
		$row_sqlVariables = mysqli_fetch_assoc($rs_sqlVariables);

		$sqlEmpleado = sprintf("SELECT * FROM tbl_empleados WHERE emp_id=%s AND emp_estado=3",
			GetSQLValueString($emp_id, "double")
		);
		$rs_sqlEmpleado = mysqli_query($_conection->connect(), $sqlEmpleado);
		$row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado);
		$emp_cond_jornada = $row_sqlEmpleado['emp_cond_jornada'];
		$emp_cond_semanas = $row_sqlEmpleado['emp_cond_semanas'];
		$emp_cond_periodo = $row_sqlEmpleado['emp_cond_periodo'];
		$emp_cond_termino = $row_sqlEmpleado['emp_cond_termino'];
		$emp_cond_sueldo = $row_sqlEmpleado['emp_cond_sueldo'];

		$sqlEmpCamb = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`<=%s AND `es_cond_fecha_relacion`<=%s ORDER BY `es_cond_fecha_relacion` DESC LIMIT 0,1",
			GetSQLValueString($emp_id, "double"),
			GetSQLValueString($fechaSimulacion, "date")
		);
		$rs_sqlEmpCamb = mysqli_query($_conection->connect(), $sqlEmpCamb);
		while ($row_sqlEmpCamb = mysqli_fetch_assoc($rs_sqlEmpCamb)) {
			$emp_cond_jornada = $row_sqlEmpCamb['es_cond_jornada'];
			$emp_cond_semanas = $row_sqlEmpCamb['es_cond_semanas'];
			$emp_cond_periodo = $row_sqlEmpCamb['es_cond_periodo'];
			$emp_cond_termino = $row_sqlEmpCamb['es_cond_termino'];
			$emp_cond_sueldo = $row_sqlEmpCamb['es_cond_sueldo'];
		}

		//Calcular días de pago para el usuario.
		if ($emp_cond_jornada == 1 ) {
			$diasLaborales = 5;
			$empiezaSemana = 1;
			$terminaSemana = 5;
			$emp_cond_semanas = "1,2,3,4,5";
		}elseif ($emp_cond_jornada == 2 ) {
			$diasLaborales = 6;
			$empiezaSemana = 1;
			$terminaSemana = 6;
			$emp_cond_semanas = "1,2,3,4,5,6";
		}else {
			$diasLaborales = count(explode(',', $emp_cond_semanas));
			$empiezaSemana = explode(',', $emp_cond_semanas)[0];
			$terminaSemana = end(explode(',', $emp_cond_semanas));
		}

		$salarioDiario = $salarioSemanal = $salarioQuincenal = $salarioMensual = 0;
		if($emp_cond_termino == 1){
			// Hora
			$salarioDiario = $emp_cond_sueldo * $row_sqlVariables['vp_horas'];
		}elseif($emp_cond_termino == 2){
			// Dia
			$salarioDiario = $emp_cond_sueldo;
		}elseif($emp_cond_termino == 3){
			// Mes
			$salarioDiario = $emp_cond_sueldo / $row_sqlVariables['vp_semanas'] / $diasLaborales;
		}

		// Domingo o Descanso
		if ( $_POST['laborado']['id'] == 1 || $_POST['laborado']['id'] == 2 ) {
			if ($_POST['laborado']['id'] == 1) {
				$dia = 'Domingo';
			}elseif ($_POST['laborado']['id'] == 2) {
				$dia = 'Descanso';
			}
			$porcentaje = $row_sqlVariables['vp_diadomingo'];
		}else{
			$dia = 'Feriado';
			$porcentaje = $row_sqlVariables['vp_diaferiado'];
		}

		$result['valorPagar'] = round( $porcentaje * ($_POST['cantidad']/$row_sqlVariables['vp_horas']) * $salarioDiario, 2 );

		crearComprobanteDiasFeriados($id, 
									 utf8_encode($row_sqlEmpleado['emp_do_nombre']),
									 $dia, 
									 $_POST['cantidad'], 
									 $result['valorPagar'], 
									 $fechaSimulacion, 
									 $firmaEmp,
									 $_conection);

		$pathFile = '../../imagenes-contenidos/confirmaciones-diaespecial/confirmacion-diaespecial-'.$id.'.pdf';

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
		
		$asunto = utf8_decode('Confirmación de día de descanso, domingo o feriados '. utf8_encode($row_sqlEmpleado['emp_do_nombre']));
		$mail->Subject = "=?ISO-8859-1?B?".base64_encode($asunto)."=?=";

		$mail->MsgHTML(include("templates/confirmacion-diaespecial.php"));
		$mail->AddAttachment($pathFile, 'confirmacion-diaespecial-'.$id.'.pdf');
		if( $mail->Send() ){
			// accion
		}

		// $sqlDiaDescanso = sprintf("INSERT INTO tbl_diaespecial (`de_usu_id`, `de_emp_id`, `de_dialaborado`, `de_cantidad`, `de_acuerdo`, `de_monto`) VALUES (%s,%s,%s,%s,%s,%s) ",
		// 		GetSQLValueString($id, "double"),
		// 		GetSQLValueString($_POST["emp_id"], "double"),
		// 		GetSQLValueString(utf8_decode($_POST['laborado']['id']), "double"),
		// 		GetSQLValueString(utf8_decode($_POST['cantidad']), "double"),
		// 		GetSQLValueString(utf8_decode($_POST['acuerdo']['id']), "double"),
		// 		GetSQLValueString(utf8_decode($_POST['monto']), "double")
		// );
		// $rs_sqlDiaDescanso = mysqli_query($conexion, $sqlDiaDescanso);
		// if ($rs_sqlDiaDescanso) {
			$result["success"] = true;
		// }
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>