<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);

	$patronTexto = "/^[A-Z][a-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ ]+$/i";
	$patronTextoNumeros = "/^[A-Za-zñÑáéíóúÁÉÍÓÚÄËÏÖÜäëïöüàèìòùÀÈÌÔÙ0-9#_\- ]+$/i";
	$patronNumeros = "/^[0-9]+$/i";

	$error = false;
	foreach ($_POST['dependientes'] as $key => $dependiente) {
		if (!preg_match( $patronTexto, trim($dependiente['nombre']) )) {
			$error=true;
			$result["error"] = 1;
		}
	}

	// if (!$_POST['fecha_cambiojornada']) {
	// 	$error=true;
	// 	$result["error"] = 2;
	// }

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		$fechaSimulacion = simuladorTiempo($fechaActual, $fechaReferencia);

		//Condiciones
		list($yearHoy, $monthHoy, $dayHoy) = explode("-", $fechaSimulacion);
		$fechaInicio = $yearHoy."-".$monthHoy."-01";
		$fechaFinal = $yearHoy."-".$monthHoy."-31";
		
		if ((int)$monthHoy==12) {
			$yearHoy+=1;
			$fechaProxima = $yearHoy."-01-01";
		}else{
			$monthHoy = (int)$monthHoy+1;
			if($monthHoy<10){
				$monthHoy = "0".$monthHoy;
			}
			$fechaProxima = $yearHoy."-".$monthHoy."-01";
		}

		$sqlCondiciones =  sprintf("SELECT `es_id` FROM tbl_empleados_cambiocondiciones 
									WHERE es_cond_fecha_relacion>=%s AND es_cond_fecha_relacion<=%s AND es_usu_id=%s AND es_emp_id=%s",
			GetSQLValueString($fechaInicio, "date"),
			GetSQLValueString($fechaFinal, "date"),
			GetSQLValueString($id, "double"),
			GetSQLValueString($_POST['emp_id'], "double")
		);
		$rs_sqlCondiciones = mysqli_query($_conection->connect(), $sqlCondiciones);
		$row_sqlCondiciones = mysqli_fetch_assoc($rs_sqlCondiciones);
		//Termina Condiciones

		if (!$error) {
			if ($row_sqlCondiciones["es_id"]) {
				$sqlEmpleado = sprintf("UPDATE tbl_empleados_cambiocondiciones SET 
								es_cond_jornada=%s, es_cond_semanas=%s, es_cond_termino=%s, es_cond_sueldo=%s, es_cond_periodo=%s, es_cond_fecha_relacion=%s, es_cond_fecha_proxima=%s WHERE es_id=%s",
						GetSQLValueString(utf8_decode($_POST['jornada']['id']), "int"),
						GetSQLValueString(utf8_decode($_POST['semanas']), "text"),
						GetSQLValueString(utf8_decode($_POST['termino']['id']), "int"),
						GetSQLValueString(utf8_decode($_POST['sueldo']), "double"),
						GetSQLValueString(utf8_decode($_POST['periodo']['id']), "text"),
						GetSQLValueString($fechaSimulacion, "date"),
						GetSQLValueString($fechaProxima, "date"),
						GetSQLValueString($row_sqlCondiciones["es_id"], "double")
				);
				$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
			}else{
				$sqlEmpleado = sprintf("INSERT INTO tbl_empleados_cambiocondiciones 
								(es_cond_jornada, es_cond_semanas, es_cond_termino, es_cond_sueldo, es_cond_periodo, es_cond_fecha_relacion, es_cond_fecha_proxima, es_usu_id, es_emp_id) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)",
						GetSQLValueString(utf8_decode($_POST['jornada']['id']), "int"),
						GetSQLValueString(utf8_decode($_POST['semanas']), "text"),
						GetSQLValueString(utf8_decode($_POST['termino']['id']), "int"),
						GetSQLValueString(utf8_decode($_POST['sueldo']), "double"),
						GetSQLValueString(utf8_decode($_POST['periodo']['id']), "text"),
						GetSQLValueString($fechaSimulacion, "date"),
						GetSQLValueString($fechaProxima, "date"),
						GetSQLValueString($id, "double"),
						GetSQLValueString($_POST["emp_id"], "double")
				);
				$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
			}

			if ($rs_sqlEmpleado) {
				//Eliminamos dependientes
				$sqlDepD = sprintf("DELETE FROM `tbl_dependientes` WHERE dp_usu_id=%s AND dp_emp_id=%s",
						GetSQLValueString($id,"double"),
						GetSQLValueString($_POST['emp_id'],"double")
				);
				$rs_sqlDepD = mysqli_query($_conection->connect(), $sqlDepD);

				foreach ($_POST['dependientes'] as $key => $dependiente) {
					$sqlDepI = sprintf("INSERT INTO `tbl_dependientes` (`dp_usu_id`, `dp_emp_id`, `dp_nombres`, `dp_parentesco`) VALUES (%s,%s,%s,%s)",
							GetSQLValueString($id,"double"),
							GetSQLValueString($_POST['emp_id'],"double"),
							GetSQLValueString(utf8_decode($dependiente['nombre']),"text"),
							GetSQLValueString(utf8_decode($dependiente['parentesco']),"int")
					);
					$rs_sqlDepI = mysqli_query($_conection->connect(), $sqlDepI);
				}

				$result["emp_cond_jornada"] = $_POST['jornada']['id'];
				$result["emp_cond_semanas"] = $_POST['semanas'];
				$result["emp_cond_termino"] = $_POST['termino']['id'];
				$result["emp_cond_sueldo"] = $_POST['sueldo'];
				$result["emp_cond_periodo"] = $_POST['periodo']['id'];

				//Dependientes
				$dependientes = array();
				$sqlDependientes =  sprintf("SELECT `dp_emp_id`, `dp_nombres`, `dp_parentesco`
										 FROM tbl_dependientes WHERE dp_emp_id=%s",
					GetSQLValueString($_POST['emp_id'], "double")
				);
				$rs_sqlDependientes = mysqli_query($_conection->connect(), $sqlDependientes);
				while( $row_sqlDependientes = mysqli_fetch_assoc($rs_sqlDependientes) ){
					$dependiente["nombre"] = utf8_encode($row_sqlDependientes['dp_nombres']);
					$dependiente["parentesco"] = utf8_encode($row_sqlDependientes['dp_parentesco']);
					$dependiente["value"] = $arrayParentesco[$row_sqlDependientes['dp_parentesco']-1];
					array_push($dependientes, $dependiente);
				}
				$result['emp_dependientes'] = $dependientes;

				$result["success"] = true;
			}
		}
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>