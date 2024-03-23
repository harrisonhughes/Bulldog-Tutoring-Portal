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
  
  ajax.open("GET", "portal.php?subject=" + subject, true);
  ajax.send();
}

function addStudent(){
  var newStudent = document.createElement("div");
  studentNumber = document.getElementById("emailList").children.length + 1;

  newStudent.className = "studentEmail";
  newStudent.id = "student" + studentNumber;
  newStudent.innerHTML = `<label for='email` + studentNumber + `'>Student ` + studentNumber + `</label>
                          <input type='text' name='emailList[]' id='email` + studentNumber + `'>
                          <p>@truman.edu</p>
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
