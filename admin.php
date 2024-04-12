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
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Bulldog Tutoring Portal</title>
    <link rel="stylesheet" href="styles.css"/>
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
    <main id='adminHome'>
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
      <h1 class='mainHeader'>Administrative Account Page</h1>
        <p id="adminMessage" class="message">
          <?php 
          if(isset($_SESSION['messages']['admin'])){
            echo $_SESSION['messages']['admin'];
            unset($_SESSION['messages']['admin']);}
          ?>
      </p>
      <div id='adminBlocks'>
        <div>
          <h2>Accounts</h2>
          <p>Manage and interact with all accounts in the "Student Accounts" and Professor Accounts" pages. You can search for a specific account, or sort through broad categories of accounts by entering as much or
            as little information as desired, and clicking the title of the topic of interest to arrange by that topic.</p>
        </div>
        <div>
          <h2>Courses</h2>
          <p>Search by a course information to view relevant tutoring information about any and all classes on the "Search by Course" page. Navigate to the "Add a Course" page to manually enter a new course into the system.</p>
        </div>
        <div>
            <h2>Process</h2>
            <p>Visit the "Transition Semesters" page to begin the process of updating the tutoring system. This should be done near the end of every semester, and relies on a course/professor file being loaded into the 
              system to allow the professor recommendation process to begin.
            </p>
        </div>
      </div>
    </main>
    <footer>
    </footer>
  </body>
</html>
