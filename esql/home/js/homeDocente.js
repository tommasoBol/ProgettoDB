let i = 0;

function addRowForAttribute() {
    i++;
    let newRow = document.createElement("tr");

    let nameColumn = document.createElement("td");
    let nameInput = document.createElement("input");
    nameInput.setAttribute("type","text");
    nameInput.required = true;
    nameInput.setAttribute("name","attribute["+i+"][name]");
    nameColumn.appendChild(nameInput);

    let typeColumn = document.createElement("td");
    let typeSelect = document.createElement("select");
    typeSelect.setAttribute("name","attribute["+i+"][type]");
    let option1 = document.createElement("option");
    option1.setAttribute("value", "int");
    option1.innerHTML = "INT";
    let option2 = document.createElement("option");
    option2.setAttribute("value", "varchar");
    option2.innerHTML = "VARCHAR";
    let option3 = document.createElement("option");
    option3.setAttribute("value", "char");
    option3.innerHTML = "CHAR";
    let option4 = document.createElement("option");
    option4.setAttribute("value", "date");
    option4.innerHTML = "DATE";
    let option5 = document.createElement("option");
    option5.setAttribute("value", "double");
    option5.innerHTML = "DOUBLE";
    let option6 = document.createElement("option");
    option6.setAttribute("value", "boolean");
    option6.innerHTML = "BOOLEAN";
    let option7 = document.createElement("option");
    option7.setAttribute("value", "blob");
    option7.innerHTML = "BLOB";
    let option8 = document.createElement("option");
    option8.setAttribute("value", "enum");
    option8.innerHTML = "ENUM";

    typeSelect.appendChild(option1);
    typeSelect.appendChild(option2);
    typeSelect.appendChild(option3);
    typeSelect.appendChild(option4);
    typeSelect.appendChild(option5);
    typeSelect.appendChild(option6);
    typeSelect.appendChild(option7);
    typeSelect.appendChild(option8);

    typeColumn.appendChild(typeSelect);

    let lengthColumn = document.createElement("td");
    let lengthInput = document.createElement("input");
    lengthInput.setAttribute("type", "text");
    lengthInput.setAttribute("name", "attribute["+i+"][length]");
    lengthColumn.appendChild(lengthInput);

    let nullColumn = document.createElement("td");
    let nullCheckbox = document.createElement("input");
    nullCheckbox.setAttribute("type", "checkbox");
    nullCheckbox.setAttribute("name", "attribute["+i+"][null]");
    nullCheckbox.setAttribute("value", "null");
    nullColumn.appendChild(nullCheckbox);

    let primaryColumn = document.createElement("td");
    let primaryCheckbox = document.createElement("input");
    primaryCheckbox.setAttribute("type", "checkbox");
    primaryCheckbox.setAttribute("name", "attribute["+i+"][primary]");
    primaryCheckbox.setAttribute("value", "primary");
    primaryColumn.appendChild(primaryCheckbox);

    let aiColumn = document.createElement("td");
    let aiCheckbox = document.createElement("input");
    aiCheckbox.setAttribute("type", "checkbox");
    aiCheckbox.setAttribute("name", "attribute["+i+"][ai]");
    aiCheckbox.setAttribute("value", "ai");
    aiColumn.appendChild(aiCheckbox);

    newRow.appendChild(nameColumn);
    newRow.appendChild(typeColumn);
    newRow.appendChild(lengthColumn);
    newRow.appendChild(nullColumn);
    newRow.appendChild(primaryColumn);
    newRow.appendChild(aiColumn);

    document.getElementById("table_for_table").appendChild(newRow);
}


function removeRowForAttribute() {
    let table = document.querySelector("table");
    if (table.rows.length>2) {
        table.deleteRow(table.rows.length-1);
    }
    i--;
}