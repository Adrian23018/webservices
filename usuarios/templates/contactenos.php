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
          <br/>
          <b>Id:</b> <?php echo $id; ?><br>
          <b>Nombre Completo:</b> <?php echo $row_sqlUsuario["usu_nombres"] . ' ' . $row_sqlUsuario["usu_apellidos"]; ?><br>
          <b>E-mail:</b> <?php echo $row_sqlUsuario["usu_email"]; ?><br>
          <b>Asunto:</b> <?php echo $asunto; ?><br/>
          <b>Mensaje:</b> <?php echo $mensaje; ?><br/>
          <br/>
          <br/>
        </td>
      </tr>
      <tr>
         <td style="height:35px; background-color:#7AC32E; text-align: center; padding: 10px; font-size: 10px; color: #ffffff;">
            Este es un correo automático enviado al administrador, por favor no responda este correo. <br>
            Recuerde que respetamos su privacidad, estamos en contra del spam.
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
