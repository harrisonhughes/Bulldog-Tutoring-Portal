<?php
  //Begin session, set inactivity timout constant (2 hours)
  session_start();
  $TIME_OUT = 60 * 60 * 2;

  //If user is not set, access credentials are not set, last activity is not set, or last activity is beyond timeout length
  if(!isset($_SESSION['user']) || !isset($_SESSION['credentials']) || !isset($_SESSION['lastActivity']) || time() - $_SESSION['lastActivity'] > $TIME_OUT){

    //If last activity is set and beyond timeout length, set output message and send user to login page to be logged out
    if(isset($_SESSION['lastActivity']) && time() - $_SESSION['lastActivity'] > $TIME_OUT){
      $_SESSION['message']['login'] = "You were logged out due to inactivity";
    }
    header("Location: login.php");
    exit();
  }

  //Update last activity variable
  $_SESSION['login'] = time();
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulldog Tutoring Home</title>
    <link rel="stylesheet" href="styles.css"/>
  </head>
  <body>
    <header>
      <div>
        <img src="https://seeklogo.com/images/T/truman-bulldogs-logo-819371EABE-seeklogo.com.png">
        <span>The Bulldog Tutoring Portal</span>
      </div>
      <nav>
          <a href="index.php"><span class='active'>Home</span></a>
          <a href="portal.php"><span>Portal</span></a>
          <a href="account.php"><span>Account</span></a>
          <a href="login.php"><span>Logout</span></a>
      </nav>
    </header>
    <main id="homePage">
      <div>
        <h1>The<br>Bulldog<br>Tutoring<br>Portal</h1>
      </div>
      <div>
        <div class='homeBlock'>
          <h2>Professors</h2>
          <p>Navigate to the <span>Account</span> page to begin referring students for tutoring opportunities. All of your courses should be loaded in and ready to go!</p>
        </div>
        <div class='homeBlock'>
          <h2>Students</h2>
          <p>Your <span>Account</span> page will display all of the current tutoring referrals that you have been approved for, as well as any of your active tutoring positions.</p>
        </div>
        <div class='homeBlock'>
          <h2>Everyone</h2>
          <p>Visit the <span>Portal</span> page to begin searching for tutors for each and every class here at Truman. Happy learning!</p>
        </div>
      </div>
    </main>
    <footer>
    </footer>
  </body>
</html>
