<?php
  //Begin session, set inactivity timout constant (2 hours), create constants for access control
  session_start();
  $TIME_OUT = 60 * 60 * 2;
  $PROFESSOR_CODE = 2;
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

  //If user has professor credentials, route to professor account referral page
  else if($_SESSION['credentials'] == $PROFESSOR_CODE){
    header("Location: reference.php");
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

  //Try block to test all database operations within
  try{
    $pdo = connect(); 

    //Get email from user session variable initialized upon login
    $email = test_input($_SESSION['user']);

    //If a form has just been submitted
    if($_SERVER["REQUEST_METHOD"] == "POST"){

      //Delete course form active tutors for current student
      if(isset($_POST['removeCourse'])){
        $courseId = test_input($_POST['removeCourse']); //Value of submit button

        //Prepare and execute mysql query to delete record from table and prevent sql injection attacks
        $sql = "DELETE FROM active_tutors WHERE course_id = ? AND email = ?";
        $result = $pdo->prepare($sql);
        $result->execute([$courseId, $email]);
      }

      //Referral has been accepted, remove from referred tutors and add to active tutors
      else if(isset($_POST['acceptReferral'])){
        $courseId = test_input($_POST['acceptReferral']); //Value of submit button

        //Prepare and execute mysql query to insert record from table and prevent sql injection attacks (Ignore query if duplicate)
        $sql = "INSERT IGNORE INTO active_tutors (email, course_id) VALUES (?, ?)";
        $result = $pdo->prepare($sql);
        $result->execute([$email, $courseId]);

        //Prepare and execute mysql query to delete record from table and prevent sql injection attacks
        $sql = "DELETE FROM referred_tutors WHERE course_id = ? AND email = ?";
        $result = $pdo->prepare($sql);
        $result->execute([$courseId, $email]);
      }

      //Referral has been declined, remove from referred tutors table
      else if(isset($_POST['declineReferral'])){
        $courseId = test_input($_POST['declineReferral']); //Value of submit button

        //Prepare and execute mysql query to delete record from table and prevent sql injection attacks
        $sql = "DELETE FROM referred_tutors WHERE course_id = ? AND email = ?";
        $result = $pdo->prepare($sql);
        $result->execute([$courseId, $email]);
      }

      //Account deleted, remove from database, route user to login page where they ar eautomatically logged out
      else if(isset($_POST['deleteAccount'])){

        //Prepare and execute mysql query to delete record from table and prevent sql injection attacks
        $sql = "DELETE FROM accounts WHERE email = ?";
        $result = $pdo->prepare($sql);
        $result->execute([$email]);

        //Create feedback message and route user to login page
        session_unset();
        $_SESSION['message']['login'] = "Account and all linked information has been deleted.";
        header("Location: login.php");
        exit();
      }
    }

    //Select query with sql injection attack prevention steps - Get account with given email
    $sql = "SELECT * FROM accounts WHERE email = ?";
    $result = $pdo->prepare($sql);
    $result->execute([$email]);
    $user = $result->fetch();

    //Build full name
    $uName = $user['firstname'] . " " . $user['lastname'];

    //Selecting all course information for the courses that this current user actively tutors
    $sql = "SELECT c.* FROM courses c 
    JOIN active_tutors a_t ON c.id = a_t.course_id
    JOIN accounts a ON a.email = a_t.email
    WHERE a.email = ?";

    //Prepare and execute mysql query to select record from table and prevent sql injection attacks
    $result = $pdo->prepare($sql);
    $result->execute([$email]);
    $activeCourses = $result->fetchAll();

    //Selecting all course information for the courses that this current user has a tutoring referral for
    $sql = "SELECT c.* FROM courses c 
    JOIN referred_tutors r_t ON c.id = r_t.course_id
    JOIN accounts a ON a.email = r_t.email
    WHERE a.email = ?";

    //Prepare and execute mysql query to select record from table and prevent sql injection attacks
    $result = $pdo->prepare($sql);
    $result->execute([$email]);
    $referredCourses = $result->fetchAll();
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
    <title>Bulldog Tutoring Account</title>
    <link rel="stylesheet" href="styles.css"/>
    <script src="actions.js"></script>
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
          <a href="account.php"><span class='active'>Account</span></a>
          <a href="login.php"><span>Logout</span></a>
      </nav>
    </header>
    <main id='accountPage'>
      <?php
        echo "<h1 class='mainHeader'>{$uName}'s Account</h1>";
      ?>
    <p id="recoverEmail" class="message">
      <?php 
      //Display error message if applicable
      if(isset($_SESSION['message']['passReset'])){
        echo $_SESSION['message']['passReset'];
        unset($_SESSION['message']['passReset']);}
      ?>
    </p>
      <div>
        <div class="accountBubble">
            <h2>Personal Information</h2>
              <table class='accountTable'>
                <tbody>
                <?php
                  $accountType = "Private";
                  if($user['account_type'] == 1){
                    $accountType = "Scholarship";
                  }

                  echo 
                  "<tr><th>Name</th>
                  <td>{$uName}</td></tr>
                  <tr><th>Email</th>
                  <td>{$email}</td></tr>
                  <tr><th>Account Type</th>
                  <td>{$accountType}</td></tr>";
                ?>
                </tbody>
              </table>
            <?php

              //Display private tutoring information
              if($accountType == "Private"){
            ?>
            <h2>What is a Private Tutor?</h2>
            <ul>
              <li>No affiliation with any tutoring body</li>
              <li>No obligation to tutor unless you desire to</li>
              <li>Accept referrals to have name/email listed in Portal</li>
              <li>Remove active courses at any time</li>
            </ul>
            <p>Interested in becoming an academic peer tutor? <a href="https://excellence.truman.edu/tutoring/cae-tutoring-center/apply-to-be-a-tutor/">Click here!</a></p>
            <?php
              }

              //Display scholarship tutoring information
              else{
            ?>
            <h2>Scholarship Tutor Roles</h2>
            <ul>
              <li>Scheduling and appointments managed through tutoring center</li>
              <li>Keep all current tutoring courses updated</li>
              <li>Accept referrals to have name/email listed in Portal</li>
              <li>Remove active courses from portal at any time</li>
              </ul>
            <?php
              }
            ?>
        </div>
        <div class="accountBubble">
          <div>
            <h2>Active Tutoring Positions</h2>
            <form action="account.php" method="post" class="accountForm">
              <fieldset>
                <table class='accountTable'>
                  <tbody>
                  <?php
                  //No active tutoring courses
                  if(empty($activeCourses)){
                    echo "<tr><td colspan='2'>You have no active courses</td></tr>";
                  }

                  //Loop and display all active tutoring courses with button to give option to remove
                  else{

                    //Message to be shown in javascript alarm to act as a confirmation message
                    $confirmMessage = "\"Are you sure you would like to remove yourself from the tutoring portal for this course? Select OK to proceed.\"";

                    foreach($activeCourses as $course){
                      $subject = $course['subject'];
                      $courseCode = $course['course_code'];
                      $id = $course['id'];
                      echo 
                      "<tr><td><b>{$subject} {$courseCode}</b></td>
                      <td><button type='submit' class='blueButton' name='removeCourse' value='{$id}' onclick='return confirmMessage({$confirmMessage});'>Remove Course</button></td></tr>";
                    }
                  }
                  ?>
                  </tbody> 
                </table>
              </fieldset>
            </form>
          </div>
          <div>
            <h2>Active Tutoring Referrals</h2>
            <form action="account.php" method="post" class="accountForm">
              <fieldset>
                <table class='accountTable'>
                  <tbody>
                  <?php

                  //No referred tutoring courses
                  if(empty($referredCourses)){
                    echo "<tr><td colspan='2'>You have no referrals at this time</td></tr>";
                  }
                  else{

                    //Message to be shown in javascript alarm to act as a confirmation message
                    $confirmMessage = "\"Are you sure you would like to decline this tutoring referral? Select OK to proceed.\"";

                    //Loop and display all referred tutoring courses with buttons to give option to remove or accept
                    foreach($referredCourses as $course){
                      $subject = $course['subject'];
                      $courseCode = $course['course_code'];
                      $id = $course['id'];

                      echo 
                      "<tr><td><b>{$subject} {$courseCode}</b></td>
                      <td><button class='blueButton' type='submit' name='acceptReferral' value='{$id}'>Accept Referral</button></td>
                      <td><button class='blueButton' type='submit' name='declineReferral' value='{$id}' onclick='return confirmMessage({$confirmMessage});'>Decline Referral</button></td></tr>";
                    }
                  }
                  ?>
                  </tbody> 
                </table>
              </fieldset>
            </form>
          </div>
        </div>
      </div>
    </main>
    <footer>
    <form action='account.php' method='post' class="deleteForm">
        <fieldset>
          <?php

            //Message to be shown in javascript alarm to act as a confirmation message
            $confirmMessage = "\"Deleting your account will result in all of your information being removed from the database, and is not reversible. Are you sure you want to proceed?\"";

            //Delete account button
            echo "<button type='submit' name='deleteAccount' onclick='return confirmMessage({$confirmMessage});'>DELETE ACCOUNT</button>";
          ?>
        </fieldset>
      </form>
      <form action='changePassword.php' method='post' id="changePassword">
        <fieldset>
            <button type='submit' name='changePassword'>Change Password</button>
        </fieldset>
      </form>
    </footer>
  </body>
</html>