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
		//Contenido
		$sqlMiPerfil =  sprintf("SELECT usu_firma_digital FROM tbl_usuarios WHERE usu_id=%s",
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
			
			unlink($pathFileId.'/'.$row_sqlMiPerfil["usu_firma_digital"]);
			$uniqid = uniqid();
			if (file_put_contents($pathFile.'usuarios/'.$id.'/imagenFirma'.$uniqid.'.jpg', $data)) {
				$sqlImagen = sprintf("UPDATE tbl_usuarios SET usu_firma_digital=%s WHERE usu_id=%s",
						GetSQLValueString("imagenFirma".$uniqid.".jpg","text"),
						GetSQLValueString($id,"double")
				);
				$rs_sqlImagen = mysqli_query($_conection->connect(), $sqlImagen);
				$result["imagenNueva"] = $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/usuarios/".$id."/imagenFirma".$uniqid.".jpg";
			}
		}else{
			$result["imagenNueva"] = $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/usuarios/".$id."/".$row_sqlMiPerfil["usu_firma_digital"];
		}

		//$_POST["imagenGuardar"]

		$result["success"] = true;
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>