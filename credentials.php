<?php
  include_once 'functions.php';
  session_start();

  $TRUMAN_EMAIL = "@truman.edu"; //Only allow this email format
  $VALID_NAME = '/^[a-zA-Z\'\- .]+$/'; //Name must be of a valid configuration
  $MAX_LENGTH = 50; //Max length of name, password, and email
  $MIN_LENGTH = 6; //Min length of password

  try{
    $pdo = connect(); 

    //User has submitted a form
    if($_SERVER["REQUEST_METHOD"] == "POST"){
      
      //Login submission has been activated
      if(isset($_POST['loginForm'])){

        //Filter the user text to prevent html script injection attacks
        $email = test_input($_POST['email']);
        $password = test_input($_POST['password']);
        
        //Assert user has filled both the email and password text inputs
        if(!empty($email) && !empty($password)){

          //Select query with sql injection attack prevention steps - Get account with given email
          $sql = "SELECT * FROM accounts WHERE email = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email]);
          $user = $result->fetch();
  
          //Assert credentials for the user
          if($user){

            //User fully authenticated, begin session and return to home page
            if(password_verify($password, $user['hashed_password'])){

              //Create session variables for email, access control, and last activity
              $_SESSION['user'] = $email;
              $_SESSION['credentials'] = $user['account_type'];
              $_SESSION['lastActivity'] = time();

              $timeStamp = date('Y-m-d H:i:s');

              //Update query with sql injection attack prevention steps - update last activity
              $sql = "UPDATE accounts SET last_activity = ? WHERE email = ?";
              $result = $pdo->prepare($sql);
              $result->execute([$timeStamp, $email]);

              header("Location: index.php");
              $pdo = null;
              exit();
            }

            //Password does not match 
            else{
              $_SESSION['errors']['password'] = "Incorrect password entered";
            }
          }

          //Email not in database
          else{
            $_SESSION['errors']['email'] = "Email is not affiliated with an account";
          }
        }
        //User did not fill both text inputs
        else{
          if(empty($email)){
            $_SESSION['errors']['email'] = "Email cannot be blank";
          }
          if(empty($password)){
            $_SESSION['errors']['password'] = "Password cannot be blank";
          }
        }

        //Route back to login page to display error messages
        header("Location: login.php");
      }

      //Account creation submission has been activated
      else if(isset($_POST['createForm'])){

        // Filter the user text to prevent script injection
        $email = test_input($_POST['email']);
        $password = test_input($_POST['password']);
        $confirmPassword = test_input($_POST['confirmPassword']);
        $firstname = test_input($_POST['firstname']);
        $lastname = test_input($_POST['lastname']);
        $validForm = true;

        //Ensure all form elements are validated according to specifications
        if(empty($email)){
          $validForm = false;
          $_SESSION['errors']['email'] = "Email cannot be blank";
        }
        else if(substr($email, -11) != $TRUMAN_EMAIL){ //Must end in @truman.edu
          $validForm = false;
          $_SESSION['errors']['email'] = "Email must be from a Truman account";
        }
        else if(strlen($email) > $MAX_LENGTH){
          $validForm = false;
          $_SESSION['errors']['email'] = "Email must be no longer than {$MAX_LENGTH} characters";
        }

        if(empty($password)){
          $validForm = false;
          $_SESSION['errors']['password'] = "Password cannot be blank";
        }
        else if(strlen($password) < $MIN_LENGTH){
          $validForm = false;
          $_SESSION['errors']['password'] = "Password must be at least {$MIN_LENGTH} characters";
        }

        if(empty($confirmPassword)){
          $validForm = false;
          $_SESSION['errors']['confPassword'] = "Confirm password cannot be blank";
        }
        else if(strlen($confirmPassword) < $MIN_LENGTH){
          $validForm = false;
          $_SESSION['errors']['confPassword'] = "Password must be at least {$MIN_LENGTH} characters";
        }
        else if($password !== $confirmPassword){
          $validForm = false;
          $_SESSION['errors']['password'] = "Passwords entered do not match";
        }

        if(empty($firstname)){
          $validForm = false;
          $_SESSION['errors']['fname'] = "Firstname cannot be blank";
        }
        else if(!preg_match($VALID_NAME, $firstname)){//Name must be of a valid name format
          $validForm = false;
          $_SESSION['errors']['fname'] = "Name must be in a valid format";
        }
        else if(strlen($email) > $MAX_LENGTH){
          $validForm = false;
          $_SESSION['errors']['fname'] = "Firstname must be no longer than {$MAX_LENGTH} characters";
        }

        if(empty($lastname)){
          $validForm = false;
          $_SESSION['errors']['lname'] = "Lastname cannot be blank";
        }
        else if(!preg_match($VALID_NAME, $lastname)){ //Name must be of a valid name format
          $validForm = false;
          $_SESSION['errors']['lname'] = "Name must be in a valid format";
        }
        else if(strlen($email) > $MAX_LENGTH){
          $validForm = false;
          $_SESSION['errors']['lname'] = "Lastname must be no longer than {$MAX_LENGTH} characters";
        }

        //All fields have been filled out
        if($validForm){

          //Select query with sql injection attack prevention steps - Get account with given email
          $sql = "SELECT * FROM accounts WHERE email = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email]);
          $user = $result->fetch();

          //Email is not already affiliated with a student account and password entries match
          if(!$user){

            //Insert query with sql injection attack prevention steps - Add new account to database
            $sqlValues = [$email, password_hash($password, PASSWORD_DEFAULT), $firstname, $lastname];
            $valuePlaceholders = rtrim(str_repeat('?,', count($sqlValues)), ',');
            $sql = "INSERT INTO accounts (email, hashed_password, firstname, lastname) VALUES ({$valuePlaceholders})";
            $result = $pdo->prepare($sql);
            $result->execute($sqlValues);

            //Begin session with email, access control, and last activity
            $_SESSION['user'] = $email;
            $_SESSION['credentials'] = 1;
            $_SESSION['lastActivity'] = time();

            header("Location: index.php");
            $pdo = null;
            exit();
          }

          //Requested email retrieved a value from the database
          else{
            $_SESSION['errors']['email'] = "Email already linked to an account";
          }
        }

        //Route back to account creation page to display error messages
        header("Location: createAccount.php");
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