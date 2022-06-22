<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);

	if( $_POST["tipo"] == 'email'){
		if($_POST["email"]=="" || $_POST["email"]==_def_email || !preg_match("#^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$#", $_POST["email"])) {
			$error=true;
			$result["error"] = 1;
		}else{
			$sqlCorreo = sprintf("SELECT * FROM tbl_usuarios WHERE usu_tipo='email' AND usu_email=%s",
				GetSQLValueString($_POST["email"], "text")
			);
			$rs_sqlCorreo = mysqli_query($conexion, $sqlCorreo);
			$row_sqlCorreo = mysqli_fetch_assoc($rs_sqlCorreo);
			if ($row_sqlCorreo["usu_id"]) {
				$error=true;
				$result["error"] = 2;
			}
		}

		if ( $_POST["contrasena"] != $_POST["contrasena2"] ) {
			$error=true;
			$result["error"] = 3;
		}
	}else{
		if ( !$_POST["id"] ) {
			$error=true;
			$result["error"] = 4;
		}

		$_POST["contrasena"] = '';
	}

	$patronTexto = "/^[A-Z][a-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ ]+$/i";
	if (!$_POST['nombres'] || !preg_match( $patronTexto, trim($_POST['nombres']) )) {
		$error=true;
		$result["error"] = 5;
	}

	if (!$_POST['apellidos'] || !preg_match( $patronTexto, trim($_POST['apellidos']) )) {
		$error=true;
		$result["error"] = 6;
	}

	if ($_POST['nacionalidad']['id'] == 1) {
		$nacionalidad = 'colombia';
	}elseif ($_POST['nacionalidad']['id'] == 2) {
		$nacionalidad = 'panama';
	}

	if (!$error) {
		$contrasena = (($_POST["contrasena"]) ? crypt($_POST["contrasena"]) : '');
		$sqlUsuario = sprintf("INSERT INTO tbl_usuarios 
				(
					usu_tipo,
					usu_nombres,
					usu_apellidos,
					usu_email,
					usu_contrasena,
					usu_id_redes,
					usu_nacionalidad
				)
				VALUES
				(%s,%s,%s,%s,%s,%s,%s)
			",
				GetSQLValueString(utf8_decode($_POST["tipo"]),"text"),
				GetSQLValueString(utf8_decode($_POST["nombres"]),"text"),
				GetSQLValueString(utf8_decode($_POST["apellidos"]),"text"),
				GetSQLValueString(utf8_decode($_POST["email"]),"text"),
				GetSQLValueString(utf8_decode($contrasena),"text"),
				GetSQLValueString(utf8_decode($_POST["id"]),"text"),
				GetSQLValueString(utf8_decode($nacionalidad),"text")
		);

		$rs_sqlUsuario = mysqli_query($conexion, $sqlUsuario);

		if ($rs_sqlUsuario) {

			$result["success"] = true;

			$idRegistro = mysqli_insert_id($conexion);

			//Generar Token
			$token = crearToken($idRegistro, $_POST["tipo"], $_POST["email"]);
			$result['token'] = $token;
			$result['nacionalidad'] = $_POST['nacionalidad']['id'];
			$result['tipo'] = $_POST['tipo'];
			$result['id'] = $idRegistro;

			//Contenido
			$data = $_POST['imagenTemporal'];
			$data = base64_decode(preg_replace("#^data:image/\w+;base64,#i", "", $data));

			$carpeta = 'usuarios';
			//Creamos Carpeta Global
			if(!file_exists($pathFile.$carpeta) && $carpeta){
				mkdir($pathFile.$carpeta,0777);
			}
			$pathFileId = $pathFile.$carpeta.'/'.$idRegistro;
			//Creamos Carpeta Archivo
			if(!file_exists($pathFileId) && $idRegistro){
				mkdir($pathFileId,0777);
			}
			
			if (file_put_contents($pathFile.'usuarios/'.$idRegistro.'/imagenPerfil.jpg', $data)) {
				$sqlImagen = sprintf("UPDATE tbl_usuarios SET usu_imagen=%s WHERE usu_id=%s",
						GetSQLValueString("imagenPerfil.jpg","text"),
						GetSQLValueString($idRegistro,"double")
				);
				$rs_sqlImagen = mysqli_query($conexion, $sqlImagen);
			}

			//Registrar Token FCM
			guardarToken($_conection, $idRegistro, $_POST['accessTokenUkuIncdustry'], $_POST['platform'], $_POST['model']);

		}
	}

	$response->result = $result;
	echo json_encode($response);
?>