<?php
define('APP', 'lrf');

require_once('inc/config.inc.php');
require_once('inc/Registration.class.php');
require_once('inc/SafePDO.class.php');

$reg = new Registration(new SafePDO($db_dsn, $db_user, $db_pass));
$reg->CleanRegistrations();
if ($reg->SetLocation($_POST['location']) && isset($_POST['key'])) {
  $data = $reg->ExportRegistrations($_POST['key']);
  $csv = '"Datum","Uhrzeit","Dauer","Name","Anschrift","PLZ","Ort","Telefon"' . "\n";
  foreach($data as $e) {
    $csv .= '"' . $e['datum'] . '",';
    $csv .= '"' . $e['uhrzeit'] . '",';
    $csv .= '"' . $e['dauer'] . '",';
    $csv .= '"' . $e['name'] . '",';
    $csv .= '"' . $e['anschrift'] . '",';
    $csv .= '"' . $e['plz'] . '",';
    $csv .= '"' . $e['ort'] . '",';
    $csv .= '"' . $e['telefon'] . '"';
    $csv .= "\n";
  }
  header('Content-type: text/csv');
  header('Content-Disposition: attachment; filename="export.csv"');
  echo $csv;
} else {
?>
<form action="export.php" method="POST" target="_self">
<input type="text" name="location" value="ea78f9de-ac27-49e2-8983-c79736482a44" /><br />
<textarea name="key"></textarea><br />
<input type="submit" value="Absenden" />
</form>
<?php
}
?>
