<?php
	// Headers App
	require("../_functions/headers_options.php");
	
	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');
	//require_once 'config.php';

	$result['success'] = false;

	$_POST = json_decode(file_get_contents('php://input'), true);
	//Valores Requeridos (Datos Normales)
	$dataRequired = array('correo','codigo');
	foreach ($_POST as $key => $valorData) {
		if (in_array($key, $dataRequired)) {
			if (!$valorData) {
				$error=true;
				$result["error_campos"][$key] = true;
				$result["error_mensaje"][$key] = '';
			}
		}
	}

	$sqlCorreo = sprintf("SELECT * FROM tbl_usuarios WHERE usu_email=%s AND usu_tipo='email' ",
				GetSQLValueString(utf8_decode($_POST['correo']), "text")
			);
	$rs_sqlCorreo = mysqli_query($_conection->connect(), $sqlCorreo);
	$row_sqlCorreo = mysqli_fetch_assoc($rs_sqlCorreo);
	if ($row_sqlCorreo["usu_id"]) {
		if ($errorInterno != 1) {
			if ($_POST['codigo'] == $row_sqlCorreo['usu_codigo_verificacion']) {
				$result["success"] = true;
			}else{
				$error=true;
				$result["error_campos"]['codigo'] = true;
				$result["error_mensaje"]['codigo'] = 'Lo sentimos, el código es incorrecto.';
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