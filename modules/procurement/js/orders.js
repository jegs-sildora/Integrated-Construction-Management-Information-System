let purchaseOrders = [];
let orderToDelete = null;
let isEditMode = false; 

document.addEventListener("DOMContentLoaded", () => {
    fetchOrders();
    loadSupplierDropdown();
});

/* =========================================
   1. DATA FETCHING
   ========================================= */
async function fetchOrders() {
    try {
        const response = await fetch('php/fetch_orders.php'); 
        const text = await response.text();
        let data;
        try { data = JSON.parse(text); } 
        catch (e) { throw new Error("Server Error: " + text); }

        if (!response.ok) throw new Error(data.message || "HTTP Error");
        
        purchaseOrders = data;
        renderTable();
    } catch (error) { 
        console.error("Fetch Error:", error);
        if (typeof showToast === "function") showToast("Error loading orders", "error");
    }
}

function loadSupplierDropdown() {
    fetch('php/fetch_suppliers.php') 
    .then(res => res.json())
    .then(data => {
        const dropdown = document.getElementById("input_supplier");
        if(!dropdown) return;
        dropdown.innerHTML = '<option value="" disabled selected>Select Supplier</option>';
        data.forEach(supplier => {
            let option = document.createElement("option");
            option.value = supplier.supplierName; 
            option.text = supplier.supplierName;
            dropdown.appendChild(option);
        });
    });
}

function renderTable() {
    const tbody = document.getElementById("order-table-body");
    if(!tbody) return;
    tbody.innerHTML = "";
    
    if (!purchaseOrders.length) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-slate-500">No records.</td></tr>';
        return;
    }

    purchaseOrders.forEach(order => {
        let statusClass = order.status === "Approved" ? "bg-green-100 text-green-700" :
                          order.status === "Rejected" ? "bg-red-100 text-red-700" : 
                          "bg-amber-100 text-amber-700";

        let cost = parseFloat(order.totalCost || 0).toLocaleString('en-US', {minimumFractionDigits: 2});

        tbody.innerHTML += `
        <tr class="hover:bg-slate-50 border-b border-slate-100">
            <td class="px-6 py-4 font-bold text-navy-dark text-sm whitespace-nowrap">${order.orderID}</td>
            <td class="px-6 py-4 font-bold text-primary text-sm whitespace-nowrap">${order.quantity} ${order.unit}</td>
            <td class="px-6 py-4 font-bold text-slate-700 text-sm whitespace-nowrap">${order.itemName}</td>
            <td class="px-6 py-4 text-slate-500 text-sm whitespace-nowrap">${order.itemSubtext || '-'}</td>
            <td class="px-6 py-4 text-slate-600 text-sm whitespace-nowrap">${order.location}</td>
            <td class="px-6 py-4 font-mono text-slate-700 text-sm whitespace-nowrap font-bold">₱${cost}</td>
            <td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs font-bold ${statusClass}">${order.status}</span></td>
            <td class="px-6 py-4 text-slate-600 text-sm whitespace-nowrap">${order.supplierName}</td>
            <td class="px-6 py-4 text-slate-500 text-sm whitespace-nowrap">${order.orderDate}</td>
            <td class="px-6 py-4 flex gap-3 text-slate-400 text-sm whitespace-nowrap">
                <i class="fa-regular fa-eye hover:text-blue-500 cursor-pointer" onclick="viewOrder('${order.orderID}')"></i>
                <i class="fa-regular fa-pen-to-square hover:text-green-500 cursor-pointer" onclick="editOrder('${order.orderID}')"></i>
                <i class="fa-regular fa-trash-can hover:text-red-500 cursor-pointer" onclick="deleteOrder('${order.orderID}')"></i>
            </td>
        </tr>`;
    });
}

/* =========================================
   2. MODAL HELPER FUNCTIONS (FORCE VISIBILITY)
   ========================================= */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.remove('hidden'); // Remove Tailwind hidden class
        modal.style.display = 'flex'; // Force display flex manually
        document.body.style.overflow = 'hidden'; // Stop scrolling
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.body.style.overflow = ''; 
    }
}

function closeModal() { 
    hideModal('addModal');
}

function closeDeleteModal() {
    hideModal('deleteModal');
    orderToDelete = null;
}

/* =========================================
   3. ACTIONS (NEW / EDIT / VIEW)
   ========================================= */
function goToNewPurchase() {
    isEditMode = false; 
    
    document.getElementById("addOrderForm").reset();
    document.querySelector("#addModal h2").textContent = "New Purchase Request";
    document.querySelector("#addOrderForm button[type='submit']").style.display = 'inline-block';
    toggleFormInputs(false);

    // Show modal immediately
    showModal('addModal');

    fetch('php/get_next_id.php') 
        .then(res => res.json())
        .then(data => { 
            if(document.getElementById("input_orderID")) {
                document.getElementById("input_orderID").value = data.nextID; 
            }
        });
}

function editOrder(id) {
    isEditMode = true; 
    document.querySelector("#addModal h2").textContent = "Edit Order";
    document.querySelector("#addOrderForm button[type='submit']").style.display = 'inline-block';
    toggleFormInputs(false);
    
    showModal('addModal'); // Open first
    fillFormWithData(id);  // Then fill
}

function viewOrder(id) {
    document.querySelector("#addModal h2").textContent = "View Order Details";
    document.querySelector("#addOrderForm button[type='submit']").style.display = 'none';
    toggleFormInputs(true);
    
    showModal('addModal'); // Open first
    fillFormWithData(id); 
}

function fillFormWithData(id) {
    fetch(`php/get_order.php?orderID=${id}`) 
        .then(res => res.json())
        .then(data => {
            if(data.error) { showToast("Order not found", "error"); return; }
            
            document.getElementById("input_orderID").value = data.orderID;
            document.getElementById("input_date").value = data.orderDate;
            document.getElementById("input_itemName").value = data.itemName;
            document.getElementById("input_itemSubtext").value = data.itemSubtext;
            document.getElementById("input_quantity").value = data.quantity;
            document.getElementById("input_unit").value = data.unit;
            document.getElementById("input_cost").value = data.totalCost;
            document.getElementById("input_supplier").value = data.supplierName;
            document.getElementById("input_location").value = data.location;
            document.getElementById("input_status").value = data.status || 'Pending';
        })
        .catch(err => {
            console.error(err);
            showToast("Error retrieving data", "error");
        });
}

function toggleFormInputs(isDisabled) {
    const inputs = document.querySelectorAll("#addOrderForm input, #addOrderForm select");
    inputs.forEach(input => {
        if(input.id !== "input_orderID") {
            input.disabled = isDisabled;
            input.classList.toggle("bg-slate-100", isDisabled);
        }
    });
}

document.getElementById("addOrderForm").addEventListener("submit", function(event) {
    event.preventDefault(); 
    let targetURL = isEditMode ? 'php/update_order.php' : 'php/save_order.php'; 
    let formData = new FormData(this);

    fetch(targetURL, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.status === "success") {
            showToast("Saved Successfully!", "success");
            closeModal();
            fetchOrders(); 
        } else {
            showToast("Error: " + data.message, "error");
        }
    });
});

/* =========================================
   4. DELETE LOGIC
   ========================================= */
function deleteOrder(id) { 
    orderToDelete = id; 
    document.getElementById('delete_id_display').textContent = id;
    showModal('deleteModal'); // Use helper
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!orderToDelete) return;
    
    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Deleting...`;

    let formData = new FormData();
    formData.append('orderID', orderToDelete);

    fetch('php/delete_order.php', { method: 'POST', body: formData }) 
    .then(res => res.json())
    .then(data => {
        if(data.status === "success") {
            showToast("Deleted successfully", "success");
            closeDeleteModal();
            fetchOrders(); 
        } else {
            showToast("Failed to delete", "error");
        }
    })
    .catch(() => showToast("Server Error", "error"))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

/* =========================================
   5. CLICK OUTSIDE TO CLOSE
   ========================================= */
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === addModal) closeModal();
    if (event.target === deleteModal) closeDeleteModal();
}