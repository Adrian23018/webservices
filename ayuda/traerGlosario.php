<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();
	
	$_POST = json_decode(file_get_contents('php://input'), true);
    $_POST['adele_idioma_cms'] = $_POST['adele_idioma_cms'] ?? 1;

	$sqlGlosario =  sprintf("SELECT * FROM tbl_glosario WHERE gso_idi_id=%s ORDER BY gso_posicion",
		GetSQLValueString($_POST['adele_idioma_cms'], "int")
	);
	$rs_sqlGlosario = mysqli_query($_conection->connect(), $sqlGlosario);
	$glosarios = array();
	while( $row_sqlGlosario = mysqli_fetch_assoc($rs_sqlGlosario) ){
		$glosario["termino"] = utf8_encode($row_sqlGlosario["gso_nombre"]);
		$glosario["definicion"] = utf8_encode($row_sqlGlosario["gso_descripcion_corta"]);
		array_push($glosarios, $glosario);
	}
	$result['glosarios'] = $glosarios;

	$response->result = $result;
	echo json_encode($response);
?>
