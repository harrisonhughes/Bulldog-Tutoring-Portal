<?php
  include_once 'functions.php';
  session_start();

  //Define constants for truman email account validation and password sizes 
  $TRUMAN_EMAIL = "@truman.edu";
  $MAX_LENGTH = 50;
  $MIN_LENGTH = 6;
  
  try{
    $pdo = connect(); 

    //User has submitted a form request
    if($_SERVER["REQUEST_METHOD"] == "POST"){

      //Initial button has been pressed, user has reset their passcode
      if(isset($_POST['recoverEmail'])){
        $email = test_input($_POST['email']);
        $validForm = true;

        //Validate entered email account
        if(empty($email)){
          $validForm = false;
          $_SESSION['error']['recover'] = "Email cannot be blank";
        }
        else if(substr($email, -11) != $TRUMAN_EMAIL){ //Email must be truman.edu type
          $validForm = false;
          $_SESSION['error']['recover'] = "Email must be from a truman account";
        }
        else if(strlen($email) > $MAX_LENGTH){
          $validForm = false;
          $_SESSION['error']['recover'] = "Email must be no longer than {$MAX_LENGTH} characters";
        }

        //User has entered a valid email type
        if($validForm){

          //Find the email account that they entered in the text box using sql injection attack prevention steps
          $sql = "SELECT * FROM accounts WHERE email = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email]);
          $user = $result->fetch();

          //If they exist, lets reset it
          if($user){

            //Generate random passowrd to use for a recovery code
            $randomPassword = randomPassword(12);

            //Update account to have the recovery code temporarily be the password using sql injection attack prevention steps
            $sql = "UPDATE accounts SET hashed_password = ? WHERE email = ?";
            $result = $pdo->prepare($sql);
            $result->execute([password_hash($randomPassword, PASSWORD_DEFAULT), $email]);

            //Build full name for email
            $name = $user['firstname'] . " " . $user['lastname'];
            
            //Build all email elements
            $emailBody = $user['firstname'] . ",<br>A request has been made to reset your account password on the Bulldog Tutoring Portal website. Your recovery code is: <b>{$randomPassword}</b><br><br>
            Copy this code and paste it into the account recovery page, along with your desired new password, to reset your account.
            <br><br><b>The Bulldog Tutoring Portal</b><br><em>Truman State University<br>BulldogTutoring@outlook.com</em>";
    
            $emailHeader = "Temporary Portal Password";

            sendEmail($email, $name, $emailHeader, $emailBody);
            $_SESSION['error']['recover'] = "Recovery code sent";

            //Save email for the next step
            $_SESSION['recoveryEmail'] = $email;
          } 

          //Email not linked to an account in the database
          else{
            $_SESSION['error']['recover'] = "The email entered is not affiliated with an account.";    
          }
        }
      }

      //User has already reset password, now they are making a new one
      else if(isset($_POST['enterPassword'])){
        //Get all information from previous session and current input types
        $email = test_input($_SESSION['recoveryEmail']);
        $code = test_input($_POST['recoveryCode']);
        $password = test_input($_POST['password']);
        $confirmPassword = test_input($_POST['confirmPassword']);
        $validForm = true;

        //Validate all information entered for accuracy and correct type
        if(empty($password)){
          $validForm = false;
          $_SESSION['error']['recover'] = "Password cannot be blank";
        }
        else if(strlen($password) < $MIN_LENGTH){
          $validForm = false;
          $_SESSION['error']['recover'] = "Password must be at least {$MIN_LENGTH} characters";
        }

        if(empty($confirmPassword)){
          $validForm = false;
          $_SESSION['error']['recover'] = "Confirm password cannot be blank";
        }
        else if(strlen($confirmPassword) < $MIN_LENGTH){
          $validForm = false;
          $_SESSION['error']['recover'] = "Password must be at least {$MIN_LENGTH} characters";
        }
        else if($password !== $confirmPassword){
          $validForm = false;
          $_SESSION['error']['recover'] = "Passwords entered do not match";
        }

        //User has correctly reset their passcode barring an incorrect recovery code
        if($validForm){

          //Select query with sql injection attack prevention steps - Get account with given email
          $sql = "SELECT * FROM accounts WHERE email = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email]);
          $user = $result->fetch();

          //Assert credentials for the user
          if($user){

            //User entered correct recovery code from email
            if(password_verify($code, $user['hashed_password'])){

              //Upload new user passcode to account using sql injection attack prevention steps
              $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
              $sql = "UPDATE accounts SET hashed_password = ? WHERE email = ?";
              $result = $pdo->prepare($sql);
              $result->execute([$hashedPassword, $email]);

              //Provide feedback to user
              $_SESSION['error']['recover'] = "Passwords successfully reset.";
              unset($_SESSION['recoveryEmail']);
            }

            //Recovery code entered does not match user passcode
            else{
              $_SESSION['error']['recover'] = "Recovery code does not match records.";
              header("Location: recoverAccount.php");
              exit();
            }
          }
        }
      }
    }
  }

  //Unable to create connection with database
  catch(PDOException $e){
    $error = $e->getMessage();
    echo "<p>Critical Error (Database):<br><br>{$error}<br><br>Please save this message and inform the head website administrator as soon as possible.</p>";
    exit(); 
  }
  $pdo = null;
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Bulldog Tutoring Portal</title>
    <link rel="stylesheet" href="styles.css"/>
  </head>
  <body>
  <header>
      <div>
        <img src="https://seeklogo.com/images/T/truman-bulldogs-logo-819371EABE-seeklogo.com.png">
        <span>The Bulldog Tutoring Portal</span>
      </div>
      <nav>
          <a href="login.php"><span>Login</span></a>
      </nav>
    </header>
    <main class = "credentialPage">
      <div>
        <form action="recoverAccount.php" method="post" class="credentialForm">
          <fieldset>
            <h1>Recover Account</h1>
            <p id="recoverEmail" class="error">
              <?php 
              //Display error message if applicable
              if(isset($_SESSION['error']['recover'])){
                echo $_SESSION['error']['recover'];
                unset($_SESSION['error']['recover']);}
              ?>
            </p>
            <?php
              //This means a user has not initiated a recovery proces yet
              if(empty($_SESSION['recoveryEmail'])){
                echo "<input type='text' placeholder='Email' name='email' id='email'>
                <input type='submit' value='Send Code' name='recoverEmail'/>";
              }

              //User has entered an email to reset their passcode just now
              else{
                echo "<input type='text' placeholder='Recovery Code' name='recoveryCode' id='recoveryCode'>
                <input type='password' placeholder='New Password' name='password' class='password' id='password'>
                <input type='password' placeholder='Confirm Password' name='confirmPassword' class='password' id='confirmPassword'>
                <input type='submit' name='enterPassword' value='Reset Password' /></button>";
              }
            ?>
          </fieldset>
        </form>
      </div>
    </main>
    <footer>
    </footer>
  </body>
</html>
