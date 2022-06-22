<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();
	
	$_POST = json_decode(file_get_contents('php://input'), true);
    $_POST['adele_idioma_cms'] = $_POST['adele_idioma_cms'] ?? 1;
    
	$sqlTerminos =  sprintf("SELECT * FROM tbl_terminos WHERE tem_idi_id=%s",
		GetSQLValueString($_POST['adele_idioma_cms'], "int")
	);
	$rs_sqlTerminos = mysqli_query($_conection->connect(), $sqlTerminos);
	while( $row_sqlTerminos = mysqli_fetch_assoc($rs_sqlTerminos) ){
		$result["titulo"] = utf8_encode($row_sqlTerminos["tem_nombre"]);
		$result["descripcion"] = utf8_encode($row_sqlTerminos["tem_descripcion"]);
	}

	$response->result = $result;
	echo json_encode($response);
?>
