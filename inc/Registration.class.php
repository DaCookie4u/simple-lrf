<?php
defined('APP') or exit(0);

class Registration {
  private $dbi = NULL;
  private $dbId = NULL;
  private $location = NULL;
  private $locationId = NULL;
  private $public_key = NULL;

  /**
   * Return a UUID (version 4) using random bytes
   * Note that version 4 follows the format:
   *     xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
   * where y is one of: [8, 9, A, B]
   *
   * We use (random_bytes(1) & 0x0F) | 0x40 to force
   * the first character of hex value to always be 4
   * in the appropriate position.
   *
   * For 4: http://3v4l.org/q2JN9
   * For Y: http://3v4l.org/EsGSU
   * For the whole shebang: https://3v4l.org/LNgJb
   *
   * @ref https://stackoverflow.com/a/31460273/2224584
   * @ref https://paragonie.com/b/JvICXzh_jhLyt4y3
   *
   * @return string
   */
  private function uuidv4() {
    return implode('-', [
        bin2hex(random_bytes(4)),
        bin2hex(random_bytes(2)),
        bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)) . bin2hex(random_bytes(1)),
        bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)) . bin2hex(random_bytes(1)),
        bin2hex(random_bytes(6))
    ]);
  }

  public function __construct($dbi) {
    $this->dbi = $dbi;

    $sql = '
      CREATE TABLE IF NOT EXISTS "lrf_location" (
        "id"	TEXT NOT NULL,
        "location"	TEXT NOT NULL,
        "public_key"	BLOB NOT NULL,
        "active"	INTEGER DEFAULT 1,
        "remove"	INTEGER DEFAULT 0,
        "timestamp"	INTEGER,
        PRIMARY KEY("id")
      );
    ';
    $this->dbi->query($sql);

    $sql = '
      CREATE TABLE IF NOT EXISTS "lrf_registration" (
      	"id"	TEXT NOT NULL,
      	"location"	TEXT NOT NULL,
      	"timestamp"	TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      	"duration"	INTEGER NOT NULL,
      	"vorname"	TEXT NOT NULL,
      	"nachname"	TEXT NOT NULL,
      	"anschrift"	TEXT NOT NULL,
      	"plz"	TEXT NOT NULL,
      	"ort"	TEXT NOT NULL,
      	"email"	TEXT NOT NULL,
      	"telefon"	TEXT NOT NULL,
      	FOREIGN KEY("location") REFERENCES "lrf_location"("id") ON DELETE CASCADE ON UPDATE CASCADE,
      	PRIMARY KEY("id")
      );
    ';
    $this->dbi->query($sql);
  }

  public function __destruct() {
    $this->dbi = null;
  }

  public function SetLocation($id) {
    $sql = 'SELECT location, public_key FROM lrf_location WHERE id = :id LIMIT 0,1;';
    $stmt = $this->dbi->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $this->locationId = $id;
      $this->public_key = $row['public_key'];
      $this->location = $row['location'];
      return true;
    } else {
      return false;
    }
  }

  public function GetLocationName() {
    return $this->location;
  }

  // cleanRegistrations
  public function CleanRegistrations() {
    $obsoleteDt = new DateTime('now');
    $obsoleteDt->setTime(0,0);
    $obsoleteDt->sub(new DateInterval('P2W'));
    $timestamp = $obsoleteDt->getTimestamp();

    $sql = 'DELETE FROM lrf_registration WHERE datetime < :timestamp;';
    $this->dbi->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $stmt = $this->dbi->prepare($sql);
    $stmt->bindParam(':timestamp', $timestamp);
    $stmt->execute();
    $stmt = null;
  }

  // create a new messages, returns:
  //  1: id from the database table
  //  2: key for decryption in hex
  public function Register($name, $address, $zip, $city, $email, $phone, $duration) {
    $this->dbId = $this->uuidv4();
    $currentDt = new DateTime('now');
    $timestamp = $currentDt->getTimestamp();

    // Encrypted values
    $name = $this->Encrypt($name);
    $address = $this->Encrypt($address);
    $zip = $this->Encrypt($zip);
    $city = $this->Encrypt($city);
    $email = $this->Encrypt($email);
    $phone = $this->Encrypt($phone);

    // insert the hashed $key and the encrypted $data inside the table
    // the dbquery() will return the insert id
    $sql = 'INSERT INTO lrf_registration (id, location, datetime, duration, name, anschrift, plz, ort, email, telefon) VALUES (:id, :location, :timestamp, :duration, :name, :anschrift, :plz, :ort, :email, :telefon);';
    $this->dbi->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $stmt = $this->dbi->prepare($sql);
    $stmt->bindParam(':id', $this->dbId);
    $stmt->bindParam(':location', $this->locationId);
    $stmt->bindParam(':timestamp', $timestamp);
    $stmt->bindParam(':duration', $duration);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':anschrift', $address);
    $stmt->bindParam(':plz', $zip);
    $stmt->bindParam(':ort', $city);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':telefon', $phone);
    $stmt->execute();
    $stmt = null;
    return $this->dbId;
  }

  public function ExportRegistrations($key) {
    $sql = 'SELECT * FROM lrf_registration WHERE location = :location ORDER BY datetime ASC';
    $this->dbi->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $stmt = $this->dbi->prepare($sql);
    $stmt->bindParam(':location', $this->locationId);
    $stmt->execute();

    $data = array();

    $rDt = new DateTime();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $rDt->setTimestamp($row['datetime']);
      $record = array(
        "datum" => $rDt->format("d.m.Y"),
        "uhrzeit" => $rDt->format("H:i"),
        "dauer" => $row['duration'],
        "name" => $this->Decrypt($row['name'], $key),
        "anschrift" => $this->Decrypt($row["anschrift"], $key),
        "plz" => $this->Decrypt($row["plz"], $key),
        "ort" => $this->Decrypt($row["ort"], $key),
        "telefon" => $this->Decrypt($row["telefon"], $key),
        "email" => $this->Decrypt($row["email"], $key)
      );
      array_push($data, $record);
    }

    return $data;
  }

  private function Encrypt($data) {
    openssl_public_encrypt($data, $enc_data, $this->public_key);
    return base64_encode($enc_data);
  }

  private function Decrypt($enc_data, $key) {
    openssl_private_decrypt(base64_decode($enc_data), $data, $key);
    return $data;
  }
}
?>
