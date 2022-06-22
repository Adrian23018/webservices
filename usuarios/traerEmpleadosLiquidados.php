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
		
		$sqlEmpleados =  sprintf("SELECT * FROM tbl_empleados WHERE emp_usu_id=%s AND emp_estado=4 ",
			GetSQLValueString($id, "double")
		);
		$rs_sqlEmpleados = mysqli_query($_conection->connect(), $sqlEmpleados);
		$arrayEmpleados = array();
		$result["mostrar"] = 'no';
		while( $row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados) ){
			$result["mostrar"] = 'si';
			unset($empleado);

			$empleado["emp_id"] = utf8_encode($row_sqlEmpleados["emp_id"]);
			$empleado["emp_perfil_nombre"] = utf8_encode($row_sqlEmpleados["emp_perfil_nombre"]);
			$empleado["emp_do_nombre"] = utf8_encode($row_sqlEmpleados["emp_do_nombre"]);
			$empleado["emp_fecha_liquidacion"] = utf8_encode($row_sqlEmpleados["emp_fecha_liquidacion"]);
			$empleado["emp_do_no_identidad"] = utf8_encode($row_sqlEmpleados["emp_do_no_identidad"]);
			$empleado["emp_do_domicilio"] = utf8_encode($row_sqlEmpleados["emp_do_domicilio"]);
			$empleado["emp_do_nacionalidad"] = utf8_encode($row_sqlEmpleados["emp_do_nacionalidad"]);

			list($year,$month,$day) = explode("-",$row_sqlEmpleados["emp_fecha_liquidacion"]);
			$empleado['emp_anho_liq'] = $year;

			$empleado["imagen"] = '';
			// echo 'asd'.$row_sqlEmpleados["emp_imagen"];

			if ($row_sqlEmpleados["emp_imagen"]) {
				$empleado["imagen"] = $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/empleados/".$row_sqlEmpleados['emp_id']."/".$row_sqlEmpleados["emp_imagen"];
			}
			$empleado["emp_estado"] = utf8_encode($row_sqlEmpleados["emp_estado"]);

			array_push($arrayEmpleados, $empleado);
		}
		$result["empleados"] = $arrayEmpleados;
		$result["success"] = true;

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>
