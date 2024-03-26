<?php

  $PROFESSOR_ACCOUNT = 2;
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

    $sql = "SELECT * FROM accounts WHERE account_type = {$PROFESSOR_ACCOUNT}";
    $result = $pdo->prepare($sql);
    $result->execute();
    $professors = $result->fetchAll();

    if($_SERVER["REQUEST_METHOD"] == "POST"){
      if(!isset($_POST['subject']) || !isset($_POST['courseCode']) || !isset($_POST['profEmail'])){
        $_SESSION['errors']['semester'] = "Must set all fields to complete a referral";
        header("Location: newSemester.php");
        exit();
      }

      $subject = test_input($_POST['subject']);
      $courseCode = test_input($_POST['courseCode']);
      $profEmail = test_input($_POST['profEmail']);

      $sql = "SELECT id FROM courses WHERE subject = ? AND course_code = ?";
      $result = $pdo->prepare($sql);
      $result->execute([$subject, $courseCode]);
      $course = $result->fetch();
      $courseId = $course[0];

      $sql = "INSERT INTO course_professors (email, course_id) VALUES (?, ?)";
      $result = $pdo->prepare($sql);
      $result->execute([$profEmail, $courseId]);
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
      <p id="semesterError" class="error">
        <?php 
        if(isset($_SESSION['errors']['semester'])){
          echo $_SESSION['errors']['semester'];
          unset($_SESSION['errors']['semester']);}
        ?>
      </p>
      <?php
          echo"
          <aside>
            <nav>
            </nav>
          </aside>
          <form action=newSemester.php method='post' id='facultyCourseAdmin'>
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
              <label for='profEmail'>Professor</label>
              <select name='profEmail' id='profEmail'>
                <option disabled selected value></option>";

                foreach($professors as $professor){
                  echo "<option value='{$professor['email']}'>" . $professor['email'] . "</option>";
                }

              echo "</select>  
              <input type='submit' name='addNewReferral'>
            </div>";
      ?>
    </main>
    <footer>
    </footer>
  </body>
</html>
