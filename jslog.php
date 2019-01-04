<!--
  This Source Code Form is subject to the terms of the Mozilla Public
  License, v. 2.0. If a copy of the MPL was not distributed with this
  file, You can obtain one at http://mozilla.org/MPL/2.0/.
-->

<?php

  /**
  *
  */
  class JSLog {

    private $dbname = "jslog";
    private $servername = "mysql:host=localhost;dbname=";
    private $username = "root";
    private $password = "";

    private $conn;

    private $auth = true;

    /**
    *
    */
    public function __construct() {
      $this->servername .= $this->dbname;
      // Create connection
      $this->conn = new PDO($this->servername, $this->username, $this->password);
      // set the PDO error mode to exception
      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
    *
    */
    public function disableAuth() {
      $this->auth = false;
    }

    /**
    *
    */
    public function enableAuth() {
      $this->auth = true;
    }

    /**
    *
    */
    public function isAuth() {
      return $this->auth;
    }

    /**
    *
    */
    public function add($json_arr) {
      $count = count($json_arr);
      for($i = 0; $i < $count; $i++) {
        $json_obj = $json_arr[$i];
        // AUTH
        if($this->auth) {
          // Check API KEY
          $stmt = $this->conn->prepare("SELECT id, log_key FROM entity WHERE log_key = ?;",
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
        }
        // end security checks

        // Build query
        $stmt = $this->conn->prepare(
          "INSERT INTO log (entity, record_date, level, UserAgent, Host, message, http_code)
            VALUES (?, ?, ?, ?, ?, ?, ?);", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        // MariaDB 10 does not support ISO date format
        $stmt->execute(array(
          isset($json_obj->entity) ? $json_obj->entity : NULL,
          explode('.', $json_obj->datetime)[0],
          $json_obj->level,
          isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : NULL,
          isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : NULL,
          $json_obj->message,
          isset($json_obj->http_code) ? $json_obj->http_code : NULL));
      } // end for
    } // end add

    /**
    *
    */
    public function profile($json) {
      // AUTH
      if($this->auth) {
        // Check API KEY
        $stmt = $this->conn->prepare("SELECT id, log_key FROM entity WHERE log_key = ?;",
          array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $stmt->execute(array($json->log_key));
        // bind result variables
        $row = $stmt->fetchAll();
        $id = $row[0][0];
        $key = $row[0][1];
        // check
        if($json->entity != $id || $json->log_key != $key) {
          http_response_code(401);
          die("Unauthorized!");
        }
      }
      // end security checks

      $stmt = $this->conn->prepare(
        "INSERT INTO profile (entity, profile_time, descr)
          VALUES (?, ?, ?);", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
      // MariaDB 10 does not support ISO date format
      $stmt->execute(array(
        isset($json->entity) ? $json->entity : NULL,
        $json->time,
        $json->desc));
    } // end profile

  } // end class

?>
