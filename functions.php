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
  function sendEmail($address, $name, $header, $body){
    /**
    require 'vendor/autoload.php'; // For mailing
      
    //Setup SMTP variable
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    
    //Provide host name for Outlook Email Server
    $mail->Host = 'YOUR EMAIL HOST';
    
    //Port number specification and Security/Authorization designations
    $mail->Port = 587;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth = true;
    $mail->SMTPDebug = 0;
    
    //Outlook Email Credentials
    $mail->Username = 'YOUR EMAIL ACCOUNT';
    $mail->Password = 'YOUR EMAIL PASSWORD';
    
    // Set who the message is to be sent from
    $mail->setFrom('YOUR EMAIL ACCOUNT', 'YOUR NAME');
    $mail->addAddress($address, $name);
    

    //Construct email
    $mail->Subject = $header;
    $mail->Body = $body;
    
    //Body text for mail providers without HTML
    $mail->AltBody = $fullBody;
    
    //Send away
    if(!$mail->send()){
        $error = $mail->ErrorInfo;
    }
    */
  }
?>
