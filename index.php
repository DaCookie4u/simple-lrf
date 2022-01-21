<?php
define('APP', 'lrf');

require_once('inc/config.inc.php');
require_once('inc/Registration.class.php');
require_once('inc/SafePDO.class.php');

$reg = new Registration(new SafePDO($db_dsn, $db_user, $db_pass));
$reg->CleanRegistrations();
if (isset($_GET['location']) && $reg->SetLocation($_GET['location'])) {
  $locationId = $_GET['location'];
}

if ($locationId && isset($_POST['duration'])) {
    $id = $reg->Register($_POST['name'], $_POST['address'], $_POST['zip'], $_POST['city'], $_POST['phone'], $_POST['duration']);
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      rel="icon"
      href="img/fire_wheel.png"
      sizes="16x16"
      type="image/png"
    />
    <link rel="stylesheet" href="css/style.css" />
    <title>Besucherregistrierung - <?php echo ($locationId) ? $reg->GetLocationName() : 'unbekannte Location'; ?></title>
  </head>
  <body>
    <header>
      <img class="header__logo" src="img/DWB_KKL_rgb_german_.gif" alt="">
    </header>
    <h3><?php echo ($locationId) ? $reg->GetLocationName() : 'Location unbekannt!'; ?></h3>
<?php if ($id) { ?>
    <p class="centered">Vielen Dank für Ihre Registrierung.</p>
<?php } else if ($locationId) { ?>
    <script type="text/javascript">
      // validate form
      function validate() {
        // save form if checked
        if (document.getElementById("save-checkbox").checked) {
          localStorage.setItem("name", document.getElementById("name").value);
          localStorage.setItem("phone", document.getElementById("phone").value);
          localStorage.setItem("address", document.getElementById("address").value);
          localStorage.setItem("zip", document.getElementById("zip").value);
          localStorage.setItem("city", document.getElementById("city").value);
        }
        return true;
      }

      // retrieve savedata
      document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("name").value = localStorage.getItem("name");
        document.getElementById("phone").value = localStorage.getItem("phone");
        document.getElementById("address").value = localStorage.getItem("address");
        document.getElementById("zip").value = localStorage.getItem("zip");
        document.getElementById("city").value = localStorage.getItem("city");
      });
    </script>

    <div class="form">
      <form id="registrationForm" method="post" onsubmit="return validate()">
        <!-- Name -->
        <label class="form-group">
          <span class="form-group__label required">Name</span>
          <input
            class="form-text"
            type="text"
            id = "name"
            name="name"
            placeholder="Max Mustermann"
            required
          />
        </label>

        <!-- Phone -->
        <label class="form-group block">
          <span class="form-group__label required">Telefon</span>
          <input
            class="form-text turn-off-number-input-arrows"
            type="number"
            id="phone"
            name="phone"
            placeholder="+49 170 12345678"
            inputmode="tel"
            required
          />
        </label>

        <!-- Address -->
        <fieldset class="mt_1rem">
          <legend>Adresse</legend>
          <!-- Street + Number -->
          <label class="form-group">
            <span class="form-group__label required">Straße und Hausnummer</span>
            <input
              class="form-text"
              type="text"
              id = "address"
              name="address"
              placeholder="Musterstraße 108"
              required
            />
          </label>

          <!-- Postal Code -->
          <label class="form-group">
            <span class="form-group__label required">Postleitzahl</span>
            <input
              class="form-text"
              type="text"
              inputmode="numeric"
              id = "zip"
              name="zip"
              placeholder="12345"
              required
            />
          </label>

          <!-- City -->
          <label class="form-group">
            <span class="form-group__label required">Stadt</span>
            <input
              class="form-text"
              type="text"
              id = "city"
              name="city"
              placeholder="Musterstadt"
              required
            />
          </label>
        </fieldset>

        <!-- Length of Stay -->
        <label class="form-group block">
          <span class="form-group__label">Vorraussichtliche Verweildauer</span>
          <select class="form-select" id="duration" name="duration" required>
            <option value="" selected disabled hidden>Bitte auswählen</option>
            <option value="15">15 Minuten</option>
            <option value="30">30 Minuten</option>
            <option value="45">45 Minunten</option>
            <option value="60">1 Stunde</option>
            <option value="90">1,5 Stunden</option>
            <option value="120">2 Stunden</option>
            <option value="150">2,5 Stunden</option>
            <option value="180">3 Stunden</option>
          </select>
        </label>

        <!-- Checkbox Privacy -->
        <label class="form-checkbox__container">
          <input value="privacy policy accepted" class="form-checkbox__input" name="privacy-checkbox" type="checkbox" required>
          <span class="required">Die <a href="#privacy">Datenschutzinformation</a> habe ich zur Kenntnis genommen.</span>
        </label>

        <!-- Save form to local storage -->
        <label class="form-checkbox__container">
          <input value="save form data" class="form-checkbox__input" id="save-checkbox" name="save-checkbox" type="checkbox">
          <span>Daten für den nächsten Besuch <a href="#saving">speichern</a>.</span>
        </label>

        <!-- Submit -->
        <div>
          <button class="button button__label" type="submit" value="Submit">
            Registrieren
          </button>
        </div>
      </form>

      <div id="privacy">
        <fieldset>
          <legend>Datenschutzinformation</legend>
          Wir sind nach § 11 Absatz 2, Satz 2 der Hamburgischen SARS-CoV-2-Eindämmungsverordnung verpflichtet, die Kontaktdaten zur Nachverfolgung einer Infektionskette durch das Gesundheitsamt zu erheben und zu speichern. Die Daten werden verschlüsselt an einen Server des Buddhistischen Zentrums Hamburg gesendet, verschlüsselt gespeichert und nach 4 Wochen automatisch gelöscht. Eine sonstige Weitergabe an Dritte erfolgt nicht.
        </fieldset>
      </div>

      <div id="saving">
        <fieldset>
          <legend>Daten speichern</legend>
          Bei Auswahl dieser Option werden die eingegeben Daten lokal auf Ihrem Gerät gespeichert und bei erneutem Aufruf wiederhergestellt. Um die lokalen Daten zu speichern benötigt diese Seite Javascript. Sie können die Daten mit diesem <a href="/<?php echo $locationId ?>" onclick="localStorage.clear();">Link löschen</a>.
        </fieldset>
      </div>

      <div id="content">
        <fieldset>
          <legend>Impressum</legend>
          Verantwortlich im Sinne der Datenschutz-Grundverordnung sind die <a href="http://www.buddhismus-nord.de/zentren/hamburg/datenschutz.php">Buddhistischen Zentren Norddeutschland e.V.</a>.
        </fieldset>
      </div>
    </div>
<?php } ?>
  </body>
</html>
