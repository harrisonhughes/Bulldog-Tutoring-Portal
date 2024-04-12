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

  //Constant to hold size of desired table page length and Professor account code constant
  $PAGE_LENGTH = 25;
  $PROFESSOR_ACCOUNT = 2;

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
      header("Location: displayFaculty.php");
      exit();
    }

    //Initialize empty variables for the sql string and sql values arrays
    $sql = "";
    $sqlValues = [];
    $sqlColumns = [];

    //User has confirmed a faculty search operation
    if(isset($_POST['facultySearch'])){

      //Build skeleton of query: selecting all course information, and # of professor referrals for each course
      $sql = "SELECT a.*, 
              COUNT(c_p.email) AS num_referred 
              FROM accounts as a
              LEFT JOIN course_professors c_p ON c_p.email = a.email        
              WHERE account_type = {$PROFESSOR_ACCOUNT}";

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
      
      //If user specifed search parameters, add them to the query string format
      if(!empty($sqlValues)){
        for($i = 0; $i < count($sqlValues); $i++){
          $sql = $sql . " AND " . $sqlColumns[$i] . " = ?";
        } 
      }

      //Group by course email to allow relative count values seen above to be possible
      $sql .= " GROUP BY a.email";

       //Save query information; we will need to reference this if there is a sort, table page shift, or return from a course display
      $_SESSION['profBase'] = $sql;
      $_SESSION['profQuery'] = $sql . " ORDER BY lastname";
      $_SESSION['profValues'] = $sqlValues;

      //If there was a previous table page shift, we can disregard this. A new search has been executed by the user
      unset($_SESSION['professorStart']);
      unset($_SESSION['professorEnd']);
    }

     //A previous query is active, and the user has selected one of the search buttons to modify the order
    else if(isset($_POST['sortSearch'])){

      //Retrieve the parameter we are sorting by
      $sortBy = test_input($_POST['sortSearch']);
      $sortString = "ORDER BY ";

      //Last name is sort choice by default, use this to track if user selects to sort by lname
      $notLast = true;

      //Modify sort string based on what was sorted by
      if($sortBy == "Firstname"){
        $sortString = $sortString . "firstname";
      }
      else if($sortBy == "Lastname"){
        $sortString = $sortString . "lastname";
        $notLast = false; //Last name sorted by. Our backup will now be first name
      }
      else if($sortBy == "Email"){
        $sortString = $sortString . "email";
      }
      else if($sortBy == "Open Referrals"){
        $sortString = $sortString . "num_referred";
      }
      else{
        $sortString = $sortString . "last_activity";
      }

      //Retrieve previous search string from original query
      $sql = test_input($_SESSION['profBase']) . " " . $sortString;

      //User has sorted by the same parameter twice in a row. Lets sort the opposite way now
      if(isset($_SESSION['profSort']) && $sortString == test_input($_SESSION['profSort'])){
        $sql = $sql . " DESC";
        unset($_SESSION['profSort']);
      }

      //Otherwise, sort normal (ascending)
      else{
        $_SESSION['profSort'] = $sortString;
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
      $_SESSION['profQuery'] = $sql;

      //If there was a previous table page shift, we can disregard this. A new search has been executed by the user
      unset($_SESSION['professorStart']);
      unset($_SESSION['professorEnd']);
    }

    //Retrieve stored query; it is still active at this point
    $sql =  $_SESSION['profQuery'];
    $sqlValues =  $_SESSION['profValues'];

    //Retrive results of query for all accounts based on user account parameters and user sort parameter
    $result = $pdo->prepare($sql);
    $result->execute($sqlValues);
    $professors = $result->fetchAll();

    //User has pressed the next page button
    if(isset($_POST['nextPage'])){

      //If there are enough remaining accounts to go forward another page, proceed to do so
      if($_SESSION['professorEnd'] + 1 < count($professors)){
        $_SESSION['professorStart'] = $_SESSION['professorEnd'];
        $_SESSION['professorEnd'] = $_SESSION['professorStart'] + $PAGE_LENGTH;
      }
    }

    //User has pressed the previous page button
    else if(isset($_POST['prevPage'])){

      //If there exists a previous page, update session page bounds
      if($_SESSION['professorStart'] - $PAGE_LENGTH >= 0){
        $_SESSION['professorStart'] = $_SESSION['professorStart'] - $PAGE_LENGTH;
      }
      else{
        $_SESSION['professorStart'] = 0;
      }
      $_SESSION['professorEnd'] = $_SESSION['professorStart'] + $PAGE_LENGTH;
    }

    //User has just began a query and no pages have been moved; start at the beginning
    if(!isset($_SESSION['professorStart'])){
      $_SESSION['professorStart'] = 0;
      $_SESSION['professorEnd'] = $PAGE_LENGTH;
    }

    //Configure output for plural or singular accounts
    $numProfs = count($professors);
    $countMessage = $numProfs . " results";
    if($numProfs == 1){
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
      <div>
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
      </div>
      <h1 class="mainHeader">Search Professor Accounts</h1>
      <?php
          echo"
          <aside>
            <nav>
            </nav>
          </aside>
          <form action='facultySearch.php' method='post' class = 'searchForm'>
          <fieldset>  
            <div>
              <div>
              <label for='fname'>First Name</label>
              <input type='text' name='fname' id='fname'>
              <label for='lname'>Last Name</label>
              <input type='text' name='lname' id='lname'>
              <label for='email'>Email</label>
              <input type='text' name='email' id='email'>
              <input class='blueButton' type='submit' name='facultySearch'>
              </div>
            </div>";

            //User has requested a search, display the results
            if($_SERVER["REQUEST_METHOD"] == "POST"){
              
              echo "<table class='searchTable'>
              <thead>
                <tr>
                  <td><input type='submit' name='sortSearch' value='Firstname'></td>
                  <td><input type='submit' name='sortSearch' value='Lastname'</td>
                  <td><input type='submit' name='sortSearch' value='Email'></td>
                  <td><input type='submit' name='sortSearch' value='Open Referrals'</td>
                  <td><input type='submit' name='sortSearch' value='Last Activity'</td>
                  <td></td>
                </tr>
              </thead>
              <tbody>";
              
              //Loop through all entries found within the current page
              for($i = $_SESSION['professorStart']; $i < $_SESSION['professorEnd']; $i++){

                //Break if the last entry has been found already
                if($i >= count($professors)){
                  break;
                }
                $professor = $professors[$i];
          
                $timeStamp = strtotime($professor['last_activity']);
                $timeStamp = date("m/d/Y", $timeStamp);
          
                echo "<tr><td>{$professor['firstname']}</td>
                <td>{$professor['lastname']}</td>
                <td>{$professor['email']}</td>
                <td>{$professor['num_referred']}</td>
                <td>{$timeStamp}</td>
                <td class='view'><button class='blueButton' type='submit' name='editAccount' value='{$professor['email']}'>Manage</button></td></tr>";
              }
          
              echo "</tbody><tfoot>
              <tr><td><button type='submit' name='prevPage'>< Prev</button></td>
              <td colspan='4'>Search returned {$countMessage}</td>
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
