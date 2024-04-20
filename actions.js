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

function confirmMessage(message){
  if(confirm(message)){
    return true;
  }
  else{
    return false;
  }
}

function showWaiting(pBlock){
  var submittedBlock = document.getElementById(pBlock);
  if(!submittedBlock) {
    console.error("Block element not found.");
    return;
  }

  submittedBlock.innerHTML = "Executing...";
}

