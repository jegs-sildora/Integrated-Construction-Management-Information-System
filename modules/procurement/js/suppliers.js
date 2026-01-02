document.addEventListener("DOMContentLoaded", () => {
    fetchSuppliers();
});

let isEditMode = false;
let supplierToDelete = null;

// NOTE: showToast() is handled globally by toast.php. 

/* =========================================
   1. FETCH & RENDER TABLE (Fixed Error Handling)
   ========================================= */
async function fetchSuppliers() {
    try {
        const response = await fetch('php/fetch_suppliers.php');
        const responseText = await response.text(); // Read raw text first

        let data;
        try {
            data = JSON.parse(responseText); // Try to parse JSON
        } catch (e) {
            console.error("Server Error (Not JSON):", responseText); // Log the HTML error
            if(typeof showToast === 'function') showToast("Server Error: Check console for details", "error");
            return;
        }

        const tbody = document.getElementById("suppliers-table-body");
        if(!tbody) return;
        
        tbody.innerHTML = "";

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-slate-500">No suppliers found.</td></tr>';
            return;
        }

        data.forEach(sup => {
            let statusClass = "bg-green-100 text-green-700";
            if(sup.status === 'Inactive') statusClass = "bg-red-100 text-red-700";

            let row = `
                <tr class="hover:bg-slate-50 border-b border-slate-100 transition-colors">
                    <td class="px-6 py-4 font-bold text-navy-dark text-sm whitespace-nowrap">${sup.supplierID}</td>
                    <td class="px-6 py-4 font-bold text-slate-700 text-sm whitespace-nowrap">${sup.supplierName}</td>
                    <td class="px-6 py-4 text-slate-600 text-sm whitespace-nowrap">${sup.contactPerson}</td>
                    <td class="px-6 py-4 text-slate-600 text-sm font-mono whitespace-nowrap">${sup.contactNumber}</td>
                    <td class="px-6 py-4 text-primary text-sm whitespace-nowrap">${sup.email}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs font-bold ${statusClass}">${sup.status}</span>
                    </td>
                    <td class="px-6 py-4 flex gap-3 text-slate-400">
                        <i class="fa-regular fa-pen-to-square hover:text-green-500 cursor-pointer transition-colors" onclick='openEditModal(${JSON.stringify(sup)})' title="Edit"></i>
                        <i class="fa-regular fa-trash-can hover:text-red-500 cursor-pointer transition-colors" onclick="deleteSupplier(${sup.supplierID})" title="Delete"></i>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });

    } catch (error) {
        console.error('Fetch Network Error:', error);
        if(typeof showToast === 'function') showToast("Connection failed", "error");
    }
}

/* =========================================
   2. MODAL HELPER
   ========================================= */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
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

/* =========================================
   3. ADD / EDIT LOGIC (Fixed Error Handling)
   ========================================= */
function openSupplierModal() {
    isEditMode = false;
    document.getElementById("supplierForm").reset();
    document.getElementById("sup_id").value = "";
    document.getElementById("sup_status").value = "Active"; 
    document.querySelector("#supplierModal h2").textContent = "Add New Supplier";
    showModal('supplierModal');
}

function openEditModal(sup) {
    isEditMode = true;
    document.querySelector("#supplierModal h2").textContent = "Edit Supplier";
    
    document.getElementById("sup_id").value = sup.supplierID;
    document.getElementById("sup_name").value = sup.supplierName;
    document.getElementById("sup_person").value = sup.contactPerson;
    document.getElementById("sup_phone").value = sup.contactNumber;
    document.getElementById("sup_email").value = sup.email;
    document.getElementById("sup_address").value = sup.address;
    document.getElementById("sup_status").value = sup.status || 'Active';

    showModal('supplierModal');
}

function closeSupplierModal() {
    hideModal('supplierModal');
}

document.getElementById("supplierForm").addEventListener("submit", async function(event) {
    event.preventDefault();

    let formData = new FormData();
    formData.append('id', document.getElementById("sup_id").value);
    formData.append('name', document.getElementById("sup_name").value);
    formData.append('person', document.getElementById("sup_person").value);
    formData.append('phone', document.getElementById("sup_phone").value);
    formData.append('email', document.getElementById("sup_email").value);
    formData.append('address', document.getElementById("sup_address").value);
    formData.append('status', document.getElementById("sup_status").value);

    try {
        const response = await fetch('php/save_supplier.php', { method: 'POST', body: formData });
        const responseText = await response.text();

        let data;
        try {
            data = JSON.parse(responseText);
        } catch(e) {
            console.error("Save Error (Not JSON):", responseText);
            if(typeof showToast === 'function') showToast("Server Error: Check console", "error");
            return;
        }

        if (data.status === "success") {
            let msg = isEditMode ? "Supplier updated successfully!" : "Supplier added successfully!";
            if(typeof showToast === 'function') showToast(msg, "success", true);
            
            window.location.reload(); // Reload to refresh data
        } else {
            if(typeof showToast === 'function') showToast("Error: " + data.message, "error");
        }
    } catch (error) {
        console.error("Save Network Error:", error);
    }
});

/* =========================================
   4. DELETE LOGIC (Fixed Error Handling)
   ========================================= */
function deleteSupplier(id) {
    supplierToDelete = id;
    
    document.getElementById('delete_title').textContent = "Delete Supplier";
    document.getElementById('delete_label').textContent = "SUPPLIER ID";
    document.getElementById('delete_id_display').textContent = id;

    showModal('deleteModal');
}

function closeDeleteModal() {
    hideModal('deleteModal');
    supplierToDelete = null;
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    if (!supplierToDelete) return;

    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Deleting...`;

    let formData = new FormData();
    formData.append('id', supplierToDelete);

    try {
        const response = await fetch('php/delete_supplier.php', { method: 'POST', body: formData });
        const responseText = await response.text();

        let data;
        try {
            data = JSON.parse(responseText);
        } catch(e) {
            console.error("Delete Error (Not JSON):", responseText);
            if(typeof showToast === 'function') showToast("Server Error: Check console", "error");
            return;
        }

        if (data.status === "success") {
            if(typeof showToast === 'function') showToast("Supplier deleted successfully!", "success", true);
            window.location.reload();
        } else {
            if(typeof showToast === 'function') showToast("Error deleting supplier.", "error");
        }
    } catch (error) {
        console.error("Delete Network Error:", error);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

/* =========================================
   5. WINDOW CLICK
   ========================================= */
window.onclick = function(event) {
    if (event.target === document.getElementById('supplierModal')) {
        closeSupplierModal();
    }
    if (event.target === document.getElementById('deleteModal')) {
        closeDeleteModal();
    }
}