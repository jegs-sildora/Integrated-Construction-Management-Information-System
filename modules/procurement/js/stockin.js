/* =========================================
   GLOBAL VARIABLES
   ========================================= */
let approvedPOs = [];

document.addEventListener("DOMContentLoaded", () => {
    fetchStockHistory();
});

/* IMPORTANT: 
   The local showToast() function has been DELETED. 
   This file will now automatically use the global showToast() 
   from 'includes/toast.php'.
*/

/* =========================================
   1. FETCH STOCK IN HISTORY
   ========================================= */
function fetchStockHistory() {
    // FIX: Changed 'php/' to 'php/' to match your folder structure
    fetch('php/fetch_stockin.php') 
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        const tbody = document.getElementById("stockin-table-body");
        tbody.innerHTML = "";

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-slate-500">No records found.</td></tr>`;
            return;
        }

        data.forEach(item => {
            let row = `
                <tr class="hover:bg-slate-50 border-b border-slate-100 last:border-b-0">
                    <td class="px-6 py-4 font-bold text-navy-dark text-sm whitespace-nowrap">${item.referenceNo}</td>
                    <td class="px-6 py-4 text-slate-600 text-sm whitespace-nowrap">${item.poID}</td>
                    <td class="px-6 py-4 text-slate-600 font-medium text-sm whitespace-nowrap">${item.itemName}</td>
                    <td class="px-6 py-4 text-slate-600 text-sm whitespace-nowrap">${item.quantityReceived} ${item.unit}</td>
                    <td class="px-6 py-4 text-slate-600 text-sm whitespace-nowrap">${item.dateReceived}</td>
                    <td class="px-6 py-4 text-slate-600 text-sm whitespace-nowrap">${item.receivedBy}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    })
    .catch(error => {
        console.error("Fetch Error:", error);
        // Using optional chaining just in case
        if (typeof showToast === "function") {
            showToast("Error loading history. Check console.", "error");
        }
    });
}

/* =========================================
   2. MODAL LOGIC (Tailwind Compatible)
   ========================================= */
function openStockModal() {
    const modal = document.getElementById('stockModal');
    if(modal) {
        modal.classList.remove('hidden'); 
        loadApprovedPOs(); 
    }
}

function closeStockModal() {
    const modal = document.getElementById('stockModal');
    if(modal) {
        modal.classList.add('hidden');
        document.getElementById("stockForm").reset();
    }
}

function loadApprovedPOs() {
    // FIX: Changed 'php/' to 'php/'
    fetch('php/get_approved_pos.php')
    .then(response => {
        if (!response.ok) throw new Error("php Not Found");
        return response.json();
    })
    .then(data => {
        approvedPOs = data;
        const dropdown = document.getElementById("stk_po_select");
        dropdown.innerHTML = '<option value="" disabled selected>Select Approved PO</option>';

        if (!data || data.length === 0) {
            let option = document.createElement("option");
            option.text = "No pending POs found";
            dropdown.appendChild(option);
            return;
        }

        data.forEach(po => {
            let option = document.createElement("option");
            option.value = po.orderID; 
            option.text = `${po.orderID} - ${po.itemName}`;
            dropdown.appendChild(option);
        });
    })
    .catch(error => {
        console.error(error);
        if (typeof showToast === "function") showToast("Error loading POs", "error");
    });
}

function autoFillStockDetails() {
    const selectedID = document.getElementById("stk_po_select").value;
    const selectedPO = approvedPOs.find(po => po.orderID === selectedID);

    if (selectedPO) {
        document.getElementById("stk_item").value = selectedPO.itemName;
        document.getElementById("stk_qty").value = selectedPO.quantity;
        document.getElementById("stk_unit").value = selectedPO.unit;
    }
}

/* =========================================
   3. SAVE STOCK IN
   ========================================= */
document.getElementById("stockForm").addEventListener("submit", function(event) {
    event.preventDefault();

    let formData = new FormData();
    formData.append('poID', document.getElementById("stk_po_select").value);
    formData.append('itemName', document.getElementById("stk_item").value);
    formData.append('quantity', document.getElementById("stk_qty").value);
    formData.append('unit', document.getElementById("stk_unit").value);
    formData.append('receivedBy', document.getElementById("stk_user").value);

    // FIX: Changed 'php/' to 'php/'
    fetch('php/save_stockin.php', { method: 'POST', body: formData })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => { throw new Error(text) });
        }
        return response.json();
    })
    .then(data => {
        if(data.status === "success") {
            showToast("Stock Received Successfully!", "success");
            closeStockModal();
            fetchStockHistory();
        } else {
            showToast("Error: " + data.message, "error");
        }
    })
    .catch(error => {
        console.error(error);
        showToast("Server Request Failed", "error");
    });
});

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('stockModal');
    if (event.target === modal) {
        closeStockModal();
    }
});