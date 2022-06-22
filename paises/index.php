<?php
	// Headers App
	require("../_functions/headers_options.php");
	
	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();
	//require_once 'config.php';

	//if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
	//{
		$data = json_decode(file_get_contents('php://input'), true);
		$sql = sprintf("SELECT * FROM tbl_pais ORDER BY bl_nombre ASC");
		$rs_sql = mysqli_query($conexion, $sql);
		$paises = array();
		while($row_sql = mysqli_fetch_assoc($rs_sql)){
			$pais["id"] = utf8_encode($row_sql["bl_id"]);
			$pais["value"] = utf8_encode($row_sql["bl_nombre"]);
			array_push($paises, $pais);
		}
		$result["paises"] = $paises;
	//}

	$response->result = $result;
	echo json_encode($response);
?>