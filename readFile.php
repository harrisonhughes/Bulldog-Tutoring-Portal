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
  
  //File operations adapted from: https://dev.to/einlinuus/how-to-upload-files-with-php-correctly-and-securely-1kng
  require 'vendor/autoload.php'; //For excel reading
  include 'functions.php';
  use Shuchkin\SimpleXLSX;

  //Define constants for access control, professor account creation, current directory, and excel file format
  $PROFESSOR_ACCOUNT = 2;
  $DEFAULT_PROF_PASSWORD = "dajdbjsabdadbad89312";
  $FILE_DIRECTORY = __DIR__  . "/courseLists/";
  $EXCEL_FILE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

//Try block to monitor all database operations
try{
  $pdo = connect();

  if(isset($_POST['submitReferrals'])){
    //Retrieve information from uploaded file
    $filepath = $_FILES['fileUpload']['tmp_name'];
    $fileSize = filesize($filepath);

    //File has not been uploaded at all
    if(!$fileSize || $fileSize == 0){
      $_SESSION['errors']['semester'] = "Must upload a file to begin the new semester process!";
      header("Location: newSemester.php");
      exit();
    }

    //Uploaded file is too big to be of the correct type
    else if($fileSize > 1000000){
      $_SESSION['errors']['semester'] = "File is too big. Ensure the correct file is selected.";
      header("Location: newSemester.php");
      exit();
    }

    //Get file information securely
    $fileinfo = finfo_open(FILEINFO_MIME_TYPE);
    $filetype = finfo_file($fileinfo, $filepath);

    //File must be of excel type
    if($filetype != $EXCEL_FILE){
      $_SESSION['errors']['semester'] = "Ensure that the uploaded file is of an '.xlsx' type.";
      header("Location: newSemester.php");
      exit();
    }

    //Get actual file into a variable now that we know it is safe
    $xlsxFile = $filepath;

    //Ensure we can access the excel sheet
    if($xlsx = SimpleXLSX::parse($xlsxFile)){

      //Obtain term row both as an integer and as a string
      $term = $xlsx->rows()[1][3];
      $strTerm = (string)$term;

      //If term code is not valid, we know the excel sheet is not of a valid type
      if(!$term || strlen($strTerm) != 6 || $strTerm[0] != '2'){
        $_SESSION['errors']['semester'] = "Excel file is in an incorrect format. Ensure proper column names and output.";
        header("Location: newSemester.php");
        exit(); 
      }

      //Otherwise if the termcode has already been seen on an input file, the user has mistakenly uploaded an old list
      else{
        $currentFilepath = $FILE_DIRECTORY . $term . "CourseList.xlsx";
        if(file_exists($currentFilepath)){
          $_SESSION['errors']['semester'] = "The system has already received a courselist for this semester.";
          header("Location: newSemester.php");
          exit(); 
        }
      }

      //Loop through each row 
      foreach ($xlsx->rows() as $row) {

        //Retrieve all data points whlie preventin injection attacks
        $firstname = test_input($row[1]);
        $lastname = test_input($row[0]);
        $email = test_input($row[2]);
        $subject = test_input($row[5]);
        $number = test_input($row[6]);

        //Make sure the row is not the first row, and that the course number is an integer (not a graduate or lab course)
        if((string)(int)$number === $number && $email != "Email"){
          //If the row still has data in it
          if($email && $subject && $number && $firstname && $lastname){

            //Select course id given the current row using sql injection attack prevention steps
            $sql = "SELECT id FROM courses WHERE subject = ? AND course_code = ?";
            $result = $pdo->prepare($sql);
            $result->execute([$subject, $number]);
            $course = $result->fetch();

            //If the course is not in the database already
            if(!$course){

              //Enter the course into the database using sql injection attack prevention steps
              $sql = "INSERT INTO courses (subject, course_code) VALUES (?, ?)";
              $result = $pdo->prepare($sql);
              $result->execute([$subject, $number]);   

              //Obtain the newly entered course id using sql injection attack prevention steps
              $sql = "SELECT id FROM courses WHERE subject = ? AND course_code = ?";
              $result = $pdo->prepare($sql);
              $result->execute([$subject, $number]);
              $course = $result->fetch();
            }

            $courseId = $course[0]; 
            
            //Create a professor referral for the row using sql injection attack prevention steps
            $sql = "INSERT IGNORE INTO course_professors (email, course_id) VALUES (?, ?)";
            $result = $pdo->prepare($sql);
            $result->execute([$email, $courseId]);

            //Check if professor account exists using sql injection attack prevention steps
            $sql = "SELECT account_type FROM accounts WHERE email = ?";
            $result = $pdo->prepare($sql);
            $result->execute([$email]);
            $accountType = $result->fetch();

            //Account does not exist in database
            if(empty($accountType)){

              //Create a professor account using a default passcode if no account exists
              $sqlValues = [$email, password_hash($DEFAULT_PROF_PASSWORD, PASSWORD_DEFAULT), $PROFESSOR_ACCOUNT, $firstname, $lastname];
              $valuePlaceholders = rtrim(str_repeat('?,', count($sqlValues)), ',');
              $sql = "INSERT INTO accounts (email, hashed_password, account_type, firstname, lastname) VALUES ({$valuePlaceholders})";
              $result = $pdo->prepare($sql);
              $result->execute($sqlValues);
            }

            //Account is already created, but does not have the necessary professor access. Give it to them
            else if($accountType != $PROFESSOR_ACCOUNT){

              //Update account to be that of a professor account
              $sql = "UPDATE accounts SET account_type = '{$PROFESSOR_ACCOUNT}' WHERE email = '{$email}'";
              $result = $pdo->prepare($sql);
              $result->execute();
            }
          }
          else{
            break;
          }
        }
      }

      //Create new filepath and store file for records
      $newFilepath = $FILE_DIRECTORY . $term . "CourseList.xlsx";
      if(!move_uploaded_file($filepath, $newFilepath)){
        $_SESSION['errors']['semester'] = "Error storing file on server.";
        header("Location: newSemester.php");
        exit();        
      }

      // Update current semester transition status row
      $sql = "SELECT * FROM semesters
      ORDER BY term_code DESC
      LIMIT 1";
      $result = $pdo->query($sql);
      $semester = $result->fetch();

      //Get term code of current semester process
      $term = $semester['term_code'];
      $currentTimestamp = date("Y-m-d H:i:s");

      //Update semster with time of file upload 
      $sql = "UPDATE semesters SET file_uploaded = '{$currentTimestamp}' WHERE term_code = '{$term}'";
      $result = $pdo->prepare($sql);
      $result->execute();

      //Update page
      header("Location: newSemester.php");  
    }

    ///Otherwise there was an error opening and reading the excel file
    else{
      $error = SimpleXLSX::parseError();
      echo "<p>Critical Error (Database):<br><br>{$error}<br><br>Please save this message and inform the head website administrator as soon as possible.</p>";
      exit();
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