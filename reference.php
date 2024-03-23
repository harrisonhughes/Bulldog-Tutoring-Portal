<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Refer Students</title>
    <link rel="stylesheet" href="styles.css"/>
    <script src="actions.js"></script>
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
        <h2>To-do List</h2>
        <ul>
          <li>CS 370</li>
        </ul>
      </div>
      <h1>CS 370</h1>
      <?php
        session_start();
        if($_SERVER["REQUEST_METHOD"] == "POST"){
          if(isset($_POST['submitReferences'])){
            $students = [];
            for($studentIndex = 0; $studentIndex < count($_POST['emailList']); $studentIndex++){
              if(!empty($_POST['emailList'][$studentIndex])){
                if(!in_array($_POST['emailList'][$studentIndex], $students)){
                  $students[] = $_POST['emailList'][$studentIndex];
                }
              }
              // $_SESSION['errors']['email' . $studentIndex] = "Student email cannot be blank";
            }

            $_SESSION['references'] = $students;
            $numStudents = count($students);

            echo "<h2>Summary for CS 370</h2><ul>";
            foreach($students as $student){
              echo "<li>{$student}@truman.edu</li>";
            }
            echo "</ul>
                  <p>You have referred <b>{$numStudents}</b> students for CS 370. Please ensure the accuracy of the emails above</p>
                  <form action='reference.php' method='post' id='confirmReference'>
                    <fieldset>
                      <input type='submit' value='< Go back' name='editReferences'/>
                      <input type='submit' value='Confirm' name='confirmReferences'/>
                    </fieldset>
                  </form>";
          }
          else if(isset($_POST['editReferences'])){
            header("Location: reference.php");
          }
          else{
            echo "DONE";
            unset($_SESSION['references']);
          }
        }
        else{
          $savedStudents = 0;
          if(isset($_SESSION['references'])){
            $savedStudents = count($_SESSION['references']);
          };

          echo "<form action='reference.php' method='post' id='reference'>
            <fieldset>
              <div id='emailList'>
                <div class='studentEmail'>
                  <label for='email1'>Student 1</label>
                  <input type='text' name='emailList[]' id='email1'";
                  if($savedStudents > 0){
                    $savedEmail = $_SESSION['references'][0];
                    echo "value='{$savedEmail}'";
                  }
                  echo "><p>@truman.edu</p>
                  <p id='email1Error' class='error'>";
                  if(isset($_SESSION['errors']['email1'])){
                    echo $_SESSION['errors']['email1'];
                    unset($_SESSION['errors']['email1']);
                  }
                  echo "</p>
                </div>";

                if($savedStudents > 1){
                  $studentNum = 2;
                  for($studentIndex = 1; $studentIndex < $savedStudents; $studentIndex++){
                    $savedEmail = $_SESSION['references'][$studentIndex];
                    echo 
                    "<div class='studentEmail'>
                      <label for='email{$studentNum}'>Student {$studentNum}</label>
                      <input type='text' name='emailList[]' id='email{$studentNum}' value='{$savedEmail}'>
                      <p>@truman.edu</p>
                      <p id='email1Error' class='error'>";
                      if(isset($_SESSION['errors']["email{$studentNum}"])){
                        echo $_SESSION['errors']["email{$studentNum}"];
                        unset($_SESSION['errors']["email{$studentNum}"]);
                      }
                      echo 
                      "</p>
                    </div>";
                    $studentNum++;
                  }
                }

              echo "</div>
              <button type='button' id='addInput' onClick='addStudent()'>+</button>
              <div>
                <input type='submit' name='submitReferences'/>
                <input type='reset'/>
              </div>
            </fieldset>
          </form>";
        }
      ?>
    </main>
    <footer>
    </footer>
  </body>
</html>
