<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Bulldog Tutoring Portal</title>
    <link rel="stylesheet" href="styles.css"/>
  </head>
  <body>
    <header>
      <h1>Bulldog Tutoring Portal</h1>
      <nav>
        <div>
          <a href="home.html">Home</a>
          <a href="portal.html">Portal</a>
          <a href="account.php">Account</a>
        </div>
      </nav>
    </header>
    <main>
      <h1>Recover Account</h1>
      <div>
        <form action="recoverAccount.php" method="post" id="loginForm">
          <fieldset>
            <input type="text" placeholder="Email" name="email" id="email">
            <div>
              <input type="submit"/>
              <input type="reset"/>
            </div>
          </fieldset>
        </form>
      </div>
    </main>
    <footer>
    </footer>
  </body>
</html>
