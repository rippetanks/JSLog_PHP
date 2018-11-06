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
  $conn = mysqli_connect($servername, $username, $password, $dbname);
  // Check connection
  if(mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
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
    $stmt = mysqli_stmt_init($conn);
    $result = mysqli_stmt_prepare($stmt, "SELECT id, log_key FROM entity WHERE log_key = ?;");
    if(!$result) {
      http_response_code(500);
      die("Incorrect query!");
    }
    mysqli_stmt_bind_param($stmt, 's', $json_obj->log_key);
    if(mysqli_stmt_execute($stmt) !== TRUE) {
      http_response_code(400);
      die(mysqli_error($conn));
    }
    // bind result variables
    mysqli_stmt_bind_result($stmt, $id, $key);
    mysqli_stmt_fetch($stmt);
    // check
    if($json_obj->entity !== $id || $json_obj->log_key !== $key) {
      http_response_code(401);
      die("Unauthorized!");
    }
    mysqli_stmt_close($stmt);
    // end security checks

    // Build query
    $stmt = mysqli_stmt_init($conn);
    $result = mysqli_stmt_prepare($stmt,
      "INSERT INTO log (entity, record_date, level, UserAgent, Host, message, http_code)
        VALUES (?, ?, ?, ?, ?, ?, ?);");
    if(!$result) {
      http_response_code(500);
      die("Incorrect query!");
    }
    // MariaDB 10 does not support ISO date format
    mysqli_stmt_bind_param($stmt, 'isssssi',
      $json_obj->entity, explode('.', $json_obj->datetime)[0], $json_obj->level,
      $_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_HOST'], $json_obj->message,
      $json_obj->http_code);
    // Run query
    if (mysqli_stmt_execute($stmt) !== TRUE) {
      http_response_code(400);
      die(mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);
  }

  // Close connection
  $conn->close();
?>
