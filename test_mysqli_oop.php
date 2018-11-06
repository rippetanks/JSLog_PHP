<!--
  This Source Code Form is subject to the terms of the Mozilla Public
  License, v. 2.0. If a copy of the MPL was not distributed with this
  file, You can obtain one at http://mozilla.org/MPL/2.0/.
-->

<?php
  $servername = "localhost";
  $dbname = "jslog";
  $username = "root";
  $password = "";

  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

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
    $stmt = $conn->prepare("SELECT id, log_key FROM entity WHERE log_key = ?;");
    if(!$stmt) {
      http_response_code(500);
      die("Incorrect query!");
    }
    $stmt->bind_param('s', $json_obj->log_key);
    if($stmt->execute() !== TRUE) {
      http_response_code(400);
      die($conn->error);
    }
    // bind result variables
    $stmt->bind_result($id, $key);
    $stmt->fetch();
    // check
    if($json_obj->entity !== $id || $json_obj->log_key !== $key) {
      http_response_code(401);
      die("Unauthorized!");
    }
    $stmt->close();
    // end security checks

    // Build query
    $stmt = $conn->prepare("INSERT INTO log (entity, record_date, level, UserAgent, Host, message, http_code)
                            VALUES (?, ?, ?, ?, ?, ?, ?);");
    if(!$stmt) {
      http_response_code(500);
      die("Incorrect query!");
    }
    // MariaDB 10 does not support ISO date format
    $stmt->bind_param('isssssi',
      $json_obj->entity, explode('.', $json_obj->datetime)[0], $json_obj->level,
      $_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_HOST'], $json_obj->message,
      $json_obj->http_code);
    // Run query
    if ($stmt->execute() !== TRUE) {
      http_response_code(400);
      die($conn->error);
    }
    $stmt->close();
  }

  // Close connection
  $conn->close();
?>
