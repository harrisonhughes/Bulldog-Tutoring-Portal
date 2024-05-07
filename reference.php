<?php
  //Begin session, set inactivity timout constant (2 hours), create constant for access control
  session_start();
  $TIME_OUT = 60 * 60 * 2;
  $PROFESSOR_CODE = 2;

  //If user is not set, access credentials are not set, last activity is not set, or last activity is beyond timeout length
  if(!isset($_SESSION['user']) || !isset($_SESSION['credentials']) || !isset($_SESSION['lastActivity']) || time() - $_SESSION['lastActivity'] > $TIME_OUT){

    //If last activity is set and beyond timeout length, set output message and send user to login page to be logged out
    if(isset($_SESSION['lastActivity']) && time() - $_SESSION['lastActivity'] > $TIME_OUT){
      $_SESSION['message']['login'] = "You were logged out due to inactivity";
    }
    header("Location: login.php");
    exit();
  }

  //If access credentials are not set or user does not have professor credentials, reroute to the home page
  else if(!isset($_SESSION['credentials']) || $_SESSION['credentials'] != $PROFESSOR_CODE){
    header("Location: index.php");
  }

  //Update last activity variable
  $_SESSION['login'] = time();

  //Constants to validate professor input
  $TRUMAN_EMAIL = "@truman.edu";
  $MAX_LENGTH = 50;
  $DEFAULT_TIMESTAMP = "2000-01-01 00:00:00";
  
  include_once 'functions.php';

  try{
    $pdo = connect(); 

    //Need to add in code to ensure user is a professor to view this page
    $user = test_input($_SESSION['user']);
    
    //Get all course information for the courses that the current professor still needs to create references for
    $sql = "SELECT c.*, c_p.completed
    FROM courses c 
    JOIN course_professors c_p ON c.id = c_p.course_id
    JOIN accounts a ON a.email = c_p.email
    WHERE a.email = ?";

    //Execute query with sql injection attack prevention steps
    $result = $pdo->prepare($sql);
    $result->execute([$user]);
    $courses = $result->fetchAll();

    //If user has just clicked a specific class make that the active course
    if(isset($_POST['selectedId'])){
      $selectedId = test_input($_POST['selectedId']);

      foreach($courses as $course){
        if($course['id'] == $selectedId){
          $_SESSION['currentCourse'] = $course;
        }
      }

      //Reload page to remove post request type tag
      header("Location: reference.php");
      exit();
    }

    //Ensure there is an active class to refer students for if applicable: this session variable is used throughout page
    if(!isset($_SESSION['currentCourse']) && !empty($courses)){
      foreach($courses as $course){
        if($course['completed'] == $DEFAULT_TIMESTAMP){
          $_SESSION['currentCourse'] = $course;
          break;
        }
      }
    }

    //Account deleted, remove from database, route user to login page where they are automatically logged out
    if(isset($_POST['deleteAccount'])){ 
      $email = test_input($_SESSION['user']);

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

  //Unable to create connection with database
  catch (PDOException $e){
    $error = $e->getMessage();
    echo "<p>Critical Error (Database):<br><br>{$error}<br><br>Please save this message and inform the head website administrator as soon as possible.</p>";
    exit(); 
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Referral Interface</title>
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
    <main id="referencePage">
    <h1 class="mainHeader">Faculty Referral Interface</h1>
    <p id="recoverEmail" class="message">
      <?php 
      //Display message if applicable
      if(isset($_SESSION['message']['passReset'])){
        echo $_SESSION['message']['passReset'];
        unset($_SESSION['message']['passReset']);}
      else if(isset($_SESSION['message']['confSubmission'])){
        echo $_SESSION['message']['confSubmission'];
        unset($_SESSION['message']['confSubmission']);}
      ?>
    </p>
      <div>
        <div class="accountBubble">
          <div>
            <h2>Referral Process</h2>
            <ul>
              <li>Fill in the email of each student that you would like to reference for the current course</li>
              <li>A <b>reference</b> means you believe a student has the ability/personality to help others with this class</li>
              <li>Your recommendation creates <b>no obligation</b> for a student to become a tutor</li>
              <li>Pro Tip: pull up your <b>Brightspace courselist</b> to have instant access to all name/email pairs</li>
            </ul>
          </div>
          <h2>Active Courses</h2>
            <?php

              //If the professor has active courses
              if(!empty($courses)){
                echo "<form action='reference.php' method='post' id='changeCourse'>
                <fieldset> ";
                
                //List all courses and their current referral status
                foreach($courses as $course){

                  //Determine whether or not the current course has already had a referral submission this semester
                  $progress = "(Completed!)";
                  if($course['completed'] == $DEFAULT_TIMESTAMP){
                    $progress = "(Incomplete)";
                  }

                  echo "<div>
                  <button type='submit' name='selectedId' value={$course['id']}>" . $course['subject'] . " " . $course['course_code'] . "</button>
                  <p>{$progress}</p></div>";
                }

                echo "</fieldset>
                </form>";
              }
              else{
                echo "<p>You have no active courses this semester!</p>";
              }
            ?>
        </div>
        <?php
          if(isset($_SESSION['currentCourse'])){
            
            //Begin referral div block
            echo "<div class='accountBubble'>";
              
              //Standardize current class
              $course = $_SESSION['currentCourse']['subject'] . " " . $_SESSION['currentCourse']['course_code'];

              echo "<h2>{$course}</h2>";

              if($_SERVER["REQUEST_METHOD"] == "POST"){

                //Preliminary submission of student email references has occurred, gather emails entered and display CONFIRMATION PAGE
                if(isset($_POST['submitReferences'])){
                  $validSubmission = true;
                  $students = [];

                  if(!empty($_POST['emailList'])){
                    //Fill array with all student names entered by professor; save errors if invalid emails entered and prevent preliminary submission
                    for($studentIndex = 0; $studentIndex < count($_POST['emailList']); $studentIndex++){
                      $studentEmail = test_input($_POST['emailList'][$studentIndex]);

                      //If user entered truman email despite warnings, remove it for ease of use
                      if(substr($studentEmail, -11) == $TRUMAN_EMAIL){
                        $studentEmail = substr($studentEmail, 0, -11);
                      }

                      //Duplicate emails entered
                      if(in_array($studentEmail, $students)){
                        $_SESSION['errors']['email' . $studentIndex + 1] = "Duplicate email entered";
                        $validSubmission = false;
                      }

                      //Field left empty
                      if(empty($studentEmail)){
                        $_SESSION['errors']['email' . $studentIndex + 1] = "Email cannot be empty";
                        $validSubmission = false;
                      }

                      //Emails must contain only alphabetic and numerical characters
                      if(!preg_match('/^[a-zA-Z0-9]*$/', $studentEmail)){
                        $_SESSION['errors']['email' . $studentIndex + 1] = "Email must consist of only english characters and numbers";
                        $validSubmission = false;
                      }

                      //Email must be short enough to fit into the database
                      if(strlen($studentEmail) + strlen($TRUMAN_EMAIL) > $MAX_LENGTH){
                        $_SESSION['errors']['email' . $studentIndex + 1] = "Email is too long to be considered a valid Truman email";
                        $validSubmission = false;
                      }

                      $students[] = strtolower($studentEmail);
                    }
                  }

                  //Save all emails to allow autofill of fields if errors occur or if professor wants to backtrack
                  $_SESSION['references'] = $students;
                  $numStudents = count($students);

                  //If errors in emails are present, remain on same page and display to user
                  if(!$validSubmission){
                    header("Location: reference.php");
                    exit();
                  }

                  //Display confirmation page with every email entered, and both a return and confirm option
                  echo "<h2>Summary for {$course}</h2><ul class='checkList'>";
                  foreach($students as $student){
                    echo "<li>{$student}@truman.edu</li>";
                  }

                  $plural = "";
                  if(count($students) != 1){
                    $plural = 's';
                  }
                  echo "</ul>
                        <p>You have referred <b>{$numStudents}</b> student{$plural} for {$course}. Please ensure this information is accurate.</p>
                        <form action='reference.php' method='post' id='confirmReference'>
                          <fieldset>
                          <div>
                            <input class='blueButton' type='submit' value='Return' name='editReferences'/>
                            <input class='blueButton' type='submit' value='Confirm' name='confirmReferences'/>
                          </div>
                          </fieldset>
                        </form>";
                }

                //Clear all emails currently in input blocks
                else if(isset($_POST['resetReferences'])){
                  unset($_SESSION['references']);
                  header("Location: reference.php");
                  exit();
                }

                //Return option from confirmation page has been pressed, go back to preliminary submission page (default with no post request)
                else if(isset($_POST['editReferences'])){
                  header("Location: reference.php");
                  exit();
                }

                //User has confirmed the list of valid emails to be sent to the temporary data table of references
                else if(isset($_POST['confirmReferences'])){
                  
                  //Create variables to identify current referral entry and time
                  $courseId = test_input($_SESSION['currentCourse']['id']);
                  $professor = $_SESSION['user'];
                  $time = date('Y-m-d H:i:s');

                  try{

                    //Insert every email into table of referred students
                    foreach($_SESSION['references'] as $student){
                      $email = test_input($student) . "@truman.edu";

                      //Ensure student is not already an active tutor before entering into referred tutors
                      $sql = "SELECT * FROM active_tutors WHERE email = ? AND course_id = ?";
                      $result = $pdo->prepare($sql);
                      $result->execute([$email, $courseId]);  
                      $alreadyActive = $result->fetch();
            
                      //Enter email into referred tutors if not active
                      if(!$alreadyActive){
                        $sql = "INSERT IGNORE INTO referred_tutors (email, course_id) VALUES (?, ?)";
                        $result = $pdo->prepare($sql);
                        $result->execute([$email, $courseId]);  
                      }
                    }

                    //Update professor referral to indicate completion and create confirmation message
                    $sql = "UPDATE course_professors SET completed = ? WHERE email = ? AND course_id = ?";
                    $result = $pdo->prepare($sql);
                    $result->execute([$time, $professor, $courseId]);
                    $_SESSION['message']['confSubmission'] = "Referral list successfully submitted";
                  }

                  //Ensure proper error message is returned upon a database error
                  catch(PDOException $e){
                    $error = $e->getMessage();
                    echo "<p>Critical Error (Database):<br><br>{$error}<br><br>Please save this message and inform the head website administrator as soon as possible.</p>";
                    exit(); 
                  }
                  $pdo = null;

                  //Clear all current email references and the current referral course from session variables, and go back to default professor page
                  unset($_SESSION['references']);
                  unset($_SESSION['currentCourse']);
                  header("Location: reference.php");
                  exit();
                }
              }

              //Default professor interface; display preliminary referral page where user can enter student emails
              else{

                //Check if any emails are saved; this means a professor is actively referring students and progress has been saved
                $savedStudents = 0;
                if(isset($_SESSION['references'])){
                  $savedStudents = count($_SESSION['references']);
                };

                //Display first student email textbox; the referral form will always show at least one textbox
                echo "<form action='reference.php' method='post' id='referenceForm'>
                  <fieldset>
                    <div id='emailList'>
                      <div class='studentEmail'>
                        <div>
                          <label for='email1'>Student 1</label>
                          <input type='text' name='emailList[]' id='email1'";

                          if($savedStudents > 0){
                            $savedEmail = $_SESSION['references'][0];
                            echo "value='{$savedEmail}'";
                          }

                          echo "><p>@truman.edu</p>
                          <button type='button' class='removeInput' onClick='removeStudent(1)'>X</button>
                        </div>
                        <p id='email1Error' class='error'>";

                        if(isset($_SESSION['errors']['email1'])){
                          echo $_SESSION['errors']['email1'];
                          unset($_SESSION['errors']['email1']);
                        }

                        echo "</p>
                        </div>";

                      //If any emails are saved (from entry errors or professor has "returned" from confirmation page) add more text boxes and display them
                      if($savedStudents > 1){
                        $studentNum = 2; //Keeps track of the email index for error displaying purposes

                        for($studentIndex = 1; $studentIndex < $savedStudents; $studentIndex++){
                          $savedEmail = $_SESSION['references'][$studentIndex];

                          echo 
                          "<div class='studentEmail'>
                            <div>
                              <label for='email{$studentNum}'>Student {$studentNum}</label>
                              <input type='text' name='emailList[]' id='email{$studentNum}' value='{$savedEmail}'>
                              <p>@truman.edu</p>
                              <button type='button' class='removeInput' onClick='removeStudent({$studentNum})'>X</button>
                            </div>
                            <p id='email{$studentNum}Error' class='error'>";

                            if(isset($_SESSION['errors']["email{$studentNum}"])){
                              echo $_SESSION['errors']["email{$studentNum}"];
                              unset($_SESSION['errors']["email{$studentNum}"]);
                            }
                            
                            echo 
                            "</p>
                            </div>";
                          $studentNum++;
                        }
                        unset($_SESSION['references']);
                      }

                    //HTML trailer 
                    echo "</div>
                    <button type='button' id='addInput' onClick='addStudent()'>Add Row +</button>
                    <div>
                      <input class='blueButton' type='submit' value='Clear' name='resetReferences'/>
                      <input class='blueButton' type='submit' value='Review' name='submitReferences'/>
                    </div>
                  </fieldset>
                </form></div>";
              }
            }
          ?>
      </div>
    </main>
    <footer>
    <form action='reference.php' method='post' class="deleteForm">
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
