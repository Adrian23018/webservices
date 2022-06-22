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
		if ($_POST['nombre_padre']) {
			if (!preg_match( $patronTexto, trim($_POST['nombre_padre']) )) {
				$error=true;
				$result["error"] = 1;
			}
		}

		if ($_POST['nombre_madre']) {
			if (!preg_match( $patronTexto, trim($_POST['nombre_madre']) )) {
				$error=true;
				$result["error"] = 1;
			}
		}

		foreach ($_POST['hermanos'] as $key => $hermano) {
			if ($hermano['nombre']) {
				if (!preg_match( $patronTexto, trim($hermano['nombre']) )) {
					$error=true;
					$result["error"] = 1;
				}		
			}
		}

		foreach ($_POST['hijos'] as $key => $hijo) {
			if ($hijo['nombre']) {
				if (!preg_match( $patronTexto, trim($hijo['nombre']) )) {
					$error=true;
					$result["error"] = 1;
				}		
			}
		}

		if(!$error){

			//Editar
			$sqlEmpD = sprintf("UPDATE tbl_empleados SET emp_nombre_padre=%s, emp_nombre_madre=%s WHERE emp_usu_id=%s AND emp_id=%s",
					GetSQLValueString($_POST['nombre_padre'],"text"),
					GetSQLValueString($_POST['nombre_madre'],"text"),
					GetSQLValueString($id,"double"),
					GetSQLValueString($_POST['emp_id'],"double")
			);
			$rs_sqlEmpD = mysqli_query($_conection->connect(), $sqlEmpD);

			//Eliminamos hermanos e hijos
			$sqlHmD = sprintf("DELETE FROM `tbl_hermanos` WHERE hm_usu_id=%s AND hm_emp_id=%s",
					GetSQLValueString($id,"double"),
					GetSQLValueString($_POST['emp_id'],"double")
			);
			$rs_sqlHmD = mysqli_query($_conection->connect(), $sqlHmD);

			$sqlHjD = sprintf("DELETE FROM `tbl_hijos` WHERE hj_usu_id=%s AND hj_emp_id=%s",
					GetSQLValueString($id,"double"),
					GetSQLValueString($_POST['emp_id'],"double")
			);
			$rs_sqlHjD = mysqli_query($_conection->connect(), $sqlHjD);

			foreach ($_POST['hermanos'] as $key => $hermano) {
				if ($hermano['nombre']) {
					$sqlHmI = sprintf("INSERT INTO `tbl_hermanos` (`hm_usu_id`, `hm_emp_id`, `hm_nombre`) VALUES (%s,%s,%s)",
							GetSQLValueString($id,"double"),
							GetSQLValueString($_POST['emp_id'],"double"),
							GetSQLValueString($hermano['nombre'],"text")
					);
					$rs_sqlHmI = mysqli_query($_conection->connect(), $sqlHmI);
				}
			}

			foreach ($_POST['hijos'] as $key => $hijo) {
				if ($hijo['nombre']) {
					$sqlHjI = sprintf("INSERT INTO `tbl_hijos` (`hj_usu_id`, `hj_emp_id`, `hj_nombre`) VALUES (%s,%s,%s)",
							GetSQLValueString($id,"double"),
							GetSQLValueString($_POST['emp_id'],"double"),
							GetSQLValueString($hijo['nombre'],"text")
					);
					$rs_sqlHjI = mysqli_query($_conection->connect(), $sqlHjI);	
				}
			}

			
			//Result
			$sqlEmpleados =  sprintf("SELECT `emp_id`, `emp_usu_id`, `emp_nombre_padre`, `emp_nombre_madre`
									 FROM tbl_empleados WHERE emp_usu_id=%s AND emp_id=%s ",
				GetSQLValueString($id, "double"),
				GetSQLValueString($_POST['emp_id'], "double")
			);
			$rs_sqlEmpleados = mysqli_query($_conection->connect(), $sqlEmpleados);
			$row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados);

			$result["emp_nombre_padre"] = utf8_encode($row_sqlEmpleados["emp_nombre_padre"]);
			$result["emp_nombre_madre"] = utf8_encode($row_sqlEmpleados["emp_nombre_madre"]);

			//Hermanos
			$hermanos = array();
			$sqlHermanos =  sprintf("SELECT `hm_emp_id`, `hm_nombre`
									 FROM tbl_hermanos WHERE hm_emp_id=%s",
				GetSQLValueString($_POST['emp_id'], "double")
			);
			$rs_sqlHermanos = mysqli_query($_conection->connect(), $sqlHermanos);
			while( $row_sqlHermanos = mysqli_fetch_assoc($rs_sqlHermanos) ){
				$hermano["nombre"] = utf8_encode($row_sqlHermanos['hm_nombre']);
				array_push($hermanos, $hermano);
			}
			$result['emp_hermanos'] = $hermanos;

			//Hijos
			$hijos = array();
			$sqlHijos =  sprintf("SELECT `hj_emp_id`, `hj_nombre`
									 FROM tbl_hijos WHERE hj_emp_id=%s",
				GetSQLValueString($_POST['emp_id'], "double")
			);
			$rs_sqlHijos = mysqli_query($_conection->connect(), $sqlHijos);
			while( $row_sqlHijos = mysqli_fetch_assoc($rs_sqlHijos) ){
				$hijo["nombre"] = utf8_encode($row_sqlHijos['hj_nombre']);
				array_push($hijos, $hijo);
			}
			$result['emp_hijos'] = $hijos;

			$result["success"] = true;
		}

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>