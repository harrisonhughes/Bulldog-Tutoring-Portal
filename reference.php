<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Refer Students</title>
    <link rel="stylesheet" href="styles.css"/>
  </head>
  <body>
    <header>
      <h1>Bulldog Tutoring Portal</h1>
    </header>
    <main>
      <div>
        <h1>Welcome to the faculty interface for the Bulldog Tutoring Portal!</h1>
        <p>First of all, thank you for your participation; without your tutoring recommendations this valuable student service could not exist.<br>
          Please follow the instructions below to nominate students who you believe are worthy of being a potential future tutor for the current class.<br>
          NOTE: you will submit one list of student recommendations for each class that you are currently teaching this semester!
        </p>
        <ol>
          <li>Enter the email of </li>
        </ol>
      </div>
      <h1>CS 370</h1>
      <form action="reference.php" method="post" id="reference">
        <fieldset>
          <div class="referredEmail" id="cs370">
            <label for="email1">Student 1</label>
            <input type="text" name="emailList[]" id="email1">
            <p>@truman.edu</p>
          </div>
          <button type="button" id="addInput">+</button>
          <div>
            <input type="submit"/>
            <input type="reset"/>
          </div>
        </fieldset>
      </form>
    </main>
    <footer>
    </footer>
  </body>
</html>
