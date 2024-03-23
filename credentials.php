<?php
  include_once 'functions.php';
  session_start();

  try{
    $pdo = connect(); 
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
              $_SESSION['user'] = $email;
              header("Location: home.html");
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
            $_SESSION['errors']['email'] = "Email is not affiliated with a portal account";
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
        header("Location: login.html");
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

        //Ensure all fields have been filled out
        if(empty($email)){
          $validForm = false;
          $_SESSION['errors']['email'] = "Email cannot be blank";
        }
        if(empty($password)){
          $validForm = false;
          $_SESSION['errors']['password'] = "Password cannot be blank";
        }
        if(empty($confirmPassword)){
          $validForm = false;
          $_SESSION['errors']['confPassword'] = "Confirm password cannot be blank";
        }
        if(empty($firstname)){
          $validForm = false;
          $_SESSION['errors']['fname'] = "Firstname cannot be blank";
        }
        if(empty($lastname)){
          $validForm = false;
          $_SESSION['errors']['lname'] = "Lastname cannot be blank";
        }

        //All fields have been filled out
        if($validForm){

          //Select query with sql injection attack prevention steps - Get account with given email
          $sql = "SELECT * FROM accounts WHERE email = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email]);
          $user = $result->fetch();

          //Email is not already affiliated with a student account and password entries match
          if(!$user && $password == $confirmPassword){

            //Insert query with sql injection attack prevention steps - Add new account to database
            $sqlValues = [$email, password_hash($password, PASSWORD_DEFAULT), $firstname, $lastname];
            $valuePlaceholders = rtrim(str_repeat('?,', count($sqlValues)), ',');
            $sql = "INSERT INTO accounts (email, hashed_password, firstname, lastname) VALUES ({$valuePlaceholders})";
            $result = $pdo->prepare($sql);
            $result->execute($sqlValues);

            //User account created, begin session and return to home page
            $_SESSION['user'] = $email;
            header("Location: home.html");
            $pdo = null;
            exit();
          }
          else{

            //Email already exists in the database
            if($user){
              $_SESSION['errors']['email'] = "Email already linked to an account";
            }

            //Passwords entered do not match
            else{
              $_SESSION['errors']['confPassword'] = "Password fields do not match";
            }
          }
        }

        //Route back to account creation page to display error messages
        header("Location: createAccount.html");
      }
    }
  }

  //Unable to create connection with database
  catch (PDOException $e){
    die( $e->getMessage());
  }
  $pdo = null;
?>