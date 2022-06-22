<?php
	// Headers App
	require("../_functions/headers_options.php");
	
	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');
	//require_once 'config.php';

	$result['success'] = false;

	$_POST = json_decode(file_get_contents('php://input'), true);
	//Valores Requeridos (Datos Normales)
	$dataRequired = array('correo','codigo', 'contrasena', 'confirmar');
	foreach ($_POST as $key => $valorData) {
		if (in_array($key, $dataRequired)) {
			if (!$valorData) {
				$error=true;
				$result["error_campos"][$key] = true;
				$result["error_mensaje"][$key] = '';
			}
		}
	}

	$sqlCorreo = sprintf("SELECT * FROM tbl_usuarios WHERE usu_email=%s AND usu_codigo_verificacion=%s",
				GetSQLValueString(utf8_decode($_POST['correo']), "text"),
				GetSQLValueString(utf8_decode($_POST['codigo']), "text")
			);
	$rs_sqlCorreo = mysqli_query($_conection->connect(),$sqlCorreo);
	$row_sqlCorreo = mysqli_fetch_assoc($rs_sqlCorreo);
	if ($row_sqlCorreo["usu_id"]) {
		if (!$error) {
			if ($_POST['contrasena'] == $_POST['confirmar']) {
				$sqlContrasena = sprintf("UPDATE tbl_usuarios SET usu_contrasena=%s, usu_codigo_verificacion='' WHERE usu_email=%s AND usu_tipo='email' ",
					GetSQLValueString(crypt($_POST['contrasena']),"text"),
					GetSQLValueString(utf8_decode($_POST['correo']), "text")
				);
				$rs_sqlContrasena = mysqli_query($_conection->connect(),$sqlContrasena);
				if ($rs_sqlContrasena) {
					$result["success"] = true;
				}else{
					$error=true;
					$result["error_campos"]['contrasena'] = true;
					$result["error_mensaje"]['contrasena'] = 'Se generó un error, por favor intente mas tarde.';
				}
			}else{
				$error=true;
				$result["error_campos"]['contrasena'] = true;
				$result["error_mensaje"]['contrasena'] = 'Las contraseñas no coinciden';	
			}
		}else{
			$error=true;
			$result["error_campos"]['contrasena'] = true;
			$result["error_mensaje"]['contrasena'] = 'Escriba las contraseñas';
		}
	}else{
		$error=true;
		$result["error_campos"]['correo'] = true;
		$result["error_mensaje"]['correo'] = 'El correo y el código son inválidos';
	}


	$response->result = $result;
	echo json_encode($response);
?>