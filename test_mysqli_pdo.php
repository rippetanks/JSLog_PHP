<!--
  This Source Code Form is subject to the terms of the Mozilla Public
  License, v. 2.0. If a copy of the MPL was not distributed with this
  file, You can obtain one at http://mozilla.org/MPL/2.0/.
-->

<?php
  $dbname = "jslog";
  $servername = "mysql:host=localhost;dbname=$dbname";
  $username = "root";
  $password = "";

  try {
    // Create connection
    $conn = new PDO($servername, $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get body and parse JSON
    $json_str = file_get_contents('php://input');
    $json_arr = json_decode($json_str);

    $count = count($json_arr);
    for($i = 0; $i < $count; $i++) {
      $json_obj = $json_arr[$i];
      // Check API KEY - remove this section for not having security checks
      if(!isset($json_obj->log_key)) {
        http_response_code(400);
        die("Missing API KEY!");
      }
      $stmt = $conn->prepare("SELECT id, log_key FROM entity WHERE log_key = ?;",
        array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
      $stmt->execute(array($json_obj->log_key));
      // bind result variables
      $row = $stmt->fetchAll();
      $id = $row[0][0];
      $key = $row[0][1];
      // check
      if($json_obj->entity != $id || $json_obj->log_key != $key) {
        http_response_code(401);
        die("Unauthorized!");
      }
      // end security checks

      // Build query
      $stmt = $conn->prepare("INSERT INTO log (entity, record_date, level, UserAgent, Host, message, http_code)
                              VALUES (?, ?, ?, ?, ?, ?, ?);",
              array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
      // MariaDB 10 does not support ISO date format
      $stmt->execute(array(
        isset($json_obj->entity) ? $json_obj->entity : NULL,
        explode('.', $json_obj->datetime)[0],
        $json_obj->level,
        isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : NULL,
        isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : NULL,
        $json_obj->message,
        isset($json_obj->http_code) ? $json_obj->http_code : NULL));
    }

    // Close connection
    $conn = null;
  }
  catch(PDOException $e) {
    http_response_code(500);
    die($e->getMessage());
  }
?>
