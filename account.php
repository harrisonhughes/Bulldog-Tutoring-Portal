<?php
  include_once 'functions.php';
  session_start();

  try{
    $pdo = connect(); 

    if(!isset($_SESSION['user'])){
      header("Location: login.html");
      exit();
    }

    $email = test_input($_SESSION['user']);

    
    if($_SERVER["REQUEST_METHOD"] == "POST"){
      if(isset($_POST['removeCourse'])){
        $courseId = test_input($_POST['removeCourse']);

        $sql = "DELETE FROM active_tutors WHERE course_id = ? AND email = ?";
        $result = $pdo->prepare($sql);
        $result->execute([$courseId, $email]);
      }
      else if(isset($_POST['acceptReferral'])){
        $courseId = test_input($_POST['acceptReferral']);

        $sql = "INSERT INTO active_tutors (email, course_id) VALUES (?, ?)";
        $result = $pdo->prepare($sql);
        $result->execute([$email, $courseId]);

        $sql = "DELETE FROM referred_tutors WHERE course_id = ? AND email = ?";
        $result = $pdo->prepare($sql);
        $result->execute([$courseId, $email]);
      }
      else if(isset($_POST['declineReferral'])){
        $courseId = test_input($_POST['declineReferral']);

        $sql = "DELETE FROM referred_tutors WHERE course_id = ? AND email = ?";
        $result = $pdo->prepare($sql);
        $result->execute([$courseId, $email]);
      }
    }

    //Select query with sql injection attack prevention steps - Get account with given email
    $sql = "SELECT * FROM accounts WHERE email = ?";
    $result = $pdo->prepare($sql);
    $result->execute([$email]);
    $user = $result->fetch();

    if($user['account_type'] == 2){
      header("Location: reference.php");
      exit();
    }
    else if($user['account_type'] == 3){
      header("Location: admin.php");
      exit();
    }

    $uName = $user['firstname'] . " " . $user['lastname'];

    $sql = "SELECT c.* FROM courses c 
    JOIN active_tutors a_t ON c.id = a_t.course_id
    JOIN accounts a ON a.email = a_t.email
    WHERE a.email = ?";

    $result = $pdo->prepare($sql);
    $result->execute([$email]);
    $activeCourses = $result->fetchAll();

    $sql = "SELECT c.* FROM courses c 
    JOIN referred_tutors r_t ON c.id = r_t.course_id
    JOIN accounts a ON a.email = r_t.email
    WHERE a.email = ?";

    $result = $pdo->prepare($sql);
    $result->execute([$email]);
    $referredCourses = $result->fetchAll();
  } 
  //Unable to create connection with database
  catch (PDOException $e){
    die( $e->getMessage());
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Bulldog Tutoring Account</title>
    <link rel="stylesheet" href="styles.css"/>
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
      <?php
        echo "<h1>{$uName}'s Account</h1>";
      ?>
      <div class="information">
        <h2>Personal Information</h2>
        <div>
          <table>
            <tbody>
            <?php
              $accountType = "Student";
              $tutorType = "Private";

              if($user['account_type'] == 1){
                $accountType = "Professor";
              }

              if($user['private_tutor'] == 1){
                $tutorType = "Scholarship";
              }

              echo 
              "<tr><th>Name</th>
              <td>{$uName}</td></tr>
              <tr><th>Email</th>
              <td>{$email}</td></tr>
              <tr><th>Account Type</th>
              <td>{$accountType}</td></tr>";
              if($accountType == "Student"){
                echo 
                "<tr><th>Tutoring Type</th>
                <td>{$tutorType}</td></tr>"; 
              }
            ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="activeCourses">
        <h2>Current Tutoring Positions</h2>
        <form action="account.php" method="post" id="manageCourses">
          <fieldset>
            <table>
              <thead>
                <tr>
                  <th>Subject</th>
                  <th>Course Code</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php
              if(empty($activeCourses)){
                echo "<tr><td colspan='2'>You have no active obligations</td></tr>";
              }
              else{
                foreach($activeCourses as $course){
                  $subject = $course['subject'];
                  $courseCode = $course['course_code'];
                  $id = $course['id'];
                  echo 
                  "<tr><td>{$subject}</td>
                  <td>{$courseCode}</td>
                  <td><button type='submit' name='removeCourse' value='{$id}'>Remove Course</button></td></tr>";
                }
              }
              ?>
              </tbody> 
            </table>
          </fieldset>
        </form>
      </div>
      <div class="referredCourses">
        <h2>Current Referrals</h2>
        <form action="account.php" method="post" id="manageReferrals">
          <fieldset>
            <table>
              <thead>
                <tr>
                  <th>Subject</th>
                  <th>Course Code</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php
              if(empty($referredCourses)){
                echo "<tr><td colspan='2'>You have no referrals at this time</td></tr>";
              }
              else{
                foreach($referredCourses as $course){
                  $subject = $course['subject'];
                  $courseCode = $course['course_code'];
                  $id = $course['id'];

                  echo 
                  "<tr><td>{$subject}</td>
                  <td>{$courseCode}</td>
                  <td><button type='submit' name='acceptReferral' value='{$id}'>Accept Referral</button></td>
                  <td><button type='submit' name='declineReferral' value='{$id}'>Decline Referral</button></td></tr>";
                }
              }
              ?>
              </tbody> 
            </table>
          </fieldset>
        </form>
      </div>
    </main>
    <footer>
    </footer>
  </body>
</html>