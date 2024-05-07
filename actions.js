//Display correct course codes in portal.html depending on subject selected
function getCourseCodes(subject, courses){
  var selectSubject = document.getElementById(subject);
  var subjectValue = selectSubject ? selectSubject.options[selectSubject.selectedIndex].value : null;

  if(!subjectValue) {
    console.error("Subject value is empty or invalid.");
    return;
  }

  var ajax = new XMLHttpRequest();
  ajax.onreadystatechange = function(){
    if(ajax.readyState == XMLHttpRequest.DONE){
      if(ajax.status == 200){
        var selectCodes = document.getElementById(courses);
        if(selectCodes){
          selectCodes.innerHTML = ajax.responseText;
        } else {
          console.error("Courses element is invalid or not found.");
        }
      }
    }
  };

  ajax.open("GET", "?subject=" + subjectValue, true);
  ajax.send();
}

//Add student box to professor referral list upon user request
function addStudent(){
  var emailList = document.getElementById("emailList");
  if(!emailList) {
    console.error("Email list element not found.");
    return;
  }

  var newStudent = document.createElement("div");
  var studentNumber = emailList.children.length + 1;

  newStudent.className = "studentEmail";
  newStudent.id = "student" + studentNumber;
  newStudent.innerHTML = `<div>
                          <label for='email` + studentNumber + `'>Student ` + studentNumber + `</label>
                          <input type='text' name='emailList[]' id='email` + studentNumber + `'>
                          <p>@truman.edu</p>
                          <button type='button' class='removeInput' onClick='removeStudent(` + studentNumber + `)'>X</button>
                          </div>
                          <p id='email1Error' class='error'>
                          <?php
                          if(isset($_SESSION['errors']['email` + studentNumber + `'])){
                            echo $_SESSION['errors']['email` + studentNumber + `'];
                            unset($_SESSION['errors']['email` + studentNumber + `']);
                          }
                          ?>
                          </p>
                          </div>`;

  document.getElementById("emailList").appendChild(newStudent);
}


//Remove student box from professor referral list upon request
function removeStudent(removalIndex) {
  var studentDivBlock = document.getElementById("emailList");

  if(!studentDivBlock) {
    console.error("Email list element not found.");
    return;
  }

  var studentList = studentDivBlock.children;
  const numStudents = studentList.length;

  if(removalIndex < 1 || removalIndex > numStudents) {
    console.error("Invalid removal index.");
    return;
  }

  var saveInputs = [];
  for(var i = 1; i <= numStudents; i++){
    if(i != removalIndex){
      saveInputs.push(document.getElementById("email" + i).value);
    }
  }

  var currentChild = studentList[numStudents - 1];
  studentDivBlock.removeChild(currentChild);

  for(var i = 1; i < numStudents; i++){
    document.getElementById("email" + i).value = saveInputs[i - 1];
  }
}


//Confirmation page for extra caution on user execution
function confirmMessage(message){
  if(confirm(message)){
    return true;
  }
  else{
    return false;
  }
}


//Let user know the new semester function is currently working
function showWaiting(pBlock){
  var submittedBlock = document.getElementById(pBlock);
  if(!submittedBlock) {
    console.error("Block element not found.");
    return;
  }

  submittedBlock.innerHTML = "Executing...";
}

//Prevent javascript injection attacks
function testInput(input){

  //Remove extra whitespace
  input = input.trim();

  //Reconfigure specific HTML characters
  input = input.replace(/&/g, "&amp;")
               .replace(/</g, "&lt;")
               .replace(/>/g, "&gt;")
               .replace(/"/g, "&quot;")
               .replace(/'/g, "&#039;");

  return input;
}

//Called upon a submission of a login attempt in login.php
function validateLogin(){

  //Assert username and password fields are not empty
  const username = testInput(document.getElementById("email").value);
  const password = testInput(document.getElementById("password").value);
  var validForm = true;

  //Ensure all form elements are validated according to specifications
  if(!username){
    validForm = false;
    document.getElementById("emailError").innerText = "Username cannot be blank";
  }
  else{
    document.getElementById("emailError").innerText = "";
  }

  if(!password){
    validForm = false;
    document.getElementById("passwordError").innerText = "Password cannot be blank";
  }
  else{
    document.getElementById("passwordError").innerText = "";  
  }

  return validForm;
}

function validateCreate(){
  const MAX_LENGTH = 50; //Max database element size
  const MIN_LENGTH = 6; //Min password size
  const VALID_NAME = /^[a-zA-Z'\- .]+$/; //Name must be of a valid configuration

  var email = testInput(document.getElementById('email').value.toLowerCase());
  var password = testInput(document.getElementById('password').value);
  var confirmPassword = testInput(document.getElementById('confirmPassword').value);
  var firstname = testInput(document.getElementById('firstname').value);
  var lastname = testInput(document.getElementById('lastname').value);
  var validForm = true;

  // Ensure all form elements are validated according to specifications
  if(email == ''){
    validForm = false;
    document.getElementById("emailError").innerText = "Email cannot be blank";
  }
  else if(!email.endsWith('@truman.edu')){ //Must end in @truman.edu
    validForm = false;
    document.getElementById("emailError").innerText = "Email must be from a Truman account";
  }
  else if(email.length > MAX_LENGTH){
    validForm = false;
    document.getElementById("emailError").innerText = "Email must be no longer than " + MAX_LENGTH + " characters";
  }
  else{
    document.getElementById("emailError").innerText = "";
  }

  if(password == ''){
    validForm = false;
    document.getElementById("passwordError").innerText = "Password cannot be blank";
  }
  else if(password.length < MIN_LENGTH){
    validForm = false;
    document.getElementById("passwordError").innerText = "Password must be at least " + MIN_LENGTH + " characters";
  }
  else{
    document.getElementById("passwordError").innerText = "";
  }

  if(confirmPassword == ''){
    validForm = false;
    document.getElementById("confPasswordError").innerText = "Confirm password cannot be blank";
  }
  else if(confirmPassword.length < MIN_LENGTH){
    validForm = false;
    document.getElementById("confPasswordError").innerText = "Password must be at least " + MIN_LENGTH + " characters";
  }
  else if(password !== confirmPassword){
    validForm = false;
    document.getElementById("confPasswordError").innerText = "Passwords entered do not match";
  }
  else{
    document.getElementById("confPasswordError").innerText = "";
  }

  if(firstname == ''){
    validForm = false;
    document.getElementById("fnameError").innerText = "Firstname cannot be blank";
  }
  else if(!firstname.match(VALID_NAME)){ //Name must be of a valid name format
    validForm = false;
    document.getElementById("fnameError").innerText = "Name must be in a valid format";
  }
  else if(firstname.length > MAX_LENGTH){
    validForm = false;
    document.getElementById("fnameError").innerText = "Firstname must be no longer than " + MAX_LENGTH + " characters";
  }
  else{
    document.getElementById("fnameError").innerText = "";
  }

  if(lastname == ''){
    validForm = false;
    document.getElementById("lnameError").innerText = "Lastname cannot be blank";
  }
  else if(!lastname.match(VALID_NAME)){ //Name must be of a valid name format
    validForm = false;
    document.getElementById("lnameError").innerText = "Name must be in a valid format";
  }
  else if(lastname.length > MAX_LENGTH){
    validForm = false;
    document.getElementById("lnameError").innerText = "Lastname must be no longer than " + MAX_LENGTH + " characters";
  }
  else{
    document.getElementById("lnameError").innerText = "";
  }

  return validForm;
}

function validateRecover(){
  const MAX_LENGTH = 50; //Max database element size
  const MIN_LENGTH = 6; //Min password size

  var validForm = true;

  const emailBlock = document.getElementById("email");
  if(emailBlock){
    const email = testInput(emailBlock.value);

    // Ensure all form elements are validated according to specifications
    if(email == ''){
      validForm = false;
      document.getElementById("recoverEmail").innerText = "Email cannot be blank";
    }
    else if(!email.endsWith('@truman.edu')){ //Must end in @truman.edu
      validForm = false;
      document.getElementById("recoverEmail").innerText = "Email must be from a Truman account";
    }
    else if(email.length > MAX_LENGTH){
      validForm = false;
      document.getElementById("recoverEmail").innerText = "Email must be no longer than " + MAX_LENGTH + " characters";
    }
    else{
      document.getElementById("recoverEmail").innerText = "";
    }
  }
  else{
    var recoveryCode = testInput(document.getElementById('recoveryCode').value);
    var password = testInput(document.getElementById('password').value);
    var confirmPassword = testInput(document.getElementById('confirmPassword').value);

    document.getElementById("recoverEmail").innerText = "";

    if(recoveryCode == ""){
      validForm = false;
      document.getElementById("recoverEmail").innerText = "Recovery code cannot be empty";
    }

    if(password == ''){
      validForm = false;
      document.getElementById("recoverEmail").innerText = "Password cannot be blank";
    }
    else if(password.length < MIN_LENGTH){
      validForm = false;
      document.getElementById("recoverEmail").innerText = "Password must be at least " + MIN_LENGTH + " characters";
    }
  
    if(confirmPassword == ''){
      validForm = false;
      document.getElementById("recoverEmail").innerText = "Confirm password cannot be blank";
    }
    else if(confirmPassword.length < MIN_LENGTH){
      validForm = false;
      document.getElementById("recoverEmail").innerText = "Password must be at least " + MIN_LENGTH + " characters";
    }
    else if(password !== confirmPassword){
      validForm = false;
      document.getElementById("recoverEmail").innerText = "Passwords entered do not match";
    }
  }

  return validForm;
}

function validatePassword(){
  const MIN_LENGTH = 6; //Min password size

  var password = testInput(document.getElementById('password').value);
  var confirmPassword = testInput(document.getElementById('confirmPassword').value);
  var validForm = true;

  document.getElementById("changePassword").innerText = "";

  // Ensure all form elements are validated according to specifications
  if(password == ''){
    validForm = false;
    document.getElementById("changePassword").innerText = "Password cannot be blank";
  }
  else if(password.length < MIN_LENGTH){
    validForm = false;
    document.getElementById("changePassword").innerText = "Password must be at least " + MIN_LENGTH + " characters";
  }

  if(confirmPassword == ''){
    validForm = false;
    document.getElementById("changePassword").innerText = "Confirm password cannot be blank";
  }
  else if(confirmPassword.length < MIN_LENGTH){
    validForm = false;
    document.getElementById("changePassword").innerText = "Password must be at least " + MIN_LENGTH + " characters";
  }
  else if(password !== confirmPassword){
    validForm = false;
    document.getElementById("changePassword").innerText = "Passwords entered do not match";
  }

  return validForm;
}

