<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');

	$_POST = json_decode(file_get_contents('php://input'), true);

	$sqlUsuario = sprintf("SELECT * FROM tbl_usuarios WHERE usu_tipo=%s AND usu_id_redes=%s",
		GetSQLValueString($_POST["tipo"], "text"),
		GetSQLValueString($_POST["id"], "text")
	);
	$rs_sqlUsuario = mysqli_query($_conection->connect(), $sqlUsuario);
	$row_sqlUsuario = mysqli_fetch_assoc($rs_sqlUsuario);

	if ($row_sqlUsuario["usu_id"]) {
		$result["registrado"] = 'si';

		//Generar Token
		$token = crearToken($row_sqlUsuario["usu_id"], $row_sqlUsuario["usu_tipo"], $row_sqlUsuario["usu_email"]);
		$result['token'] = $token;
		if ($row_sqlUsuario["usu_nacionalidad"] == 'colombia') {
			$result['nacionalidad'] = 1;
		}elseif ($row_sqlUsuario["usu_nacionalidad"] == 'panama') {
			$result['nacionalidad'] = 2;
		}
		$result['tipo'] = $row_sqlUsuario["usu_tipo"];
		
		//Registrar Token FCM
		guardarToken ($_conection, $row_sqlUsuario['usu_id'], $_POST['accessTokenUkuIncdustry'], $_POST['platform'], $_POST['model']);

	}else{
		$result["registrado"] = 'no';
	}

	$result["success"] = true;

	$response->result = $result;
	echo json_encode($response);
?>