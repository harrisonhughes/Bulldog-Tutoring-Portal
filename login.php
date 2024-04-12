<?php
session_start();
//Save relevant login messages before ending session
$messages = $_SESSION['message'];

//Login page turns into logout page when user is logged in: log out user upon visting this page
if(isset($_SESSION['user'])){
  session_unset();
}

unset($_SESSION['recoveryEmail']);
$_SESSION['message'] = $messages;
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
          <a href="login.php"><span>Login</span></a>
      </nav>
    </header>
    <main class = "credentialPage">
    <p id="loginMessage" class="message">
      <?php 
      //Display relevant messages
      if(isset($_SESSION['message']['login'])){
        echo $_SESSION['message']['login'];
        unset($_SESSION['message']['login']);}
      ?>
    </p>
      <div>
        <form action="credentials.php" method="post" class="credentialForm">
          <fieldset>
            <h1>User Login</h1>
            <div>
              <input type="text" placeholder="Email" name="email" id="email">
              <p id="emailError" class="error">
                <?php 
                //Display relevant errors for all input fields
                if(isset($_SESSION['errors']['email'])){
                  echo $_SESSION['errors']['email'];
                  unset($_SESSION['errors']['email']);}
                ?>
              </p>
            </div>
            <div>
              <input type="password" placeholder="Password" name="password" class='password' id="password">
              <p id="passwordError" class="error">
                <?php 
                if(isset($_SESSION['errors']['password'])){
                  echo $_SESSION['errors']['password'];
                  unset($_SESSION['errors']['password']);}
                ?>
              </p>
            </div>
            <a href="recoverAccount.php">Forgot password?</a>
            <input type="submit" value="LOGIN" name="loginForm" id="loginForm"/>
            <p>Don't have an account? <a href="createAccount.php">Sign up!</a></p>
          </fieldset>
        </form>
      </div>
    </main>
    <footer>
    </footer>
  </body>
</html>
