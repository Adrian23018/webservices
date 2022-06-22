<?php
	// Headers App
	require("../_functions/headers_options.php");
	
	// session_start();
	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	//require_once 'config.php';

	$_POST = json_decode(file_get_contents('php://input'), true);
	
	$result["empleador"] = false;
	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {

		// $sqlEmpleador =  sprintf("SELECT usu_id, usu_dor_nombre, usu_dor_hombre, usu_dor_mujer, usu_dor_nacionalidad, usu_dor_nacionalidad_value, usu_dor_no_identidad, usu_dor_domicilio FROM tbl_usuarios WHERE usu_id=%s",
		// 	GetSQLValueString($id, "double")
		// );
		$sqlEmpleador =  sprintf("SELECT usu_id, usu_nombres, usu_apellidos, usu_sexo, usu_pais, bl_nombre, usu_no_identidad, usu_domicilio
									FROM tbl_usuarios 
									LEFT JOIN tbl_pais ON bl_id=usu_pais
									WHERE usu_id=%s",
			GetSQLValueString($id, "double")
		);
		$rs_sqlEmpleador = mysqli_query($_conection->connect(), $sqlEmpleador);
		$row_sqlEmpleador = mysqli_fetch_assoc($rs_sqlEmpleador);

		if ($row_sqlEmpleador['usu_nombres']) {
			$result["empleador"] = true;
		}
		$result["usu_dor_nombre"] = utf8_encode($row_sqlEmpleador['usu_nombres']);	
		$result["usu_dor_apellido"] = utf8_encode($row_sqlEmpleador['usu_apellidos']);	

		if ($row_sqlEmpleador['usu_sexo'] == 1) {
			$result["usu_dor_hombre"] = true;
		}elseif ($row_sqlEmpleador['usu_sexo'] == 2) {
			$result["usu_dor_mujer"] = true;
		}

		$result["usu_dor_nacionalidad"] = utf8_encode($row_sqlEmpleador['usu_pais']);
		$result["usu_dor_nacionalidad_value"] = utf8_encode($row_sqlEmpleador['bl_nombre']);
		$result["usu_dor_no_identidad"] = utf8_encode($row_sqlEmpleador['usu_no_identidad']);
		$result["usu_dor_domicilio"] = utf8_encode($row_sqlEmpleador['usu_domicilio']);

		// if ($row_sqlEmpleador['usu_dor_nombre']) {
		// 	$result["empleador"] = true;
		// }
		// $result["usu_dor_nombre"] = utf8_encode($row_sqlEmpleador['usu_dor_nombre']);
		// $result["usu_dor_hombre"] = utf8_encode($row_sqlEmpleador['usu_dor_hombre']);
		// $result["usu_dor_mujer"] = utf8_encode($row_sqlEmpleador['usu_dor_mujer']);
		// $result["usu_dor_nacionalidad"] = utf8_encode($row_sqlEmpleador['usu_dor_nacionalidad']);
		// $result["usu_dor_nacionalidad_value"] = utf8_encode($row_sqlEmpleador['usu_dor_nacionalidad_value']);
		// $result["usu_dor_no_identidad"] = utf8_encode($row_sqlEmpleador['usu_dor_no_identidad']);
		// $result["usu_dor_domicilio"] = utf8_encode($row_sqlEmpleador['usu_dor_domicilio']);


	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>