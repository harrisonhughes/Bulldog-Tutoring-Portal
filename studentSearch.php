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
  
  //Constants to hold size of desired table page length and ensrure student account access
  $PAGE_LENGTH = 25;
  $STUDENT_ACCOUNT = '(0,1)';

include 'functions.php';

//Try block to monitor all database queries
try{
  $pdo = connect();

  //User has submitted form
  if($_SERVER["REQUEST_METHOD"] == "POST"){

    //User wants to manage account, go to display faculty page
    if(isset($_POST['editAccount'])){

      //Save account email and go to display page
      $_SESSION['editInfo'] = $_POST['editAccount'];
      header("Location: displayStudent.php");
      exit();
    }
    
    //Initialize empty variables for the sql string and sql values arrays
    $sql = "";
    $sqlValues = [];
    $sqlColumns = []; 

    //User has confirmed a faculty search operation
    if(isset($_POST['accountSearch'])){

      //Build skeleton of query: selecting all course information, as well as counts of all active and referred 
      //tutoring obligations for every student
      $sql = "SELECT a.*, 
              IFNULL(referred.num_referred, 0) AS num_referred, 
              IFNULL(active.num_active, 0) AS num_active
              FROM accounts AS a 
              LEFT JOIN 
                (SELECT email, COUNT(*) AS num_referred FROM referred_tutors GROUP BY email) AS referred ON referred.email = a.email 
              LEFT JOIN 
                (SELECT email, COUNT(*) AS num_active FROM active_tutors GROUP BY email) AS active ON active.email = a.email 
              WHERE a.account_type IN {$STUDENT_ACCOUNT}";

      //User has specifed a first name, add it to query
      if(!empty($_POST['fname'])){
        $sqlValues[] = test_input($_POST['fname']);
        $sqlColumns[] = "firstname";
      }

      //User has specified a last name, add it to query
      if(!empty($_POST['lname'])){
        $sqlValues[] = test_input($_POST['lname']);
        $sqlColumns[] = "lastname";
      }

      //User has specified an email, add it to query
      if(!empty($_POST['email'])){
        $sqlValues[] = test_input($_POST['email']);
        $sqlColumns[] = "a.email";
      }

      //User has specified tutor type, add it to the query
      if(isset($_POST['tutorType'])){
        $sqlValues[] = test_input($_POST['tutorType']);
        $sqlColumns[] = "account_type";
      }

      //If user specifed search parameters, add them to the query string format
      if(!empty($sqlValues)){
        for($i = 0; $i < count($sqlValues); $i++){
          $sql = $sql . " AND " . $sqlColumns[$i] . " = ?";
        } 
      }

      //Group by course email to allow relative count values seen above to be possible
      $sql .= " GROUP BY a.email";

      //Save query information; we will need to reference this if there is a sort, table page shift, or return from a course display
      $_SESSION['studentBase'] = $sql;
      $_SESSION['studentQuery'] = $sql . " ORDER BY lastname";
      $_SESSION['studentValues'] = $sqlValues;

      //If there was a previous table page shift, we can disregard this. A new search has been executed by the user
      unset($_SESSION['studentStart']);
      unset($_SESSION['studentEnd']);
    }

    //A previous query is active, and the user has selected one of the search buttons to modify the order
    else if(isset($_POST['sortSearch'])){

      //Retrieve the parameter we are sorting by
      $sortBy = test_input($_POST['sortSearch']);
      $sortString = "ORDER BY ";

      //Last name is sort choice by default, use this to track if user selects to sort by lname
      $notLast = true;

      //Modify sort string based on what was sorted by
      if($sortBy == "First"){
        $sortString = $sortString . "firstname";
      }
      else if($sortBy == "Last"){
        $sortString = $sortString . "lastname";
        $notLast = false; //Last name sorted by. Our backup will now be first name
      }
      else if($sortBy == "Email"){
        $sortString = $sortString . "email";
      }
      else if($sortBy == "Tutor Type"){
        $sortString = $sortString . "account_type";
      }
      else if($sortBy == "Active Courses"){
        $sortString = $sortString . "num_active";
      }
      else if($sortBy == "Referred Courses"){
        $sortString = $sortString . "num_referred";
      }
      else{
        $sortString = $sortString . "last_activity";
      }

      //Retrieve previous search string from original query
      $sql = test_input($_SESSION['studentBase']) . " " . $sortString;

      //User has sorted by the same parameter twice in a row. Lets sort the opposite way now
      if(isset($_SESSION['studentSort']) && $sortString == test_input($_SESSION['studentSort'])){
        $sql = $sql . " DESC";
        unset($_SESSION['studentSort']);
      }

      //Otherwise, sort normal (ascending)
      else{
        $_SESSION['studentSort'] = $sortString;
      }

      //Backup ordering parameter. This will be lastname unless primary sort is lname
      if($notLast){
        $sql .= ", lastname";
      }
      
      //If primary sort is lastname, secondary sort is firstname
      else{
        $sql .= ", firstname";
      }

      //Save updated sql query
      $_SESSION['studentQuery'] = $sql;

      //If there was a previous table page shift, we can disregard this. A new search has been executed by the user
      unset($_SESSION['studentStart']);
      unset($_SESSION['studentEnd']);
    }

    //Retrieve stored query; it is still active at this point
    $sql =  $_SESSION['studentQuery'];
    $sqlValues =  $_SESSION['studentValues'];

    //Retrive results of query for all accounts based on user account parameters and user sort parameter
    $result = $pdo->prepare($sql);
    $result->execute($sqlValues);
    $students = $result->fetchAll();

    //User has pressed the next page button
    if(isset($_POST['nextPage'])){

      //If there are enough remaining accounts to go forward another page, proceed to do so
      if($_SESSION['studentEnd'] + 1 < count($students)){
        $_SESSION['studentStart'] = $_SESSION['studentEnd'];
        $_SESSION['studentEnd'] = $_SESSION['studentStart'] + $PAGE_LENGTH;
      }
    }

    //User has pressed the previous page button
    else if(isset($_POST['prevPage'])){

      //If there exists a previous page, update session page bounds
      if($_SESSION['studentStart'] - $PAGE_LENGTH >= 0){
        $_SESSION['studentStart'] = $_SESSION['studentStart'] - $PAGE_LENGTH;
      }
      else{
        $_SESSION['studentStart'] = 0;
      }
      $_SESSION['studentEnd'] = $_SESSION['studentStart'] + $PAGE_LENGTH;
    }

    //User has just began a query and no pages have been moved; start at the beginning
    if(!isset($_SESSION['studentStart'])){
      $_SESSION['studentStart'] = 0;
      $_SESSION['studentEnd'] = $PAGE_LENGTH;
    }


    //Configure output for plural or singular accounts
    $numStudents = count($students);
    $countMessage = $numStudents . " results";
    if($numStudents == 1){
      $countMessage = "1 result";
    }
  }
}
//Ensure proper error message is returned upon a database error
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
          <a href="portal.php"><span>Portal</span></a>
          <a href="account.php"><span class='active'>Account</span></a>
          <a href="login.php"><span>Logout</span></a>
      </nav>
    </header>
    <main>
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
        <h1 class='mainHeader'>Search Student Accounts</h1>
      <?php
          echo"
          <form action='studentSearch.php' method='post' class = 'searchForm'>
          <fieldset>  
            <div>
              <div>
              <label for='fname'>First Name</label>
              <input type='text' name='fname' id='fname'>
              <label for='lname'>Last Name</label>
              <input type='text' name='lname' id='lname'>
              <label for='email'>Email</label>
              <input type='text' name='email' id='email'>
              </div>
              <div>
              <label for='tutorType'>Tutor Type</label>
              <select name='tutorType' id='tutorType'>
                <option disabled selected value></option>
                <option value='0'>Private</option>
                <option value='1'>Scholarship</option>
              </select>
              <input class='blueButton' type='submit' name='accountSearch' value='Submit'>
              </div>
            </div>";

            //User has requested a search, display the results
            if($_SERVER["REQUEST_METHOD"] == "POST"){
              
              echo "<table class='searchTable'>
              <thead>
                <tr>
                  <td><input type='submit' name='sortSearch' value='First'></td>
                  <td><input type='submit' name='sortSearch' value='Last'</td>
                  <td><input type='submit' name='sortSearch' value='Email'></td>
                  <td><input type='submit' name='sortSearch' value='Tutor Type'</td>
                  <td><input type='submit' name='sortSearch' value='Active Courses'</td>
                  <td><input type='submit' name='sortSearch' value='Referred Courses'</td>
                  <td><input type='submit' name='sortSearch' value='Last Activity'</td>
                  <td></td>
                </tr>
              </thead>
              <tbody>";

              //Loop through all entries found within the current page
              for($i = $_SESSION['studentStart']; $i < $_SESSION['studentEnd']; $i++){

                //Break if the last entry has been found already
                if($i >= count($students)){
                  break;
                }
                $student = $students[$i];

                $tutorType = "Private";
                if($student['account_type'] == 1){
                  $tutorType = "Scholarship";
                }
          
                $timeStamp = strtotime($student['last_activity']);
                $timeStamp = date("m/d/Y", $timeStamp);
          
                echo "<tr><td>{$student['firstname']}</td>
                <td>{$student['lastname']}</td>
                <td>{$student['email']}</td>
                <td>{$tutorType}</td>
                <td>{$student['num_active']}</td>
                <td>{$student['num_referred']}</td>
                <td>{$timeStamp}</td>
                <td class='view'><button class='blueButton' type='submit' name='editAccount' value='{$student['email']}'>Manage</button></td></tr>";
              }
          
              echo "</tbody><tfoot>
              <tr><td><button type='submit' name='prevPage'>< Prev</button></td>
              <td colspan='6'>Search returned {$countMessage}</td>
              <td><button type='submit' name='nextPage'>Next ></button></td></tr>
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
