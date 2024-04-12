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

  //Constants for account type in account table
  $SCHOLARSHIP_CODE = 1;
  $PRIVATE_CODE = 0;

  include_once 'functions.php';

  try{
    $pdo = connect();

    //Enter course codes to second select box once first select box is entered/changed ONLY ACCESSED THROUGH JAVASCRIPT AJAX REQUEST
    if(isset($_GET['subject'])){
      $subject = test_input($_GET['subject']);

      //Select query with sql injection attack prevention steps - Get all course codes for specific subject
      $sql = "SELECT * FROM courses WHERE subject = ? ORDER BY course_code";
      $result = $pdo->prepare($sql);
      $result->execute([$subject]);
      $courses = $result->fetchAll();

      //Dynamically output each course code for the given subject
      echo "<option disabled selected value></option>";
      foreach($courses as $course){
        echo "<option>" . $course['course_code'] . "</option>";
      }
      exit();
    }

    //User has just navigated from student search
    if(isset($_SESSION['editInfo'])){

      //Retrieve the selected account from previous page
      $email = test_input($_SESSION['editInfo']);

      //User has submitted a form 
      if($_SERVER["REQUEST_METHOD"] == "POST"){

        //Suer has changed the acccount type (private/scholarship) of the account
        if(isset($_POST['editType'])){

          //Change the account type from scholarship to provate or vice versa
          $sql = "UPDATE accounts
                  SET account_type = CASE
                    WHEN account_type = {$SCHOLARSHIP_CODE} THEN {$PRIVATE_CODE}
                    WHEN account_type = {$PRIVATE_CODE} THEN {$SCHOLARSHIP_CODE}
                    ELSE account_type
                    END
                  WHERE email = ?";
            
            $result = $pdo->prepare($sql);
            $result->execute([$email]);

          //Update page
          header("Location: displayStudent.php");
          exit();
        }

         //User has deleted this account
        else if(isset($_POST['deleteAccount'])){

          //Delete query with sql injection attack prevention steps
          $sql = "DELETE FROM accounts WHERE email = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email]);

          //Provide feedback and return to admin home
          $_SESSION['messages']['admin'] = "Account successfully deleted.";
          header("Location: admin.php");
          exit();
        }

        //User has removed an active course from this account
        else if(isset($_POST['removeActive'])){
          $courseId = test_input($_POST['removeActive']);

          //Delete query with sql injection attack prevention steps
          $sql = "DELETE FROM active_tutors WHERE email = ? AND course_id = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email, $courseId]);  

          //Update page
          header("Location: displayStudent.php");
          exit();
        }

        //User has removed a referrred course for this account
        else if(isset($_POST['removeReferral'])){
          $courseId = test_input($_POST['removeReferral']);

          //Delete query with sql injection attack prevention steps
          $sql = "DELETE FROM referred_tutors WHERE email = ? AND course_id = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email, $courseId]);  

          //Update page
          header("Location: displayStudent.php");
          exit();
        }

        //User has added an active course to this account
        else if(isset($_POST['addActive'])){
          //Obtain user entered values
          $subject = $_POST['subjectActive'];
          $courseCode = $_POST['courseCodeActive'];

          //Prevent change if either select box was empty
          if(empty($subject) || empty($courseCode)){
            $_SESSION['errors']['studentEdit'] = "Must select a subject and course code to add an active course.";
            header("Location: displayStudent.php");
            exit();
          }

          //Select query with sql injection attack prevention steps
          $sql = "SELECT id FROM courses WHERE subject = ? AND course_code = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$subject, $courseCode]);  
          $id = $result->fetch()[0];

          //Insert into active tutors with sql injection attack prevention
          $sql = "INSERT IGNORE INTO active_tutors (email, course_id) VALUES (?, ?)";
          $result = $pdo->prepare($sql);
          $result->execute([$email, $id]);

          //Remove from referred tutors with sql injection attack prevention (if applicable)
          $sql = "DELETE FROM referred_tutors WHERE email = ? AND course_id = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email, $id]);  

          //Update page
          header("Location: displayStudent.php");
          exit();
        }

        //Add referred course for this account
        else if(isset($_POST['addReferral'])){

          //Obtain user-input values
          $subject = $_POST['subjectReferral'];
          $courseCode = $_POST['courseCodeReferral'];

          //Do not proceed if at least one of the courses is empty
          if(empty($subject) || empty($courseCode)){
            $_SESSION['errors']['studentEdit'] = "Must select a subject and course code to add a new referral.";
            header("Location: displayStudent.php");
            exit();
          }

          //Select query with sql injection attack prevention steps
          $sql = "SELECT id FROM courses WHERE subject = ? AND course_code = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$subject, $courseCode]);  
          $id = $result->fetch()[0];

          //Select current course from active table with sql injection attack prevention stepe
          $sql = "SELECT * FROM active_tutors WHERE email = ? AND course_id = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email, $id]);  
          $alreadyActive = $result->fetch();

          //If course is not in active table, enter it into database
          if(!$alreadyActive){

            //Enter course into referred tutors table for this account with sql injection attack prevention stepe
            $sql = "INSERT IGNORE INTO referred_tutors (email, course_id) VALUES (?, ?)";
            $result = $pdo->prepare($sql);
            $result->execute([$email, $id]);  
          }

          //User is already an active tutor for this course, no need to refer them as well
          else{

            //Explain error and update page
            $_SESSION['errors']['studentEdit'] = "Student is already an active tutor for that course.";
            header("Location: displayStudent.php");
            exit();
          }

          //Update page
          header("Location: displayStudent.php");
          exit();
        }
      }

      //Select query with sql injection attack prevention steps - Get account with given email
      $sql = "SELECT * FROM accounts WHERE email = ?";
      $result = $pdo->prepare($sql);
      $result->execute([$email]);
      $user = $result->fetch();

      //Obtain user information
      $uName = $user['firstname'] . " " . $user['lastname'];
      $email = $user['email'];

      //Configure current account type and message to display for user to switch this account to opposite student account type
      $accountType = "Private";
      $oppositeType ="Scholarship";
      if($user['account_type'] == 1){
        $accountType = "Scholarship";
        $oppositeType ="Private";
      }

      //Select all course information for courses this account tutors
      $sql = "SELECT c.* FROM courses c 
      JOIN active_tutors a_t ON c.id = a_t.course_id
      JOIN accounts a ON a.email = a_t.email
      WHERE a.email = ?";
  
      //Select query with sql injection attack prevention steps
      $result = $pdo->prepare($sql);
      $result->execute([$email]);
      $activeCourses = $result->fetchAll();
  
      //Select all course information for courses this account is referred for
      $sql = "SELECT c.* FROM courses c 
      JOIN referred_tutors r_t ON c.id = r_t.course_id
      JOIN accounts a ON a.email = r_t.email
      WHERE a.email = ?";
  
      //Select query with sql injection attack prevention steps
      $result = $pdo->prepare($sql);
      $result->execute([$email]);
      $referredCourses = $result->fetchAll();

      //Select all unique subjects in database
      $sql = "SELECT DISTINCT subject FROM courses ORDER BY subject";
      $result = $pdo->prepare($sql);
      $result->execute();
      $courselist = $result->fetchAll();

      //Build javascript funciton strings to nest in html forms
      $activeJavascript = "\"getCourseCodes('subjectActive', 'courseCodeActive')\"";
      $referredJavascript = "\"getCourseCodes('subjectReferral', 'courseCodeReferral')\"";
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
    <main class='display'>
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
      <h1 class='mainHeader'>Student Management Page</h1>
      <p id="studentEditError" class="error">
        <?php 
        //Display errors if applicable
        if(isset($_SESSION['errors']['studentEdit'])){
          echo $_SESSION['errors']['studentEdit'];
          unset($_SESSION['errors']['studentEdit']);}
        ?>
      </p>
      <form action='displayStudent.php' method='post' id='editDisplayForm'>
        <fieldset>
          <div class='accountBubble'>
          <h2>Profile</h2>
            <table id="studentAccount">
              <?php
                //Display personal information
                echo 
                "<tr><th>Name</th>
                <td>{$uName}</td></tr>
                <tr><th>Email</th>
                <td>{$email}</td></tr>
                <tr><th>Tutoring Type</th>
                <td>{$accountType}</td></tr>";
              ?>
            </table>
            <?php 
              //Offer user ability to change accounts tutoring type
              echo "<button class='blueButton' type='submit' name='editType'>Change to {$oppositeType} Account</button>";
            ?>
          </div>
          <div class='accountBubble'>
            <h2>Active Tutoring Courses</h2>
            <table class='displayCourses'>
              <thead>
                <tr>
                  <th>Subject</th>
                  <th>Course Code</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php
                //Display all active courses
                foreach($activeCourses as $course){
                  $subject = $course['subject'];
                  $courseCode = $course['course_code'];
                  $id = $course['id'];
                  echo 
                  "<tr><td>{$subject}</td>
                  <td>{$courseCode}</td>
                  <td><button class='blueButton' type='submit' name='removeActive' value='{$id}'>Remove Course</button></td></tr>";
                }

                echo 
                "<tr><td><select name ='subjectActive' id='subjectActive' class='courseSelect' onchange={$activeJavascript}>
                <option disabled selected value></option>";
                  //Display all possible subjects
                  foreach($courselist as $course){
                    echo "<option value='{$course['subject']}'>" . $course['subject'] . "</option>";
                  }

                echo "  
                </select></td>
                  <td><select name ='courseCodeActive' class='courseSelect' id='courseCodeActive'>
                    <option disabled selected value></option>
                    </select></td>
                  <td><button class='blueButton' type='submit' name='addActive'>Add Course</button></td></tr>";
              ?>
              </tbody> 
            </table>
          </div>
          <div class='accountBubble'>
            <h2>Referred Tutoring Courses</h2>
            <table class='displayCourses'>
              <thead>
                <tr>
                  <th>Subject</th>
                  <th>Course Code</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php
                //Display all referred courses
                foreach($referredCourses as $course){
                  $subject = $course['subject'];
                  $courseCode = $course['course_code'];
                  $id = $course['id'];

                  echo 
                  "<tr><td>{$subject}</td>
                  <td>{$courseCode}</td>
                  <td><button class='blueButton' type='submit' name='removeReferral' value='{$id}'>Remove Course</button></td></tr>";
                }

                echo 
                "<tr><td><select name ='subjectReferral' id='subjectReferral' class='courseSelect' onchange={$referredJavascript}>
                <option disabled selected value></option>";
                  //Display all possible subjects
                  foreach($courselist as $course){
                    echo "<option value='{$course['subject']}'>" . $course['subject'] . "</option>";
                  }

                echo "  
                </select></td>
                  <td><select name ='courseCodeReferral' class='courseSelect' id='courseCodeReferral'>
                    <option disabled selected value></option>
                    </select></td>
                  <td><button class='blueButton' type='submit' name='addReferral'>Add Course</button></td></tr>";
              ?>
              </tbody> 
            </table>
          </div>
        </fieldset>
      </form>
      <form action='displayStudent.php' method='post' class="deleteForm">
        <fieldset>
          <?php
          //Message to be shown in javascript alarm to act as a confirmation message
            $confirmMessage = "\"Deleting an account will result in all of their information being removed from the database, and is not reversible. Are you sure you want to proceed?\"";

            //Delete account
            echo "<button type='submit' name='deleteAccount' onclick='return confirmMessage({$confirmMessage});'>DELETE ACCOUNT</button>";
          ?>
        </fieldset>
      </form>
      <form action='studentSearch.php' method='post' class="returnForm">
        <fieldset>
            <button type='submit' name='returnPage'>&laquo; Back</button>
        </fieldset>
      </form>
    </main>
    <footer>
    </footer>
  </body>
</html>
