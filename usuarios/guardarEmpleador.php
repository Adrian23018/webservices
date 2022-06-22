<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
    $result['success'] = false;

	if ($validacion) {
		// $id

		if ($_POST['empleadorHombre'] == "true") {
			$sexo = 1;
		}elseif ($_POST['empleadorMujer'] == "true") {
			$sexo = 2;
		}

		$sqlUsuarioEdit = sprintf("UPDATE tbl_usuarios 
				SET
					usu_nombres=%s,
					usu_apellidos=%s,
					usu_sexo=%s,
					usu_pais=%s,
					usu_no_identidad=%s,
					usu_domicilio=%s
				WHERE
				usu_id=%s
			",
				GetSQLValueString(utf8_decode($_POST['empleadorNombre']), "text"),
				GetSQLValueString(utf8_decode($_POST['empleadorApellido']), "text"),
				GetSQLValueString(utf8_decode($sexo), "int"),
				GetSQLValueString(utf8_decode($_POST['empleadorNacionalidadId']), "int"),
				GetSQLValueString(utf8_decode($_POST['empleadorNo_identidad']), "text"),
				GetSQLValueString(utf8_decode($_POST['empleadorDomicilio']), "text"),
				GetSQLValueString($id,"double")
		);
		$rs_sqlUsuarioEdit = mysqli_query($conexion, $sqlUsuarioEdit);

		if ($_POST['emp_id']) {
			$sqlEmpleadosEdit = sprintf("UPDATE tbl_empleados 
					SET
						emp_dor_nombre=%s,
						emp_dor_apellido=%s,
						emp_dor_hombre=%s,
						emp_dor_mujer=%s,
						emp_dor_nacionalidad=%s,
						emp_dor_nacionalidad_value=%s,
						emp_dor_no_identidad=%s,
						emp_dor_domicilio=%s
					WHERE
					emp_id=%s
				",
					GetSQLValueString(utf8_decode($_POST['empleadorNombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorApellido']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorHombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorMujer']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorNacionalidadId']), "int"),
					GetSQLValueString(utf8_decode($_POST['empleadorNacionalidadValue']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorNo_identidad']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorDomicilio']), "text"),
					GetSQLValueString($_POST['emp_id'],"double")
			);
			$rs_sqlEmpleadosEdit = mysqli_query($conexion, $sqlEmpleadosEdit);
		}

        if( $rs_sqlUsuarioEdit || $rs_sqlEmpleadosEdit ){
            $result['success'] = true;
        }

	}else{
		$result['error'] = -100;
	}

   echo ""
	$response->result = $result;
	echo json_encode($response);
?>