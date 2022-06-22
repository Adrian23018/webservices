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

		//Revisar si existe el prestamo
		$sqlPrest = sprintf("SELECT * FROM tbl_prestamos WHERE pr_emp_id=%s",
			GetSQLValueString($emp_id, "double")
		);
		$rs_sqlPrest = mysqli_query($_conection->connect(), $sqlPrest);
		$row_sqlPrest = mysqli_fetch_assoc($rs_sqlPrest);

		$monto_anterior = $row_sqlPrest["pr_monto"];

		if ($_POST['monto_pago'] <= 0) {
			$result['error'] = 1;
		}

		if ( $_POST['monto_pago'] > $monto_anterior ) {
			$result['error'] = 2;
		}

		if (!$result['error']) {
			$sqlPrestamos = sprintf("UPDATE tbl_prestamos 
										SET pr_monto=pr_monto-%s,pr_fecha=%s 
										WHERE pr_emp_id=%s ",
					GetSQLValueString(utf8_decode($_POST['monto_pago']), "double"),
					GetSQLValueString($fechaSimulacion, "date"),
					GetSQLValueString($emp_id, "double")
			);
			$rs_sqlPrestamos = mysqli_query($conexion, $sqlPrestamos);

			if ( $_POST['monto_pago'] == $monto_anterior ) {
				$sqlPrest1 = sprintf("UPDATE tbl_prestamos 
											SET pr_monto=0,pr_cuotas=0,pr_fecha=%s 
											WHERE pr_emp_id=%s ",
						GetSQLValueString($fechaSimulacion, "date"),
						GetSQLValueString($emp_id, "double")
				);
				$rs_sqlPrest1 = mysqli_query($conexion, $sqlPrest1);
			}else{
				$sqlPrest2 = sprintf("UPDATE tbl_prestamos 
											SET pr_cuotas=pr_cuotas-1,pr_fecha=%s 
											WHERE pr_emp_id=%s ",
						GetSQLValueString($fechaSimulacion, "date"),
						GetSQLValueString($emp_id, "double")
				);
				$rs_sqlPrest2 = mysqli_query($conexion, $sqlPrest2);
			}

			$result["success"] = true;
		}
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>