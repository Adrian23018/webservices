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
		// Verificando empleado
		$sqlPermisos =  sprintf("SELECT emp_id FROM tbl_empleados WHERE emp_usu_id=%s AND emp_id=%s",
			GetSQLValueString($id, "double"),
			GetSQLValueString($_POST["id"], "double")
		);
		$rs_sqlPermisos = mysqli_query($_conection->connect(), $sqlPermisos);
		$row_sqlPermisos = mysqli_fetch_assoc($rs_sqlPermisos);

		if ($row_sqlPermisos["emp_id"]) {
			//Eliminando Dependientes
			$sqlDeleteDep =  sprintf("DELETE FROM tbl_dependientes WHERE dp_usu_id=%s AND dp_emp_id=%s",
				GetSQLValueString($id, "double"),
				GetSQLValueString($_POST["id"], "double")
			);
			$rs_sqlDeleteDep = mysqli_query($_conection->connect(), $sqlDeleteDep);


			$sqlDeleteEmp =  sprintf("DELETE FROM tbl_empleados WHERE emp_usu_id=%s AND emp_id=%s",
				GetSQLValueString($id, "double"),
				GetSQLValueString($_POST["id"], "double")
			);
			$rs_sqlDeleteEmp = mysqli_query($_conection->connect(), $sqlDeleteEmp);

			$result["success"] = true;
		}else{
			$result['error'] = -100;	
		}
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>