<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);
	$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);

	list($year, $month, $day) = explode("-", $fechaSimulacion);

	$desde = $year.'-'.$month.'-01';
	$hasta = $year.'-'.$month.'-'.date("t",mktime(0,0,0,$month,1,$year));

	// echo $desde . '  -  ' .$hasta;
	// if ($_POST['']) {
	// 	# code...
	// }

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		if (!$error) {

			if ( $_POST["fecha_desde"] || $_POST["fecha_hasta"] ) {
				$desde = $_POST["fecha_desde"];
				$hasta = $_POST["fecha_hasta"];

				if ( $desde ) {
					list($dayDesde, $monthDesde, $yearDesde) = explode("/", $_POST["fecha_desde"]);
					$fechaSQL = sprintf(" en_fecha>=%s AND ",
						GetSQLValueString($yearDesde.'-'.$monthDesde.'-'.$dayDesde,"date")
					);
				}

				if ( $hasta ) {
					list($dayHasta, $monthHasta, $yearHasta) = explode("/", $_POST["fecha_hasta"]);
					$fechaSQL .= sprintf(" en_fecha<=%s AND ",
						GetSQLValueString($yearHasta.'-'.$monthHasta.'-'.$dayHasta,"date")
					);
				}

			}else{
				$fechaSQL = sprintf(" en_fecha>=%s AND en_fecha<=%s AND ",
					GetSQLValueString($desde,"date"),
					GetSQLValueString($hasta,"date")
				);
			}

			if ( $_POST['tipopago']['id'] ) {
				if ( $_POST['tipopago']['id'] == 2) {
					$tipoSQL = sprintf(" en_tipo='salario' AND ");
				}elseif ( $_POST['tipopago']['id'] == 3) {
					$tipoSQL = sprintf(" en_tipo='vacaciones' AND ");
				}elseif ( $_POST['tipopago']['id'] == 4) {
					$tipoSQL = sprintf(" (en_tipo='xiii' OR ( en_tipo='salario' AND en_xiii > 0)) AND ");
				}
			}

			//Empleado
			$sqlEmpleado = sprintf("SELECT * FROM tbl_empleados WHERE emp_id=%s",
				GetSQLValueString($_POST["emp_id"],"double")
			);
			$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
			$row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado);

			
			$sqlEmpNot = sprintf("SELECT * FROM tbl_empleados_notificaciones WHERE %s %s en_emp_id=%s",
				$fechaSQL,
				$tipoSQL,
				GetSQLValueString($_POST["emp_id"],"double")
			);
			// echo $sqlEmpNot;
			$rs_sqlEmpNot = mysqli_query($conexion, $sqlEmpNot);
			$historial = array();

			while( $row_sqlEmpNot = mysqli_fetch_assoc($rs_sqlEmpNot) ){
				unset($item);

				if ($row_sqlEmpNot["en_tipo"] == 'salario') {
					$item['tipo'] = 'Pago de salario';
				}elseif ($row_sqlEmpNot["en_tipo"] == 'vacaciones') {
					$item['tipo'] = 'Pago de vacaciones';
				}elseif ($row_sqlEmpNot["en_tipo"] == 'xiii') {
					$item['tipo'] = 'Pago de Xiii';
				}

				if ($row_sqlEmpNot["en_xiii"]>0) {
					$item['tipo'] .= ' y Xiii';
				}

				$item['fecha'] = utf8_encode($row_sqlEmpNot["en_fecha"]);
				list($yearM, $monthM, $dayM) = explode("-", $item['fecha']);
				$item['fecha_formato'] = $dayM.'/'.$arrayMesesGlobalAb[(int)$monthM].'/'.$yearM;

				$item['total'] = utf8_encode($row_sqlEmpNot["en_valor"] + $row_sqlEmpNot["en_xiii"]);
				$item['total_precio'] = '$'.number_format($item['total'],2);

				if ($row_sqlEmpNot["en_tipo"] == 'xiii') {
					$item['total'] = utf8_encode($row_sqlEmpNot["en_valor"]);
					$item['total_precio'] = '$'.number_format($item['total'],2);
				}

				array_push($historial, $item);
			}
			$result["success"] = true;
			$result["historial"] = $historial;
		}
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>