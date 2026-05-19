lucide.createIcons();
    document.addEventListener('DOMContentLoaded', loadGroups);
    let groupToDeleteId = null;

    async function loadGroups() {
        const loading = document.getElementById('groupsLoading');
        const emptyState = document.getElementById('groupsEmpty');
        const tableState = document.getElementById('groupsTable');
        const tbody = document.getElementById('groupsTableBody');

        if (loading) loading.classList.remove('hidden'); 
        if (emptyState) emptyState.classList.add('hidden'); 
        if (tableState) tableState.classList.add('hidden');

        try {
            // Updated to use API Gateway
            const res = await fetch(`${window.GATEWAY_URL}workforce/employee_groups`, {
                headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
            });
            const result = await res.json();
            if (loading) loading.classList.add('hidden');

            if (result.success && result.data.length > 0) {
                if (tableState) tableState.classList.remove('hidden');
                tbody.innerHTML = result.data.map(g => `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-center">
                            <div class="font-bold text-gray-900">${g.group_code || 'Pending'}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="font-bold text-gray-900">${g.group_name}</div>
                            <div class="text-gray-500 mt-1 truncate max-w-[200px]">${g.description || ''}</div>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-700 text-center">${g.leader_name || '—'}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">${g.member_count} Members</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="window.location.href='group_details.php?group_code=${encodeURIComponent(g.group_code)}'" class="text-gray-500 p-2 rounded-lg hover:text-blue-600 transition-colors"><i data-lucide="eye" class="w-5 h-5"></i></button>
                                <button onclick="openEditGroupModal(${g.group_id})" class="text-gray-500 p-2 rounded-lg hover:text-green-600 transition-colors"><i data-lucide="pencil" class="w-5 h-5"></i></button>
                                <button onclick="openDeleteModal(${g.group_id})" class="text-gray-500 p-2 rounded-lg hover:text-red-600 transition-colors"><i data-lucide="trash-2" class="w-5 h-5"></i></button>
                            </div>
                        </td>
                    </tr>
                `).join('');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                if (emptyState) emptyState.classList.remove('hidden');
            }
        } catch (e) {
            if (loading) loading.innerHTML = '<p class="text-red-500">Error loading data.</p>';
        }
    }

    function openGroupModal(reset = true) {
        if(reset) {
            const form = document.getElementById('groupForm');
            if (form) form.reset();
            const action = document.getElementById('group_action');
            if (action) action.value = 'create';
            const id = document.getElementById('group_id');
            if (id) id.value = '';
            const count = document.getElementById('selectedCount');
            if (count) count.textContent = '0 Selected';
        }
        const modal = document.getElementById('groupModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) setTimeout(() => modalContent.classList.add('modal-open'), 10);
    }

    async function openEditGroupModal(id) {
        try {
            // Updated to use API Gateway
            const res = await fetch(`${window.GATEWAY_URL}workforce/employee_groups/${id}`, {
                headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
            });
            const result = await res.json();
            if(result.success) {
                const data = result.data;
                openGroupModal(false);
                document.getElementById('group_action').value = 'update';
                document.getElementById('group_id').value = data.group_id;
                document.querySelector('[name="group_name"]').value = data.group_name;
                document.querySelector('[name="leader_id"]').value = data.group_leader_id;
                document.querySelector('[name="description"]').value = data.description;
                const checkboxes = document.querySelectorAll('.member-checkbox');
                checkboxes.forEach(cb => { cb.checked = data.members.includes(parseInt(cb.value)); });
                updateCount();
            }
        } catch(e) { alert('Error loading group details'); }
    }

    function closeGroupModal() {
        const modal = document.getElementById('groupModal');
        if (!modal) return;
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) modalContent.classList.remove('modal-open');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    async function saveGroup() {
        const form = document.getElementById('groupForm');
        const formData = new FormData(form);
        const object = {};
        formData.forEach((value, key) => {
            if (key === 'members[]') {
                if (!object.members) object.members = [];
                object.members.push(value);
            } else {
                object[key] = value;
            }
        });

        const isUpdate = !!object.group_id;
        const method = isUpdate ? 'PUT' : 'POST';
        const url = isUpdate 
            ? `${window.GATEWAY_URL}workforce/employee_groups/${object.group_id}` 
            : `${window.GATEWAY_URL}workforce/employee_groups`;

        try {
            const res = await fetch(url, { 
                method: method, 
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${window.AUTH_TOKEN}`
                },
                body: JSON.stringify(object) 
            });
            const result = await res.json();
            if(result.success) {
                showToast(result.message, 'success');
                closeGroupModal();
                loadGroups();
            } else { alert(result.message); }
        } catch(e) { alert('System error'); }
    }

    function openDeleteModal(id) {
        groupToDeleteId = id;
        const modal = document.getElementById('deleteModal');
        if (modal) {
            modal.classList.remove('hidden');
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) setTimeout(() => modalContent.classList.add('modal-open'), 10);
        }
        const deleteNameEl = document.getElementById('deleteGroupName');
        if (deleteNameEl) deleteNameEl.textContent = "Loading...";
        
        // Updated to use API Gateway
        fetch(`${window.GATEWAY_URL}workforce/employee_groups/${id}`, {
            headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
        })
            .then(res=>res.json())
            .then(d => { if(d.success) {
                    if (deleteNameEl) deleteNameEl.textContent = d.data.group_name;
                }
            });
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        if (!modal) return;
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) modalContent.classList.remove('modal-open');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    async function confirmDelete() {
        if (!groupToDeleteId) return;
        try {
            // Updated to use API Gateway
            const res = await fetch(`${window.GATEWAY_URL}workforce/employee_groups/${groupToDeleteId}`, { 
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
            });
            const data = await res.json();
            if (data.success) {
                showToast('Group deleted', 'success');
                closeDeleteModal();
                loadGroups();
            } else { alert(data.message); }
        } catch(err) { alert('Error deleting'); }
    }

    // Helper: Member Count & Search
    const checkboxes = document.querySelectorAll('.member-checkbox');
    const selectedCountLabel = document.getElementById('selectedCount');
    const memberSearch = document.getElementById('memberSearch');
    const memberItems = document.querySelectorAll('#memberList label');

    function updateCount() {
        const count = document.querySelectorAll('.member-checkbox:checked').length;
        if (selectedCountLabel) selectedCountLabel.textContent = `${count} Selected`;
    }
    checkboxes.forEach(cb => cb.addEventListener('change', updateCount));

    if (memberSearch) {
        memberSearch.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            memberItems.forEach(item => {
                item.style.display = item.innerText.toLowerCase().includes(term) ? 'flex' : 'none';
            });
        });
    }