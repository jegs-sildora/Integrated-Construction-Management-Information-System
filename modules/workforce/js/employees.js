lucide.createIcons();

        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-[#e9922c]', 'text-[#e9922c]');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-500');
            document.getElementById('tab-' + tabName).classList.add('border-[#e9922c]', 'text-[#e9922c]');
        }

        // Search Filter
        document.getElementById('searchInput').addEventListener('input', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.employee-row');

            rows.forEach(row => {
                const name = row.dataset.name;
                const rowStatus = row.dataset.status;
                const matchesSearch = name.includes(search);
                const matchesStatus = !status || rowStatus === status;

                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }

        function viewEmployee(id) {
            window.location.href = 'employee_profile.php?id=' + id;
        }

        // --- NEW AJAX REFRESH LOGIC ---
        function refreshData() {
            // Get current URL params
            const params = new URLSearchParams(window.location.search);
            params.set('fetch_updates', '1');
            
            fetch(`${window.location.pathname}?${params.toString()}`)
                .then(res => res.json())
                .then(data => {
                    // Update Stats
                    document.getElementById('stat-total').textContent = data.stats.total;
                    document.getElementById('stat-active').textContent = data.stats.active;
                    document.getElementById('stat-inactive').textContent = data.stats.inactive;
                    document.getElementById('stat-new').textContent = data.stats.new_this_month;
                    
                    // Update Table & Pagination
                    document.getElementById('employeesTableBody').innerHTML = data.tableHtml;
                    document.getElementById('paginationContainer').innerHTML = data.paginationHtml;
                    
                    // Re-initialize icons
                    lucide.createIcons();
                })
                .catch(err => console.error('Error refreshing data:', err));
        }

        // --- MODAL FORM INTERCEPTOR ---
        // Avoid double-handling: the modal (`employee_modal.js`) already intercepts `#employeeForm`.
        // This global listener will ignore submissions coming from the employee modal form.
        document.addEventListener('submit', function(e) {
            const form = e.target;

            // If this is the modal's form, let `employee_modal.js` handle it and skip here.
            if (form && (form.closest && form.closest('#employeeModal') || form.id === 'employeeForm')) {
                return;
            }

            // Other forms (if any) can be handled here in future.
        });

       // Global variable to store the ID pending deletion
        let employeeToDeleteId = null;

        async function openDeleteModal(id) {
            employeeToDeleteId = id;
            
            const modal = document.getElementById('deleteModal');
            const modalContent = modal.querySelector('.modal-content');
            const nameElement = document.getElementById('deleteEmployeeName');
            const btn = document.getElementById('confirmDeleteBtn');

            nameElement.textContent = "Fetching details...";
            btn.disabled = true; 
            
            modal.classList.remove('hidden');
            
            setTimeout(() => {
                modalContent.classList.add('modal-open');
            }, 10);

            try {
                const response = await fetch(`api/employees.php?action=get&id=${id}`);
                const result = await response.json();

                if (result.success && result.data) {
                    const emp = result.data;
                    nameElement.textContent = `${emp.first_name} ${emp.last_name}`;
                    btn.disabled = false;
                } else {
                    nameElement.textContent = "Employee not found";
                }
            } catch (error) {
                console.error('Error fetching employee:', error);
                nameElement.textContent = "Error loading details";
            }
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            const modalContent = modal.querySelector('.modal-content');
            
            modalContent.classList.remove('modal-open');
            modalContent.classList.add('modal-close');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modalContent.classList.remove('modal-close');
                employeeToDeleteId = null;
            }, 240);
        }

        function confirmDelete() {
            if (!employeeToDeleteId) return;

            const btn = document.getElementById('confirmDeleteBtn');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = 'Deleting...';
            btn.disabled = true;

            fetch('api/employees.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: employeeToDeleteId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Employee deleted successfully', 'success');
                    closeDeleteModal();
                    
                    // --- CHANGED: Refresh data via AJAX instead of Reload ---
                    refreshData(); 
                } else {
                    showToast(data.message || 'Error deleting employee', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Network error occurred', 'error');
            })
            .finally(() => {
                // Ensure button is reset
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }