<?php
	// session_start();
	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	// error_reporting(1);
	require_once '../../admin_uku/dompdf/autoload.inc.php';
	$stripe = new ClassIncStripe;

	$datetimeReferencia = date_create($fechaReferencia);
	$datetimeActual = date_create(date($fechaActual));

	$fechaSimulacionHoy =  simuladorTiempo($fechaActual, $fechaReferencia, 0);
	$sqlLog = sprintf(" SELECT * FROM tbl_log_cronjobs ORDER BY lcj_fecha DESC LIMIT 0,1 ");
	$rs_sqlLog = mysqli_query($conexion, $sqlLog);
	$row_sqlLog = mysqli_fetch_assoc($rs_sqlLog);

	if( $row_sqlLog['lcj_fecha'] == $fechaSimulacionHoy ) {
		// No Ejecutar.
		$ejecutar = false;
	}elseif ( !$row_sqlLog['lcj_fecha'] ){
		// Ejecutar
		$ejecutar = true;
		$fechasEjecutarSimulacion = array( $fechaSimulacionHoy );
	}else{
		// 
		$dias = diasTranscurridosEntreFechas( 
			$row_sqlLog['lcj_fecha'], 
			$fechaSimulacionHoy, 
			array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ) 
		);

		if ( $dias == 2 ){
			// Ejecutar una vez
			$fechasEjecutarSimulacion = array( $fechaSimulacionHoy );
			$ejecutar = true;
		}elseif( $dias > 2 ){
			// Ejecutar varias veces
			$iEjecutar = 0;
			$fechaRegistrada = new DateTime( $row_sqlLog['lcj_fecha'] );
			$fechasEjecutarSimulacion = array( );
			while ($iEjecutar < 3) {
				$fechaRegistrada->modify( "+1 days" );
				$fechaSimulacionInicial = $fechaRegistrada->format('Y-m-d');
				
				array_push($fechasEjecutarSimulacion, $fechaSimulacionInicial);
				if ($fechaSimulacionHoy == $fechaSimulacionInicial) {
					break;
				}
				$iEjecutar++;
			}
		}
	}

	foreach ($fechasEjecutarSimulacion as $key => $fechaSimulacion) {
		$fechaGuardarLog = $fechaSimulacion;
		$fechaSimulacionVacaciones = $fechaSimulacion;

		list($yearHoy, $mesHoy, $diaHoy) = explode('-',$fechaSimulacion);

		$fecha = DateTime::createFromFormat('Y-m-d', $yearHoy."-".$mesHoy."-01");
		$fecha->modify('last day of this month');

		$arrayDiasRestar['Sunday'] = 6;
		$arrayDiasRestar['Monday'] = 0;
		$arrayDiasRestar['Tuesday'] = 1;
		$arrayDiasRestar['Wednesday'] = 2;
		$arrayDiasRestar['Thursday'] = 3;
		$arrayDiasRestar['Friday'] = 4;
		$arrayDiasRestar['Saturday'] = 5;
		$diaE = date('l', strtotime($yearHoy."-".$mesHoy."-".$fecha->format('d')));
		$fechaE = DateTime::createFromFormat('Y-m-d', $yearHoy."-".$mesHoy."-".$fecha->format('d'));
		$fechaE->modify('-'.$arrayDiasRestar[$diaE].'days');
		$fechaIniciaSemana = $yearHoy."-".$mesHoy."-".$fechaE->format('d');
		$fechaTerminaSemana = $yearHoy."-".$mesHoy."-".$fecha->format('d');

		if ((int)$mesHoy==12) {
			$yearBusqueda = $yearHoy+1;
			$mesBusqueda = "01";
		}else{
			$yearBusqueda = $yearHoy;
			$mesBusqueda = (int)$mesHoy+1;
			if ($mesBusqueda<10) {
				$mesBusqueda = "0".$mesBusqueda;
			}
		}

		if ((int)$mesHoy==1) {
			$yearBusquedaAnt = $yearHoy-1;
			$mesBusquedaAnt = 12;
		}else{
			$mesBusquedaAnt = $yearHoy;
			$mesBusquedaAnt = (int)$mesHoy-1;
			if ($mesBusquedaAnt<10) {
				$mesBusquedaAnt = "0".$mesBusquedaAnt;
			}
		}

		$fechaBusquedaPresenteMes = $yearHoy."-".$mesHoy."-01";
		$fechaBusquedaProximoMes = $yearBusqueda."-".$mesBusqueda."-01";
		$fechaBusquedaAnteriorMes = $yearBusquedaAnt."-".$mesBusquedaAnt."-01";

		//Calculos.
		//Periodo de pago
		$periodo['dia'] = 'todos los dias';
		$periodo['semanal'] = 'dia en que termine el día laboral de la semana';
		$periodo['quincenal'] = array(14,29,27);
		$periodo['mensual'] = array(29,27);

		//Salario por 
		$salarioVar['termino'] = array( 'hora', 'dia', 'mes' );
		$salarioVar['salario'] =  'el que han declarado';

		$sqlVariables = sprintf("SELECT * FROM tbl_variables_panama");
		$rs_sqlVariables = mysqli_query($conexion, $sqlVariables);
		$row_sqlVariables = mysqli_fetch_assoc($rs_sqlVariables);

		//Notificacion Cumpleaños.
		$sqlEmpleadoCumple = sprintf("SELECT * FROM tbl_empleados INNER JOIN tbl_usuarios ON usu_id=emp_usu_id AND usu_nacionalidad='panama' WHERE DAY(emp_do_fechanacimiento)=DAY(%s) AND MONTH(emp_do_fechanacimiento)=MONTH(%s)",
			GetSQLValueString($fechaSimulacion, "date"),
			GetSQLValueString($fechaSimulacion, "date")
		);
		$rs_sqlEmpleadoCumple = mysqli_query($conexion, $sqlEmpleadoCumple);
		while ($row_sqlEmpleadoCumple = mysqli_fetch_assoc($rs_sqlEmpleadoCumple)) {
			$usuId = $row_sqlEmpleadoCumple['emp_usu_id'];

			$sqlNotTerminacion = sprintf("INSERT INTO tbl_empleados_notificaciones 
										(
											en_emp_id,
											en_usu_id,
											en_tipo,
											en_fecha_empieza,
											en_fecha,
											en_estado
										) 
										VALUES (%s,%s,%s,%s,%s,%s)",

				GetSQLValueString($row_sqlEmpleadoCumple['emp_id'], "double"),
				GetSQLValueString($usuId, "double"),
				GetSQLValueString('cumpleanos', "text"),
				GetSQLValueString($fechaSimulacion, "date"),
				GetSQLValueString($fechaSimulacion, "date"),
				GetSQLValueString(1, "int")
			);
			$rs_sqlNotTerminacion = mysqli_query($conexion, $sqlNotTerminacion);
					
			//ENVIAMOS NOTIFICACION PUSH AL USUARIO
			$sqlTokens = sprintf("SELECT * FROM tbl_usuarios_token WHERE ut_usu_id=%s",
						GetSQLValueString($usuId, "double")
					);
			$rs_sqlTokens = mysqli_query($conexion, $sqlTokens);
			$iTok = 0;
			$registrationIds = array();
			$registrationIdsAndroid = array();
			while( $row_sqlTokens = mysqli_fetch_assoc($rs_sqlTokens) ){
				if( $row_sqlTokens['ut_platform'] == 'ios' ){
					array_push($registrationIds, $row_sqlTokens["ut_token"]);
				}elseif( $row_sqlTokens['ut_platform'] == 'android' ){
					array_push($registrationIdsAndroid, $row_sqlTokens["ut_token"]);
				}
				$iTok++;
			}
		
			$title = 'Uku';
			$body = $row_sqlEmpleadoCumple['emp_do_nombre'] . ' está de cumpleaños';
			envioNotificacionesPush($title, $body, $registrationIds);
			envioNotificacionesPush($title, $body, $registrationIdsAndroid, 'android');
		}

		// Notificaciones Salarios
		$sqlEmpleado = sprintf("SELECT * FROM tbl_empleados INNER JOIN tbl_usuarios ON usu_id=emp_usu_id AND usu_nacionalidad='panama' WHERE emp_estado=3 AND emp_cond_fecha_relacion<=%s ",
			GetSQLValueString($fechaSimulacion, "date")
		);
		$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
		$notificacionSalarioTotal = $notificacionXIII = $notificacionVacaciones = $notificacionTerminacionDefinido = false;
		$empleadosTerminados = '';
		while ($row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado)) {
			$usuId = $row_sqlEmpleado['emp_usu_id'];

			$notificacionSalario = $guardarSalarioEnVacaciones = $guardarSalarioExtra = false;
			$salarioVacaciones = 0;
			$emp_contribuciones = 'no';
			$emp_cond_fecha_relacion = $row_sqlEmpleado['emp_cond_fecha_relacion'];

			$emp_cond_jornada = $row_sqlEmpleado['emp_cond_jornada'];
			$emp_cond_semanas = $row_sqlEmpleado['emp_cond_semanas'];
			$emp_cond_periodo = $row_sqlEmpleado['emp_cond_periodo'];
			$emp_cond_termino = $row_sqlEmpleado['emp_cond_termino'];
			$emp_cond_sueldo = $row_sqlEmpleado['emp_cond_sueldo'];
			$emp_contrato = $row_sqlEmpleado['emp_contrato'];
			$emp_tipo_definido = $row_sqlEmpleado['emp_tipo_definido'];
			$emp_tipo = $row_sqlEmpleado['emp_tipo'];

			if ($emp_contrato == 'definido') {
				// $emp_tipo_definido
				$fechaTerminacionDefinido = new DateTime($emp_cond_fecha_relacion);
				$fechaTerminacionDefinido->modify($emp_tipo_definido." months");
				if( $fechaTerminacionDefinido->format('Y-m-d') == $fechaSimulacion){
					$notificacionTerminacionDefinido = true;

					$empleadosTerminados .= utf8_encode($row_sqlEmpleado['emp_do_nombre']).' ';

					$sqlNotTerminacion = sprintf("INSERT INTO tbl_empleados_notificaciones 
												(
													en_emp_id,
													en_usu_id,
													en_tipo,
													en_fecha_empieza,
													en_fecha,
													en_estado
												) 
												VALUES (%s,%s,%s,%s,%s,%s)",

						GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
						GetSQLValueString($usuId, "double"),
						GetSQLValueString('terminacion', "text"),
						GetSQLValueString($fechaSimulacion, "date"),
						GetSQLValueString($fechaSimulacion, "date"),
						GetSQLValueString(1, "int")
					);
					$rs_sqlNotTerminacion = mysqli_query($conexion, $sqlNotTerminacion);
				}
			}
			
			$emp_vacaciones_x_descontar = $row_sqlEmpleado['emp_vacaciones_x_descontar'];

			//Revisamos si existe un cambio de condiciones laborales en el mes.
			$sqlCondPresente = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`=%s AND `es_cond_fecha_proxima`<=%s",
				GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
				GetSQLValueString($fechaBusquedaPresenteMes, "date")
			);
			$rs_sqlCondPresente = mysqli_query($conexion, $sqlCondPresente);
			$row_sqlCondPresente = mysqli_fetch_assoc($rs_sqlCondPresente);

			//Revisamos si existe un cambio de condiciones laborales en el mes siguiente.
			$sqlCondSiguiente = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`=%s AND `es_cond_fecha_proxima`<=%s",
				GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
				GetSQLValueString($fechaBusquedaProximoMes, "date")
			);
			$rs_sqlCondSiguiente = mysqli_query($conexion, $sqlCondSiguiente);
			$row_sqlCondSiguiente = mysqli_fetch_assoc($rs_sqlCondSiguiente);

			$sqlEmpCamb = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`=%s AND `es_cond_fecha_proxima`<=%s ORDER BY `es_cond_fecha_proxima` DESC LIMIT 0,1",
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

			$emp_do_nombre = $row_sqlEmpleado['emp_do_nombre'];
			$emp_contribuciones = $row_sqlEmpleado['emp_contribuciones'];

			$arrayTerminos = array('', 'Por hora','Por día','Por mes');
			$arrayPeriodo = array('', 'diario','semanal','quincenal','mensual');

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

			$diaPagoTerminaMes = false;

			$salario = 0;
			$descuento = 0;
			$fechaSimulacionInicial = '';
			if($emp_cond_periodo == 1){
				// Diario

				$periodoNot = 'diario';
				$fechaSimulacionInicial = $fechaSimulacion;
				$resultSemana = explode(',', $emp_cond_semanas);
				if (in_array($array_dias[date('l', strtotime($fechaSimulacion))], $resultSemana)){
					// Notificacion pago Diario
					$notificacionSalario = true;
				}
				$salario = $salarioDiario;
			}elseif($emp_cond_periodo == 2){
				// Semanal
				$terminaTipoCondiciones = false;
				$periodoNot = 'semanal';
				//diasLaborales empiezaSemana
				if( $row_sqlCondSiguiente["es_id"] && checkInRange($fechaIniciaSemana, $fechaTerminaSemana, $fechaSimulacion)){

					$fechaSimulacionInicial = $fechaIniciaSemana;
					$terminaTipoCondiciones = true;
					//Si la semana termina después de acabarse el mes
					if( 
						$terminaSemana > $array_dias[date('l', strtotime($fechaTerminaSemana))] 
						&& 
						$fechaTerminaSemana == $fechaSimulacion
					){
						$semanasTrabajo = explode(",", $emp_cond_semanas);
						$diasTrabajados = 0;
						foreach ($semanasTrabajo as $kST => $valueSemana) {
							if ($valueSemana <= $array_dias[date('l', strtotime($fechaTerminaSemana))]) {
								$diasTrabajados++;
							}
						}
						$notificacionSalario = true;
						$salario = $salarioDiario * $diasTrabajados;
					}else{
						if ($array_dias[date('l', strtotime($fechaSimulacion))] == $terminaSemana) {
							$fechaProcesar = DateTime::createFromFormat('Y-m-d', $fechaSimulacion);
							if ($diasLaborales == 1) {
								$fechaSimulacionInicial = $fechaSimulacion;
							}else{
								$encontrarFecha = false;
								while(!$encontrarFecha) {
									$fechaProcesar->modify("-1 days");
									if ($array_dias[date('l', strtotime($fechaProcesar->format('Y-m-d')))] == $empiezaSemana) {
										$encontrarFecha = true;
									}
								}
								$fechaSimulacionInicial = $fechaProcesar->format('Y-m-d');
							}

							// Notificacion pago Semanal
							$semanasTrabajo = explode(",", $emp_cond_semanas);
							$diasTrabajados = 0;
							foreach ($semanasTrabajo as $kST => $valueSemana) {
								if ($valueSemana <= $array_dias[date('l', strtotime($fechaTerminaSemana))]) {
									$diasTrabajados++;
								}
							}

							$notificacionSalario = true;
							$salario = $salarioDiario * $diasTrabajados;
						}
					}
				}else{

					//
					if ($array_dias[date('l', strtotime($fechaSimulacion))] == $terminaSemana) {
						$fechaProcesar = DateTime::createFromFormat('Y-m-d', $fechaSimulacion);
						if ($diasLaborales == 1) {
							$fechaSimulacionInicial = $fechaSimulacion;
						}else{
							$encontrarFecha = false;
							while(!$encontrarFecha) {
								$fechaProcesar->modify("-1 days");
								if ($array_dias[date('l', strtotime($fechaProcesar->format('Y-m-d')))] == $empiezaSemana) {
									$encontrarFecha = true;
								}
							}
							$fechaSimulacionInicial = $fechaProcesar->format('Y-m-d');
						}

						$notificacionSalario = true;
						$salario = $salarioSemanal;

						// Notificacion pago Semanal
						if ($row_sqlCondPresente["es_id"]) {
							$sqlPrimerPago = sprintf("SELECT COUNT(*) AS conteo_pagos FROM `tbl_empleados_notificaciones` WHERE en_tipo='salario' AND `en_emp_id`=%s AND `en_cond_fecha`>=%s AND `en_cond_fecha`>=%s",
								GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
								GetSQLValueString($yearHoy.'-'.$mesHoy.'-01', "date"),
								GetSQLValueString($yearHoy.'-'.$mesHoy.'-31', "date")
							);
							$rs_sqlPrimerPago = mysqli_query($conexion, $sqlPrimerPago);
							$row_sqlPrimerPago = mysqli_fetch_assoc($rs_sqlPrimerPago);
							if( $row_sqlPrimerPago["conteo_pagos"] == 0 ){
								// Notificacion pago Semanal
								$semanasTrabajo = explode(",", $emp_cond_semanas);
								$diasTrabajados = 0;
								$fechaIniciaSemanaCambio = $yearHoy."-".$mesHoy."-01";
								foreach ($semanasTrabajo as $kST => $valueSemana) {
									if ($valueSemana >= $fechaIniciaSemanaCambio) {
										$diasTrabajados++;
									}
								}
								$salario = $salarioDiario * $diasTrabajados;
							}
						}
					}
				}

				//Verificamos que no le hayan pagado antes
				$sqlPrimerPago = sprintf("SELECT COUNT(*) AS conteo_pagos FROM `tbl_empleados_notificaciones` WHERE  en_tipo='salario' AND `en_emp_id`=%s",
					GetSQLValueString($row_sqlEmpleado['emp_id'], "double")
				);
				$rs_sqlPrimerPago = mysqli_query($conexion, $sqlPrimerPago);
				$row_sqlPrimerPago = mysqli_fetch_assoc($rs_sqlPrimerPago);
				$cont = 0;
				if( $row_sqlPrimerPago["conteo_pagos"] == 0 ){
					$starDate = new DateTime( $emp_cond_fecha_relacion );
					$endDate = new DateTime( $emp_cond_fecha_relacion );
					$diasDiferencia = ($terminaSemana - $array_dias[$starDate->format('l')]);
					$endDate->modify("+".$diasDiferencia." days");
					$resultSemana = explode(',', $emp_cond_semanas);

					if ($terminaTipoCondiciones) {
						$endDate = new DateTime( $fechaTerminaSemana );
					}

					while( $starDate <= $endDate){
							if (in_array($array_dias[$starDate->format('l')], $resultSemana)){
							$cont++;
							}
							$starDate->modify("+1 days");
					}
					list($yearRel, $mesRel, $diaRel) = explode("-", $emp_cond_fecha_relacion);
					if ($yearHoy == $yearRel && $mesHoy == $mesRel) {
						$fechaSimulacionInicial = $emp_cond_fecha_relacion;
						$salario = $salarioDiario * $cont;
					}
				}

			}elseif($emp_cond_periodo == 3){
				// Quincenal
				$periodoNot = 'quincenal';
				// $periodo['quincenal'] = array(14,29,27);
				if((int)$diaHoy == $periodo['quincenal'][0]){
					// Notificacion pago Quincenal
					// $fechaSimulacionInicial = $yearHoy .'-'. $mesHoy .'-01';
					$fechaSimulacionInicial = $yearHoy .'-'. $mesHoy .'-01';
					$fechaSimulacion = $yearHoy .'-'. $mesHoy .'-15';
					$notificacionSalario = true;
				}elseif((int)$mesHoy != 2 && (int)$diaHoy == $periodo['quincenal'][1]){
					// Notificacion pago Quincenal
					$notificacionSalario = true;
					// $fechaSimulacionInicial = $yearHoy .'-'. $mesHoy .'-15';
					$fechaSimulacionInicial = $yearHoy .'-'. $mesHoy .'-16';
					$fechaSimulacion = $yearHoy .'-'. $mesHoy .'-'.getUltimoDiaMes($yearHoy, $mesHoy);
				}elseif((int)$mesHoy == 2 && (int)$diaHoy == $periodo['quincenal'][2]){
					// Notificacion pago Quincenal
					$notificacionSalario = true;
					// $fechaSimulacionInicial = $yearHoy .'-'. $mesHoy .'-15';
					$fechaSimulacionInicial = $yearHoy .'-'. $mesHoy .'-16';
					$fechaSimulacion = $yearHoy .'-'. $mesHoy .'-'.getUltimoDiaMes($yearHoy, $mesHoy);
				}

				$salario = $salarioQuincenal;

				//Verificamos que no le hayan pagado antes
				$sqlPrimerPago = sprintf("SELECT COUNT(*) AS conteo_pagos FROM `tbl_empleados_notificaciones` WHERE  en_tipo='salario' AND `en_emp_id`=%s",
					GetSQLValueString($row_sqlEmpleado['emp_id'], "double")
				);
				$rs_sqlPrimerPago = mysqli_query($conexion, $sqlPrimerPago);
				$row_sqlPrimerPago = mysqli_fetch_assoc($rs_sqlPrimerPago);
				$cont = 0;
				if( $row_sqlPrimerPago["conteo_pagos"] == 0 ){
					list($yearRel, $mesRel, $diaRel) = explode("-", $emp_cond_fecha_relacion);
					if ($yearHoy == $yearRel && $mesHoy == $mesRel) {
						$diasMes = cal_days_in_month(CAL_GREGORIAN, $mesHoy, $yearHoy);
						// Calculo Normal 
						if ( strtotime($emp_cond_fecha_relacion) >= strtotime($yearHoy.'-'.$mesHoy.'-15')) {
							$endDate = new DateTime($yearHoy.'-'.$mesHoy.'-'.$diasMes);
						}else{
							$endDate = new DateTime($yearHoy.'-'.$mesHoy.'-15');
						}
						$starDate = new DateTime( $emp_cond_fecha_relacion );
						$starDateIni = new DateTime( $emp_cond_fecha_relacion );

						// Calculo pasada una quincena.
						if ( strtotime($emp_cond_fecha_relacion) < strtotime($yearHoy.'-'.$mesHoy.'-15') && 
							strtotime($fechaSimulacion) > strtotime($yearHoy.'-'.$mesHoy.'-15')
						){
							$endDate = new DateTime($yearHoy.'-'.$mesHoy.'-'.$diasMes);
							$starDate = new DateTime($yearHoy.'-'.$mesHoy.'-15');
							$starDateIni = new DateTime($yearHoy.'-'.$mesHoy.'-16');
						}

						$resultSemana = explode(',', $emp_cond_semanas);
						while( $starDate <= $endDate){
								if (in_array($array_dias[$starDate->format('l')], $resultSemana)){
								$cont++;
								}
								$starDate->modify("+1 days");
						}

						if (strtotime($emp_cond_fecha_relacion) < strtotime($endDate->format('Y-m-d'))) {
							# code...
							$fechaSimulacionInicial = $starDateIni->format('Y-m-d');
							if ( ($salarioDiario*$cont) <= $salario ) {
								$salario = $salarioDiario * $cont;
							}
						}
					}
				}
			}elseif($emp_cond_periodo == 4){
				// Mensual

				$periodoNot = 'mensual';
				$fechaSimulacionInicial = $yearHoy .'-'. $mesHoy .'-01';
				if((int)$diaHoy == $periodo['mensual'][0]){
					// Notificacion pago Mensual
					$notificacionSalario = true;
				}elseif((int)$mesHoy == 2 && (int)$diaHoy == $periodo['mensual'][1]){
					// Notificacion pago Mensual
					$notificacionSalario = true;
				}
				$salario = $salarioMensual;

				//Verificamos que no le hayan pagado antes
				$sqlPrimerPago = sprintf("SELECT COUNT(*) AS conteo_pagos FROM `tbl_empleados_notificaciones` WHERE  en_tipo='salario' AND `en_emp_id`=%s",
					GetSQLValueString($row_sqlEmpleado['emp_id'], "double")
				);
				$rs_sqlPrimerPago = mysqli_query($conexion, $sqlPrimerPago);
				$row_sqlPrimerPago = mysqli_fetch_assoc($rs_sqlPrimerPago);
				$cont = 0;
				if( $row_sqlPrimerPago["conteo_pagos"] == 0 ){
					list($yearRel, $mesRel, $diaRel) = explode("-", $emp_cond_fecha_relacion);
					if ($yearHoy == $yearRel && $mesHoy == $mesRel) {
						$diasMes = cal_days_in_month(CAL_GREGORIAN, $mesHoy, $yearHoy);
						$starDate = new DateTime( $emp_cond_fecha_relacion );
						$endDate = new DateTime($yearHoy.'-'.$mesHoy.'-'.$diasMes);
						$resultSemana = explode(',', $emp_cond_semanas);
						while( $starDate <= $endDate){
								if (in_array($array_dias[$starDate->format('l')], $resultSemana)){
								$cont++;
								}
								$starDate->modify("+1 days");
						}

						if (strtotime($emp_cond_fecha_relacion) < strtotime($endDate->format('Y-m-d'))) {
							$fechaSimulacionInicial = $emp_cond_fecha_relacion;
							if ( ($salarioDiario*$cont) <= $salario ) {
								$salario = $salarioDiario * $cont;
							}
						}
					}
				}
			}

			//Buscar si la persona se ha ido de vacaciones para saber si se envia la notificación o no.
			//$fechaSimulacionInicial
			if($emp_vacaciones_x_descontar > 0){
				$valorVacaciones = $emp_vacaciones_x_descontar;
				$salarioRestante = $salario;
				$sqlEmpNotVac = sprintf("SELECT * FROM tbl_empleados_notificaciones WHERE en_tipo='vacaciones' AND en_emp_id=%s AND en_fecha_empieza<=%s AND en_pagado_vacaciones='no' ",
					GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
					GetSQLValueString($fechaSimulacionInicial, "date")
				);
				$rs_sqlEmpNotVac = mysqli_query($conexion, $sqlEmpNotVac);
				while ( $row_sqlEmpNotVac = mysqli_fetch_assoc($rs_sqlEmpNotVac) ){
					if( $salarioRestante > 0 ){
						// $salarioRestante -= $row_sqlEmpNotVac["en_valor"];
						$salarioRestante -= $emp_vacaciones_x_descontar;
						if ($salarioRestante >= 0) {
							//cubre todo
							$sqlEmpVac = sprintf("UPDATE tbl_empleados_notificaciones SET en_pagado_vacaciones='si' WHERE en_id=%s",
								GetSQLValueString($row_sqlEmpNotVac["en_id"], "double")
							);
							$rs_sqlEmpVac = mysqli_query($conexion, $sqlEmpVac);

							$sqlEmpTotal = sprintf("UPDATE tbl_empleados SET emp_vacaciones_x_descontar=%s WHERE emp_id=%s",
								GetSQLValueString(0, "double"),
								GetSQLValueString($row_sqlEmpleado["emp_id"], "double")
							);
							$rs_sqlEmpTotal = mysqli_query($conexion, $sqlEmpTotal);
							if ($emp_vacaciones_x_descontar > 0) {
								$salarioVacaciones = $emp_vacaciones_x_descontar;
								$guardarSalarioExtra = true;
							}
						}else{
							//no cubre todo
							$sqlEmpTotal = sprintf("UPDATE tbl_empleados SET emp_vacaciones_x_descontar=emp_vacaciones_x_descontar-%s WHERE emp_id=%s",
								GetSQLValueString(($salarioRestante * -1), "double"),
								GetSQLValueString($row_sqlEmpleado["emp_id"], "double")
							);
							$rs_sqlEmpTotal = mysqli_query($conexion, $sqlEmpTotal);
						}
					}
				}
				if( $salarioRestante <= 0 ){
					if($notificacionSalario){
						$guardarSalarioEnVacaciones = true;
					}
					$notificacionSalario = false;
				}else{
					$salario = $salarioRestante;
				}
			}

			// Salario
			if ($notificacionSalario == true || $guardarSalarioEnVacaciones == true) {
				//Calculos de SS y de SE
				//
				$ss = $se = $ssp = $sep = 0;
				if ( $emp_contribuciones == 'si' ) {
					$ss = $salario * $row_sqlVariables['vp_vc_ss'] / 100;
					$se = $salario * $row_sqlVariables['vp_vc_se'] / 100;
					$ssp = $salario * 12.25 / 100;
					$sep = $salario * 1.5 / 100;
				}

				//Busco Ausencias
				$sqlAusencias = sprintf("SELECT * FROM tbl_ausencias WHERE au_usu_id=%s AND au_emp_id=%s AND au_descontado='no' AND au_justificada=1 ",
					GetSQLValueString($usuId, "double"),
					GetSQLValueString($row_sqlEmpleado['emp_id'], "double")
				);
				$rs_sqlAusencias = mysqli_query($conexion, $sqlAusencias);
				$diasAusencias = 0;
				$descuentoAusencias = 0;
				if ($guardarSalarioEnVacaciones == false) {
					while( $row_sqlAusencias = mysqli_fetch_assoc($rs_sqlAusencias)){
						$encontrado = false;
						$fechaAus = $row_sqlAusencias["au_fecha_ausencia"];
						if( checkInRange($fechaSimulacionInicial, $fechaSimulacion, $fechaAus) ){
							$diasAusencias++;
							$descuentoAusencias += $salarioDiario;
							$encontrado = true;
						}else{
							$sqlBusSalario = sprintf("SELECT * FROM tbl_empleados_notificaciones WHERE en_tipo='salario' AND en_usu_id=%s AND en_emp_id=%s AND en_fecha_empieza<=%s AND en_fecha>=%s ",
									GetSQLValueString($usuId, "double"),
									GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
									GetSQLValueString($fechaAus, "date"),
									GetSQLValueString($fechaAus, "date")
							);
							$rs_sqlBusSalario = mysqli_query($conexion, $sqlBusSalario);
							$row_sqlBusSalario = mysqli_fetch_assoc($rs_sqlBusSalario);
							if ($row_sqlBusSalario["en_salariodiario"]) {
								$encontrado = true;
								$diasAusencias++;
								$descuentoAusencias += $row_sqlBusSalario["en_salariodiario"];
							}
						}

						//Editar au_descontado='si'
						if ($encontrado) {
							$sqlDescontado = sprintf("UPDATE tbl_ausencias SET au_descontado='si' WHERE au_id=%s",
								GetSQLValueString($row_sqlAusencias["au_id"], "double")
							);
							$rs_sqlDescontado = mysqli_query($conexion, $sqlDescontado);
						}
					}
				}

				$total = $salario - $ss - $se - $descuento - $descuentoAusencias;
				$estadoNotificacion = 1;
				if ($guardarSalarioEnVacaciones) {
					$estadoNotificacion = 2;
				}
				$sqlNotificacion = sprintf("INSERT INTO tbl_empleados_notificaciones 
											(
												en_emp_id,
												en_usu_id,
												en_tipo,
												en_periodo,
												en_valor,
												en_ss,
												en_se,
												en_ssp,
												en_sep,
												en_descuento,
												en_au_numeros,
												en_au_descuento,
												en_total,
												en_salariodiario,
												en_fecha_empieza,
												en_fecha,
												en_estado
											) 
											VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",

					GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
					GetSQLValueString($usuId, "double"),
					GetSQLValueString('salario', "text"),
					GetSQLValueString($periodoNot, "text"),
					GetSQLValueString(round($salario,2), "double"),
					GetSQLValueString(round($ss,2), "double"),
					GetSQLValueString(round($se,2), "double"),
					GetSQLValueString(round($ssp,2), "double"),
					GetSQLValueString(round($sep,2), "double"),
					GetSQLValueString(round($descuento,2), "double"),
					GetSQLValueString($diasAusencias, "double"),
					GetSQLValueString(round($descuentoAusencias,2), "double"),
					GetSQLValueString(round($total,2), "double"),
					GetSQLValueString($salarioDiario, "double"),
					GetSQLValueString($fechaSimulacionInicial, "date"),
					GetSQLValueString($fechaSimulacion, "date"),
					GetSQLValueString($estadoNotificacion, "int")
				);
				$rs_sqlNotificacion = mysqli_query($conexion, $sqlNotificacion);
				if (!$guardarSalarioEnVacaciones) {
					$idRegistro = mysqli_insert_id($conexion);
				}

				if( $guardarSalarioExtra ){
					$sqlNotificacion2 = sprintf("INSERT INTO tbl_empleados_notificaciones 
												(
													en_emp_id,
													en_usu_id,
													en_tipo,
													en_periodo,
													en_valor,
													en_ss,
													en_se,
													en_ssp,
													en_sep,
													en_descuento,
													en_au_numeros,
													en_au_descuento,
													en_total,
													en_salariodiario,
													en_fecha_empieza,
													en_fecha,
													en_estado
												) 
												VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",

						GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
						GetSQLValueString($usuId, "double"),
						GetSQLValueString('salario', "text"),
						GetSQLValueString($periodoNot, "text"),
						GetSQLValueString(round($salarioVacaciones,2), "double"),
						GetSQLValueString(round($ss,2), "double"),
						GetSQLValueString(round($se,2), "double"),
						GetSQLValueString(round($ssp,2), "double"),
						GetSQLValueString(round($sep,2), "double"),
						GetSQLValueString(round($descuento,2), "double"),
						GetSQLValueString($diasAusencias, "double"),
						GetSQLValueString(round($descuentoAusencias,2), "double"),
						GetSQLValueString(round($salarioVacaciones,2), "double"),
						GetSQLValueString($salarioDiario, "double"),
						GetSQLValueString($fechaSimulacionInicial, "date"),
						GetSQLValueString($fechaSimulacion, "date"),
						GetSQLValueString(2, "int")
					);
					$rs_sqlNotificacion2 = mysqli_query($conexion, $sqlNotificacion2);
				}
			}

			// Xiii
			if ( ($mesHoy == 4 || $mesHoy == 8 || $mesHoy == 12) && $diaHoy == 14 ) {
				$notificacionXIII = true;

				if ( $mesHoy == 4  ) {
					$xiiiFechaInicia = ($yearHoy-1)."-12-16";
					$xiiiFechaTermina = $yearHoy."-04-15";
				}elseif ( $mesHoy == 8  ) {
					$xiiiFechaInicia = $yearHoy."-04-16";
					$xiiiFechaTermina = $yearHoy."-08-15";
				}elseif ( $mesHoy == 12  ) {
					$xiiiFechaInicia = $yearHoy."-08-16";
					$xiiiFechaTermina = $yearHoy."-12-15";
				}

				if( strtotime($xiiiFechaInicia) < strtotime($emp_cond_fecha_relacion) ) {
					$xiiiFechaInicia = $emp_cond_fecha_relacion;
					$fechaXiiiI = new DateTime($xiiiFechaInicia);
					$fechaXiiiF = new DateTime($xiiiFechaTermina);
					$diferenciaXiii = $fechaXiiiI->diff($fechaXiiiF);
					$meses = ( $diferenciaXiii->d / 30 ) + $diferenciaXiii->m;
				}else{
					$meses = 4;
				}
				$xiii = ($salarioMensual*$meses/12);

				$xiii_ss = 0;

				if ( $emp_contribuciones == 'si' ) {
					$xiii_ss = $xiii * 0.0725;
				}
				$xiii_total = $xiii - $xiii_ss;

				if ($idRegistro) {
					$sqlNotificacionUpd = sprintf("UPDATE tbl_empleados_notificaciones SET en_total=%s, en_xiii=%s, en_xiii_ss=%s, en_xiii_total=%s WHERE en_id=%s",
						GetSQLValueString(round($total + $xiii_total,2), "double"),
						GetSQLValueString(round($xiii,2), "double"),
						GetSQLValueString(round($xiii_ss,2), "double"),
						GetSQLValueString(round($xiii_total,2), "double"),
						GetSQLValueString($idRegistro, "double")
					);
					$rs_sqlNotificacionUpd = mysqli_query($conexion, $sqlNotificacionUpd);
				}else{
					$sqlNotificacion = sprintf("INSERT INTO tbl_empleados_notificaciones 
												(en_emp_id,en_usu_id,en_tipo,en_valor,en_xiii,en_xiii_ss,en_xiii_total,en_fecha_empieza,en_fecha) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)",
						GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
						GetSQLValueString($usuId, "double"),
						GetSQLValueString('xiii', "text"),
						GetSQLValueString(round($xiii,2), "double"),
						GetSQLValueString(round($xiii,2), "double"),
						GetSQLValueString(round($xiii_ss,2), "double"),
						GetSQLValueString(round($xiii_total,2), "double"),
						GetSQLValueString($xiiiFechaInicia, "date"),
						GetSQLValueString($xiiiFechaTermina, "date")
					);
					$rs_sqlNotificacion = mysqli_query($conexion, $sqlNotificacion);
				}
			}

			// Vacaciones
			$sqlVacaciones = sprintf("SELECT * FROM tbl_vacaciones WHERE vc_usu_id=%s AND vc_emp_id=%s AND vc_fecha_salida=%s  AND vc_calculado='no'",
				GetSQLValueString($usuId, "double"),
				GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
				GetSQLValueString($fechaSimulacionVacaciones, "date")
			);
			$rs_sqlVacaciones = mysqli_query($conexion, $sqlVacaciones);
			$row_sqlVacaciones = mysqli_fetch_assoc($rs_sqlVacaciones);
			if ($row_sqlVacaciones['vc_id']) {
				$notificacionVacaciones = true;
				$fecha_salida = $row_sqlVacaciones['vc_fecha_salida'];
				$fecha_regreso = $row_sqlVacaciones['vc_fecha_regreso'];

				$fechaActual = new DateTime($fecha_salida);
				$fechaActual->modify("-11 months");
				$fechaActual->format('Y-m-d');

				$sqlPagos = sprintf("SELECT SUM(en_valor) AS sum_pagos FROM tbl_empleados_notificaciones WHERE en_tipo='salario' AND en_emp_id=%s AND en_fecha>=%s AND en_fecha<=%s ",
					GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
					GetSQLValueString($fechaActual->format('Y-m-d'), "date"),
					GetSQLValueString($fecha_salida, "date")
				);
				$rs_sqlPagos = mysqli_query($conexion, $sqlPagos);
				$row_sqlPagos = mysqli_fetch_assoc($rs_sqlPagos);	

				$diasVacaciones = diferenciaDias($fecha_salida, $fecha_regreso) + 1;
				// $valorDiasVacaciones = ((($row_sqlPagos["sum_pagos"])/11)/30);
				$valorDiasVacaciones = ($salarioMensual/30);
				$vacaciones_valor = ($valorDiasVacaciones * $diasVacaciones);

				if ( $emp_contribuciones == 'si' ) {
					$vacas_ss = $vacaciones_valor * $row_sqlVariables['vp_vc_ss'] / 100;
					$vacas_se = $vacaciones_valor * $row_sqlVariables['vp_vc_se'] / 100;
					$vacas_ssp = $vacaciones_valor * 12.25 / 100;
					$vacas_sep = $vacaciones_valor * 1.5 / 100;
				}
				$vacaciones_total = $vacaciones_valor - $vacas_ss - $vacas_se;

				$sqlRegistroPagoVac = sprintf("INSERT INTO tbl_empleados_notificaciones (en_usu_id, en_emp_id, en_tipo, en_valor, en_total, en_ss, en_se, en_ssp, en_sep, en_fecha_empieza, en_fecha, en_valordia_vac) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s) ",
					GetSQLValueString($usuId,"double"),
					GetSQLValueString($row_sqlEmpleado['emp_id'],"double"),
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

				//guardarTotalTabla.
				$sqlEmpleadosVacaciones = sprintf("UPDATE tbl_empleados SET emp_vacaciones_x_descontar=emp_vacaciones_x_descontar+%s WHERE emp_id=%s",
					GetSQLValueString(round($vacaciones_valor,2), "double"),
					GetSQLValueString($row_sqlEmpleado['emp_id'], "double")
				);
				$rs_sqlEmpleadosVacaciones = mysqli_query($conexion, $sqlEmpleadosVacaciones);

				$sqlVacacionesCalc = sprintf("UPDATE tbl_vacaciones SET vc_calculado='si' WHERE vc_id=%s",
					GetSQLValueString($row_sqlVacaciones['vc_id'], "double")
				);
				$rs_sqlVacacionesCalc = mysqli_query($conexion, $sqlVacacionesCalc);
			}

			if ($notificacionSalario) {
				$notificacionSalarioTotal = true;
			}

			$consulta = ukuGetPlanCustomer( $usuId );
			$sub_status = $consulta["ss_status"];
			if ( ( time() > $consulta['ss_period_end'] || $consulta['ss_status'] != 'active' ) ) {
				// Revisar actualizacion
				try {
					$subscription = \Stripe\Subscription::retrieve( $consulta['ss_stripe_id'] );
					$sub_status = $subscription["status"];
				} catch (Exception $e) {
					// $result['error_cancelando'] = true;
				}
			}

			$enviarNotificacion = true;
			switch ($sub_status) {
				case 'past_due':
					$enviarNotificacion = false;
					break;

				case 'canceled':
					$enviarNotificacion = false;
					break;

				case 'unpaid':
					$enviarNotificacion = false;
					break;
				
				default:
					break;
			}

			if ($enviarNotificacion) {
				//ENVIAMOS NOTIFICACION PUSH AL USUARIO
				$sqlTokens = sprintf("SELECT * FROM tbl_usuarios_token WHERE ut_usu_id=%s",
							GetSQLValueString($usuId, "double")
						);
				$rs_sqlTokens = mysqli_query($conexion, $sqlTokens);
				$iTok = 0;
				$registrationIds = array();
				$registrationIdsAndroid = array();
				while( $row_sqlTokens = mysqli_fetch_assoc($rs_sqlTokens) ){
					if( $row_sqlTokens['ut_platform'] == 'ios' ){
						array_push($registrationIds, $row_sqlTokens["ut_token"]);
					}elseif( $row_sqlTokens['ut_platform'] == 'android' ){
						array_push($registrationIdsAndroid, $row_sqlTokens["ut_token"]);
					}
					$iTok++;
				}

				if ($notificacionSalarioTotal) {
					// Uku - Notificaciones de Salario
					$title = 'Uku - Notificación de Salario';
					$body = 'Tienes pendientes pagos a empleados';
					envioNotificacionesPush($title, $body, $registrationIds);
					envioNotificacionesPush($title, $body, $registrationIdsAndroid, 'android');
				}

				if ($notificacionXIII) {
					// Uku - Notificaciones de Xiii
					$title = 'Uku - Notificación de xiii';
					$body = 'Recordatorio de pagos de décimo tercer mes';
					envioNotificacionesPush($title, $body, $registrationIds);
					envioNotificacionesPush($title, $body, $registrationIdsAndroid, 'android');
				}

				if ($notificacionVacaciones) {
					// Uku - Notificaciones de Xiii
					$title = 'Uku - Notificación de vacaciones';
					$body = 'Recordatorio de vacaciones';
					envioNotificacionesPush($title, $body, $registrationIds);
					envioNotificacionesPush($title, $body, $registrationIdsAndroid, 'android');
				}

				if ($notificacionTerminacionDefinido) {
					// Uku - Notificaciones de Xiii
					$title = 'Uku - Notificación de terminación de contrato definido';
					$body = $empleadosTerminados;
					envioNotificacionesPush($title, $body, $registrationIds);
					envioNotificacionesPush($title, $body, $registrationIdsAndroid, 'android');
				}
			}
		}

		// Notificaciones Prestamos
		$sqlEmpleado = sprintf("SELECT * FROM tbl_prestamos INNER JOIN tbl_empleados ON pr_emp_id=emp_id INNER JOIN tbl_usuarios ON emp_usu_id=usu_id AND usu_nacionalidad='panama' WHERE pr_cuotas>0 AND emp_estado=3 AND emp_cond_fecha_relacion<=%s ",
			GetSQLValueString($fechaSimulacion, "date")
		);
		$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
		
        //Préstamos
        while ($row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado)) {
			$notificacionSalario = $guardarSalarioEnVacaciones = $guardarSalarioExtra = $notificacionCuota = false;
			$valorPagar = $pr_monto = $pr_cuotas = 0;
			
			$usuId = $row_sqlEmpleado['emp_usu_id'];
					
			$salarioVacaciones = 0;
			$emp_contribuciones = 'no';
			$emp_cond_fecha_relacion = $row_sqlEmpleado['emp_cond_fecha_relacion'];
			
			$emp_cond_jornada = $row_sqlEmpleado['emp_cond_jornada'];
			$emp_cond_semanas = $row_sqlEmpleado['emp_cond_semanas'];
			$emp_cond_periodo = $row_sqlEmpleado['emp_cond_periodo'];
			$emp_cond_termino = $row_sqlEmpleado['emp_cond_termino'];
			$emp_cond_sueldo = $row_sqlEmpleado['emp_cond_sueldo'];
			$emp_contrato = $row_sqlEmpleado['emp_contrato'];
			$emp_tipo_definido = $row_sqlEmpleado['emp_tipo_definido'];
			$emp_tipo = $row_sqlEmpleado['emp_tipo'];

			if ($emp_contrato == 'definido') {
				// $emp_tipo_definido
				$fechaTerminacionDefinido = new DateTime($emp_cond_fecha_relacion);
				$fechaTerminacionDefinido->modify($emp_tipo_definido." months");
				if( $fechaTerminacionDefinido->format('Y-m-d') == $fechaSimulacion){
				}
			}
			
			$emp_vacaciones_x_descontar = $row_sqlEmpleado['emp_vacaciones_x_descontar'];

			//Revisamos si existe un cambio de condiciones laborales en el mes.
			$sqlCondPresente = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`=%s AND `es_cond_fecha_proxima`<=%s",
				GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
				GetSQLValueString($fechaBusquedaPresenteMes, "date")
			);
			$rs_sqlCondPresente = mysqli_query($conexion, $sqlCondPresente);
			$row_sqlCondPresente = mysqli_fetch_assoc($rs_sqlCondPresente);

			//Revisamos si existe un cambio de condiciones laborales en el mes siguiente.
			$sqlCondSiguiente = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`=%s AND `es_cond_fecha_proxima`<=%s",
				GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
				GetSQLValueString($fechaBusquedaProximoMes, "date")
			);
			$rs_sqlCondSiguiente = mysqli_query($conexion, $sqlCondSiguiente);
			$row_sqlCondSiguiente = mysqli_fetch_assoc($rs_sqlCondSiguiente);

			$sqlEmpCamb = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`=%s AND `es_cond_fecha_proxima`<=%s ORDER BY `es_cond_fecha_proxima` DESC LIMIT 0,1",
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

			$emp_do_nombre = $row_sqlEmpleado['emp_do_nombre'];
			$emp_contribuciones = $row_sqlEmpleado['emp_contribuciones'];

			$arrayTerminos = array('', 'Por hora','Por día','Por mes');
			$arrayPeriodo = array('', 'diario','semanal','quincenal','mensual');

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

			$diaPagoTerminaMes = false;

			$salario = 0;
			$descuento = 0;
			$fechaSimulacionInicial = '';
			
			$pr_monto = $row_sqlEmpleado['pr_monto'];
			$pr_cuotas_tipo = $row_sqlEmpleado['pr_cuotas_tipo'];
			$pr_cuotas = $row_sqlEmpleado['pr_cuotas'];
			
			if($pr_cuotas_tipo == 1){
				$periodoNot = 'diario';
				$fechaSimulacionInicial = $fechaSimulacion;
				$resultSemana = explode(',', $emp_cond_semanas);
				if (in_array($array_dias[date('l', strtotime($fechaSimulacion))], $resultSemana)){
					// Notificacion pago Diario
					$notificacionCuota = true;
					$valorPagar = $pr_monto / $pr_cuotas;
					$pr_monto = $pr_monto - $valorPagar;
					$pr_cuotas = $pr_cuotas - 1;
				}
			}elseif($pr_cuotas_tipo == 2){
			    if( $row_sqlCondSiguiente["es_id"] && checkInRange($fechaIniciaSemana, $fechaTerminaSemana, $fechaSimulacion)){
			        $fechaSimulacionInicial = $fechaIniciaSemana;
					$terminaTipoCondiciones = true;
					//Si la semana termina después de acabarse el mes
					if( 
						$terminaSemana > $array_dias[date('l', strtotime($fechaTerminaSemana))] 
						&& 
						$fechaTerminaSemana == $fechaSimulacion
					){
						$notificacionCuota = true;
						$valorPagar = $pr_monto / $pr_cuotas;
						$pr_monto = $pr_monto - $valorPagar;
						$pr_cuotas = $pr_cuotas - 1;
					}else{
						if ($array_dias[date('l', strtotime($fechaSimulacion))] == $terminaSemana) {
							$notificacionCuota = true;
    						$valorPagar = $pr_monto / $pr_cuotas;
    						$pr_monto = $pr_monto - $valorPagar;
    						$pr_cuotas = $pr_cuotas - 1;
						}
					}
			    }else{
			        if ($array_dias[date('l', strtotime($fechaSimulacion))] == $terminaSemana) {
			            $notificacionCuota = true;
						$valorPagar = $pr_monto / $pr_cuotas;
						$pr_monto = $pr_monto - $valorPagar;
						$pr_cuotas = $pr_cuotas - 1;
			        }
			    }
				
			}elseif($pr_cuotas_tipo == 3){
				if((int)$diaHoy == $periodo['quincenal'][0]){
					// Notificacion pago Quincenal
					$notificacionCuota = true;
					$valorPagar = $pr_monto / $pr_cuotas;
					$pr_monto = $pr_monto - $valorPagar;
					$pr_cuotas = $pr_cuotas - 1;
				}elseif((int)$mesHoy != 2 && (int)$diaHoy == $periodo['quincenal'][1]){
					// Notificacion pago Quincenal
					$notificacionCuota = true;
					$valorPagar = $pr_monto / $pr_cuotas;
					$pr_monto = $pr_monto - $valorPagar;
					$pr_cuotas = $pr_cuotas - 1;
				}elseif((int)$mesHoy == 2 && (int)$diaHoy == $periodo['quincenal'][2]){
					// Notificacion pago Quincenal
					$notificacionCuota = true;
					$valorPagar = $pr_monto / $pr_cuotas;
					$pr_monto = $pr_monto - $valorPagar;
					$pr_cuotas = $pr_cuotas - 1;
				}
			}elseif($pr_cuotas_tipo == 4){
				
			}
			
			if($notificacionCuota){
			    // Update y Notificación
			    $sqlCuotaP = sprintf("INSERT INTO tbl_empleados_notificaciones 
											(
												en_emp_id,
												en_usu_id,
												en_valor,
												en_tipo,
												en_fecha_empieza,
												en_fecha,
												en_estado
											) 
											VALUES (%s,%s,%s,%s,%s,%s,%s)",

					GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
					GetSQLValueString($usuId, "double"),
					GetSQLValueString($valorPagar, "double"),
					GetSQLValueString('pagocuota_prestamo', "text"),
					GetSQLValueString($fechaSimulacion, "date"),
					GetSQLValueString($fechaSimulacion, "date"),
					GetSQLValueString(1, "int")
				);
				$rs_sqlCuotaP = mysqli_query($conexion, $sqlCuotaP);
				
				$sqlEmpTotal = sprintf("UPDATE tbl_prestamos SET pr_monto=%s, pr_cuotas=%s WHERE pr_id=%s",
					GetSQLValueString($pr_monto, "double"),
					GetSQLValueString($pr_cuotas, "double"),
					GetSQLValueString($row_sqlEmpleado["pr_id"], "double")
				);
				$rs_sqlEmpTotal = mysqli_query($conexion, $sqlEmpTotal);
			}
			//

			$consulta = ukuGetPlanCustomer( $usuId );
			$sub_status = $consulta["ss_status"];
			if ( ( time() > $consulta['ss_period_end'] || $consulta['ss_status'] != 'active' ) ) {
				// Revisar actualizacion
				try {
					$subscription = \Stripe\Subscription::retrieve( $consulta['ss_stripe_id'] );
					$sub_status = $subscription["status"];
				} catch (Exception $e) {
					// $result['error_cancelando'] = true;
				}
			}

			$enviarNotificacion = true;
			switch ($sub_status) {
				case 'past_due':
					$enviarNotificacion = false;
					break;

				case 'canceled':
					$enviarNotificacion = false;
					break;

				case 'unpaid':
					$enviarNotificacion = false;
					break;
				
				default:
					break;
			}
            
			if ($enviarNotificacion) {
				//ENVIAMOS NOTIFICACION PUSH AL USUARIO
				$sqlTokens = sprintf("SELECT * FROM tbl_usuarios_token WHERE ut_usu_id=%s",
							GetSQLValueString($usuId, "double")
						);
				$rs_sqlTokens = mysqli_query($conexion, $sqlTokens);
				$iTok = 0;
				$registrationIds = array();
				$registrationIdsAndroid = array();
				while( $row_sqlTokens = mysqli_fetch_assoc($rs_sqlTokens) ){
					if( $row_sqlTokens['ut_platform'] == 'ios' ){
					    array_push($registrationIds, $row_sqlTokens["ut_token"]);
				    }elseif( $row_sqlTokens['ut_platform'] == 'android' ){
				        array_push($registrationIdsAndroid, $row_sqlTokens["ut_token"]);
				    }
					$iTok++;
				}

				if ($notificacionCuota) {
					// Uku - Notificaciones de Préstamos
					$title = 'Uku - Notificación de Préstamos';
					$body = 'Se ha descontado la cuota de un préstamo';
					envioNotificacionesPush($title, $body, $registrationIds);
					envioNotificacionesPush($title, $body, $registrationIdsAndroid, 'android');
				}
			}
		}

		// Notificaciones Adelantos
		$sqlEmpleado = sprintf("SELECT * FROM tbl_adelantos INNER JOIN tbl_empleados ON ad_emp_id=emp_id INNER JOIN tbl_usuarios ON emp_usu_id=usu_id AND usu_nacionalidad='panama' WHERE ad_monto>0 AND emp_estado=3 AND emp_cond_fecha_relacion<=%s ",
			GetSQLValueString($fechaSimulacion, "date")
		);
		$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
		
        //Adelantos
        while ($row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado)) {
			$notificacionSalario = $guardarSalarioEnVacaciones = $guardarSalarioExtra = $notificacionCuota = false;
			$valorPagar = $pr_monto = $pr_cuotas = 0;
			
			$usuId = $row_sqlEmpleado['emp_usu_id'];
					
			$salarioVacaciones = 0;
			$emp_contribuciones = 'no';
			$emp_cond_fecha_relacion = $row_sqlEmpleado['emp_cond_fecha_relacion'];
			
			$emp_cond_jornada = $row_sqlEmpleado['emp_cond_jornada'];
			$emp_cond_semanas = $row_sqlEmpleado['emp_cond_semanas'];
			$emp_cond_periodo = $row_sqlEmpleado['emp_cond_periodo'];
			$emp_cond_termino = $row_sqlEmpleado['emp_cond_termino'];
			$emp_cond_sueldo = $row_sqlEmpleado['emp_cond_sueldo'];
			$emp_contrato = $row_sqlEmpleado['emp_contrato'];
			$emp_tipo_definido = $row_sqlEmpleado['emp_tipo_definido'];
			$emp_tipo = $row_sqlEmpleado['emp_tipo'];

			if ($emp_contrato == 'definido') {
				// $emp_tipo_definido
				$fechaTerminacionDefinido = new DateTime($emp_cond_fecha_relacion);
				$fechaTerminacionDefinido->modify($emp_tipo_definido." months");
				if( $fechaTerminacionDefinido->format('Y-m-d') == $fechaSimulacion){
				}
			}
			
			$emp_vacaciones_x_descontar = $row_sqlEmpleado['emp_vacaciones_x_descontar'];

			//Revisamos si existe un cambio de condiciones laborales en el mes.
			$sqlCondPresente = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`=%s AND `es_cond_fecha_proxima`<=%s",
				GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
				GetSQLValueString($fechaBusquedaPresenteMes, "date")
			);
			$rs_sqlCondPresente = mysqli_query($conexion, $sqlCondPresente);
			$row_sqlCondPresente = mysqli_fetch_assoc($rs_sqlCondPresente);

			//Revisamos si existe un cambio de condiciones laborales en el mes siguiente.
			$sqlCondSiguiente = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`=%s AND `es_cond_fecha_proxima`<=%s",
				GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
				GetSQLValueString($fechaBusquedaProximoMes, "date")
			);
			$rs_sqlCondSiguiente = mysqli_query($conexion, $sqlCondSiguiente);
			$row_sqlCondSiguiente = mysqli_fetch_assoc($rs_sqlCondSiguiente);

			$sqlEmpCamb = sprintf("SELECT * FROM `tbl_empleados_cambiocondiciones` WHERE  `es_emp_id`=%s AND `es_cond_fecha_proxima`<=%s ORDER BY `es_cond_fecha_proxima` DESC LIMIT 0,1",
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

			$emp_do_nombre = $row_sqlEmpleado['emp_do_nombre'];
			$emp_contribuciones = $row_sqlEmpleado['emp_contribuciones'];

			$arrayTerminos = array('', 'Por hora','Por día','Por mes');
			$arrayPeriodo = array('', 'diario','semanal','quincenal','mensual');

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

			$diaPagoTerminaMes = false;

			$salario = 0;
			$descuento = 0;
			$fechaSimulacionInicial = '';
			
			$ad_monto = $row_sqlEmpleado['ad_monto'];
			$ad_cuotas_tipo = $row_sqlEmpleado['ad_tipo'];
			
			if($ad_cuotas_tipo == 1){
				$periodoNot = 'diario';
				$fechaSimulacionInicial = $fechaSimulacion;
				$resultSemana = explode(',', $emp_cond_semanas);
				if (in_array($array_dias[date('l', strtotime($fechaSimulacion))], $resultSemana)){
					// Notificacion pago Diario
					$notificacionCuota = true;
					$valorPagar = $ad_monto;
					$ad_monto = 0;
				}
			}elseif($ad_cuotas_tipo == 2){
			    if( $row_sqlCondSiguiente["es_id"] && checkInRange($fechaIniciaSemana, $fechaTerminaSemana, $fechaSimulacion)){
			        $fechaSimulacionInicial = $fechaIniciaSemana;
					$terminaTipoCondiciones = true;
					//Si la semana termina después de acabarse el mes
					if( 
						$terminaSemana > $array_dias[date('l', strtotime($fechaTerminaSemana))] 
						&& 
						$fechaTerminaSemana == $fechaSimulacion
					){
						$notificacionCuota = true;
						$valorPagar = $ad_monto;
						$ad_monto = 0;
					}else{
						if ($array_dias[date('l', strtotime($fechaSimulacion))] == $terminaSemana) {
							$notificacionCuota = true;
							$valorPagar = $ad_monto;
							$ad_monto = 0;
						}
					}
			    }else{
			        if ($array_dias[date('l', strtotime($fechaSimulacion))] == $terminaSemana) {
			            $notificacionCuota = true;
						$valorPagar = $ad_monto;
						$ad_monto = 0;
			        }
			    }
				
			}elseif($ad_cuotas_tipo == 3){
				if((int)$diaHoy == $periodo['quincenal'][0]){
					// Notificacion pago Quincenal
					$notificacionCuota = true;
					$valorPagar = $ad_monto;
					$ad_monto = 0;
				}elseif((int)$mesHoy != 2 && (int)$diaHoy == $periodo['quincenal'][1]){
					// Notificacion pago Quincenal
					$notificacionCuota = true;
					$valorPagar = $ad_monto;
					$ad_monto = 0;
				}elseif((int)$mesHoy == 2 && (int)$diaHoy == $periodo['quincenal'][2]){
					// Notificacion pago Quincenal
					$notificacionCuota = true;
					$valorPagar = $ad_monto;
					$ad_monto = 0;
				}
			}elseif($ad_cuotas_tipo == 4){
				
			}
			
			if($notificacionCuota){
			    // Update y Notificación
			    $sqlCuotaP = sprintf("INSERT INTO tbl_empleados_notificaciones 
											(
												en_emp_id,
												en_usu_id,
												en_valor,
												en_tipo,
												en_fecha_empieza,
												en_fecha,
												en_estado
											) 
											VALUES (%s,%s,%s,%s,%s,%s,%s)",

					GetSQLValueString($row_sqlEmpleado['emp_id'], "double"),
					GetSQLValueString($usuId, "double"),
					GetSQLValueString($valorPagar, "double"),
					GetSQLValueString('adelanto', "text"),
					GetSQLValueString($fechaSimulacion, "date"),
					GetSQLValueString($fechaSimulacion, "date"),
					GetSQLValueString(1, "int")
				);
				$rs_sqlCuotaP = mysqli_query($conexion, $sqlCuotaP);
				
				$sqlEmpTotal = sprintf("UPDATE tbl_adelantos SET ad_monto=%s WHERE ad_id=%s",
					GetSQLValueString($ad_monto, "double"),
					GetSQLValueString($row_sqlEmpleado["ad_id"], "double")
				);
				$rs_sqlEmpTotal = mysqli_query($conexion, $sqlEmpTotal);
			}
			//

			$consulta = ukuGetPlanCustomer( $usuId );
			$sub_status = $consulta["ss_status"];
			if ( ( time() > $consulta['ss_period_end'] || $consulta['ss_status'] != 'active' ) ) {
				// Revisar actualizacion
				try {
					$subscription = \Stripe\Subscription::retrieve( $consulta['ss_stripe_id'] );
					$sub_status = $subscription["status"];
				} catch (Exception $e) {
					// $result['error_cancelando'] = true;
				}
			}

			$enviarNotificacion = true;
			switch ($sub_status) {
				case 'past_due':
					$enviarNotificacion = false;
					break;

				case 'canceled':
					$enviarNotificacion = false;
					break;

				case 'unpaid':
					$enviarNotificacion = false;
					break;
				
				default:
					break;
			}
            
			if ($enviarNotificacion) {
				//ENVIAMOS NOTIFICACION PUSH AL USUARIO
				$sqlTokens = sprintf("SELECT * FROM tbl_usuarios_token WHERE ut_usu_id=%s",
							GetSQLValueString($usuId, "double")
						);
				$rs_sqlTokens = mysqli_query($conexion, $sqlTokens);
				$iTok = 0;
				$registrationIds = array();
				$registrationIdsAndroid = array();
				while( $row_sqlTokens = mysqli_fetch_assoc($rs_sqlTokens) ){
					if( $row_sqlTokens['ut_platform'] == 'ios' ){
					    array_push($registrationIds, $row_sqlTokens["ut_token"]);
				    }elseif( $row_sqlTokens['ut_platform'] == 'android' ){
				        array_push($registrationIdsAndroid, $row_sqlTokens["ut_token"]);
				    }
					$iTok++;
				}

				if ($notificacionCuota) {
					// Uku - Notificaciones del Adelanto
					$title = 'Uku - Notificación de Adelanto';
					$body = 'Se ha descontado el adelanto del empleado';
					envioNotificacionesPush($title, $body, $registrationIds);
					envioNotificacionesPush($title, $body, $registrationIdsAndroid, 'android');
				}
			}
		}

		// Guardar el día ejecutado.
		$sqlLog = sprintf(" SELECT * FROM tbl_log_cronjobs WHERE lcj_fecha=%s ",
						GetSQLValueString($fechaGuardarLog, "date")
					);
		$rs_sqlLog = mysqli_query($conexion, $sqlLog);
		$row_sqlLog = mysqli_fetch_assoc($rs_sqlLog);
		if (!$row_sqlLog['lcj_id']) {
			$sqlInsLog = 	sprintf(" INSERT INTO tbl_log_cronjobs (lcj_fecha) VALUES (%s)",
								GetSQLValueString($fechaGuardarLog, "date")
							);
			$rs_sqlInsLog = mysqli_query($conexion, $sqlInsLog);
		}


	}
	// Termina el Foreach
	$sqlInsLogTest = 	sprintf(" INSERT INTO tbl_log_testday (lt_fecha) VALUES (%s)",
							GetSQLValueString($fechaSimulacionHoy, "date")
						);
	$rs_sqlInsLogTest = mysqli_query($conexion, $sqlInsLogTest);
?>