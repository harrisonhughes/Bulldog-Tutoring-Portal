<?php
  //Begin session, set inactivity timout constant (2 hours), create constants for access control
  session_start();
  $TIME_OUT = 60 * 60 * 2;
  $ADMIN_CODE = 3;

  //If user is not set, access credentials are not set, last activity is not set, or last activity is beyond timeout length
  if(!isset($_SESSION['user']) || !isset($_SESSION['credentials']) || !isset($_SESSION['lastActivity']) || time() - $_SESSION['lastActivity'] > $TIME_OUT){

    //If last activity is set and beyond timeout length, set output message and send user to login page to be logged out
    if(isset($_SESSION['lastActivity']) && time() - $_SESSION['lastActivity'] > $TIME_OUT){
      $_SESSION['message']['login'] = "You were logged out due to inactivity";
    }
    header("Location: login.php");
    exit();
  }

  //If user has admin credentials, route to admin home page
  else if($_SESSION['credentials'] == $ADMIN_CODE){
    header("Location: admin.php");
    exit();
  }

  //Update last activity variable
  $_SESSION['lastActivity'] = time();
  
  include_once 'functions.php';
  //Define constants for password sizes 
  $MAX_LENGTH = 50;
  $MIN_LENGTH = 6;
  
  try{
    $pdo = connect(); 

    //User has submitted a form request
    if($_SERVER["REQUEST_METHOD"] == "POST"){

      //User has requested password change
      if(isset($_POST['enterPassword'])){
        //Get all information from previous session and current input types
        $email = test_input($_SESSION['user']);
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

            //Upload new user passcode to account using sql injection attack prevention steps
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE accounts SET hashed_password = ? WHERE email = ?";
            $result = $pdo->prepare($sql);
            $result->execute([$hashedPassword, $email]);

            //Provide feedback to user
            $_SESSION['message']['passReset'] = "Password successfully reset.";
            header("Location: account.php");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
          <a href="index.php"><span>Home</span></a>
          <a href="portal.php"><span>Portal</span></a>
          <a href="account.php"><span>Account</span></a>
          <a href="login.php"><span>Logout</span></a>
      </nav>
    </header>
    <main class = "credentialPage">
      <div>
        <form action="changePassword.php" method="post" class="credentialForm">
          <fieldset>
            <h1>Change Password</h1>
            <p id="recoverEmail" class="error">
              <?php 
              //Display error message if applicable
              if(isset($_SESSION['error']['recover'])){
                echo $_SESSION['error']['recover'];
                unset($_SESSION['error']['recover']);}
              ?>
            </p>
            <?php
                echo "
                <input type='password' placeholder='New Password' name='password' class='password' id='password'>
                <input type='password' placeholder='Confirm Password' name='confirmPassword' class='password' id='confirmPassword'>
                <input type='submit' name='enterPassword' value='Reset Password' /></button>";
            ?>
          </fieldset>
        </form>
      </div>
    </main>
    <footer>
    </footer>
  </body>
</html>
