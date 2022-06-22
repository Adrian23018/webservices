<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		//	Condiciones de trabajo usada.
		$emp_id = $_POST["emp_id"];
		$firma = $_POST['firma'];
		$fechaSimulacion = simuladorTiempo($fechaActual, $fechaReferencia);

		// Crear PDF sin Firma
		if ($firma) {
			$firmaEmp = $_POST['imagenGuardar'];
		}

		$sqlEmpleado = sprintf("SELECT * FROM tbl_empleados WHERE emp_id=%s",
			GetSQLValueString($emp_id, "double")
		);
		$rs_sqlEmpleado = mysqli_query($_conection->connect(), $sqlEmpleado);
		$row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado);

		//Revisar si existe el prestamo
		$sqlPrest = sprintf("SELECT * FROM tbl_prestamos WHERE pr_emp_id=%s",
			GetSQLValueString($emp_id, "double")
		);
		$rs_sqlPrest = mysqli_query($_conection->connect(), $sqlPrest);
		$row_sqlPrest = mysqli_fetch_assoc($rs_sqlPrest);
		
		$result["tipo"] = $row_sqlPrest["pr_cuotas_tipo"];
		$result["monto"] = $row_sqlPrest["pr_monto"];
		$result["cuotas"] = $row_sqlPrest["pr_cuotas"];
		
		$result["success"] = true;
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>