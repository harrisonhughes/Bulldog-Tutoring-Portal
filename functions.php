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

  //Create random character password of a user specified length
  function randomPassword($length){
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $numChars = strlen($characters);
    
    $password = '';
    for($i = 0; $i < $length; $i++){
      $randomChar = random_int(0, $numChars - 1);
      $password .= $characters[$randomChar];
    }

    return $password;
  }

  //Mail through our outlook account given all relevant mail parameters defined by user
  //Mail operations adapted from: https://alexwebdevelop.com/phpmailer-tutorial/#:~:text=HOW%20TO%20USE%20THE%20PHPMAILER%20CLASS%201%20Set,an%20attachment%20from%20binary%20data%20...%20More%20items

  //Mail function has been stripped of all official website information for privacy. Custom fields must be entered to test this feature
  function sendEmail($address, $name, $header, $body){
    //Require mailing dependency
    require 'vendor/autoload.php';

    //Prevent emails from beng sent to those on the opt out list
    /**
    try{
      $pdo = connect();
      $sql = "SELECT * FROM opt_outs WHERE email = ?";
      $result = $pdo->prepare($sql);
      $result->execute([$address]);
      $optOut = $result->fetch();

      if(!empty($optOut)){
        return;
      }
    }

    //Unable to create connection with database
    catch(PDOException $e){
      $error = $e->getMessage();
      echo "<p>Critical Error (Database):<br><br>{$error}<br><br>Please save this message and inform the head website administrator as soon as possible.</p>";
      exit(); 
    }
    $pdo = null;
    */
    
    //Setup SMTP variable
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    
    //Provide host name for Outlook Email Server
    //$mail->Host = '';
    
    //Port number specification and Security/Authorization designations
    $mail->Port = 587;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth = true;
    $mail->SMTPDebug = 0;
    
    //Outlook Email Credentials
    //$mail->Username = "";
    //$mail->Password = "";
    
    // Set who the message is to be sent from
    //$mail->setFrom('', '');
    $mail->addAddress($address, $name);

    //Format signature at bottom of page
    //$signature = "";
    $fullBody = "<p>{$body}{$signature}</p>"; 
    
    //Format email body and add opt out footer
    //$URL = "https://bulldogtutoringportal.com/optOut.php";
    //$optOut = "<p style='font-size: 10pt;'>Click <a href='{$URL}'>here</a> to opt out of receiving any future emails</p>";
    //$fullBody = $fullBody . $optOut;

    //Construct email
    $mail->Subject = $header;
    $mail->Body = $fullBody;
    
    //Body text for mail providers without HTML
    $mail->AltBody = $fullBody;
    
    //Send away
    if(!$mail->send()){
        $error = $mail->ErrorInfo;
    }
  }
?>
