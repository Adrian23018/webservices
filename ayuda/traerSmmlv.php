<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();
	
	$sqlSmmlv =  sprintf("SELECT * FROM tbl_smmlv WHERE sl_id=1");
	$rs_sqlSmmlv = mysqli_query($_conection->connect(), $sqlSmmlv);
	while( $row_sqlSmmlv = mysqli_fetch_assoc($rs_sqlSmmlv) ){
		$result["salario"] = utf8_encode($row_sqlSmmlv["sl_salario"]);
	}

	$response->result = $result;
	echo json_encode($response);
?>
