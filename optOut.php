<?php
include 'functions.php';

//User has clicked an opt out from an email, and has passed their own email as a parameter
if(isset($_GET['email'])){

  //Retrieve email and unset id variable
  $email = test_input($_GET['email']);
  unset($_GET['email']);

  //Try block to monitor all database entries
  try{
    $pdo = connect();

    //Opt user out using sql injection attack prevention steps
    $sql = "INSERT IGNORE INTO opt_outs (email) VALUES (?)";
    $result = $pdo->prepare($sql);
    $result->execute([$email]);

    //Update page
    header("Location: optOut.php");
  }

  //Unable to create connection with database
  catch(PDOException $e){
    $error = $e->getMessage();
    echo "<p>Critical Error (Database):<br><br>{$error}<br><br>Please save this message and inform the head website administrator as soon as possible.</p>";
    exit(); 
  }
  $pdo = null;
}

//Page has been updated and user is free from the mailing list
else{
  echo "You have successfully opted out of all emails from this website.";
}
?>