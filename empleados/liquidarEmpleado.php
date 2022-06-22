<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	//$fechaReferencia = "2027-10-30 10:38:00 AM";
	//$fechaActual = "2027-10-30 10:38:00 AM";
	$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);

	$_POST = json_decode(file_get_contents('php://input'), true);
	// $_POST['emp_id'] = 36;
	list($validacion, $id) = validarToken($_conection, $_POST["token"]);

	if ($validacion) {
		list($dayL, $monthL, $yearL) = explode("/", $_POST['fecha_liquidar']);
		$fecha_liquidar = $yearL."-".$monthL."-".$dayL;

		// PRUEBA Descomentar esta y comentar la otra
		// $fecha_liquidar = $fechaSimulacion;
		$fechaSimulacion = $fecha_liquidar;

		// $_POST["emp_id"]
		// $_POST["fecha_liquidar"]
		//$_POST["terminacion"]['id']
		//1. Renuncia
		//2. D justificado
		//3. D injustificado
		//4. Mutuo acuerdo
		//5. Expiración Contrato (definido)
        //$_POST['emp_id'] = 58;
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

		//salario base
		//$salarioMensual

		$sqlPagos = sprintf("SELECT SUM(en_valor) AS salarios_devengados FROM `tbl_empleados_notificaciones` WHERE `en_emp_id`=%s AND en_tipo='salario' ",
			GetSQLValueString($_POST['emp_id'], "double")
		);
		$rs_sqlPagos = mysqli_query($conexion, $sqlPagos);
		$row_sqlPagos = mysqli_fetch_assoc($rs_sqlPagos);
		//salarios devengados
		$salariosDevengados = $row_sqlPagos["salarios_devengados"];
		//Prima de Antigüedad a pagar
		$primaAntiguedad += $salariosDevengados;
		list($year, $month, $day) = explode("-", $row_sqlEmpleado["emp_cond_fecha_relacion"]);

        if( $row_sqlEmpleado["emp_promedio"] == 'si' ){
    		$promedioAntiguo = 0;
    		for ($i=5; $i>0; $i--) {
    			if($row_sqlEmpleado['emp_anho'.$i]){
    				$yearMasAntiguo = $row_sqlEmpleado['emp_anho'.$i];
    				$promedioAntiguo = $row_sqlEmpleado['emp_anho'.$i.'_valor'];
    
    				if( $yearMasAntiguo ==  $year){
    					$fechainicial = new DateTime($row_sqlEmpleado["emp_cond_fecha_relacion"]);
    					$fechafinal = new DateTime($yearMasAntiguo."-01-01");
    					$diferencia = $fechainicial->diff($fechafinal);
    					$primaAntiguedad += $promedioAntiguo * (12 - $diferencia->m);
    				}else{
    					$primaAntiguedad += $promedioAntiguo * 24;
    				}
    			}
    		}
        }else{
            $fechaInd1 = new DateTime($row_sqlEmpleado["emp_cond_fecha_relacion"]);
    		$fechaInd2 = new DateTime($fechaSimulacion);
    		$diferenciaInd = $fechaInd1->diff($fechaInd2);
    		$primaAntiguedad = ($diferenciaInd->y * 12 + $diferenciaInd->m + ($diferenciaInd->d/30)) * $salarioMensual;
        }

		$primaAntiguedadTotal =  ($primaAntiguedad*(1.923077/100));
		$result["primaAntiguedad"] = round($primaAntiguedadTotal,2);

		//Indemnización
		// $salarioMensual
		if ( $row_sqlEmpleado["emp_contrato"] == 'indefinido'){
			$fechaInd1 = new DateTime($row_sqlEmpleado["emp_cond_fecha_relacion"]);
			$fechaInd2 = new DateTime($fechaSimulacion);
			$diferenciaInd = $fechaInd1->diff($fechaInd2);

			if( 
				($diferenciaInd->y == 0 && $diferenciaInd->m > 0 && $diferenciaInd->m < 3) ||  
				($diferenciaInd->y == 0 && $diferenciaInd->m == 0 && $diferenciaInd->d >= 10)
			){
				$indemnizacion = $salarioMensual / $row_sqlVariables['vp_semanas'];
			}elseif( $diferenciaInd->y == 0 && $diferenciaInd->m >= 3 && $diferenciaInd->y < 12 ){
				$indemnizacion = ($salarioMensual / $row_sqlVariables['vp_semanas']) * 2;
			}elseif( $diferenciaInd->y >= 1 && $diferenciaInd->y < 2 ){
				$indemnizacion = $salarioMensual;
			}elseif( $diferenciaInd->y >= 2 && $diferenciaInd->y < 4 ){
				$indemnizacion = $salarioMensual * 2;
			}elseif( $diferenciaInd->y >= 4 && $diferenciaInd->y < 6 ){
				$indemnizacion = $salarioMensual * 3;
			}elseif( $diferenciaInd->y >= 6 && $diferenciaInd->y < 10 ){
				$indemnizacion = $salarioMensual * 4;
			}elseif( $diferenciaInd->y >= 10 && $diferenciaInd->y < 15 ){
				$indemnizacion = $salarioMensual * 5;
			}elseif( $diferenciaInd->y >= 15 && $diferenciaInd->y < 20 ){
				$indemnizacion = $salarioMensual * 6;
			}elseif( $diferenciaInd->y >= 20 ){
				$indemnizacion = $salarioMensual * 7;
			}
		}elseif ( $row_sqlEmpleado["emp_contrato"] == 'definido'){
			$fechaInd1 = new DateTime($row_sqlEmpleado["emp_cond_fecha_relacion"]);
			$fechaInd2 = new DateTime($fecha_liquidar);
			$diferenciaInd = $fechaInd1->diff($fechaInd2);

			if ($diferenciaInd->y > 0) {
				$indemnizacion = 0;
			}else{
				if( $diferenciaInd->m >= $row_sqlEmpleado["emp_tipo_definido"] ){
					$indemnizacion = 0;
				}else{
					$indemnizacion = ($row_sqlEmpleado["emp_tipo_definido"] - $diferenciaInd->m) * $salarioMensual;
				}
			}
		}

		$indemnizacionTotal = $indemnizacion;
		if ($_POST['terminacion']['id'] == 1 || $_POST['terminacion']['id'] == 2 ) {
			$result["indemnizacion"] = -1;
		}else{
			$result["indemnizacion"] = round($indemnizacionTotal,2);
		}

		//Vacaciones
		$empFechaInicioUku = $row_sqlEmpleado["emp_cond_fecha_relacion"];
		$empVacaciones = $row_sqlEmpleado["emp_vacaciones"];
		if ($empVacaciones == 'no') {
			$empVacacionesDias = $row_sqlEmpleado["emp_dias"];
		}

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
		$fechafinal = new DateTime($fecha_liquidar);
		$diferencia = $fechainicial->diff($fechafinal);
		$mesesVacaciones = ($diferencia->y * 12) + $diferencia->m + (($diferencia->d + 1) / 30);
		$diasVacaciones = round($mesesVacaciones * (30/11));
		$result["fechainicial"] = $fechainicial;
		$result["fechafinal"] = $fechafinal;
		$result["diasVacaciones"] = $diasVacaciones;
		$diasTotalVacaciones = $diasVacaciones + $empVacacionesDias - $diferenciaDias;

		$totalVacaciones = ($salariosDevengados / 11);
		$sqlVacacionesPagadas = sprintf("SELECT SUM(en_valor) AS vacaciones_pagadas FROM `tbl_empleados_notificaciones` WHERE `en_emp_id`=%s AND en_tipo='vacaciones' ",
			GetSQLValueString($_POST['emp_id'], "double")
		);
		$rs_sqlVacacionesPagadas = mysqli_query($conexion, $sqlVacacionesPagadas);
		$row_sqlVacacionesPagadas = mysqli_fetch_assoc($rs_sqlVacacionesPagadas);
		$vacacionesPagadas = $row_sqlVacacionesPagadas["vacaciones_pagadas"];
		$vacacionesPendientes = ( $totalVacaciones - $vacacionesPagadas );
		$result["vacacionesPendientesDias"] = $diasTotalVacaciones;
		$result["vacacionesPendientes"] = round( (($salarioMensual/30)*$diasTotalVacaciones) ,2);

		//Xiii 
		$fechaCalculo = $fecha_liquidar;
		list($yearSim, $monthSim, $daySim) = explode("-", $fechaCalculo);
		$mesEntero = (int)$monthSim;
		$diaEntero = (int)$daySim;


		$xiiiFechaTermina = $fechaCalculo;
		if(
			($mesEntero==12 && $diaEntero >= 16) || 
			($mesEntero>=1 && $mesEntero<=3) || 
			($mesEntero==4 && $diaEntero <= 15)
		){
			if ($mesEntero==12 && $diaEntero >= 16) {
				$xiiiFechaInicia = $yearSim."-12-15";
				$xiiiFechaIniciaConsulta = $yearSim."-12-16";
			}else{
				$xiiiFechaInicia = ($yearSim-1)."-12-15";
				$xiiiFechaIniciaConsulta = ($yearSim-1)."-12-16";
			}
		}elseif(
			($mesEntero==4 && $diaEntero >= 16) || 
			($mesEntero>=5 && $mesEntero<=7) || 
			($mesEntero==8 && $diaEntero <= 15)
		){
			$xiiiFechaInicia = $yearSim."-04-15";
			$xiiiFechaIniciaConsulta = $yearSim."-04-16";
		}elseif(
			($mesEntero==8 && $diaEntero >= 16) || 
			($mesEntero>=9 && $mesEntero<=11) || 
			($mesEntero==12 && $diaEntero <= 15)
		){
			$xiiiFechaInicia = $yearSim."-08-15";
			$xiiiFechaIniciaConsulta = $yearSim."-08-16";
		}

		// 2014-09-15 
		// $xiiiFechaInicia = 2124-08-16

		$result['xiiiFechaInicia'] = $xiiiFechaInicia;
		$result['xiiiFechaRelacion'] = $row_sqlEmpleado["emp_cond_fecha_relacion"];

		if( strtotime($xiiiFechaInicia) < strtotime($row_sqlEmpleado["emp_cond_fecha_relacion"]) ) {
			$xiiiFechaInicia = $row_sqlEmpleado["emp_cond_fecha_relacion"];
			//Calcular Meses del invervalo
			$fechaXiiiI = new DateTime($xiiiFechaInicia);
			$fechaXiiiF = new DateTime($xiiiFechaTermina);
			$diferenciaXiii = $fechaXiiiI->diff($fechaXiiiF);
			$meses = ( $diferenciaXiii->d / 30 ) + $diferenciaXiii->m;
		}elseif( strtotime($xiiiFechaInicia) < strtotime($fecha_liquidar) && !(($monthSim == 4 || $monthSim == 8 || $monthSim == 12) && $daySim == 15 ) ){
			$fechaXiiiI = new DateTime($xiiiFechaInicia);
			$fechaXiiiF = new DateTime($xiiiFechaTermina);
			$diferenciaXiii = $fechaXiiiI->diff($fechaXiiiF);
			$meses = ( $diferenciaXiii->d / 30 ) + $diferenciaXiii->m;
		}else{
			$meses = 4;
		}

		$result["meses"] = $meses;
		$result["xiiiPendientes"] = round(($salarioMensual*$meses/12),2);

		$sqlXiii = sprintf("SELECT * 
								FROM `tbl_empleados_notificaciones`
								WHERE 
									en_emp_id=%s AND 
									(
										(en_tipo='salario' AND en_xiii>0) OR 
										en_tipo='xiii'
									)
							 		AND en_fecha>=%s  AND en_fecha<=%s ",
			GetSQLValueString($_POST['emp_id'], "double"),
			GetSQLValueString($xiiiFechaIniciaConsulta, "date"),
			GetSQLValueString($xiiiFechaTermina, "date")
		);

		$rs_sqlXiii = mysqli_query($conexion, $sqlXiii);
		while( $row_sqlXiii = mysqli_fetch_assoc($rs_sqlXiii) ){
			if( $row_sqlXiii['en_xiii'] && $row_sqlXiii['en_tipo'] == 'salario' ){
				$result["xiiiPendientes"] = 0;
			}elseif($row_sqlXiii['en_valor'] && $row_sqlXiii['en_tipo'] == 'xiii'){
				$result["xiiiPendientes"] = 0;
			}
		}


		if ($_POST['terminacion']['id'] == 1 || $_POST['terminacion']['id'] == 2  || $_POST['terminacion']['id'] == 4 ) {
			$result["preaviso"] = -1;
		}else{
			$result["preaviso"] = round($salarioMensual,2);
		}

		if ( $row_sqlEmpleado["emp_contrato"] == 'indefinido' && $_POST['terminacion']['id']== 5 ){
			$result['error'] = 1;	
		}

		//Definido
		if ( ($_POST['terminacion']['id'] == 1 || $_POST['terminacion']['id'] == 2) &&  $row_sqlEmpleado["emp_contrato"] == 'definido' ) {
			
		}else if (  $row_sqlEmpleado["emp_contrato"] == 'definido' ) {
			$mesesFaltantes = 0;
			$fechainicial = new DateTime($row_sqlEmpleado["emp_cond_fecha_relacion"]);
			$fechafinal = new DateTime($fechaSimulacion);
			$diferencia = $fechainicial->diff($fechafinal);

			if ( $diferencia->y >=1 ){
				//Indemnización nada
			}elseif( $diferencia->y == 0 && $diferencia->m < 12 ) {
				//Indemnización
				$mesesFaltantes = $diferencia->m + ( $diferencia->d / 30 );
			}
		}

		$sqlFechaLiquidar = sprintf("SELECT COUNT(*) AS cont_noti FROM `tbl_empleados_notificaciones` WHERE `en_emp_id`=%s AND en_fecha>%s ",
			GetSQLValueString($_POST['emp_id'], "double"),
			GetSQLValueString($fecha_liquidar, "date")
		);
		$rs_sqlFechaLiquidar = mysqli_query($conexion, $sqlFechaLiquidar);
		$row_sqlFechaLiquidar = mysqli_fetch_assoc($rs_sqlFechaLiquidar);
		if ($row_sqlFechaLiquidar["cont_noti"] > 0) {
			$result['error'] = 2;
		}

		//Error, no se puede registrar vacaciones con mas de un mes de anticipación
		$fechaMasUnMes = new DateTime($fecha_liquidar);
		$fechaMasUnMes->modify("-1 months");
		$fechaUnMes = strtotime($fechaMasUnMes->format('Y-m-d'));
		$fechaComparacion = strtotime($fechaSimulacion);
		if ($fechaUnMes > $fechaComparacion) {
			$error = true;
			$result["error"] = 3;
		}

		$result['termino'] = utf8_encode($row_sqlEmpleado["emp_contrato"]);
		$result['fecha_liquidar'] = $fecha_liquidar;

	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>