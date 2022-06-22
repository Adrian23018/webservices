<?php
ob_start();
?>
<html>
  <head>
  </head>
  <body>
    <table width="100%" cellpadding="10" cellspacing="0" style="color: #000000; background-color: #f5f5f5;">
      <tr>
        <td align="center" style="height:70px; background-color:#26437A; padding: 10px; font-size:13px; font-family:Arial, Helvetica, sans-serif;"><img src="<?php echo $logoadministador; ?>" height="50"></td>
      </tr>
      <tr >
        <td align="left" style="font-family:Arial, Helvetica, sans-serif; font-size:13px;">
          <h2>UKU</h2>
          Comprobante de pago de liquidación<br><br>

          <h3>Resumen Compra</h3>
          <table cellpadding="5" cellspacing="2" border="0" >
            <tr>
              <td>Total pagado</td>
              <td><?php echo '$'.number_format($totalPagarTarjeta); ?></td>
            </tr>
            <tr>
              <td>Ciudad</td>
              <td><?php echo $_POST["ciudad"]; ?></td>
            </tr>
            <tr>
              <td>Dirección</td>
              <td><?php echo $_POST["direccion"]; ?></td>
            </tr>
            <tr>
              <td>Teléfono / Celular</td>
              <td><?php echo $_POST["telefono"]; ?></td>
            </tr>
          </table>

          <h3>Resumen Transacción</h3>
          <table cellpadding="5" cellspacing="2" border="0" >
            <tr>
              <td>Identificador de la orden PayU</td>
              <td><?php echo $responseData["transactionResponse"]["orderId"]; ?></td>
            </tr>
            <tr>
              <td>Identificador de la transacción</td>
              <td><?php echo $responseData["transactionResponse"]["transactionId"]; ?></td>
            </tr>
            <tr>
              <td>Estado transacción</td>
              <td>Aprobada</td>
            </tr>
            <tr>
              <td>El código de respuesta asociado al estado.</small></td>
              <td><?php echo $responseData["transactionResponse"]["responseCode"]; ?></td>
            </tr>

            <tr>
              <td>Código de respuesta<br> <small>retornado por la red financiera.</small></td>
              <td><?php echo $responseData["transactionResponse"]["paymentNetworkResponseCode"]; ?></td>
            </tr>
            <tr>
              <td>Código de trazabilidad <br><small>retornado por la red financiera.</small></td>
              <td><?php echo $responseData["transactionResponse"]["trazabilityCode"]; ?></td>
            </tr>
            <tr>
              <td>Código de autorización <br><small>retornado por la red financiera.</small></td>
              <td><?php echo $responseData["transactionResponse"]["authorizationCode"]; ?></td>
            </tr>
            <tr>
              <td>Mensaje asociado al código de respuesta.</td>
              <td><?php echo $responseData["transactionResponse"]["responseMessage"]; ?></td>
            </tr>
            <tr>
              <td>Fecha de la transacción</td>
              <td><?php echo $responseData["transactionResponse"]["transactionDate"]; ?></td>
            </tr>
            <tr>
              <td>Hora de la transacción</td>
              <td><?php echo $responseData["transactionResponse"]["transactionTime"]; ?></td>
            </tr>
          </table>

        </td>
      </tr>
      <tr>
         <td style="height:35px; background-color:#7AC32E; text-align: center; padding: 10px; font-size: 10px; color: #ffffff;">
          <?php echo utf8_encode($row_sqlRegistro['msj_mensaje_correo_enviado_a']); ?> <b><a href="mailto:<?php echo $row_sqlPuntos["usu_email"]; ?>" target="_blank" style="color:#ffffff; "><?php echo $row_sqlUsuario["usu_email"]; ?></a></b><?php echo utf8_encode($row_sqlRegistro['msj_mensaje_correo_enviado_a_resto']); ?> <br>
          <?php echo utf8_encode($row_sqlRegistro['msj_mensaje_correo_footer']); ?>
         </td>
      </tr>
    </table>
  </body>
</html>
<?php
$content = ob_get_contents();
ob_end_clean();
return($content);
?>
