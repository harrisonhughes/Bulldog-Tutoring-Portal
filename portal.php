<?php
  //Begin session, set inactivity timout constant (2 hours)
  session_start();
  $TIME_OUT = 60 * 60 * 2;

  //If user is not set, access credentials are not set, last activity is not set, or last activity is beyond timeout length
  if(!isset($_SESSION['user']) || !isset($_SESSION['credentials']) || !isset($_SESSION['lastActivity']) || time() - $_SESSION['lastActivity'] > $TIME_OUT){

    //If last activity is set and beyond timeout length, set output message and send user to login page to be logged out
    if(isset($_SESSION['lastActivity']) && time() - $_SESSION['lastActivity'] > $TIME_OUT){
      $_SESSION['message']['login'] = "You were logged out due to inactivity";
    }
    header("Location: login.php");
    exit();
  }

  //Update last activity variable
  $_SESSION['login'] = time();
  
  include_once 'functions.php';

  try {
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

     //Select query with sql injection attack prevention steps - Get all subjects
    $sql = "SELECT DISTINCT subject FROM courses ORDER BY subject";
    $result = $pdo->prepare($sql);
    $result->execute();
    $courses = $result->fetchAll();

    //User has submitted a tutoring search
    if($_SERVER["REQUEST_METHOD"] == "POST"){

      //Portal submission has been executed
      if(isset($_POST['subject']) && isset($_POST['courseCode'])){
        $vaildQuery = true; // Ensures that webpage only displays table if both fields are set
        $subject = test_input($_POST['subject']);
        $courseCode = test_input($_POST['courseCode']);

         //Select query with sql injection attack prevention steps - Get all tutors for specific class 
        $sql = "SELECT a.* FROM accounts a 
                JOIN active_tutors a_t ON a.email = a_t.email
                JOIN courses c ON a_t.course_id = c.id
                WHERE c.subject = ? AND c.course_code = ?";

        //Remove private tutors from query if scholarship select box is checked
        if(isset($_POST['scholTutor'])){
          
          //1 indicates only student tutors
          $sql = $sql . " AND a.account_type = 1";
        }
        else{

          //(0,1) indicates both student and private tutors
          $sql = $sql . " AND a.account_type IN (0,1)"; 
        }

        $sql .= " ORDER BY a.lastname";

        $result = $pdo->prepare($sql);
        $result->execute([$subject, $courseCode]);
        $tutors = $result->fetchAll();
      }

      //User did not enter value for both subject and course code
      else{

        //No value for subject select box
        if(!isset($_POST['subject'])){
          $_SESSION['errors']['portal'] = "Must select a subject and course code to search for tutors";
        }

        //No value for course code select box
        else{
          $_SESSION['errors']['portal'] = "Must select a course code to search for tutors";
        }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
          <a href="portal.php"><span class='active'>Portal</span></a>
          <a href="account.php"><span>Account</span></a>
          <a href="login.php"><span>Logout</span></a>
      </nav>
    </header>
    <main id="portal">
      <aside>
        <div>
          <h2><a href="https://excellence.truman.edu/tutoring/">University Resources</a></h2>
          <div>
          <a href="https://excellence.truman.edu/tutoring/cae-tutoring-center/">Tutoring Center</a>
          <a href="https://writingcenter.truman.edu/">Writing Center</a>
          <a href="https://truman.mywconline.com/">Schedule University Tutoring</a>
          </div>
          <h2><a href="https://excellence.truman.edu/tutoring/departmental-tutoring/">Departmental Resources</a></h2>
          <div>
          <a href="https://chemlab.truman.edu/ccc/">Chemistry Tutoring</a>
          <a href="https://sps.truman.edu/tutoring/">Physics Tutoring</a>
          <a href="https://llc.truman.edu/">Language Learning Center</a>
          </div>
        </div>  
      </aside>
      <div>
        <h1 class="mainHeader">Search for a Tutor!</h1>
        <p id="portalError" class="error">
          <?php 
          //Display error message if applicable
          if(isset($_SESSION['errors']['portal'])){
            echo $_SESSION['errors']['portal'];
            unset($_SESSION['errors']['portal']);}
          ?>
        </p>
        <form action="portal.php" method="post">
          <fieldset>
            <div>
              <label for="subject">Subject</label>
              <select name ="subject" id="subject" class='courseSelect' onchange="getCourseCodes('subject', 'courseCode')">
                <option disabled selected value></option>
                <?php
                  //Display all possible subjects
                  foreach($courses as $course){
                    echo "<option value='{$course['subject']}'>" . $course['subject'] . "</option>";
                  }
                ?>
              </select>
              <label for="courseCode">Course Number</label>
              <select name ="courseCode" class='courseSelect' id="courseCode">
                <option disabled selected value></option>
              </select>
              <div class="features">
                <input type="checkbox" name="scholTutor" id="scholTutor" value="1">
                <label for="scholTutor">Scholarship Tutors Only</label>
              </div>
            </div>
            <div id="portalButtons">
              <input class='blueButton' type="submit" value="Search for Tutors!"/>
            </div>
          </fieldset>
        </form>
              <?php

                //Only display table if both subject and course code fields are set
                if(!empty($vaildQuery)){ 

                //Open Table to display tutors
                echo "<h2 class='resultHeader'>Showing results for {$subject} {$courseCode}</h2>
                <table class='searchTable'>
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Tutor Type</th>
                  </tr>
                </thead>
                <tbody>";
                
                //Fill table rows with tutor information if available
                foreach($tutors as $tutor){
                  $tutorType = "Private";
                  if(test_input($tutor['account_type']) == 1){
                    $tutorType = "Scholarship";
                  }

                  $name = $tutor['firstname'] . ' ' . $tutor['lastname'];
                  echo "<tr><td>{$name}</td>
                  <td><a href='mailto:{$tutor['email']}'>{$tutor['email']}</a></td>
                  <td>{$tutorType}</td></tr>";
                }

                //Display number of tutors in footer of table
                $numTutors = count($tutors);
                $countMessage = $numTutors . " results";
                if($numTutors == 1){
                  $countMessage = "1 result";
                }
                echo "</tbody><tfoot>
                <tr><td colspan='4'>Search returned {$countMessage}</td></tr>
                </tfoot></table>";
                }
              ?>
            </tbody>
          </table>
        </div>
    </main>
    <footer>
    </footer>
  </body>
</html>