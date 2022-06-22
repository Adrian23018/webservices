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
		if($_POST["email"]=="" || $_POST["email"]==_def_email || !preg_match("#^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$#", $_POST["email"])) {
			$error=true;
			$result["error"] = 1;
		}else{
			$sqlCorreo = sprintf("SELECT * FROM tbl_usuarios WHERE usu_tipo='email' AND usu_email=%s AND usu_id!=%s",
				GetSQLValueString($_POST["email"], "text"),
				GetSQLValueString($id, "text")
			);
			$rs_sqlCorreo = mysqli_query($_conection->connect(), $sqlCorreo);
			$row_sqlCorreo = mysqli_fetch_assoc($rs_sqlCorreo);
			if ($row_sqlCorreo["usu_id"]) {
				$error=true;
				$result["error"] = 2;
			}
		}

		$patronTexto = "/^[A-Z][a-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ ]+$/i";
		$patronTextoNumeros = "/^[A-Za-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ0-9#_.,\- ]+$/i";
		$patronNumeros = "/^[0-9]+$/i";

		if (!$_POST['nombres'] || !preg_match( $patronTexto, trim($_POST['nombres']) )) {
			$error=true;
			$result["error"] = 5;
		}

		if (!$_POST['apellidos'] || !preg_match( $patronTexto, trim($_POST['apellidos']) )) {
			$error=true;
			$result["error"] = 6;
		}
		
		if (!$_POST['domicilio'] || !preg_match( $patronTextoNumeros, trim($_POST['domicilio'])) ) {
			$error = true;
			$result["error"] = 7;
		}

		if (!$_POST['no_identidad'] || !preg_match( $patronTextoNumeros, trim($_POST['no_identidad']) , $matches )) {
			$error = true;
			$result["error"] = 8;
		}

		// var_dump($_POST);
		// echo 'prueba';

		if(!$error){
			if ($_POST["hombre"]) {
				$sexo = 1;
			}else if ($_POST["mujer"]) {
				$sexo = 2;
			}

			$sqlUsuarioEdit = sprintf("UPDATE tbl_usuarios 
					SET
						usu_nombres=%s,
						usu_apellidos=%s,
						usu_email=%s,
						usu_domicilio=%s,
						usu_no_identidad=%s,
						usu_sexo=%s,
						usu_pais=%s
					WHERE
					usu_id=%s
				",
					GetSQLValueString(utf8_decode($_POST["nombres"]),"text"),
					GetSQLValueString(utf8_decode($_POST["apellidos"]),"text"),
					GetSQLValueString(utf8_decode($_POST["email"]),"text"),
					GetSQLValueString(utf8_decode($_POST["domicilio"]),"text"),
					GetSQLValueString(utf8_decode($_POST["no_identidad"]),"text"),
					GetSQLValueString(utf8_decode($sexo),"text"),
					GetSQLValueString(utf8_decode($_POST["nacionalidad"]["id"]),"text"),
					GetSQLValueString($id,"text")
			);
			$rs_sqlUsuarioEdit = mysqli_query($_conection->connect(), $sqlUsuarioEdit);

			//Contenido
			$sqlMiPerfil =  sprintf("SELECT usu_imagen FROM tbl_usuarios WHERE usu_id=%s",
				GetSQLValueString($id, "double")
			);
			$rs_sqlMiPerfil = mysqli_query($_conection->connect(), $sqlMiPerfil);
			$row_sqlMiPerfil = mysqli_fetch_assoc($rs_sqlMiPerfil);
			
			if ($_POST['imagenGuardar']) {
				$data = $_POST['imagenGuardar'];
				$data = base64_decode(preg_replace("#^data:image/\w+;base64,#i", "", $data));

				$carpeta = 'usuarios';
				//Creamos Carpeta Global
				if(!file_exists($pathFile.$carpeta) && $carpeta){
					mkdir($pathFile.$carpeta,0777);
				}
				$pathFileId = $pathFile.$carpeta.'/'.$id;
				//Creamos Carpeta Archivo
				if(!file_exists($pathFileId) && $id){
					mkdir($pathFileId,0777);
				}
				
				unlink($pathFileId.'/'.$row_sqlMiPerfil["usu_imagen"]);
				$uniqid = uniqid();
				if (file_put_contents($pathFile.'usuarios/'.$id.'/imagenPerfil'.$uniqid.'.jpg', $data)) {
					$sqlImagen = sprintf("UPDATE tbl_usuarios SET usu_imagen=%s WHERE usu_id=%s",
							GetSQLValueString("imagenPerfil".$uniqid.".jpg","text"),
							GetSQLValueString($id,"double")
					);
					$rs_sqlImagen = mysqli_query($_conection->connect(), $sqlImagen);
					$result["imagenNueva"] = $pathFile.'usuarios/'.$id.'/imagenPerfil'.$uniqid.'.jpg';
				}
			}else if($row_sqlMiPerfil["usu_imagen"]){
				$result["imagenNueva"] = $pathFile.'usuarios/'.$id.'/'.$row_sqlMiPerfil["usu_imagen"];
			}

			//$_POST["imagenGuardar"]

			$result["success"] = true;
		}
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>