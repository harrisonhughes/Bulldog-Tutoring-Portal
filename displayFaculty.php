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

    //User has just navigated from faculty search
    if(isset($_SESSION['editInfo'])){

      //Retrieve the selected account from previous page
      $email = test_input($_SESSION['editInfo']);

      //User has submitted a form 
      if($_SERVER["REQUEST_METHOD"] == "POST"){

        //User has deleted this account
        if(isset($_POST['deleteAccount'])){

          //Delete query with sql injection attack prevention steps
          $sql = "DELETE FROM accounts WHERE email = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email]);

          //Provide feedback and return to admin home
          $_SESSION['messages']['admin'] = "Account successfully deleted.";
          header("Location: admin.php");
          exit();
        }

        //User has chosen to remove a professor referral
        else if(isset($_POST['removeReferral'])){

          //Get course id of course removed
          $courseId = test_input($_POST['removeReferral']);

          //Delete query with sql injection attack prevention steps
          $sql = "DELETE FROM course_professors WHERE email = ? AND course_id = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$email, $courseId]);  

          //Update page
          header("Location: displayFaculty.php");
          exit();
        }

        //User has chosen to add a professor referral
        else if(isset($_POST['addReferral'])){

          //Get course id of course added
          $subject = $_POST['subject'];
          $courseCode = $_POST['courseCode'];

          //Ensure user has supplied values for both select boxes
          if(empty($subject) || empty($courseCode)){
            $_SESSION['errors']['professorEdit'] = "Must select a subject and course code to add a new open referral.";
          }

          //Select query with sql injection attack prevetion steps
          $sql = "SELECT id FROM courses WHERE subject = ? AND course_code = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$subject, $courseCode]);  
          $id = $result->fetch()[0];

          //Insert query with sql injection attack prevetion steps
          $sql = "INSERT IGNORE INTO course_professors (email, course_id) VALUES (?, ?)";
          $result = $pdo->prepare($sql);
          $result->execute([$email, $id]);

          //Update page
          header("Location: displayFaculty.php");
          exit();
        }
      }

      //Select query with sql injection attack prevention steps - Get account with given email
      $sql = "SELECT * FROM accounts WHERE email = ?";
      $result = $pdo->prepare($sql);
      $result->execute([$email]);
      $user = $result->fetch();

      //Get user information from retrieved data
      $uName = $user['firstname'] . " " . $user['lastname'];
      $email = $user['email'];
  
      //Select all open referrals for the professor
      $sql = "SELECT c.* FROM courses c 
      JOIN course_professors c_p ON c.id = c_p.course_id
      JOIN accounts a ON a.email = c_p.email
      WHERE a.email = ?";
  
      //Sql prevention steps for select query
      $result = $pdo->prepare($sql);
      $result->execute([$email]);
      $openReferrals = $result->fetchAll();

      //Obtain all individual subjects from database
      $sql = "SELECT DISTINCT subject FROM courses";
      $result = $pdo->prepare($sql);
      $result->execute();
      $courselist = $result->fetchAll();

      //Buld javascript fucntion string to use in subject select box 
      $javascript = "\"getCourseCodes('subject', 'courseCode')\"";
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
      <h1 class="mainHeader" >Professor Management Page</h1>
      <p id="professorEditError" class="error">
                <?php 
                //Display any current errors
                if(isset($_SESSION['errors']['professorEdit'])){
                  echo $_SESSION['errors']['professorEdit'];
                  unset($_SESSION['errors']['professorEdit']);}
                ?>
              </p>
      <form action='displayFaculty.php' method='post' id='editDisplayForm'>
        <fieldset>
          <div class='accountBubble'>
            <h2>Profile</h2>
            <table id="professorAccount">
              <?php
                echo 
                //Display personal information
                "<tr><th>Name</th>
                <td>{$uName}</td></tr>
                <tr><th>Email</th>
                <td>{$email}</td></tr>
                <tr><th>Account Type</th>
                <td>Professor</td></tr>";
              ?>
            </table>
          </div>
          <div class='accountBubble'>
          <h2>Open Referrals</h2>
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
                //Display all professor referrals
                foreach($openReferrals as $course){
                  $subject = $course['subject'];
                  $courseCode = $course['course_code'];
                  $id = $course['id'];

                  echo 
                  "<tr><td>{$subject}</td>
                  <td>{$courseCode}</td>
                  <td><button class='blueButton' type='submit' name='removeReferral' value='{$id}'>Remove Referral</button></td></tr>";
                }

                echo 
                "<tr><td><select name ='subject' id='subject' class='courseSelect' onchange={$javascript}>
                <option disabled selected value></option>";
                  //Display all possible subjects
                  foreach($courselist as $course){
                    echo "<option value='{$course['subject']}'>" . $course['subject'] . "</option>";
                  }

                echo "  
                </select></td>
                  <td><select name ='courseCode' class='courseSelect' id='courseCode'>
                    <option disabled selected value></option>
                    </select></td>
                  <td><button class='blueButton' type='submit' name='addReferral'>Add Referral</button></td></tr>";
              ?>
              </tbody> 
            </table>
          </div>
        </fieldset>
      </form>
      <form action='facultySearch.php' method='post' class="returnForm">
        <fieldset>
            <button type='submit' name='returnPage'>&laquo; Back</button>
        </fieldset>
      </form>
    </main>
    <footer class="displayFoot">
    <form action='displayFaculty.php' method='post' class="deleteForm">
        <fieldset>
          <?php
          //Message to be shown in javascript alarm to act as a confirmation message
            $confirmMessage = "\"Deleting an account will result in all of their information being removed from the database, and is not reversible. Are you sure you want to proceed?\"";

            //Delete account
            echo "<button type='submit' name='deleteAccount' onclick='return confirmMessage({$confirmMessage});'>DELETE ACCOUNT</button>";
          ?>
        </fieldset>
      </form>
    </footer>
  </body>
</html>
