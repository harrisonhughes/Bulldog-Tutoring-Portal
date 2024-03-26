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
      <h1>Bulldog Tutoring Portal</h1>
      <nav>
        <div>
          <a href="home.html">Home</a>
          <a href="portal.php">Portal</a>
          <a href="account.php">Account</a>
        </div>
      </nav>
    </header>
    <main>
      <div>
      <nav>
          <a href='admin.php'>Home</a>
          <div class="adminLink">
            <h2>Students</h2>
            <a href='studentSearch.php'>Search by Student<a>
            <a href='courseSearch.php'>Search by Course<a>
          </div>
          <div class="adminLink">
            <h2>Professors</h2>
            <a href='adminFaculty.php'>Professors<a>
          </div>
          <div class="adminLink">
            <h2>Admin</h2>
            <a href='adminDriver.php'>Begin<a>
          </div>
        </nav>
      </div>
      <?php
        include 'functions.php';
        session_start();

        try{
          $pdo = connect();

          echo"
          <aside>
            <nav>
            </nav>
          </aside>
          <form action='studentSearch.php' method='post' id='studentAdmin'>
          <fieldset>  
            <div>
              <label for='fname'>First Name</label>
              <input type='text' name='fname' id='fname'>
              <label for='lname'>Last Name</label>
              <input type='text' name='lname' id='lname'>
              <label for='email'>Email</label>
              <input type='text' name='email' id='email'>
              <label for='tutorType'>Tutor Type</label>
              <select name='tutorType' id='tutorType'>
                <option disabled selected value></option>
                <option value='0'>Private</option>
                <option value='1'>Scholarship</option>
              </select>
              <input type='submit' name='accountSearch'>
            </div>";

            if($_SERVER["REQUEST_METHOD"] == "POST"){
              $sql = "";
              $sqlValues = [];

              if(isset($_POST['accountSearch'])){
                $sql = "SELECT * FROM accounts";
                $sqlColumns = [];

                if(!empty($_POST['fname'])){
                  $sqlValues[] = test_input($_POST['fname']);
                  $sqlColumns[] = "firstname";
                }
                if(!empty($_POST['lname'])){
                  $sqlValues[] = test_input($_POST['lname']);
                  $sqlColumns[] = "lastname";
                }
                if(!empty($_POST['email'])){
                  $sqlValues[] = test_input($_POST['email']);
                  $sqlColumns[] = "email";
                }
                if(!empty($_POST['tutorType']) || test_input($_POST['tutorType']) == 0){
                  $sqlValues[] = test_input($_POST['tutorType']);
                  $sqlColumns[] = "private_tutor";
                }

                if(!empty($sqlValues)){
                  $sql = $sql . " WHERE " . $sqlColumns[0] . " = ?";
                  for($i = 1; $i < count($sqlValues); $i++){
                    $sql = $sql . " AND " . $sqlColumns[$i] . " = ?";
                  } 
                }

                $_SESSION['studentQuery'] = $sql;
                $_SESSION['studentValues'] = $sqlValues;
              }

              else if(isset($_POST['sortSearch'])){
                $sortBy = test_input($_POST['sortSearch']);
                $sortString = "ORDER BY ";

                if($sortBy == "Firstname"){
                  $sortString = $sortString . "firstname";
                }
                else if($sortBy == "Lastname"){
                  $sortString = $sortString . "lastname";
                }
                else if($sortBy == "Email"){
                  $sortString = $sortString . "email";
                }
                else if($sortBy == "Tutor Type"){
                  $sortString = $sortString . "private_tutor";
                }
                else{
                  $sortString = $sortString . "last_activity";
                }

                $sqlValues = $_SESSION['studentValues'];
                $sql = test_input($_SESSION['studentQuery']) . " " . $sortString;
                if($sortString == test_input($_SESSION['studentSort'])){
                  $sql = $sql . " DESC";
                  unset($_SESSION['studentSort']);
                }
                else{
                  $_SESSION['studentSort'] = $sortString;
                }
              }

              $result = $pdo->prepare($sql);
              $result->execute($sqlValues);
              $students = $result->fetchAll();

              echo "<table>
              <thead>
                <tr>
                  <td><input type='submit' name='sortSearch' value='Firstname'></td>
                  <td><input type='submit' name='sortSearch' value='Lastname'</td>
                  <td><input type='submit' name='sortSearch' value='Email'></td>
                  <td><input type='submit' name='sortSearch' value='Tutor Type'</td>
                  <td><input type='submit' name='sortSearch' value='Last Activity'</td>
                </tr>
              </thead>
              <tbody>";
              
              foreach($students as $student){
                $tutorType = "Private";
                if($student['private_tutor'] == 1){
                  $tutorType = "Scholarship";
                }

                $timeStamp = strtotime($student['last_activity']);
                $timeStamp = date("m/d/Y", $timeStamp);

                echo "<tr><td>{$student['firstname']}</td>
                <td>{$student['lastname']}</td>
                <td>{$student['email']}</td>
                <td>{$tutorType}</td>
                <td>{$timeStamp}</td></tr>";
              }

              $numStudents = count($students);
              echo "</tbody><tfoot>
              <tr><td colspan='6'>Search returned {$numStudents} students</td></tr>
              </tfoot></table>";
            }
              
          echo"</fieldset>
        </form>";
        }
        
        //Ensure proper error message is returned upon a database error
        catch (PDOException $e){
          die( $e->getMessage());
        }
        $pdo = null;
      ?>
    </main>
    <footer>
    </footer>
  </body>
</html>
