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
          <h2>Contrato del empleado</h2>
          <br/>
          <p>
            Has recibido el contrato generado por Uku para el empleado <?php echo utf8_encode($row_sqlEmpleados['emp_do_nombre']); ?> 
          </p>
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
