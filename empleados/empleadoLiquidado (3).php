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
		
		$sqlEmpleados =  sprintf("SELECT * FROM tbl_empleados WHERE emp_usu_id=%s AND emp_estado=4 AND emp_id=%s",
			GetSQLValueString($id, "double"),
			GetSQLValueString($_POST["emp_id"], "double")
		);
		$rs_sqlEmpleados = mysqli_query($_conection->connect(), $sqlEmpleados);
		$arrayEmpleados = array();
		while( $row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados) ){

			$emp_cond_jornada = $row_sqlEmpleados['emp_cond_jornada'];
			$emp_cond_semanas = $row_sqlEmpleados['emp_cond_semanas'];
			$emp_cond_periodo = $row_sqlEmpleados['emp_cond_periodo'];
			$emp_cond_termino = $row_sqlEmpleados['emp_cond_termino'];
			$emp_cond_sueldo = $row_sqlEmpleados['emp_cond_sueldo'];

			$sqlEmpCamb = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`<=%s AND `es_cond_fecha_proxima`<=%s ORDER BY `es_cond_fecha_proxima` DESC LIMIT 0,1",
				GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
				GetSQLValueString($fechaSimulacion, "date")
			);
			$rs_sqlEmpCamb = mysqli_query($conexion, $sqlEmpCamb);
			while ($row_sqlEmpCamb = mysqli_fetch_assoc($rs_sqlEmpCamb)) {
				$emp_cond_jornada = $row_sqlEmpCamb['es_cond_jornada'];
				$emp_cond_semanas = $row_sqlEmpCamb['es_cond_semanas'];
				$emp_cond_periodo = $row_sqlEmpCamb['es_cond_periodo'];
				$emp_cond_termino = $row_sqlEmpCamb['es_cond_termino'];
				$emp_cond_sueldo = $row_sqlEmpCamb['es_cond_sueldo'];
			}

			$result["emp_id"] = utf8_encode($row_sqlEmpleados["emp_id"]);
			$result["emp_perfil_nombre"] = utf8_encode($row_sqlEmpleados["emp_perfil_nombre"]);
			$result["emp_perfil_nombre"] = utf8_encode($row_sqlEmpleados["emp_perfil_nombre"]);
			$result["emp_do_nombre"] = utf8_encode($row_sqlEmpleados["emp_do_nombre"]);
			$result["emp_fecha_liquidacion"] = utf8_encode($row_sqlEmpleados["emp_fecha_liquidacion"]);
			$result["emp_do_no_identidad"] = utf8_encode($row_sqlEmpleados["emp_do_no_identidad"]);
			$result["emp_do_domicilio"] = utf8_encode($row_sqlEmpleados["emp_do_domicilio"]);
			$result["emp_do_nacionalidad"] = utf8_encode($row_sqlEmpleados["emp_do_nacionalidad"]);
			$result["emp_do_nacionalidad_value"] = utf8_encode($row_sqlEmpleados["emp_do_nacionalidad_value"]);
			$result["emp_motivo_liquidacion"] = utf8_encode($row_sqlEmpleados["emp_motivo_liquidacion"]);

			list($yearE,$monthE,$dayE) = explode("-",$row_sqlEmpleados["emp_cond_fecha_relacion"]);
			$result['emp_fecha1'] = $dayE.'/'.$arrayMesesGlobalAb[(int)$monthE].'/'.$yearE;

			list($yearE,$monthE,$dayE) = explode("-",$row_sqlEmpleados["emp_fecha_liquidacion"]);
			$result['emp_fecha2'] = $dayE.'/'.$arrayMesesGlobalAb[(int)$monthE].'/'.$yearE;

			$sqlVariables = sprintf("SELECT * FROM tbl_variables_panama");
			$rs_sqlVariables = mysqli_query($conexion, $sqlVariables);
			$row_sqlVariables = mysqli_fetch_assoc($rs_sqlVariables);

			//Calcular días de pago para el usuario.
			if ($emp_cond_jornada == 1 ) {
				$diasLaborales = 5;
				$empiezaSemana = 1;
				$terminaSemana = 5;
				$emp_cond_semanas = "1,2,3,4,5";
			}elseif ($emp_cond_jornada == 2 ) {
				$diasLaborales = 6;
				$empiezaSemana = 1;
				$terminaSemana = 6;
				$emp_cond_semanas = "1,2,3,4,5,6";
			}else {
				$diasLaborales = count(explode(',', $emp_cond_semanas));
				$empiezaSemana = explode(',', $emp_cond_semanas)[0];
				$terminaSemana = end(explode(',', $emp_cond_semanas));
			}

			$salarioDiario = $salarioSemanal = $salarioQuincenal = $salarioMensual = 0;
			if($emp_cond_termino == 1){
				// Hora
				$salarioMensual = $emp_cond_sueldo * $row_sqlVariables['vp_horas'] * $diasLaborales * $row_sqlVariables['vp_semanas'];
			}elseif($emp_cond_termino == 2){
				// Dia
				$salarioMensual = $emp_cond_sueldo * $diasLaborales * $row_sqlVariables['vp_semanas'];
			}elseif($emp_cond_termino == 3){
				// Mes
				$salarioMensual = $emp_cond_sueldo;
			}
			$result["salarioMensual"] = $salarioMensual;

			$result["imagen"] = '';
			// echo 'asd'.$row_sqlEmpleados["emp_imagen"];

			if ($row_sqlEmpleados["emp_imagen"]) {
				$result["imagen"] = $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/empleados/".$row_sqlEmpleados['emp_id']."/".$row_sqlEmpleados["emp_imagen"];
			}
			$result["emp_estado"] = utf8_encode($row_sqlEmpleados["emp_estado"]);

		}
		$result["success"] = true;

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>
