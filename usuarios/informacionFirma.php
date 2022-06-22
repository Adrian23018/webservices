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

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		$sqlMiPerfil =  sprintf("SELECT usu_id,usu_firma_digital FROM tbl_usuarios WHERE usu_id=%s",
			GetSQLValueString($id, "double")
		);
		$rs_sqlMiPerfil = mysqli_query($_conection->connect(), $sqlMiPerfil);
		$row_sqlMiPerfil = mysqli_fetch_assoc($rs_sqlMiPerfil);

		$result["imagenFirma"] = '';
		//Validamos que exista
		if ($row_sqlMiPerfil["usu_firma_digital"] && file_exists($pathFile.'usuarios/'.$row_sqlMiPerfil["usu_id"].'/'.$row_sqlMiPerfil["usu_firma_digital"])) {
			$result["imagenFirma"] =  $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/usuarios/".$row_sqlMiPerfil["usu_id"]."/".$row_sqlMiPerfil["usu_firma_digital"];
		}

		$result["success"] = true;
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>