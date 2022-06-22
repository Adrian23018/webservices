<?php
	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	error_reporting(1);
	require_once '../../admin_uku/dompdf/autoload.inc.php';

	$datetimeReferencia = date_create($fechaReferencia);
	$datetimeActual = date_create(date($fechaActual));
	$interval = date_diff($datetimeReferencia, $datetimeActual);

	$hora = $interval->format("%H");
	$minuto = $interval->format("%I");
	$segundo = $interval->format("%S");

	$diastranscurridos = ($hora*60)+$minuto + $interval->days * 24 * 60;
	echo $diastranscurridos.' dias transcurridos<br>';

	$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);
	echo $fechaSimulacion.'<br>';

	$array_dias['Sunday'] = 7;
	$array_dias['Monday'] = 1;
	$array_dias['Tuesday'] = 2;
	$array_dias['Wednesday'] = 3;
	$array_dias['Thursday'] = 4;
	$array_dias['Friday'] = 5;
	$array_dias['Saturday'] = 6;
	echo $array_dias[date('l', strtotime($fechaSimulacion))];

	$sqlVariables = sprintf("SELECT * FROM tbl_variables_panama");
	$rs_sqlVariables = mysqli_query($_conection->connect(), $sqlVariables);
	$row_sqlVariables = mysqli_fetch_assoc($rs_sqlVariables);
	echo '<br><br><h3>Variables</h3>';
	echo '<b>Semanas en un mes:</b> ' . $row_sqlVariables['vp_semanas'].'<br>';
	echo '<b>Horas en el día:</b> ' . $row_sqlVariables['vp_horas'].'<br>';
	echo '<b>Pago por ser Domingo:</b> ' . $row_sqlVariables['vp_diadomingo'].'<br>';
	echo '<b>Pago por ser feriado:</b> ' . $row_sqlVariables['vp_diaferiado'].'<br>';
	//Seguro social  SS
	echo '<b>Seguro social  SS:  </b>' . $row_sqlVariables['vp_vc_ss'].'<br>';
	//Seguro social  S. Educativo
	echo '<b>Seguro social  S. Educativo:  </b>' . $row_sqlVariables['vp_vc_se'].'<br>';
	//Seguro social  Xiii
	echo '<b>Seguro social  Xiii: </b>' . $row_sqlVariables['vp_xiii_ss'].'<br>';

	$usuId = 2;
	$sqlUsuario = sprintf("SELECT * FROM tbl_usuarios WHERE usu_nacionalidad='panama' AND usu_id=".$usuId);
	$rs_sqlUsuario = mysqli_query($_conection->connect(), $sqlUsuario);
	while ($row_sqlUsuario = mysqli_fetch_assoc($rs_sqlUsuario)) {
		$usuId = $row_sqlUsuario['usu_id'];

		$sqlEmpleado = sprintf("SELECT * FROM tbl_empleados WHERE emp_usu_id=".$usuId." AND emp_estado=3");
		$rs_sqlEmpleado = mysqli_query($_conection->connect(), $sqlEmpleado);
		while ($row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado)) {
			$emp_cond_jornada = $row_sqlEmpleado['emp_cond_jornada'];
			$emp_cond_semanas = $row_sqlEmpleado['emp_cond_semanas'];
			$emp_cond_periodo = $row_sqlEmpleado['emp_cond_periodo'];
			$emp_cond_termino = $row_sqlEmpleado['emp_cond_termino'];
			$emp_cond_sueldo = $row_sqlEmpleado['emp_cond_sueldo'];
			$emp_do_nombre = $row_sqlEmpleado['emp_do_nombre'];

			echo '<br><br><h3>Empleado: <small>'.$emp_do_nombre.'</small></h3>';
			echo '<b>Tipo de empleado registrado</b>: '.$row_sqlEmpleado['emp_tipo'].'<br>';
			echo '<b>Contrato</b>: '.$row_sqlEmpleado['emp_contrato'].'<br>';
			echo '<b>Meses Contrato Definido</b>: '.$row_sqlEmpleado['emp_tipo_definido'].'<br>';
			
			if ($row_sqlEmpleado['emp_cond_jornada'] == 1 ) {
				echo '<b>Jornada de</b>: Lunes a Viernes, 5 días laborales <br>';
			}elseif ($row_sqlEmpleado['emp_cond_jornada'] == 2 ) {
				echo '<b>Jornada de</b>: Lunes a Sábado, 6 días laborales <br>';
			}else {
				echo '<b>Jornada de</b>: '. count(explode(',', $row_sqlEmpleado['emp_cond_semanas'])) .' días laborales<br>';
				echo '<b>Días de la semana</b>: '.$row_sqlEmpleado['emp_cond_semanas'].'<br>';
			}
			echo '<b>Fecha inicio de contrato</b>: '.$row_sqlEmpleado['emp_cond_fecha_relacion'].'<br>';

			$arrayTerminos = array('', 'Por hora','Por día','Por mes');
			echo '<b>Término del Salario</b>: '.$arrayTerminos[$row_sqlEmpleado['emp_cond_termino']].'<br>';

			$arrayPeriodo = array('', 'diario','semanal','quincenal','mensual');
			echo '<b>Salario</b>: '.$row_sqlEmpleado['emp_cond_sueldo'].'<br>';
			echo '<b>Período de pago</b>: '.$arrayPeriodo[$row_sqlEmpleado['emp_cond_periodo']].'<br>';
			echo '<b> Caja de seguro social - contribuciones</b>: '.$row_sqlEmpleado['emp_contribuciones'].'<br>';
			

			echo '<b> Promedio Salarial Empleado Existente</b>: '.$row_sqlEmpleado['emp_promedio'].'<br>';
			if ($row_sqlEmpleado['emp_promedio'] == 'si') {
				if ($row_sqlEmpleado['emp_anho1_valor']) {
					echo '<b> Promedio año '.$row_sqlEmpleado['emp_anho1'].' </b>: '.$row_sqlEmpleado['emp_anho1_valor'].'<br>';
				}

				if ($row_sqlEmpleado['emp_anho2_valor']) {
					echo '<b> Promedio año '.$row_sqlEmpleado['emp_anho2'].' </b>: '.$row_sqlEmpleado['emp_anho2_valor'].'<br>';
				}

				if ($row_sqlEmpleado['emp_anho3_valor']) {
					echo '<b> Promedio año '.$row_sqlEmpleado['emp_anho3'].' </b>: '.$row_sqlEmpleado['emp_anho3_valor'].'<br>';
				}

				if ($row_sqlEmpleado['emp_anho4_valor']) {
					echo '<b> Promedio año '.$row_sqlEmpleado['emp_anho4'].' </b>: '.$row_sqlEmpleado['emp_anho4_valor'].'<br>';
				}

				if ($row_sqlEmpleado['emp_anho5_valor']) {
					echo '<b> Promedio año '.$row_sqlEmpleado['emp_anho5'].' </b>: '.$row_sqlEmpleado['emp_anho5_valor'].'<br>';
				}
			}

			echo '<b>Vacaciones</b>: '.$row_sqlEmpleado['emp_vacaciones'].'<br>';
			echo '<b>Días de las vacaciones</b>: '.$row_sqlEmpleado['emp_dias'].'<br>';

			//Calculos.
			//Periodo de pago
			$periodo['dia'] = 'todos los dias';
			$periodo['semanal'] = 'dia en que termine el día laboral de la semana';
			$periodo['quincenal'] = array(14,29,27);
			$periodo['mensual'] = array(29,27);

			//Salario por 
			$salario['termino'] = array( 'hora', 'dia', 'mes' );
			$salario['salario'] =  'el que han declarado';

			//Calcular días de pago para el usuario.
			if ($emp_cond_jornada == 1 ) {
				$diasLaborales = 5;
				$terminaSemana = 5;
				$emp_cond_semanas = "1,2,3,4,5";
			}elseif ($emp_cond_jornada == 2 ) {
				$diasLaborales = 6;
				$terminaSemana = 6;
				$emp_cond_semanas = "1,2,3,4,5,6";
			}else {
				$diasLaborales = count(explode(',', $emp_cond_semanas));
				$terminaSemana = end(explode(',', $emp_cond_semanas));
			}

			echo '<br><br><h3>Cálculos: <small>'.$emp_do_nombre.'</small></h3>';
			echo 'Días laborales ---> ' . $diasLaborales . '<br>';
			echo 'Día termina Semana ---> ' . $terminaSemana . '<br>';

			$salarioDiario = $salarioSemanal = $salarioQuincenal = $salarioMensual = 0;
			if($emp_cond_termino == 1){
				// Hora
				$salarioDiario = $emp_cond_sueldo * $row_sqlVariables['vp_horas'];
				$salarioSemanal = $emp_cond_sueldo * $row_sqlVariables['vp_horas'] * $diasLaborales;
				$salarioQuincenal = $emp_cond_sueldo * $row_sqlVariables['vp_horas'] * $diasLaborales * ($row_sqlVariables['vp_semanas'] / 2);
				$salarioMensual = $emp_cond_sueldo * $row_sqlVariables['vp_horas'] * $diasLaborales * $row_sqlVariables['vp_semanas'];
			}elseif($emp_cond_termino == 2){
				// Dia
				$salarioDiario = $emp_cond_sueldo;
				$salarioSemanal = $emp_cond_sueldo * $diasLaborales;
				$salarioQuincenal = $emp_cond_sueldo * $diasLaborales * $row_sqlVariables['vp_semanas'] / 2;
				$salarioMensual = $emp_cond_sueldo * $diasLaborales * $row_sqlVariables['vp_semanas'];
			}elseif($emp_cond_termino == 3){
				// Mes
				$salarioDiario = $emp_cond_sueldo / $row_sqlVariables['vp_semanas'] / $diasLaborales;
				$salarioSemanal = $emp_cond_sueldo / $row_sqlVariables['vp_semanas'];
				$salarioQuincenal = $emp_cond_sueldo / 2;
				$salarioMensual = $emp_cond_sueldo;
			}


			list($yearHoy, $mesHoy, $diaHoy) = explode('-',$fechaSimulacion);
			$notificacionSalario = false;
			if($emp_cond_periodo == 1){
				// Diario
				$resultSemana = explode(',', $emp_cond_semanas);
				if (in_array($array_dias[date('l', strtotime($fechaSimulacion))], $resultSemana)){
					// Notificacion pago Diario
					$notificacionSalario = true;
				}

				echo 'Salario Diario ' . round($salarioDiario,2) . '<br>';
			}elseif($emp_cond_periodo == 2){
				// Semanal
				if ($array_dias[date('l', strtotime($fechaSimulacion))] == $terminaSemana) {
					// Notificacion pago Semanal
					$notificacionSalario = true;
				}

				echo 'Salario Semanal ' . round($salarioSemanal,2) . '<br>';
			}elseif($emp_cond_periodo == 3){
				// Quincenal
				// $periodo['quincenal'] = array(14,29,27);
				if((int)$diaHoy == $periodo['quincenal'][0]){
					// Notificacion pago Quincenal
					$notificacionSalario = true;
				}elseif((int)$diaHoy == $periodo['quincenal'][1]){
					// Notificacion pago Quincenal
					$notificacionSalario = true;
				}elseif((int)$mesHoy == 2 && (int)$diaHoy == $periodo['quincenal'][2]){
					// Notificacion pago Quincenal
					$notificacionSalario = true;
				}

				echo 'Salario Quincenal ' . round($salarioQuincenal,2) . '<br>';
			}elseif($emp_cond_periodo == 4){
				// Mensual
				if((int)$diaHoy == $periodo['mensual'][0]){
					// Notificacion pago Mensual
					$notificacionSalario = true;
				}elseif((int)$mesHoy == 2 && (int)$diaHoy == $periodo['mensual'][1]){
					// Notificacion pago Mensual
					$notificacionSalario = true;
				}
				echo 'Salario Mensual ' . round($salarioMensual,2) . '<br>';
			}

			if ($notificacionSalario) {
				$sqlNotificacion = sprintf("INSERT INTO tbl_empleados_notificaciones 
											(en_emp_id,en_usu_id,en_tipo,en_fecha) VALUES (%s,%s,%s,%s)",
					GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
					GetSQLValueString($usuId, "double"),
					GetSQLValueString('salario', "text"),
					GetSQLValueString($fechaSimulacion, "date")
				);
				echo '<br>'.$sqlNotificacion;
				$rs_sqlNotificacion = mysqli_query($_conection->connect(), $sqlNotificacion);
				// break;
			}
		}

		//
		if ($notificacionSalario) {
			//Enviar Notificación

			//ENVIAMOS NOTIFICACION PUSH AL USUARIO
			$sqlTokens = sprintf("SELECT * FROM tbl_usuarios_token WHERE ut_usu_id=%s",
						GetSQLValueString($usuId, "double")
					);
			$rs_sqlTokens = mysqli_query($_conection->connect(), $sqlTokens);
			$iTok = 0;
			$registrationIds = array();
			while( $row_sqlTokens = mysqli_fetch_assoc($rs_sqlTokens) ){
				array_push($registrationIds, $row_sqlTokens["ut_token"]);
				$iTok++;
			}

			// Uku - Notificaciones de Salario
			$title = 'Uku - Notificación de Salario';
			$body = 'Tienes pendientes pagos a empleados';
			envioNotificacionesPush($title, $body, $registrationIds);
		}
	}



	//

?>