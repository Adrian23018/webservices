<?php
	// session_start();
	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	// error_reporting(1);
	require_once '../../admin_uku/dompdf/autoload.inc.php';
	$stripe = new ClassIncStripe;
	// exit();

	// $fechaReferencia = "2017-11-29 06:30:00 AM";
	// $fechaActual = "2017-11-29 10:44:00 AM";

	$datetimeReferencia = date_create($fechaReferencia);
	$datetimeActual = date_create(date($fechaActual));
	// $datetimeActual = date_create($fechaActual);
	// $interval = date_diff($datetimeReferencia, $datetimeActual);

	// $hora = $interval->format("%H");
	// $minuto = $interval->format("%I");
	// $segundo = $interval->format("%S");
	// $diastranscurridos = floor((($hora*60)+$minuto + $interval->days * 24 * 60 )/2);
	
	//ENVIAMOS NOTIFICACION PUSH AL USUARIO
	$sqlTokens = sprintf("SELECT * FROM tbl_usuarios_token WHERE ut_usu_id=%s",
				GetSQLValueString(12, "double")
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
	// Uku - Notificaciones de Préstamos
	var_dump($registrationIds);
	$title = 'Uku - Notificación de Préstamos';
	$body = 'Se ha descontado la cuota de un préstamo';
	envioNotificacionesPush($title, $body, $registrationIds);
	envioNotificacionesPush($title, $body, $registrationIdsAndroid, 'android');
	
	//dlzDcIiiJa0:APA91bHH7GFwqpgUyiea8ScUY85V8FeN7-H6aYvaZn6PZpWNIoj-eGECRRyMXo5RZeCV0WadfZ0eos7xGnDs7RenBaHfcB2hKVfPuZXhwdUWtLz1h6vD65zvvqtVn1RxzxKS4J5J8KkA
	
	exit();

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

    $fechasEjecutarSimulacion = array( '2019-07-29' );
	foreach ($fechasEjecutarSimulacion as $key => $fechaSimulacion) {
		$fechaGuardarLog = $fechaSimulacion;
		// $fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia, 0);
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

		// $usuId = 2;
		$sqlEmpleado = sprintf("SELECT * FROM tbl_prestamos INNER JOIN tbl_empleados ON pr_emp_id=emp_id INNER JOIN tbl_usuarios ON emp_usu_id=usu_id AND usu_nacionalidad='panama' WHERE pr_cuotas>0 AND emp_estado=3 AND emp_cond_fecha_relacion<=%s ",
			GetSQLValueString($fechaSimulacion, "date")
		);
		print $sqlEmpleado . '<br>';
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
				
			    print $valorPagar . '<br>';
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
            
            $enviarNotificacion = true;
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

		$sqlLog = sprintf(" SELECT * FROM tbl_log_cronjobs WHERE lcj_fecha=%s ",
						GetSQLValueString($fechaGuardarLog, "date")
					);
		$rs_sqlLog = mysqli_query($conexion, $sqlLog);
		$row_sqlLog = mysqli_fetch_assoc($rs_sqlLog);
		if (!$row_sqlLog['lcj_id']) {
			$sqlInsLog = 	sprintf(" INSERT INTO tbl_log_cronjobs (lcj_fecha) VALUES (%s)",
								GetSQLValueString($fechaGuardarLog, "date")
							);
			//$rs_sqlInsLog = mysqli_query($conexion, $sqlInsLog);
		}
	}
	
	// Termina el Foreach
	$sqlInsLogTest = 	sprintf(" INSERT INTO tbl_log_testday (lt_fecha) VALUES (%s)",
							GetSQLValueString($fechaSimulacionHoy, "date")
						);
	//$rs_sqlInsLogTest = mysqli_query($conexion, $sqlInsLogTest);
?>