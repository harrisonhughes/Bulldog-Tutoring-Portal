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

  //Constant to hold size of desired table page length and default database timestamp
  $PAGE_LENGTH = 25;
  $DEFAULT_TIMESTAMP = "2000-01-01 00:00:00";

  include 'functions.php';

  //Try block to monitor all sql database accesses
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

    //Get all subjects of courses in the database using sql injection attack prevention steps
    $sql = "SELECT DISTINCT subject FROM courses ORDER BY subject";
    $result = $pdo->prepare($sql);
    $result->execute();
    $courses = $result->fetchAll();

    //Format javascript course code retrieval string for subject select box
    $javascript = "\"getCourseCodes('subject', 'courseCode')\"";

    //If user has submitted a form entry
    if($_SERVER["REQUEST_METHOD"] == "POST"){

      //User has pressed the course informational button
      if(isset($_POST['editCourse'])){

        //Save the specific course that was clicked and route to the course display page
        $_SESSION['editInfo'] = $_POST['editCourse'];
        header("Location: displayCourse.php");
        exit();
      }

      //Initialize empty variables for the sql string and sql values array
      $sql = "";
      $sqlValues = [];
      $sqlColumns = [];

      //User has confirmed a course search operation
      if(isset($_POST['courseSearch'])){

        //Build skeleton of query: selecting all course information, including the number of active tutors, 
        //number of referred tutors, and numebr of professor referrals for each course
        $sql = "SELECT c.*, 
                COUNT(DISTINCT a_t.email) AS active_tutors, 
                COUNT(DISTINCT r_t.email) AS referred_tutors,
                COUNT(DISTINCT c_p.email) AS open_prof_referrals
                FROM courses c
                LEFT JOIN active_tutors a_t ON a_t.course_id = c.id
                LEFT JOIN referred_tutors r_t ON r_t.course_id = c.id
                LEFT JOIN course_professors c_p ON c_p.course_id = c.id";

        //User has specified a subject. We must add this to the query 
        if(!empty($_POST['subject'])){
          $sqlValues[] = test_input($_POST['subject']);
          $sqlColumns[] = "c.subject";
        }

        //User has specified a course code. We must add this to the query
        if(!empty($_POST['courseCode'])){
          $sqlValues[] = test_input($_POST['courseCode']);
          $sqlColumns[] = "c.course_code";
        }

        //If user specifed search parameters, add them to the query string format
        if(!empty($sqlValues)){
          $sql = $sql . " WHERE " . $sqlColumns[0] . " = ?";        
          if(count($sqlValues) > 1){
            $sql = $sql . " AND " . $sqlColumns[1] . " = ?";
          } 
        }

        //Group by course id value to allow relative count values seen above to be possible
        $sql = $sql . " GROUP BY c.id";

        //Save query information; we will need to reference this if there is a sort, table page shift, or return from a course display
        $_SESSION['courseBase'] = $sql;
        $_SESSION['courseQuery'] = $sql . " ORDER BY subject, course_code";
        $_SESSION['courseValues'] = $sqlValues;

        //If there was a previous table page shift, we can disregard this. A new search has been executed by the user
        unset($_SESSION['courseStart']);
        unset($_SESSION['courseEnd']);
      }

      //A previous query is active, and the user has selected one of the search buttons to modify the order
      else if(isset($_POST['sortSearch'])){

        //Retrieve the parameter we are sorting by
        $sortBy = test_input($_POST['sortSearch']);
        $sortString = "ORDER BY ";

        //Subject is sort choice by default, use this to track if user selects to sort by subject
        $notSubject = true;

        //Modify sort string based on what was sorted by
        if($sortBy == "Subject"){
          $sortString = $sortString . "subject";
          $notSubject = false; //Subject sorted by. Our backup will now be course code
        }
        else if($sortBy == "Course Number"){
          $sortString = $sortString . "course_code";
        }
        else if($sortBy == "Active Tutors"){
          $sortString = $sortString . "active_tutors";
        }
        else if($sortBy == "Referred Tutors"){
          $sortString = $sortString . "referred_tutors";
        }
        else if($sortBy == "Open Prof Referrals"){
          $sortString = $sortString . "open_prof_referrals";
        }

        //Retrieve previous search string from original query
        $sql = test_input($_SESSION['courseBase']) . " " . $sortString;

        //User has sorted by the same parameter twice in a row. Lets sort the opposite way now
        if(isset($_SESSION['courseSort']) && $sortString == test_input($_SESSION['courseSort'])){
          $sql = $sql . " DESC"; //Descending instead of ascending
          unset($_SESSION['courseSort']); //If pressed again, we will do ascending again
        }

        //Otherwise, sort normal (ascending)
        else{
          $_SESSION['courseSort'] = $sortString;
        }

        //Backup ordering parameter. This will be subject unless primary sort is subject
        if($notSubject){
          $sql .= ", subject";
        }

        //If primary sort is subject, secondary sort is course code
        else{
          $sql .= ", course_code";
        }

        //Save updated sql query
        $_SESSION['courseQuery'] = $sql;

        //If there was a previous table page shift, we can disregard this. A new search has been executed by the user
        unset($_SESSION['courseStart']);
        unset($_SESSION['courseEnd']);
      }

      //Retrieve stored query; it is still active at this point
      $sql =  $_SESSION['courseQuery'];
      $sqlValues =  $_SESSION['courseValues'];

      //Retrive results of query for all courses based on user course parameters and user sort parameter
      $result = $pdo->prepare($sql);
      $result->execute($sqlValues);
      $courseVals = $result->fetchAll();

      //User has pressed the next page button
      if(isset($_POST['nextPage'])){

        //If there are enough remaining courses to go forward another page, proceed to do so
        if($_SESSION['courseEnd'] + 1 < count($courseVals)){
          $_SESSION['courseStart'] = $_SESSION['courseEnd'];
          $_SESSION['courseEnd'] = $_SESSION['courseStart'] + $PAGE_LENGTH;
        }
      }
  
      //User has pressed the previous page button
      else if(isset($_POST['prevPage'])){

        //If there exists a previous page, update session page bounds
        if($_SESSION['courseStart'] - $PAGE_LENGTH >= 0){
          $_SESSION['courseStart'] = $_SESSION['courseStart'] - $PAGE_LENGTH;
        }
        else{
          $_SESSION['courseStart'] = 0;
        }
        $_SESSION['courseEnd'] = $_SESSION['courseStart'] + $PAGE_LENGTH;
      }

      //User has just began a query and no pages have been moved; start at the beginning
      if(!isset($_SESSION['courseStart'])){
        $_SESSION['courseStart'] = 0;
        $_SESSION['courseEnd'] = $PAGE_LENGTH;
      }

      //Configure output for plural or singular courses
      $numCourses = count($courseVals);
      $countMessage = $numCourses . " results";
      if($numCourses == 1){
        $countMessage = "1 result";
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
      </div>
      <h1 class="mainHeader">Search Courses</h1>
      <?php
          echo"
          <aside>
            <nav>
            </nav>
          </aside>
          <form action='courseSearch.php' method='post' class = 'searchForm'>
          <fieldset>  
            <div>
              <div>
              <label for='subject'>Subject</label>
              <select name='subject' class='courseSelect' id='subject' onchange={$javascript}>
                <option disabled selected value></option>";

                //Display all subjects in select box
                foreach($courses as $course){
                  echo "<option value='{$course['subject']}'>" . $course['subject'] . "</option>";
                }

              echo "</select>  
              <label for='courseCode'>Course Number</label>
              <select name ='courseCode' class='courseSelect' id='courseCode'>
                <option disabled selected value></option>
              </select>
              <input class='blueButton' type='submit' name='courseSearch'>
              </div>
            </div>";
            
          //User has requested a search, display the results
          if($_SERVER["REQUEST_METHOD"] == "POST"){
            echo "<table class='searchTable'>
            <thead>
              <tr>
                <td><input type='submit' name='sortSearch' value='Subject'></td>
                <td><input type='submit' name='sortSearch' value='Course Number'</td>
                <td><input type='submit' name='sortSearch' value='Active Tutors'></td>
                <td><input type='submit' name='sortSearch' value='Referred Tutors'</td>
                <td><input type='submit' name='sortSearch' value='Open Prof Referrals'</td>
                <td></td>
              </tr>
            </thead>
            <tbody>";

            //Loop through all entries found within the current page
            for($i = $_SESSION['courseStart']; $i < $_SESSION['courseEnd']; $i++){

              //Break if the last entry has been found already
              if($i >= count($courseVals)){
                break;
              }
              $course = $courseVals[$i];

              //Binary entry for professor referrals: doesn' really matter the number
              $profReferrals = "No";
              if(!empty($course['open_prof_referrals'])){
                $profReferrals = "Yes";
              }
              echo "<tr><td>{$course['subject']}</td>
              <td>{$course['course_code']}</td>
              <td>{$course['active_tutors']}</td>
              <td>{$course['referred_tutors']}</td>
              <td>{$profReferrals}</td>
              <td class='view'><button class='blueButton' type='submit' name='editCourse' value='{$course['id']}'>View</button></td></tr>";
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
