<?php

  $PROFESSOR_ACCOUNT = '2';
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

      if(isset($_POST['facultyCourseSearch'])){
        $sql = "SELECT a.*, c.* FROM accounts a 
        JOIN course_professors c_p ON a.email = c_p.email
        JOIN courses c ON c_p.course_id = c.id
        WHERE a.account_type = {$PROFESSOR_ACCOUNT}";

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

        $_SESSION['profCourseQuery'] = $sql;
        $_SESSION['profCourseValues'] = $sqlValues;
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
        else{
          $sortString = $sortString . "last_activity";
        }

        $sqlValues = $_SESSION['profCourseValues'];
        $sql = test_input($_SESSION['profCourseQuery']) . " " . $sortString;
        if($sortString == test_input($_SESSION['profSort'])){
          $sql = $sql . " DESC";
          unset($_SESSION['profSort']);
        }
        else{
          $_SESSION['profSort'] = $sortString;
        }
      }

      $result = $pdo->prepare($sql);
      $result->execute($sqlValues);
      $professors = $result->fetchAll();

      $numProfs = count($professors);
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
      <h1>Bulldog Tutoring Portal</h1>
      <nav>
        <div>
          <a href="home.html">Home</a>
          <a href="portal.php">Portal</a>
          <a href="account.php">Account</a>
        </div>
      </nav>
    </header>
    <main>
      <div>
      <nav>
          <a href='admin.php'>Home</a>
          <div class="adminLink">
            <h2>Students</h2>
            <a href='studentSearch.php'>Search by Student<a>
            <a href='courseSearch.php'>Search by Course<a>
          </div>
          <div class="adminLink">
            <h2>Professors</h2>
            <a href='facultySearch.php'>Search by Professor<a>
            <a href='facultyCourseSearch.php'>Search by Course<a>
          </div>
          <div class="adminLink">
            <h2>Admin</h2>
            <a href='adminSearch.php'>Admin Accounts<a>
            <a href='newSemester.php'>Transition Semesters<a>
          </div>
        </nav>
      </div>
      <?php
          echo"
          <aside>
            <nav>
            </nav>
          </aside>
          <form action='facultyCourseSearch.php' method='post' id='facultyCourseAdmin'>
          <fieldset>  
            <div>
              <label for='subject'>Subject</label>
              <select name='subject' id='subject' onchange='getCourseCodes()'>
                <option disabled selected value></option>";

                foreach($courses as $course){
                  echo "<option value='{$course['subject']}'>" . $course['subject'] . "</option>";
                }

              echo "</select>  
              <label for='courseCode'>Course Number</label>
              <select name ='courseCode' id='courseCode'>
                <option disabled selected value></option>
              </select>
              <input type='submit' name='facultyCourseSearch'>
            </div>";
            
          if($_SERVER["REQUEST_METHOD"] == "POST"){
            echo "<table>
            <thead>
              <tr>
                <td><input type='submit' name='sortSearch' value='Subject'></td>
                <td><input type='submit' name='sortSearch' value='Course Number'</td>
                <td><input type='submit' name='sortSearch' value='Firstname'></td>
                <td><input type='submit' name='sortSearch' value='Lastname'</td>
                <td><input type='submit' name='sortSearch' value='Email'></td>
                <td><input type='submit' name='sortSearch' value='Last Activity'</td>
              </tr>
            </thead>
            <tbody>";
    
            foreach($professors as $professor){

              $timeStamp = strtotime($professor['last_activity']);
              $timeStamp = date("m/d/Y", $timeStamp);
    
            echo "<tr><td>{$professor['subject']}</td>
            <td>{$professor['course_code']}</td>
            <td>{$professor['firstname']}</td>
            <td>{$professor['lastname']}</td>
            <td>{$professor['email']}</td>
            <td>{$timeStamp}</td></tr>";
          }
    
          echo "</tbody><tfoot>
          <tr><td colspan='6'>Search returned {$numProfs} results</td></tr>
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
