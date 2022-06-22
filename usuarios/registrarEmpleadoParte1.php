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
		// $id

		list($dia,$mes,$anho) = explode("/", $_POST['cond_fecha_nacimiento']);
		$_POST['cond_fecha_nacimiento'] = $anho.'-'.$mes.'-'.$dia;
		
		if( $_POST['empleadoFechaNacimiento'] ){
		    list($dia,$mes,$anho) = explode("/", $_POST['empleadoFechaNacimiento']);
		    $_POST['empleadoFechaNacimiento'] = $anho.'-'.$mes.'-'.$dia;    
		}

		$result['post'] = $_POST;
		if (!$_POST['emp_id']) {
			//Revisar si el usuario antes tenía empleados
			$sqlEmpleadoExist = sprintf("SELECT emp_id FROM `tbl_empleados` WHERE emp_usu_id=%s",
				GetSQLValueString($id, "double")
			);
			$rs_sqlEmpleadoExist = mysqli_query($conexion, $sqlEmpleadoExist);
			$row_rs_sqlEmpleadoExist = mysqli_fetch_assoc($rs_rs_sqlEmpleadoExist);
			if (!$row_rs_sqlEmpleadoExist['emp_id']) {
				$sqlUsuarioEdit = sprintf("UPDATE tbl_usuarios 
						SET
							usu_dor_nombre=%s,
							usu_dor_apellido=%s,
							usu_dor_hombre=%s,
							usu_dor_mujer=%s,
							usu_dor_nacionalidad=%s,
							usu_dor_nacionalidad_value=%s,
							usu_dor_no_identidad=%s,
							usu_dor_domicilio=%s
						WHERE
						usu_id=%s
					",
						GetSQLValueString(utf8_decode($_POST['empleadorNombre']), "text"),
						GetSQLValueString(utf8_decode($_POST['empleadorApellido']), "text"),
						GetSQLValueString(utf8_decode($_POST['empleadorHombre']), "text"),
						GetSQLValueString(utf8_decode($_POST['empleadorMujer']), "text"),
						GetSQLValueString(utf8_decode($_POST['empleadorNacionalidadId']), "int"),
						GetSQLValueString(utf8_decode($_POST['empleadorNacionalidadValue']), "text"),
						GetSQLValueString(utf8_decode($_POST['empleadorNo_identidad']), "text"),
						GetSQLValueString(utf8_decode($_POST['empleadorDomicilio']), "text"),
						GetSQLValueString($id,"text")
				);
				$rs_sqlUsuarioEdit = mysqli_query($conexion, $sqlUsuarioEdit);				
			}

			$sqlEmpleado = sprintf("INSERT INTO tbl_empleados 
					(
						emp_usu_id,
						emp_tipo,
						emp_perfil,
						emp_perfil_nombre,
						emp_perfil_otro,
						emp_modalidad,
						emp_modalidad_nombre,
						emp_contrato,
						emp_tipo_definido,
						emp_tipo_definido_value,
						emp_meses,
						emp_meses_value,
						emp_anhos,
						emp_anhos_value,
						emp_dor_nombre,
						emp_dor_apellido,
						emp_dor_hombre,
						emp_dor_mujer,
						emp_dor_nacionalidad,
						emp_dor_nacionalidad_value,
						emp_dor_no_identidad,
						emp_dor_domicilio,
						emp_do_nombre,
						emp_do_hombre,
						emp_do_mujer,
						emp_do_nacionalidad,
						emp_do_nacionalidad_value,
						emp_do_no_identidad,
						emp_do_domicilio,
						emp_do_fechanacimiento,
						emp_do_edad,
						emp_cond_jornada,
						emp_cond_semanas,
						emp_cond_fecha_relacion,
						emp_cond_termino,
						emp_cond_sueldo,
						emp_cond_periodo,
						emp_cond_auxilio,
						emp_cond_generacion,
						emp_estado
					)
					VALUES
					(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
				",
					GetSQLValueString($id, "double"),
					GetSQLValueString(utf8_decode($_POST['empleado']), "text"),
					GetSQLValueString(utf8_decode($_POST['perfil']), "int"),
					GetSQLValueString(utf8_decode($_POST['perfilNombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['perfilOtro']), "text"),
					GetSQLValueString(utf8_decode($_POST['modalidad']), "int"),
					GetSQLValueString(utf8_decode($_POST['modalidadNombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['tipoContrato']), "text"),
					GetSQLValueString(utf8_decode($_POST['terminoId']), "int"),
					GetSQLValueString(utf8_decode($_POST['terminoValue']), "text"),
					GetSQLValueString(utf8_decode($_POST['mesesId']), "int"),
					GetSQLValueString(utf8_decode($_POST['mesesValue']), "text"),
					GetSQLValueString(utf8_decode($_POST['anhosId']), "int"),
					GetSQLValueString(utf8_decode($_POST['anhosValue']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorNombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorApellido']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorHombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorMujer']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorNacionalidadId']), "int"),
					GetSQLValueString(utf8_decode($_POST['empleadorNacionalidadValue']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorNo_identidad']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorDomicilio']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoNombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoHombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoMujer']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoNacionalidadId']), "int"),
					GetSQLValueString(utf8_decode($_POST['empleadoNacionalidadValue']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoNo_identidad']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoDomicilio']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoFechaNacimiento']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoEdad']), "int"),
					GetSQLValueString(utf8_decode($_POST['cond_jornada']), "int"),
					GetSQLValueString(utf8_decode($_POST['cond_semanas']), "text"),
					GetSQLValueString(utf8_decode($_POST['cond_fecha_nacimiento']), "date"),
					GetSQLValueString(utf8_decode($_POST['cond_termino']), "int"),
					GetSQLValueString(utf8_decode($_POST['cond_sueldo']), "double"),
					GetSQLValueString(utf8_decode($_POST['cond_periodo']), "text"),
					GetSQLValueString(utf8_decode($_POST['cond_auxilio']), "double"),
					GetSQLValueString(utf8_decode($_POST['cond_generacion']), "int"),
					GetSQLValueString(1, "int")
			);
			$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
			if ($rs_sqlEmpleado) {
				$result["success"] = true;
				$idRegistro = mysqli_insert_id($conexion);

				$result['emp_id'] = $idRegistro;
				
				// //Insertar Dependientes.
				// $sqlDependiente = sprintf("INSERT INTO tbl_dependientes 
				// 		(
				// 			dp_usu_id,
				// 			dp_emp_id,
				// 			dp_nombres,
				// 			dp_parentesco
				// 		)
				// 		VALUES
				// 		(%s,%s,%s,%s)
				// 	",
				// 		GetSQLValueString($id, "double"),
				// 		GetSQLValueString($idRegistro, "double"),
				// 		GetSQLValueString(utf8_decode($_POST['cond_dependiente']), "text"),
				// 		GetSQLValueString(utf8_decode($_POST['cond_parentesco']), "int")
				// );
				// $rs_sqlDependiente = mysqli_query($conexion, $sqlDependiente);

				//Eliminamos dependientes
				$sqlDepD = sprintf("DELETE FROM `tbl_dependientes` WHERE dp_usu_id=%s AND dp_emp_id=%s",
						GetSQLValueString($id,"double"),
						GetSQLValueString($idRegistro,"double")
				);
				$rs_sqlDepD = mysqli_query($_conection->connect(), $sqlDepD);

				foreach ($_POST['dependientes'] as $key => $dependiente) {
					$sqlDepI = sprintf("INSERT INTO `tbl_dependientes` (`dp_usu_id`, `dp_emp_id`, `dp_nombres`, `dp_parentesco`) VALUES (%s,%s,%s,%s)",
							GetSQLValueString($id,"double"),
							GetSQLValueString($idRegistro,"double"),
							GetSQLValueString(utf8_decode($dependiente['nombre']),"text"),
							GetSQLValueString(utf8_decode($dependiente['parentesco']),"int")
					);
					$rs_sqlDepI = mysqli_query($_conection->connect(), $sqlDepI);
				}

			}
		}else{
			//Editar
			$sqlEmpleado = sprintf("UPDATE tbl_empleados SET 
						emp_tipo=%s,
						emp_perfil=%s,
						emp_perfil_nombre=%s,
						emp_perfil_otro=%s,
						emp_modalidad=%s,
						emp_modalidad_nombre=%s,
						emp_contrato=%s,
						emp_tipo_definido=%s,
						emp_tipo_definido_value=%s,
						emp_meses=%s,
						emp_meses_value=%s,
						emp_anhos=%s,
						emp_anhos_value=%s,
						emp_dor_nombre=%s,
						emp_dor_apellido=%s,
						emp_dor_hombre=%s,
						emp_dor_mujer=%s,
						emp_dor_nacionalidad=%s,
						emp_dor_nacionalidad_value=%s,
						emp_dor_no_identidad=%s,
						emp_dor_domicilio=%s,
						emp_do_nombre=%s,
						emp_do_hombre=%s,
						emp_do_mujer=%s,
						emp_do_nacionalidad=%s,
						emp_do_nacionalidad_value=%s,
						emp_do_no_identidad=%s,
						emp_do_domicilio=%s,
						emp_do_fechanacimiento=%s,
						emp_do_edad=%s,
						emp_cond_jornada=%s,
						emp_cond_semanas=%s,
						emp_cond_fecha_relacion=%s,
						emp_cond_termino=%s,
						emp_cond_sueldo=%s,
						emp_cond_periodo=%s,
						emp_cond_auxilio=%s,
						emp_cond_generacion=%s,
						emp_estado=%s
					WHERE 
						emp_usu_id=%s AND 
						emp_id=%s
				",
					GetSQLValueString(utf8_decode($_POST['empleado']), "text"),
					GetSQLValueString(utf8_decode($_POST['perfil']), "int"),
					GetSQLValueString(utf8_decode($_POST['perfilNombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['perfilOtro']), "text"),
					GetSQLValueString(utf8_decode($_POST['modalidad']), "int"),
					GetSQLValueString(utf8_decode($_POST['modalidadNombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['tipoContrato']), "text"),
					GetSQLValueString(utf8_decode($_POST['terminoId']), "int"),
					GetSQLValueString(utf8_decode($_POST['terminoValue']), "text"),
					GetSQLValueString(utf8_decode($_POST['mesesId']), "int"),
					GetSQLValueString(utf8_decode($_POST['mesesValue']), "text"),
					GetSQLValueString(utf8_decode($_POST['anhosId']), "int"),
					GetSQLValueString(utf8_decode($_POST['anhosValue']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorNombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorApellido']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorHombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorMujer']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorNacionalidadId']), "int"),
					GetSQLValueString(utf8_decode($_POST['empleadorNacionalidadValue']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorNo_identidad']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadorDomicilio']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoNombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoHombre']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoMujer']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoNacionalidadId']), "int"),
					GetSQLValueString(utf8_decode($_POST['empleadoNacionalidadValue']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoNo_identidad']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoDomicilio']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoFechaNacimiento']), "text"),
					GetSQLValueString(utf8_decode($_POST['empleadoEdad']), "int"),
					GetSQLValueString(utf8_decode($_POST['cond_jornada']), "int"),
					GetSQLValueString(utf8_decode($_POST['cond_semanas']), "text"),
					GetSQLValueString(utf8_decode($_POST['cond_fecha_nacimiento']), "date"),
					GetSQLValueString(utf8_decode($_POST['cond_termino']), "int"),
					GetSQLValueString(utf8_decode($_POST['cond_sueldo']), "double"),
					GetSQLValueString(utf8_decode($_POST['cond_periodo']), "text"),
					GetSQLValueString(utf8_decode($_POST['cond_auxilio']), "double"),
					GetSQLValueString(utf8_decode($_POST['cond_generacion']), "int"),
					GetSQLValueString(1, "int"),
					GetSQLValueString($id, "double"),
					GetSQLValueString($_POST["emp_id"], "double")
			);
			$rs_sqlEmpleado = mysqli_query($conexion, $sqlEmpleado);
			if ($rs_sqlEmpleado) {
				$result["success"] = true;
				$result["emp_id"] = $_POST["emp_id"];

				//Eliminamos dependientes
				$sqlDepD = sprintf("DELETE FROM `tbl_dependientes` WHERE dp_usu_id=%s AND dp_emp_id=%s",
						GetSQLValueString($id,"double"),
						GetSQLValueString($_POST["emp_id"],"double")
				);
				$rs_sqlDepD = mysqli_query($_conection->connect(), $sqlDepD);

				foreach ($_POST['dependientes'] as $key => $dependiente) {
					$sqlDepI = sprintf("INSERT INTO `tbl_dependientes` (`dp_usu_id`, `dp_emp_id`, `dp_nombres`, `dp_parentesco`) VALUES (%s,%s,%s,%s)",
							GetSQLValueString($id,"double"),
							GetSQLValueString($_POST["emp_id"],"double"),
							GetSQLValueString(utf8_decode($dependiente['nombre']),"text"),
							GetSQLValueString(utf8_decode($dependiente['parentesco']),"int")
					);
					$rs_sqlDepI = mysqli_query($_conection->connect(), $sqlDepI);
				}

				// //Insertar Dependientes.
				// $sqlDependiente = sprintf("INSERT INTO tbl_dependientes 
				// 		(
				// 			dp_usu_id,
				// 			dp_emp_id,
				// 			dp_nombres,
				// 			dp_parentesco
				// 		)
				// 		VALUES
				// 		(%s,%s,%s,%s)
				// 	",
				// 		GetSQLValueString($id, "double"),
				// 		GetSQLValueString($_POST["emp_id"], "double"),
				// 		GetSQLValueString(utf8_decode($_POST['cond_dependiente']), "text"),
				// 		GetSQLValueString(utf8_decode($_POST['cond_parentesco']), "int")
				// );
				// $rs_sqlDependiente = mysqli_query($conexion, $sqlDependiente);
			}
		}
	}else{
		$result['error'] = -100;
	}


	$response->result = $result;
	echo json_encode($response);
?>