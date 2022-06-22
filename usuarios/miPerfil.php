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
		$sqlMiPerfil =  sprintf("SELECT usu_id, usu_tipo, usu_email, usu_id_redes, usu_nombres, usu_apellidos, usu_imagen, usu_sexo, usu_pais, usu_no_identidad, usu_domicilio FROM tbl_usuarios WHERE usu_id=%s",
			GetSQLValueString($id, "double")
		);
		$rs_sqlMiPerfil = mysqli_query($_conection->connect(), $sqlMiPerfil);
		$row_sqlMiPerfil = mysqli_fetch_assoc($rs_sqlMiPerfil);

		$result["nombres"] = utf8_encode($row_sqlMiPerfil['usu_nombres']);
		$result["apellidos"] = utf8_encode($row_sqlMiPerfil['usu_apellidos']);
		$result["email"] = utf8_encode($row_sqlMiPerfil['usu_email']);
		$result["imagenPerfil"] = '';
		//Validamos que exista
		if ($row_sqlMiPerfil["usu_imagen"] && file_exists($pathFile.'usuarios/'.$row_sqlMiPerfil["usu_id"].'/'.$row_sqlMiPerfil["usu_imagen"])) {
			$result["imagenPerfil"] =  $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/usuarios/".$row_sqlMiPerfil["usu_id"]."/".$row_sqlMiPerfil["usu_imagen"];
		}elseif($row_sqlMiPerfil["usu_tipo"] == 'facebook'){
			$result["imagenPerfil"] = "http://graph.facebook.com/".$row_sqlMiPerfil["usu_id_redes"]."/picture?type=large"; 
		}

		$result["success"] = true;
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>