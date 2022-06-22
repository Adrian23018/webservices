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

		$patronTextoNumeros = "/^[A-Za-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ0-9#_\- ]+$/i";
		if (!$_POST['domicilio'] || !preg_match( $patronTextoNumeros, trim($_POST['domicilio'])) ) {
			$error = true;
			$result["error"] = 2;
		}

		if(!$error){

			//Contenido
			$sqlEmpleado =  sprintf("SELECT emp_usu_id, emp_id, emp_imagen, emp_imagen_doc FROM tbl_empleados WHERE emp_usu_id=%s AND emp_id=%s",
				GetSQLValueString($id, "double"),
				GetSQLValueString($_POST['emp_id'], "double")
			);
			$rs_sqlEmpleado = mysqli_query($_conection->connect(), $sqlEmpleado);
			$row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado);
            
            if( $_POST['fecha_nacimiento'] ){
    		    list($dia,$mes,$anho) = explode("/", $_POST['fecha_nacimiento']);
    		    $_POST['fecha_nacimiento'] = $anho.'-'.$mes.'-'.$dia;    
    		}
		
			$sqlEmpD = sprintf("UPDATE tbl_empleados SET emp_do_domicilio=%s, emp_do_fechanacimiento=%s WHERE emp_usu_id=%s AND emp_id=%s",
					GetSQLValueString($_POST['domicilio'],"text"),
					GetSQLValueString($_POST['fecha_nacimiento'],"text"),
					GetSQLValueString($id,"double"),
					GetSQLValueString($_POST['emp_id'],"double")
			);
			$rs_EmpD = mysqli_query($_conection->connect(), $sqlEmpD);

			if ($_POST['imagenTemporal']) {
				$data = $_POST['imagenTemporal'];
				$data = base64_decode(preg_replace("#^data:image/\w+;base64,#i", "", $data));

				$carpeta = 'empleados';
				//Creamos Carpeta Global
				if(!file_exists($pathFile.$carpeta) && $carpeta){
					mkdir($pathFile.$carpeta,0777);
				}
				$pathFileId = $pathFile.$carpeta.'/'.$_POST['emp_id'];
				//Creamos Carpeta Archivo
				if(!file_exists($pathFileId) && $_POST['emp_id']){
					mkdir($pathFileId,0777);
				}
				
				unlink($pathFileId.'/'.$row_sqlEmpleado["emp_imagen"]);
				$uniqid = uniqid();
				if (file_put_contents($pathFile.'empleados/'.$_POST['emp_id'].'/imagen'.$uniqid.'.jpg', $data)) {
					$sqlImagen = sprintf("UPDATE tbl_empleados SET emp_imagen=%s WHERE emp_usu_id=%s AND emp_id=%s",
							GetSQLValueString("imagen".$uniqid.".jpg","text"),
							GetSQLValueString($id,"double"),
							GetSQLValueString($_POST['emp_id'],"double")
					);
					$rs_sqlImagen = mysqli_query($_conection->connect(), $sqlImagen);
					$result["imagen"] = $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/empleados/".$_POST['emp_id']."/imagen".$uniqid.".jpg";
				}
			}else if($row_sqlEmpleado['emp_imagen']){
				$result["imagen"] = $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/empleados/".$_POST['emp_id']."/".$row_sqlEmpleado["emp_imagen"];
			}else{
				$result['imagen'] = '';
			}
			
			if ($_POST['imagenTemporalDoc']) {
				$data = $_POST['imagenTemporalDoc'];
				$data = base64_decode(preg_replace("#^data:image/\w+;base64,#i", "", $data));

				$carpeta = 'empleados';
				//Creamos Carpeta Global
				if(!file_exists($pathFile.$carpeta) && $carpeta){
					mkdir($pathFile.$carpeta,0777);
				}
				$pathFileId = $pathFile.$carpeta.'/'.$_POST['emp_id'];
				//Creamos Carpeta Archivo
				if(!file_exists($pathFileId) && $_POST['emp_id']){
					mkdir($pathFileId,0777);
				}
				
				unlink($pathFileId.'/'.$row_sqlEmpleado["emp_imagen_doc"]);
				$uniqid = uniqid();
				if (file_put_contents($pathFile.'empleados/'.$_POST['emp_id'].'/imagenDoc'.$uniqid.'.jpg', $data)) {
					$sqlImagen = sprintf("UPDATE tbl_empleados SET emp_imagen_doc=%s WHERE emp_usu_id=%s AND emp_id=%s",
							GetSQLValueString("imagenDoc".$uniqid.".jpg","text"),
							GetSQLValueString($id,"double"),
							GetSQLValueString($_POST['emp_id'],"double")
					);
					$rs_sqlImagen = mysqli_query($_conection->connect(), $sqlImagen);
					$result["imagenDoc"] = $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/empleados/".$_POST['emp_id']."/imagenDoc".$uniqid.".jpg";
				}
			}else if($row_sqlEmpleado['emp_imagen_doc']){
				$result["imagenDoc"] = $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/empleados/".$_POST['emp_id']."/".$row_sqlEmpleado["emp_imagen_doc"];
			}else{
				$result['imagenDoc'] = '';
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