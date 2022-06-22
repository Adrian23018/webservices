<?php
	// session_start();
	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	// error_reporting(1);
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
	echo $fechaSimulacion;

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

			$sqlEmpCamb = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`<=%s AND `es_cond_fecha_relacion`<=%s ORDER BY `es_cond_fecha_relacion` DESC LIMIT 0,1",
				GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
				GetSQLValueString($fechaSimulacion, "date")
			);
			$rs_sqlEmpCamb = mysqli_query($_conection->connect(), $sqlEmpCamb);
			while ($row_sqlEmpCamb = mysqli_fetch_assoc($rs_sqlEmpCamb)) {
				$emp_cond_jornada = $row_sqlEmpCamb['es_cond_jornada'];
				$emp_cond_semanas = $row_sqlEmpCamb['es_cond_semanas'];
				$emp_cond_periodo = $row_sqlEmpCamb['es_cond_periodo'];
				$emp_cond_termino = $row_sqlEmpCamb['es_cond_termino'];
				$emp_cond_sueldo = $row_sqlEmpCamb['es_cond_sueldo'];
			}

			$emp_do_nombre = $row_sqlEmpleado['emp_do_nombre'];
			$emp_contribuciones = $row_sqlEmpleado['emp_contribuciones'];

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
			}elseif ($emp_cond_jornada == 2 ) {
				$diasLaborales = 6;
				$terminaSemana = 6;
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
				$salarioQuincenal = ($emp_cond_sueldo * $row_sqlVariables['vp_horas'] * $diasLaborales * $row_sqlVariables['vp_semanas']) / 2;
				$salarioMensual = $emp_cond_sueldo * $row_sqlVariables['vp_horas'] * $diasLaborales * $row_sqlVariables['vp_semanas'];
			}elseif($emp_cond_termino == 2){
				// Dia
				$salarioDiario = $emp_cond_sueldo;
				$salarioSemanal = $emp_cond_sueldo * $diasLaborales;
				$salarioQuincenal = ($emp_cond_sueldo * $diasLaborales * $row_sqlVariables['vp_semanas']) / 2;
				$salarioMensual = $emp_cond_sueldo * $diasLaborales * $row_sqlVariables['vp_semanas'];
			}elseif($emp_cond_termino == 3){
				// Mes
				$salarioDiario = $emp_cond_sueldo / $row_sqlVariables['vp_semanas'] / $diasLaborales;
				$salarioSemanal = $emp_cond_sueldo / $row_sqlVariables['vp_semanas'];
				$salarioQuincenal = $emp_cond_sueldo / 2;
				$salarioMensual = $emp_cond_sueldo;
			}


			list($yearHoy, $mesHoy, $diaHoy) = explode('-',$fechaSimulacion);
			$mesHoy = (int)$mesHoy;

			$notificacionXIII = false;
		
			if ( $mesHoy == 4 || $mesHoy == 8 || $mesHoy == 12 && $diaHoy == 14 ) {
				$notificacionXIII = true;
			}

			if ($notificacionXIII) {
				$sqlNotificacion = sprintf("INSERT INTO tbl_empleados_notificaciones 
											(en_emp_id,en_usu_id,en_tipo,en_fecha) VALUES (%s,%s,%s,%s)",
					GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
					GetSQLValueString($usuId, "double"),
					GetSQLValueString('xiii', "text"),
					GetSQLValueString($fechaSimulacion, "date")
				);
				$rs_sqlNotificacion = mysqli_query($_conection->connect(), $sqlNotificacion);
				// break;
			}
		}

		//
		if ($notificacionXIII) {
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

			// Uku - Notificaciones de Xiii
			$title = 'Uku - Notificación de xiii';
			$body = 'Recordatorio de pagos de décimo primer mes';
			envioNotificacionesPush($title, $body, $registrationIds);
		}
	}



	//

?>