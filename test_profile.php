<!--
  This Source Code Form is subject to the terms of the Mozilla Public
  License, v. 2.0. If a copy of the MPL was not distributed with this
  file, You can obtain one at http://mozilla.org/MPL/2.0/.
-->

<?php
  include_once('jslog.php');

  try {
    // Get body and parse JSON
    $json_str = file_get_contents('php://input');
    $json = json_decode($json_str);

    $jslog = new JSLog();
    $jslog->enableAuth();
    $jslog->profile($json);
  }
  catch(PDOException $e) {
    http_response_code(500);
    die($e->getMessage());
  }
?>
