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