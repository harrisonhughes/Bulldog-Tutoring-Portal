<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulldog Tutoring Account Creation</title>
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
          <a href="login.php"><span>Login</span></a>
      </nav>
    </header>
    <main class = "credentialPage">
      <div>
        <form action="credentials.php" onsubmit="return validateCreate()" method="post" class="credentialForm">
          <fieldset>
            <h1>Create an Account</h1>
            <div>
            <input type="text" placeholder="Firstname" name="firstname" id="firstname">
              <p id="fnameError" class="error">
                <?php 
                session_start();

                //Error message configurations for each of the input types (more below)
                if(isset($_SESSION['errors']['fname'])){
                  echo $_SESSION['errors']['fname'];
                  unset($_SESSION['errors']['fname']);}
                ?>
              </p>
            </div>
            <div>
              <input type="text" placeholder="Lastname" name="lastname" id="lastname">
              <p id="lnameError" class="error">
                <?php 
                if(isset($_SESSION['errors']['lname'])){
                  echo $_SESSION['errors']['lname'];
                  unset($_SESSION['errors']['lname']);}
                ?>
              </p>
            </div>
            <div>
              <input type="text" placeholder="Email" name="email" id="email">
              <p id="emailError" class="error">
                <?php 
                if(isset($_SESSION['errors']['email'])){
                  echo $_SESSION['errors']['email'];
                  unset($_SESSION['errors']['email']);}
                ?>
              </p>
            </div>
            <div>
              <input type="password" placeholder="Create password" name="password" class='password' id="password">
              <p id="passwordError" class="error">
                <?php 
                if(isset($_SESSION['errors']['password'])){
                  echo $_SESSION['errors']['password'];
                  unset($_SESSION['errors']['password']);}
                ?>
              </p>
            </div>
            <div>
              <input type="password" placeholder="Comfirm password" name="confirmPassword" class='password' id="confirmPassword">
              <p id="confPasswordError" class="error">
                <?php 
                if(isset($_SESSION['errors']['confPassword'])){
                  echo $_SESSION['errors']['confPassword'];
                  unset($_SESSION['errors']['confPassword']);}
                ?>
              </p>
            </div>
            <input type="submit" value="Create Account" name="createForm" id="createForm"/>
          </fieldset>
        </form>
      </div>
    </main>
    <footer>
    </footer>
  </body>
</html>
