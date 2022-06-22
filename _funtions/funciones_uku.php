<?php

function ukuEditTarjetaDefault( $card ){
	$sqlDefault = sprintf(" UPDATE tbl_stripe_cards SET sca_default=1 WHERE sca_card=%s ",
		GetSQLValueString($card, "text")
	);
	$rs_sqlDefault = mysqli_query($GLOBALS['_conexion'], $sqlDefault);
	if ($rs_sqlDefault)
		return true;

	return false;
}

function ukuDeleteTarjeta( $id ){
	$sqlCard = sprintf( " DELETE FROM tbl_stripe_cards WHERE sca_id=%s " ,
		GetSQLValueString($id, "text")
	);
	$rs_sqlCard = mysqli_query($GLOBALS['_conexion'], $sqlCard);
	if ($rs_sqlCard)
		return true;

	return false;
}

// Obtener Tarjetas Usuario
function ukuGetTarjetasId( $id ){

	$sqlCards = sprintf("SELECT * FROM tbl_stripe_cards WHERE sca_usu_id=%s ORDER BY sca_default DESC ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlCards = mysqli_query($GLOBALS['_conexion'], $sqlCards);
	$cards = array();
	while ( $row_sqlCards = mysqli_fetch_assoc($rs_sqlCards) ){
		$card['id'] = utf8_encode($row_sqlCards['sca_id']);
		$card['card'] = utf8_encode($row_sqlCards['sca_card']);

		array_push($cards, $card);
	}

	return $cards;

}

function ukuGetTarjetas( $id ){

	$sqlCards = sprintf("SELECT * FROM tbl_stripe_cards WHERE sca_usu_id=%s ORDER BY sca_default DESC ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlCards = mysqli_query($GLOBALS['_conexion'], $sqlCards);
	$cards = array();
	while ( $row_sqlCards = mysqli_fetch_assoc($rs_sqlCards) ){
		$card['id'] = utf8_encode($row_sqlCards['sca_id']);
		$card['franquicia'] = utf8_encode($row_sqlCards['sca_franquicia']);
		$card['exp_month'] = utf8_encode($row_sqlCards['sca_exp_month']);
		$card['exp_year'] = utf8_encode($row_sqlCards['sca_exp_year']);
		$card['last4'] = utf8_encode($row_sqlCards['sca_last4']);
		$card['name'] = utf8_encode($row_sqlCards['sca_name']);
		$card['default'] = utf8_encode($row_sqlCards['sca_default']);

		array_push($cards, $card);
	}

	return $cards;

}

function ukuGetPlanId( $id ) {
	$sqlPlan = sprintf("SELECT * FROM tbl_stripe_plans WHERE spl_id=%s ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlPlan = mysqli_query($GLOBALS['_conexion'], $sqlPlan);

	$row_sqlPlan = mysqli_fetch_assoc($rs_sqlPlan);
	return $row_sqlPlan['spl_stripe_id'];
}

function ukuGetPlan( $id ) {
	$sqlPlan = sprintf("SELECT * FROM tbl_stripe_plans WHERE spl_id=%s ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlPlan = mysqli_query($GLOBALS['_conexion'], $sqlPlan);

	$row_sqlPlan = mysqli_fetch_assoc($rs_sqlPlan);
	return $row_sqlPlan;
}

function ukuGetPlanAnteriorId( $id ) {
	$sqlPlan = sprintf("SELECT * FROM tbl_stripe_subscriptions WHERE ss_usu_id=%s ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlPlan = mysqli_query($GLOBALS['_conexion'], $sqlPlan);

	$row_sqlPlan = mysqli_fetch_assoc($rs_sqlPlan);
	return $row_sqlPlan['ss_stripe_id'];
}

function ukuDeletePlanAnteriorId( $id ){
	$sqlPlan = sprintf("DELETE FROM tbl_stripe_subscriptions WHERE ss_usu_id=%s ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlPlan = mysqli_query($GLOBALS['_conexion'], $sqlPlan);
	if ( $rs_sqlPlan )
		return true;
	return false;
}

function ukuGetCustomerId( $id ) {
	$sqlUsu = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlUsu = mysqli_query($GLOBALS['_conexion'], $sqlUsu);

	$row_sqlUsu = mysqli_fetch_assoc($rs_sqlUsu);
	return $row_sqlUsu['usu_stripe_id'];
}

function ukuGetCustomerCanceled( $id ) {
	$sqlUsu = sprintf("SELECT * FROM tbl_usuarios WHERE usu_id=%s ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlUsu = mysqli_query($GLOBALS['_conexion'], $sqlUsu);

	$row_sqlUsu = mysqli_fetch_assoc($rs_sqlUsu);
	return $row_sqlUsu['usu_canceled_suscription'];
}

function ukuCustomerCanceledPlan( $id ){
	$sqlUsu = sprintf("UPDATE tbl_usuarios SET usu_canceled_suscription=1 WHERE usu_id=%s ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlUsu = mysqli_query($GLOBALS['_conexion'], $sqlUsu);
}

function ukuGetPlanCustomer( $id ){
	$sqlPlan = sprintf(" SELECT * FROM tbl_stripe_subscriptions INNER JOIN tbl_stripe_plans ON ss_spl_id=spl_id WHERE ss_usu_id=%s ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlPlan = mysqli_query($GLOBALS['_conexion'], $sqlPlan);
	$row_sqlPlan = mysqli_fetch_assoc($rs_sqlPlan);

	return $row_sqlPlan;
}

function ukuGetPlanCustomerApple( $id ){
	$sqlPlan = sprintf(" SELECT * FROM tbl_apple_pay INNER JOIN tbl_stripe_plans ON app_plan_id=spl_id WHERE app_usu_id=%s ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlPlan = mysqli_query($GLOBALS['_conexion'], $sqlPlan);
	$row_sqlPlan = mysqli_fetch_assoc($rs_sqlPlan);

	return $row_sqlPlan;
}

function ukuGetPlanCustomerAndroid( $id ){
	$sqlPlan = sprintf(" SELECT * FROM tbl_google_pay INNER JOIN tbl_stripe_plans ON app_plan_id=spl_id WHERE app_usu_id=%s ",
		GetSQLValueString($id, "text")
	);
	$rs_sqlPlan = mysqli_query($GLOBALS['_conexion'], $sqlPlan);
	$row_sqlPlan = mysqli_fetch_assoc($rs_sqlPlan);

	return $row_sqlPlan;
}