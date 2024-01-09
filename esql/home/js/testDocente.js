
let i = 2;

function addOpzione() {
    let div = document.getElementById("opzioni_di_risposta");
    let newDiv = document.createElement("div");
    newDiv.id = "opzione["+i+"]";
    let newInput = document.createElement("input");
    newInput.type = "text";
    newInput.name = "opzione["+i+"]";
    newInput.required = true;
    let newCheck = document.createElement("input");
    newCheck.type = "checkbox";
    newCheck.name = "corretta["+i+"]";
    newCheck.value = i;
    newDiv.innerHTML = (i+1)+".";
    newDiv.appendChild(newInput);
    newDiv.appendChild(newCheck);
    div.appendChild(newDiv);
    i++;
}


function removeOpzione() {
    if (i>2) {
        i--;
        let div = document.getElementById("opzioni_di_risposta");
        let divToRemove = document.getElementById("opzione["+i+"]");
        div.removeChild(divToRemove);
    }
}