<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

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

		$sqlSaldos =  sprintf("SELECT *, SUM(en_valor) AS valor FROM tbl_empleados_notificaciones 
										INNER JOIN tbl_empleados ON emp_id=en_emp_id 
										WHERE en_usu_id=%s AND en_estado=1 GROUP BY en_emp_id",
			GetSQLValueString($id, "double")
		);
		$rs_sqlSaldos = mysqli_query($_conection->connect(), $sqlSaldos);
		$arraySaldos = array();
		$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);
		list($yearHoy, $mesHoy, $diaHoy) = explode('-',$fechaSimulacion);

		while( $row_sqlSaldos = mysqli_fetch_assoc($rs_sqlSaldos) ){
			$en_id = $row_sqlSaldos['en_id'];
			$emp_id = $row_sqlSaldos['emp_id'];
			$valor = $row_sqlSaldos['valor'];
			$emp_do_nombre = utf8_encode($row_sqlSaldos['emp_do_nombre']);

			$total += $valor;

			$arraySaldo = array(
									'nombre' => $emp_do_nombre,
									'valor' => round($valor,2)
								);
			array_push($arraySaldos, $arraySaldo);
		}

		$result["saldos"] = $arraySaldos;
		$result["total"] = round($total,2);
		$result["success"] = true;

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>
