const form = document.getElementById("entryForm");
const tableBody = document.getElementById("tableBody");
const status = document.getElementById("status");

const totalDebitEl = document.getElementById("totalDebit");
const totalCreditEl = document.getElementById("totalCredit");

let entries = JSON.parse(localStorage.getItem("entries")) || [];

// Submit
form.addEventListener("submit", function (e) {
    e.preventDefault();

    const entry = {
        date: document.getElementById("date").value,
        code: document.getElementById("code").value,
        name: document.getElementById("name").value,
        type: document.getElementById("type").value,
        debit: parseFloat(document.getElementById("debit").value) || 0,
        credit: parseFloat(document.getElementById("credit").value) || 0
    };

    // Validation
    if (entry.debit === 0 && entry.credit === 0) {
        alert("Please enter Debit or Credit");
        return;
    }

    entries.push(entry);
    saveData();
    render();
    form.reset();
});

// Save
function saveData() {
    localStorage.setItem("entries", JSON.stringify(entries));
}

// Delete with animation
function deleteEntry(index) {

    const rows = document.querySelectorAll("tbody tr");
    rows[index].classList.add("fade-out");

    setTimeout(() => {
        entries.splice(index, 1);
        saveData();
        render();
    }, 300);
}

// Render table
function render() {
    tableBody.innerHTML = "";

    let totalDebit = 0;
    let totalCredit = 0;

    entries.forEach((e, index) => {
        totalDebit += e.debit;
        totalCredit += e.credit;

        tableBody.innerHTML += `
        <tr>
            <td>${e.date}</td>
            <td>${e.code}</td>
            <td>${e.name}</td>
            <td>${e.type}</td>
            <td style="color:green">${e.debit ? e.debit + "$" : "---"}</td>
            <td style="color:red">${e.credit ? e.credit + "$" : "---"}</td>
            <td>
                <button class="delete-btn" onclick="deleteEntry(${index})">
                    <i class="fa-solid fa-x"></i>
                </button>
            </td>
        </tr>
        `;
    });

    if (totalDebitEl && totalCreditEl) {
        totalDebitEl.innerText = totalDebit + " $";
        totalCreditEl.innerText = totalCredit + " $";
    }

    // Status handling
    if (entries.length === 0) {
        status.innerText = "";
        return;
    }

    if (totalDebit === totalCredit && totalDebit !== 0) {
        status.innerText = "Balanced ✓";
        status.style.color = "green";
    } else {
        status.innerText = "Not Balanced";
        status.style.color = "red";
    }
}

// Initial load
render();


