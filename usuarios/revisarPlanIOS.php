<?php
	// Headers App
	require("../_functions/headers_options.php");

	// session_start();
	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	//require_once 'config.php';

	//$result['success'] = false;

	$_POST = json_decode(file_get_contents('php://input'), true);
	
	$result["success"] = false;
	$result['error'] = false;
	$result['errorEmpleados'] = false;
	$result['cards'] = '';

    list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
        // $consultaApple = ukuGetPlanCustomerApple( $id );
        $planSql = ukuGetPlan($_POST['planId']);
		$spl_min = $planSql['spl_min'];
        $spl_max = $planSql['spl_max'];
        
        // Enviamos informacion de los empleados
        $empleados = array();
		$empleados['creados'] = $empleados['activos'] = $empleados['liquidados'] = 0;

		$sqlEmpleados = sprintf(" SELECT * FROM tbl_empleados WHERE emp_usu_id=%s ",
			GetSQLValueString($id, "text")
		);
		$rs_sqlEmpleados = mysqli_query($_conection->connect(), $sqlEmpleados);
		$arrayActivos = array(1,2);
		while ( $row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados) ) {
			if ( in_array($arrayActivos, $row_sqlEmpleados['emp_estado']) ) {
				$empleados['creados'] += 1;
			}elseif( $row_sqlEmpleados['emp_estado'] == 3 ){
				$empleados['activos'] += 1;
			}else{
				$empleados['liquidados'] += 1;
			}
        }
        
        if( $empleados['activos'] > $spl_max ){
            // Error, no puede suscribirse a un plan con menos empleados
            $result['error'] = 1;
            $result['message_error'] = 'No puede suscribirse a un plan con menos empleados de los que tiene "Activos"';
        }
	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>