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
  
  include_once 'functions.php';

  //"Null" value for our purposes in the semester table
  $DEFAULT_TIMESTAMP = "2000-01-01 00:00:00";

  //Try block to monitor all sql database requests
  try{
    $pdo = connect();
    
    //Obtain information on most recent (active) semester process using sql injection attack prevention steps
    $sql = "SELECT * FROM semesters
            ORDER BY term_code DESC
            LIMIT 1";
    $result = $pdo->query($sql);
    $semester = $result->fetch();
    $term = $semester['term_code'];

    //User has submitted a form request, beign semester transition
    if($_SERVER["REQUEST_METHOD"] == "POST"){

      //Course term increments by 50 '202410'
      $newTerm = $term + 50;

      //Begin new term with new term code using sql injection attack prevention steps
      $sql = "INSERT INTO semesters (term_code) VALUES (?)";
      $result = $pdo->prepare($sql);
      $result->execute([$newTerm]);

      //Delete all professor references remaining in database
      $sql = "DELETE FROM course_professors";
      $result = $pdo->query($sql);
      $result->execute();

      //Delete all referred tutor entries in database
      $sql = "DELETE FROM referred_tutors";
      $result = $pdo->query($sql);
      $result->execute();

      //Delete all accounts that have not logged on in over a year
      $sql = "DELETE FROM accounts
      WHERE last_activity < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
      $result = $pdo->prepare($sql);
      $result->execute();
  
      //Delete all opt out emails that have been in database for over 4 years (graduated)
      $sql = "DELETE FROM opt_outs
      WHERE date < DATE_SUB(NOW(), INTERVAL 4 YEAR)";
      $result = $pdo->prepare($sql);
      $result->execute();

      //Update page
      header("Location: newSemester.php");
      exit();
    }

    //Configure semester title information
    $termCode = "";
    if($term % 100 == 10){
      $termCode .= "Spring ";
    }
    else{
      $termCode .= "Fall ";
    }
    $termCode .= intdiv($term, 100);

    //Check if file has been uploaded this semester
    if($semester['file_uploaded'] != $DEFAULT_TIMESTAMP){
      $fileUploadDate = date("m/d/Y", strtotime($semester['file_uploaded']));
    }

    //Check if professor emails have been sent this semester
    if($semester['professor_emails'] != $DEFAULT_TIMESTAMP){
      $facultyEmailDate = date("m/d/Y", strtotime($semester['professor_emails']));
    }

    //Check if student emails have been sent this semester
    if($semester['student_emails'] != $DEFAULT_TIMESTAMP){
      $studentEmailDate = date("m/d/Y", strtotime($semester['student_emails']));
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
    <main id='newSemester'>
      <div>
      <nav>
          <div class="adminLink">
            <a href='admin.php'>Home</a>
          </div>
          <div class="adminLink">
            <span>Accounts</span>
            <div>
              <a href='studentSearch.php'>Student Accounts</a>
              <a href='facultySearch.php'>Professor Accounts</a>
              <a href='courseSearch.php'>Search by Course</a>
            </div>
          </div>
          <div class="adminLink">
            <span>Management</span>
            <div>
              <a href='addCourse.php'>Add a Course</a>    
              <a href='newSemester.php'>Transition Semesters</a>
            </div>
          </div>
        </nav>
      </div>
      <h1 class="mainHeader">Referral Management Page</h1>
      <p id="semesterError" class="error">
        <?php 
        //Display error messages if applicable
        if(isset($_SESSION['errors']['semester'])){
          echo $_SESSION['errors']['semester'];
          unset($_SESSION['errors']['semester']);}
        ?>
      </p>
      <?php
        //Custom heading to state the current semester
        echo "<h2>Current Referral Semester: {$termCode}</h2>";
      ?>
    <h3 class='stepHeader'>Step 1. Course Catalog Submission</h3>
    <p class='completionStatus' id='fileBlock'>
      <?php
        //Dispay correct information based on upload status of file
        if(!empty($fileUploadDate)){
          echo "Status: Complete! <em>{$fileUploadDate}</em></p>";
          $uploadedFile = true;
        }
        else{
          //Javascript parameter to pass (block id)
          $pBlockToUpdate = "\"fileBlock\"";
          
          echo "Status: Incomplete </p>
          <form action='readFile.php' method='post' enctype='multipart/form-data' onSubmit='showWaiting({$pBlockToUpdate})' class='transitionForm' id='fileForm'>
          <input type='file' name='fileUpload' id='fileUpload'>
          <input class='blueButton' type='submit' value='Submit File' name='submitReferrals'>
          </form>";
        }

        if(!empty($fileUploadDate)){
      ?>
    <h3 class='stepHeader'>Step 2. Send Faculty Emails</h3>
    <p class='completionStatus' id='professorBlock'>
      <?php
          //Dispay correct information based on professor email status
          if(!empty($facultyEmailDate)){
            echo "Status: Complete! <em>{$facultyEmailDate}</em></p>";
          }
          else{
            //Javascript parameter to pass (block id)
            $pBlockToUpdate = "\"professorBlock\"";

            //Javascript alert confirmation message upon button press
            $confirmMessage = "\"This will send an email to every professor that taught a course this semester. Please be sure that this is the right time to do so. Select OK to proceed.\"";
            echo "Status: Incomplete</p>
            <form action='facultyEmails.php' method='post' onSubmit='showWaiting({$pBlockToUpdate})' class='transitionForm' id='facultyEmails'>
            <input class='blueButton' type='submit' value='Send Emails' name='facultyReferrals' onclick='return confirmMessage({$confirmMessage});'>
        </form>";
          }
        }
        if(!empty($facultyEmailDate)){
      ?>
    <h3 class='stepHeader'>Step 3. Send Student Emails</h3>
    <p class='completionStatus' id='studentBlock'>
      <?php
          //Dispay correct information based on student email status
          if(!empty($studentEmailDate)){
            echo "Status: Complete! <em>{$studentEmailDate}</em></p>";
          }
          else{
            //Javascript parameter to pass (block id)
            $pBlockToUpdate = "\"studentBlock\"";

            //Javascript alert confirmation message upon button press
            $confirmMessage = "\"This will send an email to every student that has been referred by a professor in the previous step. Please be sure that you have given ample time for";
            $confirmMessage .= " professors to complete their referrals. Select OK to proceed.\"";

            echo "Status: Incomplete</p>
            <form action='studentEmails.php' method='post' onSubmit='showWaiting({$pBlockToUpdate})' class='transitionForm' id='studentEmails'>
            <input class='blueButton' type='submit' value='Send Emails' name='studentReferrals' onclick='return confirmMessage({$confirmMessage});'>
            </form>";
          }
        }
        //Dispay correct information based on student email status
        if(!empty($studentEmailDate)){
      ?>
      <?php
        //Javascript alert confirmation message upon button press
          $confirmMessage = "\"This is not reversible from the admin account page. Confirming this action means that you have completed all the steps of the previous semeter, and it is";
          $confirmMessage .= " time to begin the referral process for the new semester.\"";
          echo "<h3 class='stepHeader'>Transition to Next Semester</h3>
          <form action='newSemester.php' method='post' class='transitionForm' id='nextSemester'>
          <button class='blueButton' type='submit' name='nextSemester' onclick='return confirmMessage({$confirmMessage});'>Begin Next Semester</button>
          </form>";
        }
      ?>
    </main>
    <footer>
    </footer>
  </body>
</html>
