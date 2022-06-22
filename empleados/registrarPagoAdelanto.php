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
		//	Condiciones de trabajo usada.
		$emp_id = $_POST["emp_id"];
		$firma = $_POST['firma'];
		$fechaSimulacion = simuladorTiempo($fechaActual, $fechaReferencia);

		// Crear PDF sin Firma
		if ($firma) {
			$firmaEmp = $_POST['imagenGuardar'];
		}

		$sqlEmpleado = sprintf("SELECT * FROM tbl_empleados WHERE emp_id=%s",
			GetSQLValueString($emp_id, "double")
		);
		$rs_sqlEmpleado = mysqli_query($_conection->connect(), $sqlEmpleado);
		$row_sqlEmpleado = mysqli_fetch_assoc($rs_sqlEmpleado);

		//Revisar si existe el adelanto
		$sqlAdel = sprintf("SELECT * FROM tbl_adelantos WHERE ad_tipo=%s AND ad_emp_id=%s",
			GetSQLValueString($_POST['adelanto']['id'], "int"),
			GetSQLValueString($emp_id, "double")
		);
		$rs_sqlAdel = mysqli_query($_conection->connect(), $sqlAdel);
		$row_sqlAdel = mysqli_fetch_assoc($rs_sqlAdel);

		$monto_anterior = $row_sqlAdel["ad_monto"];

		if ($_POST['monto_pago'] <= 0) {
			$result['error'] = 1;
		}

		if ( $_POST['monto_pago'] > $monto_anterior ) {
			$result['error'] = 2;
		}

		if (!$result['error']) {
			$sqlAdelantos = sprintf("UPDATE tbl_adelantos 
										SET ad_monto=ad_monto-%s,ad_fecha=%s 
										WHERE ad_tipo=%s AND ad_emp_id=%s ",
					GetSQLValueString(utf8_decode($_POST['monto_pago']), "double"),
					GetSQLValueString($fechaSimulacion, "date"),
					GetSQLValueString($_POST['adelanto']['id'], "int"),
					GetSQLValueString($emp_id, "double")
			);
			$rs_sqlAdelantos = mysqli_query($conexion, $sqlAdelantos);

			if ( $_POST['monto_pago'] == $monto_anterior ) {
				$sqlAdel1 = sprintf("UPDATE tbl_adelantos 
											SET ad_monto=0,ad_fecha=%s 
											WHERE ad_tipo=%s AND ad_emp_id=%s ",
						GetSQLValueString($fechaSimulacion, "date"),
						GetSQLValueString($_POST['adelanto']['id'], "int"),
						GetSQLValueString($emp_id, "double")
				);
				$rs_sqlAdel1 = mysqli_query($conexion, $sqlAdel1);
			}

			$result["success"] = true;
		}
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>