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
            <a href='adminStudent.php?id=0'>Search by Student<a>
            <a href='adminStudent.php?id=1'>Search by Course<a>
          </div>
          <div class="adminLink">
            <h2>Faculty</h2>
            <a href='adminFaculty.php'>Professors<a>
          </div>
          <div class="adminLink">
            <h2>New Semester</h2>
            <a href='adminDriver.php'>Begin<a>
          </div>
        </nav>
      </div>
      <div>
        <?php
        $to = "hkh5485@truman.edu";
        $subject = "Test Email";
        $message = "This is a test email.";
        $headers = "From: test.mail.com" . "\r\n"; // Specify the sender's email address
        
        // Send email
        $mailSent = mail($to, $subject, $message, $headers);
        
        if ($mailSent) {
            echo "Email sent successfully.";
        } else {
            echo "Failed to send email.";
        }
        ?>
      </div>
    </main>
    <footer>
    </footer>
  </body>
</html>
