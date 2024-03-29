<?php
  include_once 'functions.php';
  session_start();

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
    $sql = "SELECT DISTINCT subject FROM courses";
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
  catch (PDOException $e){
    die( $e->getMessage());
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
        <span>Bulldog Tutoring Portal</span>
      </div>
      <nav>
        <div>
          <a href="home.html">Home</a>
          <a href="portal.php">Portal</a>
          <a href="account.php">Account</a>
        </div>
      </nav>
    </header>
    <main>
      <h1>Search for a Tutor!</h1>
      <p id="portalError" class="error">
        <?php 
        if(isset($_SESSION['errors']['portal'])){
          echo $_SESSION['errors']['portal'];
          unset($_SESSION['errors']['portal']);}
        ?>
      </p>
      <form action="portal.php" method="post" class = 'searchForm'>
        <fieldset>
          <div>
            <label for="subject">Subject</label>
            <select name ="subject" id="subject" class='courseSelect' onchange="getCourseCodes('portal.php')">
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
          <div class="portalButtons">
            <input type="submit" value="Search"/>
            <input type="reset" value="Clear"/>
          </div>
        </fieldset>
      </form>
            <?php

              //Only display table if both subject and course code fields are set
              if($vaildQuery){ 

              //Open Table to display tutors
              echo "<h2>Showing results for {$subject} {$courseCode} tutors</h2>
              <table class='searchTable'>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Tutor Type</th>
                  <th>Availability</th>
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
                echo "<tr><td>{$name}</td>";
                echo "<td>{$tutor['email']}</td>";
                echo "<td>{$tutorType}</td>";
                echo "<td>N/A</td></tr>";
              }

              //Display number of tutors in footer of table
              $numTutors = count($tutors);
              echo "</tbody><tfoot>
              <tr><td colspan='4'>Search returned {$numTutors} students</td></tr>
              </tfoot></table>";
              }
            ?>
          </tbody>
        </table>
    </main>
    <footer>
    </footer>
  </body>
</html>