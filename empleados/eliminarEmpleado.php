<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);
	$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);

	//Condiciones
	list($yearHoy, $monthHoy, $dayHoy) = explode("-", $fechaSimulacion);
	$fechaInicio = $yearHoy."-".$monthHoy."-01";
	$fechaFinal = $yearHoy."-".$monthHoy."-31";

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		//Eliminar Empleado
		$sqlEmpleado = sprintf("DELETE FROM tbl_empleados WHERE emp_id=%s",
			GetSQLValueString($_POST['emp_id'],"double")
		);
		$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);

		//Eliminar Adelantos
		$sqlEmpleadoAde = sprintf("DELETE FROM tbl_adelantos WHERE ad_emp_id=%s",
			GetSQLValueString($_POST['emp_id'],"double")
		);
		$rs_sqlEmpleadoAde = mysqli_query($conexion, $sqlEmpleadoAde);

		//Eliminar Ausencias
		$sqlEmpleadoAus = sprintf("DELETE FROM tbl_ausencias WHERE au_emp_id=%s",
			GetSQLValueString($_POST['emp_id'],"double")
		);
		$rs_sqlEmpleadoAus = mysqli_query($conexion, $sqlEmpleadoAus);

		//Eliminar DiaEspecial
		$sqlEmpleadoDE = sprintf("DELETE FROM tbl_diaespecial WHERE de_emp_id=%s",
			GetSQLValueString($_POST['emp_id'],"double")
		);
		$rs_sqlEmpleadoDE = mysqli_query($conexion, $sqlEmpleadoDE);

		//Eliminar Cambio Condiciones
		$sqlEmpleadoCC = sprintf("DELETE FROM tbl_empleados_cambiocondiciones WHERE es_emp_id=%s",
			GetSQLValueString($_POST['emp_id'],"double")
		);
		$rs_sqlEmpleadoCC = mysqli_query($conexion, $sqlEmpleadoCC);

		//Eliminar Notificaciones
		$sqlEmpleadoNot = sprintf("DELETE FROM tbl_empleados_notificaciones WHERE en_emp_id=%s",
			GetSQLValueString($_POST['emp_id'],"double")
		);
		$rs_sqlEmpleadoNot = mysqli_query($conexion, $sqlEmpleadoNot);

		//Eliminar Hermanos
		$sqlEmpleadoHer = sprintf("DELETE FROM tbl_hermanos WHERE hm_emp_id=%s",
			GetSQLValueString($_POST['emp_id'],"double")
		);
		$rs_sqlEmpleadoHer = mysqli_query($conexion, $sqlEmpleadoHer);

		//Eliminar Hijos
		$sqlEmpleadoHij = sprintf("DELETE FROM tbl_hijos WHERE hj_emp_id=%s",
			GetSQLValueString($_POST['emp_id'],"double")
		);
		$rs_sqlEmpleadoHij = mysqli_query($conexion, $sqlEmpleadoHij);

		//Eliminar Horas Extras
		$sqlEmpleadoHE = sprintf("DELETE FROM tbl_horasextras WHERE he_emp_id=%s",
			GetSQLValueString($_POST['emp_id'],"double")
		);
		$rs_sqlEmpleadoHE = mysqli_query($conexion, $sqlEmpleadoHE);

		//Eliminar Prestamos
		$sqlEmpleadoPre = sprintf("DELETE FROM tbl_prestamos WHERE pr_emp_id=%s",
			GetSQLValueString($_POST['emp_id'],"double")
		);
		$rs_sqlEmpleadoPre = mysqli_query($conexion, $sqlEmpleadoPre);

		//Eliminar Vacaciones
		$sqlEmpleadoVac = sprintf("DELETE FROM tbl_vacaciones WHERE vc_emp_id=%s",
			GetSQLValueString($_POST['emp_id'],"double")
		);
		$rs_sqlEmpleadoVac = mysqli_query($conexion, $sqlEmpleadoVac);

		$result["success"] = true;

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>
