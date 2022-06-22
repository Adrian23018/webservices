<?php
	// Headers App
	require("../_functions/headers_options.php");

	require("../../admin_uku/includes/autoloader.php");
	$pathFile = '../../imagenes-contenidos/';
	date_default_timezone_set('America/Bogota');
	$conexion = $_conection->connect();

	$_POST = json_decode(file_get_contents('php://input'), true);
	$fechaSimulacion =  simuladorTiempo($fechaActual, $fechaReferencia);

	//Condiciones
	list($yearHoy, $monthHoy, $dayHoy) = explode("-", $fechaSimulacion);
	$fechaInicio = $yearHoy."-".$monthHoy."-01";
	$fechaFinal = $yearHoy."-".$monthHoy."-31";

	list($validacion, $id) = validarToken($_conection, $_POST["token"]);
	if ($validacion) {
		
		if( $_POST['tipoA'] == 3){
			$whereTipo = ' AND emp_estado=3 ';
		}else{
			$whereTipo = ' AND (emp_estado=1 OR emp_estado=2) ';
		}

		$sqlEmpleados =  sprintf("SELECT `emp_id`, `emp_usu_id`, `emp_tipo`, `emp_perfil`, `emp_perfil_nombre`, `emp_perfil_otro`, `emp_modalidad`, `emp_modalidad_nombre`, `emp_contrato`, `emp_tipo_definido`, `emp_tipo_definido_value`, `emp_meses`, `emp_meses_value`, `emp_anhos`, `emp_anhos_value`, `emp_dor_nombre`, `emp_dor_hombre`, `emp_dor_mujer`, `emp_dor_nacionalidad`, `emp_dor_nacionalidad_value`, `emp_dor_no_identidad`, `emp_dor_domicilio`, `emp_do_nombre`, `emp_do_hombre`, `emp_do_mujer`, `emp_do_nacionalidad`, `emp_do_nacionalidad_value`, `emp_do_no_identidad`, `emp_do_domicilio`, `emp_do_fechanacimiento`, `emp_do_edad`, `emp_cond_jornada`, `emp_cond_semanas`, `emp_cond_fecha_relacion`, `emp_cond_termino`, `emp_cond_sueldo`, `emp_cond_periodo`, `emp_cond_auxilio`, `emp_cond_generacion`, `emp_contribuciones`, `emp_promedio`, `emp_anho1`, `emp_anho1_valor`, `emp_anho2`, `emp_anho2_valor`, `emp_anho3`, `emp_anho3_valor`, `emp_anho4`, `emp_anho4_valor`, `emp_anho5`, `emp_anho5_valor`, `emp_vacaciones`, `emp_dias`, `emp_estado`, `emp_imagen`, `emp_imagen_doc`, emp_nombre_padre, emp_nombre_madre, emp_ce_nombre, emp_ce_cedula, emp_ce_pasaporte, emp_ce_parentesco, emp_ce_telefono, emp_ce_direccion, `emp_ie_tiposangre`, `emp_ie_alergias`, `emp_ie_medico`, `emp_ie_telefono`, `emp_ie_notas`
								 FROM tbl_empleados WHERE emp_usu_id=%s ".$whereTipo,
			GetSQLValueString($id, "double")
		);
		$rs_sqlEmpleados = mysqli_query($_conection->connect(), $sqlEmpleados);
		$arrayEmpleados = array();
		while( $row_sqlEmpleados = mysqli_fetch_assoc($rs_sqlEmpleados) ){
			$empleado["emp_id"] = utf8_encode($row_sqlEmpleados["emp_id"]);
			$empleado["emp_tipo"] = utf8_encode($row_sqlEmpleados["emp_tipo"]);
			$empleado["emp_perfil"] = utf8_encode($row_sqlEmpleados["emp_perfil"]);
			$empleado["emp_perfil_nombre"] = utf8_encode($row_sqlEmpleados["emp_perfil_nombre"]);
			$empleado["emp_perfil_otro"] = utf8_encode($row_sqlEmpleados["emp_perfil_otro"]);
			$empleado["emp_modalidad"] = utf8_encode($row_sqlEmpleados["emp_modalidad"]);
			$empleado["emp_modalidad_nombre"] = utf8_encode($row_sqlEmpleados["emp_modalidad_nombre"]);
			$empleado["emp_contrato"] = utf8_encode($row_sqlEmpleados["emp_contrato"]);
			$empleado["emp_tipo_definido"] = utf8_encode($row_sqlEmpleados["emp_tipo_definido"]);
			$empleado["emp_tipo_definido_value"] = utf8_encode($row_sqlEmpleados["emp_tipo_definido_value"]);
			$empleado["emp_meses"] = utf8_encode($row_sqlEmpleados["emp_meses"]);
			$empleado["emp_meses_value"] = utf8_encode($row_sqlEmpleados["emp_meses_value"]);
			$empleado["emp_anhos"] = utf8_encode($row_sqlEmpleados["emp_anhos"]);
			$empleado["emp_anhos_value"] = utf8_encode($row_sqlEmpleados["emp_anhos_value"]);
			$empleado["emp_dor_nombre"] = utf8_encode($row_sqlEmpleados["emp_dor_nombre"]);
			$empleado["emp_dor_hombre"] = utf8_encode($row_sqlEmpleados["emp_dor_hombre"]);
			$empleado["emp_dor_mujer"] = utf8_encode($row_sqlEmpleados["emp_dor_mujer"]);
			$empleado["emp_dor_nacionalidad"] = utf8_encode($row_sqlEmpleados["emp_dor_nacionalidad"]);
			$empleado["emp_dor_nacionalidad_value"] = utf8_encode($row_sqlEmpleados["emp_dor_nacionalidad_value"]);
			$empleado["emp_dor_no_identidad"] = utf8_encode($row_sqlEmpleados["emp_dor_no_identidad"]);
			$empleado["emp_dor_domicilio"] = utf8_encode($row_sqlEmpleados["emp_dor_domicilio"]);
			$empleado["emp_do_nombre"] = utf8_encode($row_sqlEmpleados["emp_do_nombre"]);
			$empleado["emp_do_hombre"] = utf8_encode($row_sqlEmpleados["emp_do_hombre"]);
			$empleado["emp_do_mujer"] = utf8_encode($row_sqlEmpleados["emp_do_mujer"]);
			$empleado["emp_do_nacionalidad"] = utf8_encode($row_sqlEmpleados["emp_do_nacionalidad"]);
			$empleado["emp_do_nacionalidad_value"] = utf8_encode($row_sqlEmpleados["emp_do_nacionalidad_value"]);
			$empleado["emp_do_no_identidad"] = utf8_encode($row_sqlEmpleados["emp_do_no_identidad"]);
			$empleado["emp_do_domicilio"] = utf8_encode($row_sqlEmpleados["emp_do_domicilio"]);
			list($anhoN,$mesN,$diaN) = explode("-", utf8_encode($row_sqlEmpleados["emp_do_fechanacimiento"]));
			$empleado["emp_do_fechanacimiento"] = $diaN.'/'.$mesN.'/'.$anhoN;
			$empleado["emp_do_edad"] = utf8_encode($row_sqlEmpleados["emp_do_edad"]);
			$empleado["emp_cond_jornada"] = utf8_encode($row_sqlEmpleados["emp_cond_jornada"]);
			$empleado["emp_cond_semanas"] = utf8_encode($row_sqlEmpleados["emp_cond_semanas"]);
			list($anho,$mes,$dia) = explode("-", utf8_encode($row_sqlEmpleados["emp_cond_fecha_relacion"]));
			$empleado["emp_cond_fecha_nacimiento"] = $dia.'/'.$mes.'/'.$anho;
			$empleado["emp_cond_fecha_relacion"] = $dia.' de '.$arrayMesesGlobal[(int)$mes].' del '.$anho;
			$empleado["emp_cond_termino"] = utf8_encode($row_sqlEmpleados["emp_cond_termino"]);
			$empleado["emp_cond_sueldo"] = utf8_encode($row_sqlEmpleados["emp_cond_sueldo"]);
			$empleado["emp_cond_periodo"] = utf8_encode($row_sqlEmpleados["emp_cond_periodo"]);
			$empleado["emp_cond_auxilio"] = utf8_encode($row_sqlEmpleados["emp_cond_auxilio"]);
			$empleado["emp_cond_generacion"] = utf8_encode($row_sqlEmpleados["emp_cond_generacion"]);

			$sqlCondiciones =  sprintf("SELECT * FROM tbl_empleados_cambiocondiciones 
										WHERE es_cond_fecha_relacion>=%s AND es_cond_fecha_relacion<=%s AND es_emp_id=%s",
				GetSQLValueString($fechaInicio, "date"),
				GetSQLValueString($fechaFinal, "date"),
				GetSQLValueString($empleado["emp_id"], "double")
			);
			$rs_sqlCondiciones = mysqli_query($_conection->connect(), $sqlCondiciones);
			while ($row_sqlCondiciones = mysqli_fetch_assoc($rs_sqlCondiciones)) {
				$empleado["emp_cond_jornada"] = $row_sqlCondiciones['es_cond_jornada'];
				$empleado["emp_cond_semanas"] = $row_sqlCondiciones['es_cond_semanas'];
				$empleado["emp_cond_periodo"] = $row_sqlCondiciones['es_cond_periodo'];
				$empleado["emp_cond_termino"] = $row_sqlCondiciones['es_cond_termino'];
				$empleado["emp_cond_sueldo"] = $row_sqlCondiciones['es_cond_sueldo'];
			}
			
			$empleado["emp_contribuciones"] = utf8_encode($row_sqlEmpleados["emp_contribuciones"]);
			$empleado["emp_promedio"] = utf8_encode($row_sqlEmpleados["emp_promedio"]);
			$empleado["emp_anho1"] = utf8_encode($row_sqlEmpleados["emp_anho1"]);
			$empleado["emp_anho1_valor"] = utf8_encode($row_sqlEmpleados["emp_anho1_valor"]);
			$empleado["emp_anho2"] = utf8_encode($row_sqlEmpleados["emp_anho2"]);
			$empleado["emp_anho2_valor"] = utf8_encode($row_sqlEmpleados["emp_anho2_valor"]);
			$empleado["emp_anho3"] = utf8_encode($row_sqlEmpleados["emp_anho3"]);
			$empleado["emp_anho3_valor"] = utf8_encode($row_sqlEmpleados["emp_anho3_valor"]);
			$empleado["emp_anho4"] = utf8_encode($row_sqlEmpleados["emp_anho4"]);
			$empleado["emp_anho4_valor"] = utf8_encode($row_sqlEmpleados["emp_anho4_valor"]);
			$empleado["emp_anho5"] = utf8_encode($row_sqlEmpleados["emp_anho5"]);
			$empleado["emp_anho5_valor"] = utf8_encode($row_sqlEmpleados["emp_anho5_valor"]);
			$empleado["emp_vacaciones"] = utf8_encode($row_sqlEmpleados["emp_vacaciones"]);
			$empleado["emp_dias"] = utf8_encode($row_sqlEmpleados["emp_dias"]);
			
			$empleado["emp_nombre_padre"] = utf8_encode($row_sqlEmpleados["emp_nombre_padre"]);
			$empleado["emp_nombre_madre"] = utf8_encode($row_sqlEmpleados["emp_nombre_madre"]);
			
			$empleado["imagen"] = '';
			$empleado["imagenDoc"] = '';
			// echo 'asd'.$row_sqlEmpleados["emp_imagen"];

			if ($row_sqlEmpleados["emp_imagen"]) {
				$empleado["imagen"] = $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/empleados/".$row_sqlEmpleados['emp_id']."/".$row_sqlEmpleados["emp_imagen"];
			}
			
			if ($row_sqlEmpleados["emp_imagen_doc"]) {
				$empleado["imagenDoc"] = $puertoHttp . $_SERVER["SERVER_NAME"] ."/"._carpetaAdministrador_global."imagenes-contenidos/empleados/".$row_sqlEmpleados['emp_id']."/".$row_sqlEmpleados["emp_imagen_doc"];
			}
			
			$empleado["emp_estado"] = utf8_encode($row_sqlEmpleados["emp_estado"]);

			$sqlDependientes =  sprintf("SELECT `dp_usu_id`, `dp_emp_id`, `dp_nombres`, `dp_parentesco`
									 FROM tbl_dependientes WHERE dp_emp_id=%s LIMIT 0,1",
				GetSQLValueString($empleado["emp_id"], "double")
			);
			$rs_sqlDependientes = mysqli_query($_conection->connect(), $sqlDependientes);
			$row_sqlDependientes = mysqli_fetch_assoc($rs_sqlDependientes);
			$empleado["emp_cond_dependiente"] = utf8_encode($row_sqlDependientes["dp_nombres"]);
			$empleado["emp_cond_parentesco"] = utf8_encode($row_sqlDependientes["dp_parentesco"]);

			//Hermanos
			$hermanos = array();
			$sqlHermanos =  sprintf("SELECT `hm_emp_id`, `hm_nombre`
									 FROM tbl_hermanos WHERE hm_emp_id=%s",
				GetSQLValueString($empleado["emp_id"], "double")
			);
			$rs_sqlHermanos = mysqli_query($_conection->connect(), $sqlHermanos);
			while( $row_sqlHermanos = mysqli_fetch_assoc($rs_sqlHermanos) ){
				$hermano["nombre"] = utf8_encode($row_sqlHermanos['hm_nombre']);
				array_push($hermanos, $hermano);
			}
			$empleado['emp_hermanos'] = $hermanos;

			//Hijos
			$hijos = array();
			$sqlHijos =  sprintf("SELECT `hj_emp_id`, `hj_nombre`
									 FROM tbl_hijos WHERE hj_emp_id=%s",
				GetSQLValueString($empleado["emp_id"], "double")
			);
			$rs_sqlHijos = mysqli_query($_conection->connect(), $sqlHijos);
			while( $row_sqlHijos = mysqli_fetch_assoc($rs_sqlHijos) ){
				$hijo["nombre"] = utf8_encode($row_sqlHijos['hj_nombre']);
				array_push($hijos, $hijo);
			}
			$empleado['emp_hijos'] = $hijos;

			//Contacto de emergencia
			$empleado["emp_ce_nombre"] = utf8_encode($row_sqlEmpleados["emp_ce_nombre"]);
			$empleado["emp_ce_cedula"] = utf8_encode($row_sqlEmpleados["emp_ce_cedula"]);
			$empleado["emp_ce_pasaporte"] = utf8_encode($row_sqlEmpleados["emp_ce_pasaporte"]);
			$empleado["emp_ce_parentesco"] = utf8_encode($row_sqlEmpleados["emp_ce_parentesco"]);
			$empleado["emp_ce_telefono"] = utf8_encode($row_sqlEmpleados["emp_ce_telefono"]);
			$empleado["emp_ce_direccion"] = utf8_encode($row_sqlEmpleados["emp_ce_direccion"]);

			//Informacion Emergencia
			$empleado["emp_ie_tiposangre"] = utf8_encode($row_sqlEmpleados["emp_ie_tiposangre"]);
			$empleado["emp_ie_alergias"] = utf8_encode($row_sqlEmpleados["emp_ie_alergias"]);
			$empleado["emp_ie_medico"] = utf8_encode($row_sqlEmpleados["emp_ie_medico"]);
			$empleado["emp_ie_telefono"] = utf8_encode($row_sqlEmpleados["emp_ie_telefono"]);
			$empleado["emp_ie_notas"] = utf8_encode($row_sqlEmpleados["emp_ie_notas"]);

			//Dependientes
			$dependientes = array();
			$sqlDep =  sprintf("SELECT `dp_emp_id`, `dp_nombres`, `dp_parentesco`
									 FROM tbl_dependientes WHERE dp_emp_id=%s",
				GetSQLValueString($row_sqlEmpleados['emp_id'], "double")
			);
			$rs_sqlDep = mysqli_query($_conection->connect(), $sqlDep);
			while( $row_sqlDep = mysqli_fetch_assoc($rs_sqlDep) ){
				$dependiente["nombre"] = utf8_encode($row_sqlDep['dp_nombres']);
				$dependiente["parentesco"] = utf8_encode($row_sqlDep['dp_parentesco']);
				$dependiente["value"] = $arrayParentesco[$row_sqlDep['dp_parentesco']-1];
				array_push($dependientes, $dependiente);
			}
			$empleado['emp_dependientes'] = $dependientes;


			array_push($arrayEmpleados, $empleado);
		}
		$result["empleados"] = $arrayEmpleados;
		$result["success"] = true;

	}else{
		$result['error'] = -100;
	}

	$response->result = $result;
	echo json_encode($response);
?>
