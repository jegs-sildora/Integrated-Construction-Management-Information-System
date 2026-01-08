lucide.createIcons();
    document.addEventListener('DOMContentLoaded', loadGroups);
    let groupToDeleteId = null;

    async function loadGroups() {
        const loading = document.getElementById('groupsLoading');
        const emptyState = document.getElementById('groupsEmpty');
        const tableState = document.getElementById('groupsTable');
        const tbody = document.getElementById('groupsTableBody');

        loading.classList.remove('hidden'); emptyState.classList.add('hidden'); tableState.classList.add('hidden');

        try {
            const res = await fetch('api/employee_groups.php?action=list');
            const result = await res.json();
            loading.classList.add('hidden');

            if (result.success && result.data.length > 0) {
                tableState.classList.remove('hidden');
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
                lucide.createIcons();
            } else {
                emptyState.classList.remove('hidden');
            }
        } catch (e) {
            loading.innerHTML = '<p class="text-red-500">Error loading data.</p>';
        }
    }

    function openGroupModal(reset = true) {
        if(reset) {
            document.getElementById('groupForm').reset();
            document.getElementById('group_action').value = 'create';
            document.getElementById('group_id').value = '';
            document.getElementById('selectedCount').textContent = '0 Selected';
        }
        const modal = document.getElementById('groupModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) setTimeout(() => modalContent.classList.add('modal-open'), 10);
    }

    async function openEditGroupModal(id) {
        try {
            const res = await fetch(`api/employee_groups.php?action=get&id=${id}`);
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
        try {
            const res = await fetch('api/employee_groups.php', { method: 'POST', body: formData });
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
        fetch(`api/employee_groups.php?action=get&id=${id}`)
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
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', groupToDeleteId);
        try {
            const res = await fetch('api/employee_groups.php', { method: 'POST', body: formData });
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