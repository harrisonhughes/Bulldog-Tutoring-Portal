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
  //$DEFAULT_PROF_PASSWORD = getenv('DEFAULT_PROF');
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

      //Define constants to ensure the excel sheet is formatted properly
      $EXCEL_ROW_LENGTH = 9; //Excel row length in proerly formatted document
      $TERM_CODE_LENGTH = 6; //Term Code length for a semester code
      $TERM_CODE_FIRSTCHAR = '2'; //Ensure 6 digit value begins with a '2' for 2000

      //Switch variable to ensure all file aspects are up to code
      $validFile = true;
      
      //Ensure first row is filled in 
      if(!empty($xlsx->rows()[0])){

        //Get first row and calculate length to ensure excel file is of correct format
        $firstRow = $xlsx->rows()[0];
        $excelRowLength = count($firstRow);

        //Do not allow submission if format is wrong
        if($excelRowLength != $EXCEL_ROW_LENGTH){
          $validFile = false;
        }
      }

      //Do not allow submission if form is empty
      else{
        $validFile = false;
      }

      //Check the first professor row and ensure it has a term code
      if(!empty($xlsx->rows()[1][3])){

        //Capture the term code and ensure it is of the right format to indicate a semester
        $termCode = (string)$xlsx->rows()[1][3];
        if(strlen($termCode) != $TERM_CODE_LENGTH || $termCode[0] != $TERM_CODE_FIRSTCHAR){

          //Do not allow submission if term code is not formatted correctly
          $validFile = false;
        }

        //Otherwise if the termcode has already been seen on an input file, the user has mistakenly uploaded an old list
        else{

          //Build file location based on term code, should conform to eventual filename pattern
          $currentFilepath = $FILE_DIRECTORY . $termCode . "CourseList.xlsx";

          //If the file exists, you have mistakenly uploaded an old courselist
          if(file_exists($currentFilepath)){

            //Store error message and prevent submission
            $_SESSION['errors']['semester'] = "The system has already received a courselist for this semester.";
            header("Location: newSemester.php");
            exit(); 
          }
        }
      }

      //Prevent submission if first row is empty
      else{
        $validFile = false;
      }

      //Prevent submission and provide error messages if the file is not of the correct format 
      if(!$validFile){

        //Store error message and prevent submission
        $_SESSION['errors']['semester'] = "Excel file is in an incorrect format. Ensure proper column names and output.";
        header("Location: newSemester.php");
        exit(); 
      }

      //Set excel sheet cell constants for individual rows
      $TRUMAN_EMAIL = "@truman.edu"; //Only allow this email format
      $VALID_NAME = '/^[a-zA-Z\'\- .]+$/'; //Name must be of a valid configuration
      $MAX_LENGTH = 50; //Max length of name, password, and email
      $MIN_LENGTH = 6; //Min length of password
      $MIN_COURSE_LENGTH = 2; //Min of subject and course code specifications
      $MAX_COURSE_LENGTH = 5; //Max of subject and course code specifications

      //Loop through each row 
      foreach ($xlsx->rows() as $row) {
        
        //Initialize row format boolean variable switch
        $validRow = true;

        //Check if all necessary cells are filled with data, otherwise flip the switch
        if(empty($row[0]) || empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[5]) || empty($row[6])){
          $validRow = false;
        }
        else{

          //Ensure first name cell is of correct format
          $firstname = test_input($row[1]);
          if(!preg_match($VALID_NAME, $firstname)){//Name must be of a valid name format
            $validRow = false;
          }
          else if(strlen($firstname) > $MAX_LENGTH){
            $validRow = false;
          }
  
          //Ensure last name cell is of correct format
          $lastname = test_input($row[0]);
          if(!preg_match($VALID_NAME, $lastname)){ //Name must be of a valid name format
            $validRow = false;
          }
          else if(strlen($lastname) > $MAX_LENGTH){
            $validRow = false;
          }

          //Ensure email cell is of correct format
          $email = test_input($row[2]);
          if(substr($email, -11) != $TRUMAN_EMAIL){ //Must end in @truman.edu
            $validRow = false;
          }
          else if(strlen($email) > $MAX_LENGTH){
            $validRow = false;
          }

          //Ensure subject cell is of correct format
          $subject = test_input($row[5]);
          if($subject !== strtoupper($subject)){ //Ensure subject is of uppercase type
            $validRow = false;
          }
          else if(strLen($subject) > $MAX_COURSE_LENGTH){
            $validRow = false;
          }
          else if(strLen($subject) < $MIN_COURSE_LENGTH){
            $validRow = false;
          }

          //Ensure course code cell is of correct format
          $number = test_input($row[6]);
          if(!ctype_digit($number)){ //Prevent non-numerical course codes, including graduate and lab sections
            $validRow = false;
          }
          else if(strLen($number) > $MAX_COURSE_LENGTH){
            $validRow = false;
          }
          else if(strLen($number) < $MIN_COURSE_LENGTH){
            $validRow = false;
          }
        }

        //If row conforms to specifications above, store it in the database accurately
        if($validRow){

          //Double check to ensure our values are active (redundant checking)
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
      $newFilepath = $FILE_DIRECTORY . $termCode . "CourseList.xlsx";
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