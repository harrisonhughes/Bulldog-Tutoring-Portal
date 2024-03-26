<?php
$ADMIN_ACCOUNT = 3;
include 'functions.php';
session_start();

try{
  $pdo = connect();
  if($_SERVER["REQUEST_METHOD"] == "POST"){
    $sql = "";
    $sqlValues = [];

    if(isset($_POST['adminSearch'])){
      $sql = "SELECT * FROM accounts WHERE account_type = {$ADMIN_ACCOUNT}";
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

      if(!empty($sqlValues)){
        for($i = 0; $i < count($sqlValues); $i++){
          $sql = $sql . " AND " . $sqlColumns[$i] . " = ?";
        } 
      }

      $_SESSION['adminQuery'] = $sql;
      $_SESSION['adminValues'] = $sqlValues;
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
      else{
        $sortString = $sortString . "last_activity";
      }

      $sqlValues = $_SESSION['adminValues'];
      $sql = test_input($_SESSION['adminQuery']) . " " . $sortString;
      if($sortString == test_input($_SESSION['adminSort'])){
        $sql = $sql . " DESC";
        unset($_SESSION['adminSort']);
      }
      else{
        $_SESSION['adminSort'] = $sortString;
      }
    }

    $result = $pdo->prepare($sql);
    $result->execute($sqlValues);
    $admins = $result->fetchAll();

    $numAdmins = count($admins);
  }
}
//Ensure proper error message is returned upon a database error
catch (PDOException $e){
  die( $e->getMessage());
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
            <a href='facultySearch.php'>Search by Professor<a>
            <a href='facultyCourseSearch.php'>Search by Course<a>
          </div>
          <div class="adminLink">
            <h2>Admin</h2>
            <a href='adminSearch.php'>Admin Accounts<a>
            <a href='newSemester.php'>Transition Semesters<a>
          </div>
        </nav>
      </div>
      <?php
          echo"
          <aside>
            <nav>
            </nav>
          </aside>
          <form action='adminSearch.php' method='post' id='adminSearch'>
          <fieldset>  
            <div>
              <label for='fname'>First Name</label>
              <input type='text' name='fname' id='fname'>
              <label for='lname'>Last Name</label>
              <input type='text' name='lname' id='lname'>
              <label for='email'>Email</label>
              <input type='text' name='email' id='email'>
              <input type='submit' name='adminSearch'>
            </div>";

            if($_SERVER["REQUEST_METHOD"] == "POST"){
              
              echo "<table>
              <thead>
                <tr>
                  <td><input type='submit' name='sortSearch' value='Firstname'></td>
                  <td><input type='submit' name='sortSearch' value='Lastname'</td>
                  <td><input type='submit' name='sortSearch' value='Email'></td>
                  <td><input type='submit' name='sortSearch' value='Last Activity'</td>
                </tr>
              </thead>
              <tbody>";

              foreach($admins as $admin){
          
                $timeStamp = strtotime($admin['last_activity']);
                $timeStamp = date("m/d/Y", $timeStamp);
          
                echo "<tr><td>{$admin['firstname']}</td>
                <td>{$admin['lastname']}</td>
                <td>{$admin['email']}</td>
                <td>{$timeStamp}</td></tr>";
              }
          
              echo "</tbody><tfoot>
              <tr><td colspan='6'>Search returned {$numAdmins} accounts</td></tr>
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