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

  //Student account type codes, urls for emails
  $STUDENT_ACCOUNT = "(0,1)";
  $URL_LOGIN = "https://bulldogtutoringportal.com/login.php";
  $URL_CREATE = "https://bulldogtutoringportal.com/createAccount.php";
  
  include 'functions.php';

  //Return to newSemester while executing
  header("Location: newSemester.php");  

//Try block to monitor all database acceses
try{
  $pdo = connect();
  
  //User has just requested an email blast to students
  if(isset($_POST['studentReferrals'])){

    //Get all student information including counts of all referred and active tutoring obligations
    $sql = "SELECT a.*, 
            IFNULL(referred.num_referred, 0) AS num_referred, 
            IFNULL(active.num_active, 0) AS num_active
            FROM accounts AS a 
            LEFT JOIN 
              (SELECT email, COUNT(*) AS num_referred FROM referred_tutors GROUP BY email) AS referred ON referred.email = a.email 
            LEFT JOIN 
              (SELECT email, COUNT(*) AS num_active FROM active_tutors GROUP BY email) AS active ON active.email = a.email 
            WHERE a.account_type IN {$STUDENT_ACCOUNT}
            GROUP BY a.email";

    $result = $pdo->query($sql);
    $tutoringAccounts = $result->fetchAll();

    //Get all records from the active tutoring table
    $sql = "SELECT * FROM active_tutors";
    $result = $pdo->query($sql);
    $activeTutors = $result->fetchAll();

    //Move all active tutors to the referral table - every student must redeclare their interest in tutoring each semester
    if($activeTutors){
      foreach($activeTutors as $activeTutor){

        //Remove from active
        $sql = "DELETE FROM active_tutors WHERE email = ? AND course_id = ?";
        $result = $pdo->prepare($sql);
        $result->execute([$activeTutor['email'], $activeTutor['course_id']]);
  
        //Insert into referred
        $sql = "INSERT IGNORE INTO referred_tutors (email, course_id) VALUES (?, ?)";
        $result = $pdo->prepare($sql);
        $result->execute([$activeTutor['email'], $activeTutor['course_id']]);
      }
    }

    //Loop through all students who currently have at least one active tutoring referral
    foreach($tutoringAccounts as $account){

      //Only email student if they have courses to interact with 
      if($account['num_referred'] > 0 || $account['num_active'] > 0){

        //Build full student name
        $name = $account['firstname'] . " " . $account['lastname'];

        //Build email elements
        $emailBody = "Dear {$name},<br><br>It's that time again! We have on record that this email is linked to an account on our platform - so we will assume you know how this works; if not, 
        feel free to explore the site to become familiar.<br><br>";

        //Configure plural vs singular obligations
        $also = "";
        if($account['num_active'] > 0){

          //Individual had more than one active tutoring obligation in the previous semester
          $pluralActive = "";
          if($account['num_active'] > 1){
            $pluralActive = "s";
          }

          //Continue to build message with previous active courses portion
          $emailBody .= "You previously had tutoring obligations for <b>{$account['num_active']}</b> course{$pluralActive}. ";
          $also = "also ";
        }

        //Configure plural vs singular obligations
        if($account['num_referred'] > 0){

          //Individual has more than one new referral
          $pluralReferred = "";
          if($account['num_referred'] > 1){
            $pluralReferred = "s";
          }

          $emailBody .= "You have {$also}been referred for <b>{$account['num_referred']}</b> new course{$pluralReferred} by your professors.<br><br>";
        }

        $emailBody .= "If you would like to continue participation with our platform as a tutor, please <a href='{$URL_LOGIN}'>sign in</a>
        and visit your account page to interact with all of your referrals. Thank you for your participation in the tutoring process!";

        $emailHeader = "Student Tutoring Opportunity";

        sendEmail($account['email'], $name, $emailHeader, $emailBody);
      }
    }

    //Get email of all referred students without an account
    $sql = "SELECT r_t.email, COUNT(r_t.email) AS num_referred 
    FROM referred_tutors r_t
    WHERE NOT EXISTS(
      SELECT 1
      FROM accounts AS a
      WHERE a.email = r_t.email
    )
    GROUP BY r_t.email";

    $result = $pdo->query($sql);
    $tutoringEmails = $result->fetchAll();

    //Email all students who do not have an account in the system yet
    foreach($tutoringEmails as $tutors){
      $plural = "";
      if($tutors['num_referred'] > 1){
        $plural = "s";
      }

        $emailBody = "Dear Truman State Student,<br><br>We are the Bulldog Tutoring Portal, and our goal is to inspire and facilitate a greater level of peer-to-peer learning here at Truman State.<br><br>

        We would like to inform you that you have been referred to be a potential tutor for <b>{$tutors['num_referred']}</b> course{$plural} by your professors. While participation is not mandatory, 
        your acceptance of any tutoring referral will provide other Bulldogs with the opportunity to potentially connect with you and learn from a great student such as yourself.<br><br>
        Please <a href='{$URL_CREATE}'>create an account</a> and navigate to your account page to interact with your tutoring referrals. You can opt out at
        any time.";

        $emailHeader = "Student Tutoring Opportunity";

        sendEmail($tutors['email'], $tutors['email'], $emailHeader, $emailBody);
    }
  }

  // Update current semester transition status row
  $sql = "SELECT * FROM semesters
  ORDER BY term_code DESC
  LIMIT 1";
  $result = $pdo->query($sql);
  $semester = $result->fetch();

  //Get current term code of actiev semester and timestamp
  $term = $semester['term_code'];
  $currentTimestamp = date("Y-m-d H:i:s");

  //Update current semester with final timestamp update
  $sql = "UPDATE semesters SET student_emails  = '{$currentTimestamp}' WHERE term_code = '{$term}'";
  $result = $pdo->prepare($sql);
  $result->execute();

  header("Location: newSemester.php");  
}

//Unable to create connection with database
catch(PDOException $e){
  $error = $e->getMessage();
  echo "<p>Critical Error (Database):<br><br>{$error}<br><br>Please save this message and inform the head website administrator as soon as possible.</p>";
  exit(); 
}
$pdo = null;
?>