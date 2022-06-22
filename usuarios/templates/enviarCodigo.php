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
          Se ha generado el siguiente código de recuperación:<br>
          <b>E-mail:</b> <?php echo $row_sqlCorreo["usu_email"]; ?><br>
          <b>Código:</b> <?php echo $codigo; ?>
          <br/>
          <br/>
        </td>
      </tr>
      <tr>
         <td style="height:35px; background-color:#7AC32E; text-align: center; padding: 10px; font-size: 10px; color: #ffffff;">
            Este es un correo automático enviado a <a href="mailto:<?php echo $row_sqlCorreo["usu_email"]; ?>" target="_blank" style="color:#ffffff; "><?php echo $row_sqlCorreo["usu_email"]; ?></a>, por favor no responda este correo. <br>
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
