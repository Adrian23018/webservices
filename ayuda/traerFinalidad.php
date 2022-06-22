<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();
	
	$_POST = json_decode(file_get_contents('php://input'), true);
    $_POST['adele_idioma_cms'] = $_POST['adele_idioma_cms'] ?? 1;

	$sqlFinalidad =  sprintf("SELECT * FROM tbl_finalidad WHERE fld_idi_id=%s",
		GetSQLValueString($_POST['adele_idioma_cms'], "int")
	);
	$rs_sqlFinalidad = mysqli_query($_conection->connect(), $sqlFinalidad);
	$result = [];
	while( $row_sqlFinalidad = mysqli_fetch_assoc($rs_sqlFinalidad) ){
		$result["titulo"] = utf8_encode($row_sqlFinalidad["fld_nombre"]);
		$result["descripcion"] = utf8_encode($row_sqlFinalidad["fld_descripcion"]);
	}
	
	$response->result = $result;
	echo json_encode($response);
?>
