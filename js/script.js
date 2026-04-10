const form = document.getElementById("entryForm");
const tableBody = document.getElementById("tableBody");

let entries = [];

form.addEventListener("submit", function (e) {
    e.preventDefault();

    const entry = {
        date: document.getElementById("date").value,
        code: document.getElementById("code").value,
        name: document.getElementById("name").value,
        type: document.getElementById("type").value,
        debit: document.getElementById("debit").value,
        credit: document.getElementById("credit").value
    };
    entries.push(entry);
    render();
    form.reset();
});

function render() {
    tableBody.innerHTML = "";
        if(debit === ""){
            entry.debit = "--";
        }
        if(credit === ""){
            entry.credit = "--";
        }
    entries.forEach(e => {
        tableBody.innerHTML += `
        <tr>
        <td>${e.date}</td>
        <td>${e.code}</td>
        <td>${e.name}</td>
        <td>${e.type}</td>
        <td>${e.debit}</td>
        <td>${e.credit}</td>
        </tr>
        `;
    });
}