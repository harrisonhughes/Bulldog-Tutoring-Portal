<?php
  //Connect to tutoring database: must be executed within try block
  function connect(){
    define('DBHOST', 'localhost');
    define('DBNAME', 'tutoring_portal');
    define('DBUSER', 'root');
    define('DBPASS', 'root');
    define('DBCONNSTRING',"mysql:host=". DBHOST. ";dbname=". DBNAME);
    return new PDO(DBCONNSTRING,DBUSER,DBPASS);
  }

  //Clean form entries to prevent html script injection attacks
  function test_input($string) {
    $string = trim($string);
    $string = stripslashes($string);
    $string = htmlspecialchars($string);
    return $string;
  }
?>
