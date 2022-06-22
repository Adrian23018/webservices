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

		$patronTexto = "/^[A-Z][a-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ ]+$/i";
		$patronTextoNumeros = "/^[A-Za-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ0-9#_\- ]+$/i";
		$patronNumeros = "/^[0-9]+$/i";

		if ($_POST['medico']) {
			if (!preg_match( $patronTexto, trim($_POST['medico']) )) {
				$error=true;
				$result["error"] = 1;
			}
		}

		if ($_POST['telefono']){
			if(!preg_match( $patronNumeros, trim($_POST['telefono']) )) {
				$error = true;
				$result["error"] = 5;
			}
		}


		if(!$error){

			//Editar
			$sqlContactoE = sprintf("UPDATE tbl_empleados SET `emp_ie_tiposangre`=%s, `emp_ie_alergias`=%s, `emp_ie_medico`=%s, `emp_ie_telefono`=%s, `emp_ie_notas`=%s WHERE emp_usu_id=%s AND emp_id=%s",
					GetSQLValueString($_POST['tiposangre'],"text"),
					GetSQLValueString($_POST['alergias'],"text"),
					GetSQLValueString($_POST['medico'],"text"),
					GetSQLValueString($_POST['telefono'],"double"),
					GetSQLValueString($_POST['notas'],"text"),
					GetSQLValueString($id,"double"),
					GetSQLValueString($_POST['emp_id'],"double")
			);
			$rs_sqlContactoE = mysqli_query($_conection->connect(), $sqlContactoE);

			
			//Result
			$sqlEmpleados =  sprintf("SELECT `emp_id`, `emp_usu_id`, `emp_ie_tiposangre`, `emp_ie_alergias`, `emp_ie_medico`, `emp_ie_telefono`, `emp_ie_notas`
									 FROM tbl_empleados WHERE emp_usu_id=%s AND emp_id=%s ",
				GetSQLValueString($id, "double"),
				GetSQLValueString($_POST['emp_id'], "double")
			);
			$rs_sqlEmpleados = mysqli_query($_conection->connect(), $sqlEmpleados);
			$row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados);

			$result["emp_ie_tiposangre"] = utf8_encode($row_sqlEmpleados["emp_ie_tiposangre"]);
			$result["emp_ie_alergias"] = utf8_encode($row_sqlEmpleados["emp_ie_alergias"]);
			$result["emp_ie_medico"] = utf8_encode($row_sqlEmpleados["emp_ie_medico"]);
			$result["emp_ie_telefono"] = utf8_encode($row_sqlEmpleados["emp_ie_telefono"]);
			$result["emp_ie_notas"] = utf8_encode($row_sqlEmpleados["emp_ie_notas"]);
			

			$result["success"] = true;
		}

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>