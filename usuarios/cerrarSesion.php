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
	
	$result["success"] = true;

	if ($_POST['model'] && $_POST['accessTokenUkuIncdustry'] ) {
		$sqlDeleteToken = sprintf("DELETE FROM tbl_usuarios_token WHERE ut_uuid=%s",
			GetSQLValueString($_POST["model"], "text")
		);
		$rs_sqlDeleteToken = mysqli_query($_conection->connect(), $sqlDeleteToken);
	}elseif($_POST['accessTokenUkuIncdustry']){
		$sqlDeleteToken = sprintf("DELETE FROM tbl_usuarios_token WHERE ut_token=%s",
			GetSQLValueString($_POST["accessTokenUkuIncdustry"], "text")
		);
		$rs_sqlDeleteToken = mysqli_query($_conection->connect(), $sqlDeleteToken);
	}

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		// $id
	}

	$response->result = $result;
	echo json_encode($response);
?>