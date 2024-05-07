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

  //Begin try block to monitor all database queries
  try{
    $pdo = connect();

    //User has just navigated from course search
    if(isset($_SESSION['editInfo'])){

      //Retrieve the selected course from previous page
      $courseId = test_input($_SESSION['editInfo']);

      //Course has been deleted by the user
      if($_SERVER["REQUEST_METHOD"] == "POST"){

        //Delete the course as requested
        if(isset($_POST['deleteCourse'])){

          //Delete query with sql injectiona attack prevention steps
          $sql = "DELETE FROM courses WHERE id = ?";
          $result = $pdo->prepare($sql);
          $result->execute([$courseId]);

          //Provide feedback and return to admin home
          $_SESSION['messages']['admin'] = "Course successfully deleted.";
          header("Location: admin.php");
          exit();
        }
      }

      //Select query with sql injection attack prevention steps - Get course with given ID
      $sql = "SELECT * FROM courses WHERE id = ?";
      $result = $pdo->prepare($sql);
      $result->execute([$courseId]);
      $course = $result->fetch();

      //Obtain information for current course
      $subject = $course['subject'];
      $courseCode = $course['course_code'];

      //Select query to get all professor referral information for this course
      $sql = "SELECT a.* FROM accounts a 
      JOIN course_professors c_p ON a.email = c_p.email
      JOIN courses c ON c.id = c_p.course_id
      WHERE c.id = ?";
  
      //Sql select query using sql injection prevention steps
      $result = $pdo->prepare($sql);
      $result->execute([$courseId]);
      $profReferral = $result->fetchAll();

      //Select query to get all active tutoring information for this course
      $sql = "SELECT a.* FROM accounts a 
      JOIN active_tutors a_t ON a.email = a_t.email
      JOIN courses c ON c.id = a_t.course_id
      WHERE c.id = ?";
  
      //Sql select query using sql injection prevention steps
      $result = $pdo->prepare($sql);
      $result->execute([$courseId]);
      $activeTutors = $result->fetchAll();
  
      //Select query to get all referred tutoring information for this course
      $sql = "SELECT a.*, r_t.email 
              FROM referred_tutors r_t
              LEFT JOIN accounts a ON a.email = r_t.email
              JOIN courses c ON c.id = r_t.course_id
              WHERE c.id = ?";
  
      //Sql select query using sql injection prevention steps
      $result = $pdo->prepare($sql);
      $result->execute([$courseId]);
      $referredTutors = $result->fetchAll();
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
      <p id="courseEditError" class="error">
        <?php 
        if(isset($_SESSION['errors']['courseEdit'])){
          echo $_SESSION['errors']['courseEdit'];
          unset($_SESSION['errors']['courseEdit']);}
        ?>
      </p>
      <?php
      //Personalize header to current class
        echo "<h1 class='mainHeader'>{$subject} {$courseCode} Details</h1>"
      ?>
      <form action='displayStudent.php' method='post' id='editDisplayForm'>
        <fieldset>
        <div class='accountBubble'>
          <h2>Active Professors</h2>
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                </tr>
              </thead>
              <tbody>
              <?php
                //Display all professor referrals
                foreach($profReferral as $prof){
                  $email = $prof['email'];
                  $name = $prof['firstname'] . " " . $prof['lastname'];
                  echo 
                  "<tr><td>{$name}</td>
                  <td>{$email}</td></tr>";
                }
              ?>
              </tbody> 
            </table>
          </div>
          <div class='accountBubble'>
          <h2>Active Student Tutors</h2>
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                </tr>
              </thead>
              <tbody>
              <?php
                //Display all active tutors
                foreach($activeTutors as $tutor){
                  $email = $tutor['email'];
                  $name = $tutor['firstname'] . " " . $tutor['lastname'];
                  echo 
                  "<tr><td>{$name}</td>
                  <td>{$email}</td></tr>";
                }
              ?>
              </tbody> 
            </table>
          </div>
          <div class='accountBubble'>
          <h2>Referred Student Tutors</h2>
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                </tr>
              </thead>
              <tbody>
              <?php
                //Display all referred tutors
                foreach($referredTutors as $tutor){
                  $email = $tutor['email'];
                  if(!empty($tutor['firstname'])){
                    $name = $tutor['firstname'] . " " . $tutor['lastname'];
                  }
                  else{
                    $name = "NO ACCOUNT";
                  }
                  echo 
                  "<tr><td>{$name}</td>
                  <td>{$email}</td></tr>";
                }
              ?>
              </tbody> 
            </table>
          </div>
        </fieldset>
      </form>
      <form action='courseSearch.php' method='post' class="returnForm">
        <fieldset>
            <button type='submit' name='returnPage'>&laquo; Back</button>
        </fieldset>
      </form>
    </main>
    <footer class="displayFoot">
    <form action='displayCourse.php' method='post' class="deleteForm">
        <fieldset>
          <?php
          //Message to be shown in javascript alarm to act as a confirmation message
            $confirmMessage = "\"Deleting a course will result in all of its information and linked tutors being removed from the database, and is not reversible. Are you sure you want to proceed?\"";

            //Delete course
            echo "<button type='submit' name='deleteCourse' onclick='return confirmMessage({$confirmMessage});'>DELETE COURSE</button>";
          ?>
        </fieldset>
      </form>
    </footer>
  </body>
</html>
