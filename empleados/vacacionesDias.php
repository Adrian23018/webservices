<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);
	$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		if (!$error) {
			//Empleado
			$sqlEmpleado = sprintf("SELECT * FROM tbl_empleados WHERE emp_id=%s",
				GetSQLValueString($_POST["emp_id"],"double")
			);
			$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
			$row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado);

			$empFechaInicioUku = $row_sqlEmpleado["emp_fecha_creacion"];
			$empVacaciones = $row_sqlEmpleado["emp_vacaciones"];
			if ($empVacaciones == 'no') {
				$empVacacionesDias = $row_sqlEmpleado["emp_dias"];
			}

			//Vacaciones
			$sqlEmpVacaciones = sprintf("SELECT * FROM tbl_vacaciones WHERE vc_emp_id=%s",
				GetSQLValueString($_POST["emp_id"],"double")
			);
			$rs_sqlEmpVacaciones = mysqli_query($conexion, $sqlEmpVacaciones);
			$diferenciaDias = 0;
			while ( $row_sqlEmpVacaciones = mysqli_fetch_assoc($rs_sqlEmpVacaciones) ){
				$fechaSalida = $row_sqlEmpVacaciones["vc_fecha_salida"];
				$fechaRegreso = $row_sqlEmpVacaciones["vc_fecha_regreso"];

				$diferenciaDias += diferenciaDias($fechaSalida, $fechaRegreso) + 1;
			}

			$fechainicial = new DateTime($empFechaInicioUku);
			$fechafinal = new DateTime($fechaSimulacion);

			$result['calculos']['ini'] = $fechainicial;
			$result['calculos']['fin'] = $fechafinal;
			$diferencia = $fechainicial->diff($fechafinal);
			$mesesVacaciones = ($diferencia->y * 12) + $diferencia->m + (($diferencia->d + 1) / 30);
			$diasVacaciones = round($mesesVacaciones * (30/11));
			$result["dias"] = $diasVacaciones + $empVacacionesDias - $diferenciaDias;
			$result["success"] = true;
		}
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>