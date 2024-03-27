//Display correct course codes in portal.html depending on subject selected
function getCourseCodes(){

  //Get value of subject select box when changed
  var selectSubject = document.getElementById('subject');
  var subject = selectSubject.options[selectSubject.selectedIndex].value;
  
  var ajax = new XMLHttpRequest();

  ajax.onreadystatechange = function() {
    if(ajax.readyState == XMLHttpRequest.DONE) {
        if (ajax.status == 200) {
        var selectCodes = document.getElementById("courseCode");
        selectCodes.innerHTML = ajax.responseText;
      }
    }
  };
  
  ajax.open("GET", "?subject=" + subject, true);
  ajax.send();
}

function addStudent(){
  var newStudent = document.createElement("div");
  studentNumber = document.getElementById("emailList").children.length + 1;

  newStudent.className = "studentEmail";
  newStudent.id = "student" + studentNumber;
  newStudent.innerHTML = `<div>
                          <label for='email` + studentNumber + `'>Student ` + studentNumber + `</label>
                          <input type='text' name='emailList[]' id='email` + studentNumber + `'>
                          <p>@truman.edu</p>
                          <button type='button' class='removeInput' onClick='removeStudent(` + studentNumber + `)'>x</button>
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
  var studentList = studentDivBlock.children;
  const numStudents = studentList.length;

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

function dropdown(){
  var elements = document.getElementsByClassName("dropdown");
  
} 

