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

		if ($_POST['nombre']) {
			if (!preg_match( $patronTexto, trim($_POST['nombre']) )) {
				$error=true;
				$result["error"] = 1;
			}
		}

		if ($_POST['cedula']){
			if(!preg_match( $patronNumeros, trim($_POST['cedula']) )) {
				$error = true;
				$result["error"] = 2;
			}
		}

		if ($_POST['pasaporte']){
			if (!preg_match( $patronNumeros, trim($_POST['pasaporte']) )) {
				$error = true;
				$result["error"] = 3;
			}
		}

		if ($_POST['parentesco']) {
			if (!preg_match( $patronTexto, trim($_POST['parentesco']) )) {
				$error=true;
				$result["error"] = 4;
			}
		}

		if ($_POST['telefono']){
			if(!preg_match( $patronNumeros, trim($_POST['telefono']) )) {
				$error = true;
				$result["error"] = 5;
			}
		}

		if ($_POST['direccion']){
			if (!preg_match( $patronTextoNumeros, trim($_POST['direccion'])) ) {
				$error = true;
				$result["error"] = 6;
			}
		}

		if(!$error){

			//Editar
			$sqlContactoE = sprintf("UPDATE tbl_empleados SET emp_ce_nombre=%s, emp_ce_cedula=%s, emp_ce_pasaporte=%s, emp_ce_parentesco=%s, emp_ce_telefono=%s, emp_ce_direccion=%s WHERE emp_usu_id=%s AND emp_id=%s",
					GetSQLValueString($_POST['nombre'],"text"),
					GetSQLValueString($_POST['cedula'],"double"),
					GetSQLValueString($_POST['pasaporte'],"double"),
					GetSQLValueString($_POST['parentesco'],"text"),
					GetSQLValueString($_POST['telefono'],"double"),
					GetSQLValueString($_POST['direccion'],"text"),
					GetSQLValueString($id,"double"),
					GetSQLValueString($_POST['emp_id'],"double")
			);
			$rs_sqlContactoE = mysqli_query($_conection->connect(), $sqlContactoE);

			
			//Result
			$sqlEmpleados =  sprintf("SELECT `emp_id`, `emp_usu_id`, `emp_ce_nombre`, `emp_ce_cedula`, `emp_ce_pasaporte`, `emp_ce_parentesco`, `emp_ce_telefono`, `emp_ce_direccion`
									 FROM tbl_empleados WHERE emp_usu_id=%s AND emp_id=%s ",
				GetSQLValueString($id, "double"),
				GetSQLValueString($_POST['emp_id'], "double")
			);
			$rs_sqlEmpleados = mysqli_query($_conection->connect(), $sqlEmpleados);
			$row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados);

			$result["emp_ce_nombre"] = utf8_encode($row_sqlEmpleados["emp_ce_nombre"]);
			$result["emp_ce_cedula"] = utf8_encode($row_sqlEmpleados["emp_ce_cedula"]);
			$result["emp_ce_pasaporte"] = utf8_encode($row_sqlEmpleados["emp_ce_pasaporte"]);
			$result["emp_ce_parentesco"] = utf8_encode($row_sqlEmpleados["emp_ce_parentesco"]);
			$result["emp_ce_telefono"] = utf8_encode($row_sqlEmpleados["emp_ce_telefono"]);
			$result["emp_ce_direccion"] = utf8_encode($row_sqlEmpleados["emp_ce_direccion"]);

			$result["success"] = true;
		}

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>