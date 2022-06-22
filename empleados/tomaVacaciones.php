<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);
	$fechaSimulacion = simuladorTiempo($fechaActual, $fechaReferencia);

	list($dia,$mes,$anho) = explode("/", $_POST['fecha_salida']);
	list($dia2,$mes2,$anho2) = explode("/", $_POST['fecha_regreso']);

	$fecha_salida = $anho.'-'.$mes.'-'.$dia;
	$fecha_regreso = $anho2.'-'.$mes2.'-'.$dia2;

	$fecha1=strtotime($fecha_salida);
	$fecha2=strtotime($fecha_regreso);
	if($fecha1 > $fecha2){
		$error=true;
		$result["error"] = 1;
	}

	$sqlVariables = sprintf("SELECT * FROM tbl_variables_panama");
	$rs_sqlVariables = mysqli_query($conexion, $sqlVariables);
	$row_sqlVariables = mysqli_fetch_assoc($rs_sqlVariables);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	// $validacion = false;
	if ($validacion) {
		if (!$error) {
			//No se pueden truncar las fechas.
			//Vacaciones pagar con los salarios de los últimos 11 meses.
			//Cuando el usuario deja acumular mas de 11 meses (ejemplo 2 años).

			$sqlVerificarVac = sprintf("SELECT COUNT(*) AS count_fechas FROM tbl_vacaciones WHERE vc_usu_id=%s AND vc_emp_id=%s AND ( (vc_fecha_salida<=%s AND vc_fecha_regreso>=%s) OR (vc_fecha_salida<=%s AND vc_fecha_regreso>=%s) OR (vc_fecha_salida>=%s AND vc_fecha_regreso<=%s ) )",
					GetSQLValueString($id, "double"),
					GetSQLValueString($_POST["emp_id"], "double"),
					GetSQLValueString(utf8_decode($fecha_salida), "date"),
					GetSQLValueString(utf8_decode($fecha_salida), "date"),
					GetSQLValueString(utf8_decode($fecha_regreso), "date"),
					GetSQLValueString(utf8_decode($fecha_regreso), "date"),
					GetSQLValueString(utf8_decode($fecha_salida), "date"),
					GetSQLValueString(utf8_decode($fecha_regreso), "date")
			);
			$rs_sqlVerificarVac = mysqli_query($conexion, $sqlVerificarVac);
			$row_sqlVerificarVac = mysqli_fetch_assoc($rs_sqlVerificarVac);

			//Colocar el historial de pagos de las vacaciones.
			//11 meses anterior
			//list($year, $month, $day) = explode("-", $fechaSimulacion);
			$fechaActual = new DateTime($fecha_salida);
			$fechaActual->modify("-11 months");
			$fechaActual->format('Y-m-d');

			$sqlPagos = sprintf("SELECT SUM(en_valor) AS sum_pagos FROM tbl_empleados_notificaciones WHERE en_tipo='salario' AND en_emp_id=%s AND en_fecha>=%s AND en_fecha<=%s ",
				GetSQLValueString($_POST["emp_id"], "double"),
				GetSQLValueString($fechaActual->format('Y-m-d'), "date"),
				GetSQLValueString($fecha_salida, "date")
			);
			$rs_sqlPagos = mysqli_query($conexion, $sqlPagos);
			$row_sqlPagos = mysqli_fetch_assoc($rs_sqlPagos);
			//Error, si no tiene pagos anteriores a las vacaciones (solo puede llegar a pasar en pruebas)
			if( $row_sqlPagos["sum_pagos"] == 0 ){
				$error=true;
				$result["error"] = 3;
			}

			//Error, fechas que abarcan otras fechas
			if ($row_sqlVerificarVac["count_fechas"] != 0) {
				$error=true;
				$result["error"] = 2;
			}

			//Error, no se puede registrar vacaciones con mas de un mes de anticipación
			$fechaMasUnMes = new DateTime($fecha_salida);
			$fechaSalidaStr = strtotime($fechaMasUnMes->format('Y-m-d'));
			$fechaMasUnMes->modify("-1 months");
			$fechaUnMes = strtotime($fechaMasUnMes->format('Y-m-d'));
			$fechaComparacion = strtotime($fechaSimulacion);
			if ($fechaUnMes > $fechaComparacion) {
				$error = true;
				$result["error"] = 4;
			}

			if (!$error) {
				$calculado = 'si';
				$result['fecha_salida'] = $fecha_salida;
				$result['fechaSimulacion'] = $fechaSimulacion;

				$result['fechaSalidaStr'] = $fechaSalidaStr;
				$result['fechaComparacion'] = $fechaComparacion;
				/* 
					Error en condicional de vacaciones, si el día que toma las vacaciones es el mismo día de simulación se debe hacer los cálculos de las vacaciones, porque ese día puede que ya hayan pasado los cálculos.
					$fechaSalidaStr >= $fechaComparacion
					E.g.  $irse = 15 de Noviembre
					E.g.  $simulacion = 15 de Noviembre
					Si el cálculo del día ya ha pasado, no podrá registrar esas vacaciones.
	
					Se cambia a:
					$fechaSalidaStr > $fechaComparacion
					Solo si es mayor no se toma el cálculo
				*/
				if ($fechaSalidaStr > $fechaComparacion) {
					$calculado = 'no';
				}

				$sqlVacaciones = sprintf("INSERT INTO tbl_vacaciones (vc_usu_id,vc_emp_id,vc_fecha_salida,vc_fecha_regreso,vc_calculado,vc_simulacion) VALUES (%s,%s,%s,%s,%s,%s) ",
						GetSQLValueString($id, "double"),
						GetSQLValueString($_POST["emp_id"], "double"),
						GetSQLValueString(utf8_decode($fecha_salida), "date"),
						GetSQLValueString(utf8_decode($fecha_regreso), "date"),
						GetSQLValueString($calculado, "text"),
						GetSQLValueString($fechaSimulacion, "date")
				);
				// $result['consulta'] = $sqlVacaciones;
				$rs_sqlVacaciones = mysqli_query($conexion, $sqlVacaciones);

				//
				if ($calculado == 'si') {

					$sqlEmpleado = sprintf("SELECT * FROM tbl_empleados WHERE emp_id=%s",
						GetSQLValueString($_POST['emp_id'], "double")
					);
					$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
					$row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado);
					$emp_cond_jornada = $row_sqlEmpleado['emp_cond_jornada'];
					$emp_cond_semanas = $row_sqlEmpleado['emp_cond_semanas'];
					$emp_cond_periodo = $row_sqlEmpleado['emp_cond_periodo'];
					$emp_cond_termino = $row_sqlEmpleado['emp_cond_termino'];
					$emp_cond_sueldo = $row_sqlEmpleado['emp_cond_sueldo'];

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

					$diasVacaciones = diferenciaDias($fecha_salida, $fecha_regreso) + 1;
					$valorDiasVacaciones = ($salarioMensual/30);
					$vacaciones_valor = ($valorDiasVacaciones * $diasVacaciones);

					$emp_contribuciones = $row_sqlEmpleado['emp_contribuciones'];

					if ( $emp_contribuciones == 'si' ) {
						$vacas_ss = $vacaciones_valor * $row_sqlVariables['vp_vc_ss'] / 100;
						$vacas_se = $vacaciones_valor * $row_sqlVariables['vp_vc_se'] / 100;
						$vacas_ssp = $vacaciones_valor * 12.25 / 100;
						$vacas_sep = $vacaciones_valor * 1.5 / 100;
					}
					$vacaciones_total = $vacaciones_valor - $vacas_ss - $vacas_se;

					$sqlRegistroPagoVac = sprintf("INSERT INTO tbl_empleados_notificaciones (en_usu_id, en_emp_id, en_tipo, en_valor, en_total, en_ss, en_se, en_ssp, en_sep, en_fecha_empieza, en_fecha, en_valordia_vac) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s) ",
						GetSQLValueString($id,"double"),
						GetSQLValueString($_POST["emp_id"],"double"),
						GetSQLValueString("vacaciones","text"),
						GetSQLValueString(round($vacaciones_valor,2),"double"),
						GetSQLValueString(round($vacaciones_total,2),"double"),
						GetSQLValueString(round($vacas_ss,2),"double"),
						GetSQLValueString(round($vacas_se,2),"double"),
						GetSQLValueString(round($vacas_ssp,2),"double"),
						GetSQLValueString(round($vacas_sep,2),"double"),
						GetSQLValueString(utf8_decode($fecha_salida), "date"),
						GetSQLValueString(utf8_decode($fecha_regreso), "date"),
						GetSQLValueString($valorDiasVacaciones, "double")
					);
					$rs_sqlRegistroPagoVac = mysqli_query($conexion, $sqlRegistroPagoVac);

					// guardarTotalTabla.
					$sqlEmpleadosVacaciones = sprintf("UPDATE tbl_empleados SET emp_vacaciones_x_descontar=emp_vacaciones_x_descontar+%s WHERE emp_id=%s",
						GetSQLValueString(round($vacaciones_valor,2), "double"),
						GetSQLValueString($_POST["emp_id"], "double")
					);
					$rs_sqlEmpleadosVacaciones = mysqli_query($conexion, $sqlEmpleadosVacaciones);

					//ENVIAMOS NOTIFICACION PUSH AL USUARIO
					$sqlTokens = sprintf("SELECT * FROM tbl_usuarios_token WHERE ut_usu_id=%s",
								GetSQLValueString($id, "double")
							);
					$rs_sqlTokens = mysqli_query($conexion, $sqlTokens);
					$iTok = 0;
					$registrationIds = array();
					while( $row_sqlTokens = mysqli_fetch_assoc($rs_sqlTokens) ){
						array_push($registrationIds, $row_sqlTokens["ut_token"]);
						$iTok++;
					}

					// Uku - Notificaciones de Xiii
					$title = 'Uku - Notificación de vacaciones';
					$body = 'Recordatorio de vacaciones';
					envioNotificacionesPush($title, $body, $registrationIds);
				}
			}

			if ($rs_sqlVacaciones) {
				$result["success"] = true;
			}
		}
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>