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

  //Try block to ensure all database functions are working
  try{
    $pdo = connect();

    //Form submission has occurred
    if($_SERVER["REQUEST_METHOD"] == "POST"){

      //Add course submit button has been pressed
      if(isset($_POST['addCourse'])){

        //Retrieve user input from input blocks, clean to prevent javascript/server injection
        $subject = test_input($_POST['subject']);
        $courseCode = test_input($_POST['courseCode']);

        //Prevent empty text submissions
        if(empty($subject) || empty($courseCode)){
          $_SESSION['errors']['addCourse'] = "Both fields must be filled";
          header("Location: addCourse.php");
          exit();
        }
        
        //Ensure subject is in proper capitalized format
        if($subject !== strtoupper($subject)){
          $_SESSION['errors']['addCourse'] = "Subject must be in an all capital format, like 'MATH' or 'CHEM'";
          header("Location: addCourse.php");
          exit();
        }

        //Ensure course code is an integer
        if(!ctype_digit($courseCode)){
          $_SESSION['errors']['addCourse'] = "Course code must be in an all digit format";
          header("Location: addCourse.php");
          exit();
        }

        //Ensure subject is no more than 5 characters
        if(strLen($subject) > 5){
          $_SESSION['errors']['addCourse'] = "Subject cannot be longer than 5 characters";
          header("Location: addCourse.php");
          exit();
        }

        //Ensure subject is no less than 2 characters 
        if(strLen($subject) < 2){
          $_SESSION['errors']['addCourse'] = "Subject cannot be shorter than 2 characters";
          header("Location: addCourse.php");
          exit();
        }

        //Ensure course code is no more than 5 characters
        if(strLen($courseCode) > 5){
          $_SESSION['errors']['addCourse'] = "Course code cannot be longer than 5 digits";
          header("Location: addCourse.php");
          exit();
        }

        //Ensure course code is no less than 2 characters
        if(strLen($courseCode) < 2){
          $_SESSION['errors']['addCourse'] = "Course code cannot be shorter than 2 digits";
          header("Location: addCourse.php");
          exit();
        }

        //Sql query with sql injection attack prevention to retrieve subject from table using user input values
        $sql = "SELECT * FROM courses WHERE subject = ? AND course_code = ?";
        $result = $pdo->prepare($sql);
        $result->execute([$subject, $courseCode]);
        $alreadyExists = $result->fetch();

        //Ensure course entered does not already exist, inform user if it does
        if($alreadyExists){
          $_SESSION['errors']['addCourse'] = "The course entered already exists in the database";
          header("Location: addCourse.php");
          exit();
        }

        //Sql insert using sql injection prevention to insert new course into table
        $sql = "INSERT INTO courses (subject, course_code) VALUES (?, ?)";
        $result = $pdo->prepare($sql);
        $result->execute([$subject, $courseCode]);

        //Inform user of succes status
        $_SESSION['message']['addCourse'] = "Course successfully added to the database";
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

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Bulldog Tutoring Portal</title>
    <link rel="stylesheet" href="styles.css"/>
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
    <main id='addPage'>
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
      <h1 class='mainHeader'>Add a New Course</h1>
      <p id="addError" class="error">
        <?php
        
        //Display any errors with previous submission
        if(isset($_SESSION['errors']['addCourse'])){
          echo $_SESSION['errors']['addCourse'];
          unset($_SESSION['errors']['addCourse']);}
        ?>
      </p>
      <p id="addMessage" class="message">
        <?php 

        //Confirm new course has been added if applicable
        if(isset($_SESSION['message']['addCourse'])){
          echo $_SESSION['message']['addCourse'];
          unset($_SESSION['message']['addCourse']);}
        ?>
      </p>
      <form action='addCourse.php' method='post' id="addCourse">
        <fieldset>
          <label for="subject">Subject</label>
          <input type="text" name="subject" id="subject">
          <label for="courseCode">Course Code</label>
          <input type="text" name="courseCode" id="courseCode">
          <input class='blueButton' type="submit" name="addCourse" value="Create Course">
        </fieldset>
      </form>
    </main>
    <footer>
    </footer>
  </body>
</html>
