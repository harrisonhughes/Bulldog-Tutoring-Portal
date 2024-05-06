<?php 
  //Begin session, set inactivity timout constant (2 hours), configure access control value
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

  //If credentials are not set or user does not have admin credentials, reroute to home page
  else if(!isset($_SESSION['credentials']) || $_SESSION['credentials'] != $ADMIN_CODE){
    header("Location: index.php");
  }

  //Update last activity variable
  $_SESSION['lastActivity'] = time();
  
  include 'functions.php';

  //Constants for default professor account creation and account type specification
  $PROFESSOR_ACCOUNT = 2;
  //$DEFAULT_PROF_PASSWORD = getenv('DEFAULT_PROF');
  $DEFAULT_PROF_PASSWORD = "dajdbjsabdadbad89312";
  $URL = "https://bulldogtutoringportal.com/login.php";

  //Return to newSemester while executing
  header("Location: newSemester.php"); 
  
try{
  $pdo = connect();
    
  //Faculty email button has been pressed
  if(isset($_POST['facultyReferrals'])){

    //Get all professor accounts using sql injection attack prevention steps
    $sql = "SELECT * FROM accounts WHERE account_type = ?";
    $result = $pdo->prepare($sql);
    $result->execute([$PROFESSOR_ACCOUNT]);
    $professorAccounts = $result->fetchAll();

    //Loop through each professor account to send an email
    foreach($professorAccounts as $professor){

      //Get the number of professor referrals the current account has using sql injection attack prevention steps
      $sql = "SELECT COUNT(*) FROM course_professors WHERE email = ?";
      $result = $pdo->prepare($sql);
      $result->execute([$professor['email']]);
      $referralCount = $result->fetchColumn();

      //Configure plural vs singular
      if($referralCount > 0){
        $plural = "";
        if($referralCount > 1){
          $plural = "es";
        }

        //Url for the login link

        //Create message body
        $emailBody = "Dear Professor {$professor['lastname']},<br><br>We are the Bulldog Tutoring Portal, and our goal is to inspire and facilitate a greater level of peer-to-peer learning here at Truman State.<br><br>
        Our platform aims to streamline the entire tutoring recruitment process from start to finish: your role is simply to recommend students that you believe possess the right blend of 
        ability and personality to be a tutor for each of your current classes. The entire process should take you no more than 5 minutes - you need only enter student email addresses to complete 
        your obligation.<br><br>

        We have on record that you are teaching <b>{$referralCount}</b> class{$plural} this semester. To participate, please <a href='{$URL}'>sign in</a> 
        with your faculty email to begin recommending.";

        //If professor account has just been created, assign a random password
        if(password_verify($DEFAULT_PROF_PASSWORD, $professor['hashed_password'])){

          //Create random password and update email
          $randomPassword = randomPassword(12);
          $emailBody .= " Your account has been created for you with the temporary password: <b>{$randomPassword}</b>";

          //Assign updated password to account
          $sql = "UPDATE accounts SET hashed_password = ? WHERE email = ?";
          $result = $pdo->prepare($sql);
          $result->execute([password_hash($randomPassword, PASSWORD_DEFAULT), $professor['email']]);
        }

        $emailBody .= "<br><br>Thank you for your participation!";

        $emailHeader = "Truman State Tutoring";

        $name = $professor['firstname'] . " " . $professor['lastname'];

        sendEmail($professor['email'], $name, $emailHeader, $emailBody);
      }
    }
  }

    // Update current semester transition status row for professor emails sent, retrieve most recent semester
    $sql = "SELECT * FROM semesters
    ORDER BY term_code DESC
    LIMIT 1";
    $result = $pdo->query($sql);
    $semester = $result->fetch();
  
    //Get current term code and curren time
    $term = $semester['term_code'];
    $currentTimestamp = date("Y-m-d H:i:s");
  
    //Update semeser table with current time
    $sql = "UPDATE semesters SET professor_emails = '{$currentTimestamp}' WHERE term_code = '{$term}'";
    $result = $pdo->prepare($sql);
    $result->execute();
  
    header("Location: newSemester.php"); 
}

//Unable to create connection with database
catch(PDOException $e){
  $error = $e->getMessage();
  echo "<p>Critical Error (Database):<br><br>{$error}<br><br>Please save this message and inform the head website administrator as soon as possible.</p>";
  exit(); 
}
$pdo = null;
?>