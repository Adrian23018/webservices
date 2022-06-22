<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	//error_reporting(E_ALL);

	$_POST = json_decode(file_get_contents('php://input'), true);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {

		$sqlVariables = sprintf("SELECT * FROM tbl_variables_panama");
		$rs_sqlVariables = mysqli_query($_conection->connect(), $sqlVariables);
		$row_sqlVariables = mysqli_fetch_assoc($rs_sqlVariables);

		$array_dias['Sunday'] = "Domingo";
		$array_dias['Monday'] = "Lunes";
		$array_dias['Tuesday'] = "Martes";
		$array_dias['Wednesday'] = "Miércoles";
		$array_dias['Thursday'] = "Jueves";
		$array_dias['Friday'] = "Viernes";
		$array_dias['Saturday'] = "Sábado";

		$periodo['dia'] = 'todos los dias';
		$periodo['semanal'] = 'dia en que termine el día laboral de la semana';
		$periodo['quincenal'] = array(14,29,27);
		$periodo['mensual'] = array(29,27);

		$salario['termino'] = array( 'hora', 'dia', 'mes' );
		$salario['salario'] =  'el que han declarado';

		$sqlNotificaciones =  sprintf("SELECT * FROM tbl_empleados_notificaciones 
										INNER JOIN tbl_empleados ON emp_id=en_emp_id 
										WHERE en_usu_id=%s AND en_estado=1 ORDER BY en_id DESC   
										LIMIT %s,%s",
			GetSQLValueString($id, "double"),
			GetSQLValueString($_POST["offset"], "int"),
			GetSQLValueString($_POST["limit"], "int")
		);
		$rs_sqlNotificaciones = mysqli_query($_conection->connect(), $sqlNotificaciones);
		$arrayNotificaciones = array();
		$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);
		list($yearHoy, $mesHoy, $diaHoy) = explode('-',$fechaSimulacion);

		while( $row_sqlNotificaciones = mysqli_fetch_assoc($rs_sqlNotificaciones) ){
			$en_id = utf8_encode($row_sqlNotificaciones['en_id']);
			$emp_id = utf8_encode($row_sqlNotificaciones['emp_id']);
			$en_periodo = utf8_encode($row_sqlNotificaciones['en_periodo']);
			$emp_cond_jornada = utf8_encode($row_sqlNotificaciones['emp_cond_jornada']);
			$emp_cond_semanas = utf8_encode($row_sqlNotificaciones['emp_cond_semanas']);
			$emp_cond_periodo = utf8_encode($row_sqlNotificaciones['emp_cond_periodo']);
			$emp_cond_termino = utf8_encode($row_sqlNotificaciones['emp_cond_termino']);
			$emp_cond_sueldo = utf8_encode($row_sqlNotificaciones['emp_cond_sueldo']);
			$emp_perfil_nombre = utf8_encode($row_sqlNotificaciones['emp_perfil_nombre']);
			$emp_fecha_relacion = utf8_encode($row_sqlNotificaciones['emp_cond_fecha_relacion']);
			$emp_do_nombre = utf8_encode($row_sqlNotificaciones['emp_do_nombre']);
			$emp_imagen = '';
			
			if ($row_sqlNotificaciones["emp_imagen"]) {
				$emp_imagen = $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/empleados/".$row_sqlNotificaciones['emp_id']."/".$row_sqlNotificaciones["emp_imagen"];
			}

			$en_xiii = '';
			if ($row_sqlNotificaciones['en_tipo'] == 'salario' && $row_sqlNotificaciones['en_xiii']>0 ) {
				$en_xiii = ' y Xiii';
			}

			list($yearR, $mesR, $diaR) = explode('-',$emp_fecha_relacion);
			if ( (int)$mesR == 1 ){
				$mesAntes = 12;
				$mes2Antes = 11;
				$yearVacaciones = (int)$yearHoy + 1;
			}elseif ( (int)$mesR == 2 ){
				$mesAntes = 1;
				$mes2Antes = 12;
				$yearVacaciones = (int)$yearHoy + 1;
			}else{
				$mesAntes = $mesR-1;
				$mes2Antes = $mesR-2;
				$yearVacaciones = (int)$yearHoy;
			}
            
            $vacacionesFecha = '';
			if ( $mesHoy == $mesAntes || $mesHoy == $mes2Antes ) {
				// $vacaciones = true;
				$vacacionesFecha = $array_dias[date('l', strtotime($yearVacaciones.'-'.$mesR.'-'.$diaR))] . " " . $diaR . " de " . $arrayMesesGlobal[(int)$mesR];
			}

			list($anho, $mes, $dia) = explode("-", $row_sqlNotificaciones['en_fecha']);
			$fecha = $array_dias[date('l', strtotime($row_sqlNotificaciones['en_fecha']))] . " " . $dia . " de " . $arrayMesesGlobal[(int)$mes] . " del " . $anho;

			if ($row_sqlNotificaciones['en_tipo'] == 'vacaciones') {
				if ( $row_sqlNotificaciones["en_fecha_empieza"] == $row_sqlNotificaciones["en_fecha"] ) {
					list($year, $month, $day) = explode("-", $row_sqlNotificaciones["en_fecha_empieza"]);
					$fecha = $day . " de " . $arrayMesesGlobal[(int)$month] . " de " . $year;
				}else{
					list($yearE, $monthE, $dayE) = explode("-", $row_sqlNotificaciones["en_fecha_empieza"]);
					list($yearT, $monthT, $dayT) = explode("-", $row_sqlNotificaciones["en_fecha"]);
					if ( $monthE == $monthT ) {
						$fecha = $dayE . " al " . $dayT . " de " . $arrayMesesGlobal[(int)$monthT] . " de " . $yearT;
					}elseif ( $yearE == $yearT ) {
						$fecha = $dayE . " de " . $arrayMesesGlobal[(int)$monthE] . " al " . $dayT . " de " . $arrayMesesGlobal[(int)$monthT] . " de " . $yearT;
					}else{
						$fecha = $dayE . " de " . $arrayMesesGlobal[(int)$monthE] . " de " . $yearE . " al " . $dayT . " de " . $arrayMesesGlobal[(int)$monthT] . " de " . $yearT;
					}
				}
			}

			$arrayNotificacion = array(
									'tipo' => utf8_encode($row_sqlNotificaciones['en_tipo']),
									'fecha' => $fecha,
									'en_id' => $en_id,
									'en_periodo' => $en_periodo,
									'en_xiii' => $en_xiii,
									'xiii' => utf8_encode($row_sqlNotificaciones['en_xiii']),
									'xiii_ss' => utf8_encode($row_sqlNotificaciones['en_xiii_ss']),
									'xiii_total' => utf8_encode($row_sqlNotificaciones['en_xiii_total']),
									'emp_id' => $emp_id,
									'emp_nombre' => $emp_do_nombre,
									'emp_perfil' => $emp_perfil_nombre,
									'emp_periodo' => $emp_cond_periodo,
									'vacaciones' => false,
									'vacaciones_fecha' => $vacacionesFecha,
									'fecha_simulacion' => $fechaSimulacion,
									'emp_fecha_relacion' => $emp_fecha_relacion,
									'emp_imagen' => $emp_imagen
								);

			// var_dump($arrayNotificacion);
			array_push($arrayNotificaciones, $arrayNotificacion);
		}

		$result["notificaciones"] = $arrayNotificaciones;
		$result["success"] = true;

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>
