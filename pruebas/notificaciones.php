<?php
	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();
	error_reporting(1);

	//ENVIAMOS NOTIFICACION PUSH AL USUARIO
	$usuId = 12;
	$sqlTokens = sprintf("SELECT * FROM tbl_usuarios_token");
	$rs_sqlTokens = mysqli_query($conexion, $sqlTokens);
	$iTok = 0;
	$registrationIdsAndroid = array();
	$registrationIdsIos = array();
	while( $row_sqlTokens = mysqli_fetch_assoc($rs_sqlTokens) ){
		if ($row_sqlTokens["ut_platform"] == 'android') {
			array_push($registrationIdsAndroid, $row_sqlTokens["ut_token"]);
		}elseif ($row_sqlTokens["ut_platform"] == 'ios') {
			array_push($registrationIdsIos, $row_sqlTokens["ut_token"]);
		}
		$iTok++;
	}

	// Uku - Notificaciones de Xiii
	$title = 'Uku - Notificación de xiii';
	$body = 'Recordatorio de pagos de décimo tercer mes';
	var_dump($registrationIdsAndroid);
	var_dump($registrationIdsIos);
	envioNotificacionesPush($title, $body, $registrationIdsAndroid, 'android');
	envioNotificacionesPush($title, $body, $registrationIdsIos, 'ios');
?>