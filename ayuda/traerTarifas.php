<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$sqlTarifas =  sprintf("SELECT * FROM tbl_stripe_plans WHERE spl_estado=1 ORDER BY spl_max ASC");
	$rs_sqlTarifas = mysqli_query($_conection->connect(), $sqlTarifas);
	$tarifas = array();
	$i = 0;
	while( $row_sqlTarifas = mysqli_fetch_assoc($rs_sqlTarifas) ){
		$tarifa["id"] = $i;
		$tarifa["idT"] = utf8_encode($row_sqlTarifas["spl_id"]);
		$tarifa["minimo"] = utf8_encode($row_sqlTarifas["spl_min"]);
		$tarifa["maximo"] = utf8_encode($row_sqlTarifas["spl_max"]);

		switch (utf8_encode($row_sqlTarifas["spl_interval"])) {
			case 'day':
				$interval = 'Diario';
				break;
			case 'week':
				$interval = 'Semanal';
				break;
			case 'month':
				$interval = 'Mensual';
				break;
			case 'year':
				$interval = 'Anual';
				break;
			default:
				$interval = 'Diario';
				break;
		}

		$tarifa["interval"] = $interval;
		$precio = number_format($row_sqlTarifas["spl_amount"]/100,2);

		$value = $select = '';
		if ( $tarifa["minimo"] == $tarifa["maximo"] && $tarifa["maximo"] == 1 ) {
			$value = $tarifa["maximo"]. ' Empleado ';
		}elseif ( $tarifa["minimo"] == $tarifa["maximo"] ) {
			$value = $tarifa["maximo"] . ' Empleados ';
		}else{
			$value = $tarifa["minimo"] .' a '. $tarifa["maximo"] .	' Empleados ';
		}

		$tarifa["value"] = $value;
		$tarifa["select"] = $value . '($'.$precio.'/'.$interval.')';
		$tarifa["mensual"] = $precio;
		$tarifa["stripeId"] = utf8_encode($row_sqlTarifas["spl_stripe_id"]);
		$tarifa["idApple"] = 'a_'.str_replace("-","_",$row_sqlTarifas["spl_stripe_id"]);
		$tarifa["idApple2"] = 'a2_'.str_replace("-","_",$row_sqlTarifas["spl_stripe_id"]);
		$tarifa["idAndroid2"] = str_replace("-","_",$row_sqlTarifas["spl_stripe_id"]);
		// $tarifa["anual"] = utf8_encode($row_sqlTarifas["ttar_anual"]);
		array_push($tarifas, $tarifa);
		$i++;
	}
	$result['tarifas'] = $tarifas;

	$response->result = $result;
	echo json_encode($response);
?>
