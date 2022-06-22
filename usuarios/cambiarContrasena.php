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
	//Valores Requeridos (Datos Normales)
	$dataRequired = array('contrasena_actual', 'contrasena_nueva');
	foreach ($_POST as $key => $valorData) {
		if (in_array($key, $dataRequired)) {
			if (!$valorData) {
				$error=true;
				$result["error_campos"][$key] = true;
				$result["error_mensaje"][$key] = '';
			}
		}
	}

	$result["success"] = false;

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		$sqlPassword = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s",
			GetSQLValueString($id, "text")
		);
		$rs_sqlPassword = mysqli_query($_conection->connect(), $sqlPassword);
		$row_sqlPassword = mysqli_fetch_assoc($rs_sqlPassword);

		$password_hash = $row_sqlPassword['usu_contrasena'];
		if(crypt($_POST['contrasena_actual'], $password_hash) != $password_hash) {
			$error=true;
			$result["error"] = 1;
		}

		if(!$error){
			$sqlUsuarioEdit = sprintf("UPDATE tbl_usuarios 
					SET
						usu_contrasena=%s
					WHERE
					usu_id=%s
				",
					GetSQLValueString(crypt($_POST['contrasena_nueva']),"text"),
					GetSQLValueString($id,"text")
			);
			$rs_sqlUsuarioEdit = mysqli_query($_conection->connect(), $sqlUsuarioEdit);

			$result["success"] = true;
		}
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>