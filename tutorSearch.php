<?php

  $STUDENT_ACCOUNT = '(0,1)';
  include 'functions.php';
  session_start();

  try{
    $pdo = connect();

    //Enter course codes to second select box once first select box is entered/changed
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

    $sql = "SELECT DISTINCT subject FROM courses";
    $result = $pdo->prepare($sql);
    $result->execute();
    $courses = $result->fetchAll();

    if($_SERVER["REQUEST_METHOD"] == "POST"){
      $sqlValues = [];
      $sql = "";

      if(isset($_POST['courseSearch'])){
        $sql = "SELECT a.*, c.* FROM accounts a 
        JOIN active_tutors a_t ON a.email = a_t.email
        JOIN courses c ON a_t.course_id = c.id
        WHERE a.account_type IN {$STUDENT_ACCOUNT}";

        if(!empty($_POST['subject'])){
          $sqlValues[] = test_input($_POST['subject']);
          $sqlColumns[] = "c.subject";
        }
        if(!empty($_POST['courseCode'])){
          $sqlValues[] = test_input($_POST['courseCode']);
          $sqlColumns[] = "c.course_code";
        }

        if(!empty($sqlValues)){
          for($i = 0; $i < count($sqlValues); $i++){
            $sql = $sql . " AND " . $sqlColumns[$i] . " = ?";
          } 
        }

        $_SESSION['courseQuery'] = $sql;
        $_SESSION['courseValues'] = $sqlValues;
      }

      else if(isset($_POST['sortSearch'])){
        $sortBy = test_input($_POST['sortSearch']);
        $sortString = "ORDER BY ";

        if($sortBy == "Subject"){
          $sortString = $sortString . "subject";
        }
        else if($sortBy == "Course Number"){
          $sortString = $sortString . "course_code";
        }
        else if($sortBy == "Firstname"){
          $sortString = $sortString . "firstname";
        }
        else if($sortBy == "Lastname"){
          $sortString = $sortString . "lastname";
        }
        else if($sortBy == "Email"){
          $sortString = $sortString . "email";
        }
        else if($sortBy == "Tutor Type"){
          $sortString = $sortString . "account_type";
        }
        else{
          $sortString = $sortString . "last_activity";
        }

        $sqlValues = $_SESSION['courseValues'];
        $sql = test_input($_SESSION['courseQuery']) . " " . $sortString;
        if($sortString == test_input($_SESSION['tutorSort'])){
          $sql = $sql . " DESC";
          unset($_SESSION['tutorSort']);
        }
        else{
          $_SESSION['tutorSort'] = $sortString;
        }
      }

      $result = $pdo->prepare($sql);
      $result->execute($sqlValues);
      $students = $result->fetchAll();

      $numStudents = count($students);
      $countMessage = $numStudents . " results";
      if($numStudents == 1){
        $countMessage = "1 result";
      }
    }
  }

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
          <a href="login.html">Login</a>
        </div>
      </nav>
    </header>
    <main>
      <div>
      <nav>
          <div class="adminLink">
            <a href='admin.php'>Home</a>
          </div>
          <div class="adminLink">
            <span>Students</span>
            <div>
              <a href='studentSearch.php'>Student Accounts</a>
              <a href='tutorSearch.php'>Student Tutors</a>
              <a href='referralSearch.php'>Student Referrals</a>
            </div>
          </div>
          <div class="adminLink">
            <span>Professors</span>
            <div>
              <a href='facultySearch.php'>Professor Accounts</a>
              <a href='facultyCourseSearch.php'>Professor Referrals</a>
            </div>
          </div>
          <div class="adminLink">
            <span>Admin</span>
            <div>
              <a href='adminSearch.php'>Admin Accounts</a>
              <a href='courseSearch.php'>Manage Courses</a>          
              <a href='newSemester.php'>Transition Semesters</a>
            </div>
          </div>
        </nav>
      </div>
      <h1 class="searchHeader">Search Active Tutors</h1>
      <?php
          echo"
          <form action='tutorSearch.php' method='post' class = 'searchForm'>
          <fieldset>  
            <div>
              <label for='subject'>Subject</label>
              <select name='subject' id='subject' class='courseSelect' onchange='getCourseCodes()'>
                <option disabled selected value></option>";

                foreach($courses as $course){
                  echo "<option value='{$course['subject']}'>" . $course['subject'] . "</option>";
                }

              echo "</select>  
              <label for='courseCode'>Course Number</label>
              <select name ='courseCode' class='courseSelect' id='courseCode'>
                <option disabled selected value></option>
              </select>
              <label for='tutorType'>Tutor Type</label>
              <select name='tutorType' id='tutorType'>
                <option disabled selected value></option>
                <option value='0'>Private</option>
                <option value='1'>Scholarship</option>
              </select>
              <input type='submit' name='courseSearch'>
            </div>";
            
          if($_SERVER["REQUEST_METHOD"] == "POST"){
            echo "<table class='searchTable'>
            <thead>
              <tr>
                <td><input type='submit' name='sortSearch' value='Subject'></td>
                <td><input type='submit' name='sortSearch' value='Course Number'</td>
                <td><input type='submit' name='sortSearch' value='Firstname'></td>
                <td><input type='submit' name='sortSearch' value='Lastname'</td>
                <td><input type='submit' name='sortSearch' value='Email'></td>
                <td><input type='submit' name='sortSearch' value='Tutor Type'</td>
                <td><input type='submit' name='sortSearch' value='Last Activity'</td>
              </tr>
            </thead>
            <tbody>";

            foreach($students as $student){
            $tutorType = "Private";
            if(test_input($student['account_type']) == 1){
              $tutorType = "Scholarship";
            }
    
            $timeStamp = strtotime($student['last_activity']);
            $timeStamp = date("m/d/Y", $timeStamp);
    
            echo "<tr><td>{$student['subject']}</td>
            <td>{$student['course_code']}</td>
            <td>{$student['firstname']}</td>
            <td>{$student['lastname']}</td>
            <td>{$student['email']}</td>
            <td>{$tutorType}</td>
            <td>{$timeStamp}</td></tr>";
          }
    
          echo "</tbody><tfoot>
          <tr><td colspan='7'>Search returned {$countMessage}</td></tr>
          </tfoot></table>";
          }
              
          echo"</fieldset>
        </form>";
      ?>
    </main>
    <footer>
    </footer>
  </body>
</html>
