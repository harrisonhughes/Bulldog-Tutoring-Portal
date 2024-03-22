<?php
  include_once 'functions.php';
  session_start();

  try {
    $pdo = connect(); 
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      
      //Login submission has been activated
      if(isset($_POST['loginForm'])){

        //Filter the user text to prevent html script injection attacks
        $email = test_input($_POST['email']);
        $password = test_input($_POST['password']);
        
        //Assert user has filled both the email and password text inputs
        if(!empty($email) && !empty($password)){

          //Select query with sql injection attack prevention steps
          $sql = "SELECT * FROM student_accounts WHERE email = ?";
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

            }
          }

          //Email not in database
          else{

          }
        }
        //User did not fill both text inputs
        else{
          if(empty($email)){

          }
          if(empty($password)){

          }
        }
      }

      //Account creation submission has been activated
      else if(isset($_POST['createForm'])){

        // Filter the user text to prevent script injection
        $email = test_input($_POST['email']);
        $password = test_input($_POST['password']);
        $confirmPassword = test_input($_POST['confirmPassword']);
        $firstname = test_input($_POST['firstname']);
        $lastname = test_input($_POST['lastname']);

        //Select query with sql injection attack prevention steps
        $sql = "SELECT * FROM student_accounts WHERE email = ?";
        $result = $pdo->prepare($sql);
        $result->execute([$email]);
        $user = $result->fetch();

        //Email is not already affiliated with a student account and password entries match
        if(!$user && $password == $confirmPassword){

          //Insert query with sql injection attack prevention steps
          $sqlValues = [$email, password_hash($password, PASSWORD_DEFAULT), $firstname, $lastname];
          $valuePlaceholders = rtrim(str_repeat('?,', count($sqlValues)), ',');
          $sql = "INSERT INTO student_accounts (email, hashed_password, firstname, lastname) VALUES ({$valuePlaceholders})";
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

          }

          //Passwords entered do not match
          else{

          }
        }
      }
    }
  }

  //Unable to create connection with database
  catch (PDOException $e){
    die( $e->getMessage());
  }
  $pdo = null;
?>