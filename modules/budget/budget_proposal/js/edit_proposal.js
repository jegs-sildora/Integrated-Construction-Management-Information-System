// Externalized script for edit_proposal.php
(function() {
    // AJAX Toast Function
    function showToastAjax(message, type = 'success', persist = false) {
        if (persist) {
            sessionStorage.setItem('pendingToast', JSON.stringify({ message, type }));
            return;
        }
        fetch('/icmis/includes/toast.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, type })
        }).then(r => r.json()).then(data => {
            document.getElementById('toast-container').insertAdjacentHTML('beforeend', data.html);
            setTimeout(() => dismissToast(data.id), 4000);
        }).catch(err => console.error('Toast error:', err));
    }
    // ==========================================
    // 1. STATE MANAGEMENT & INITIALIZATION
    // ==========================================
    let items = [];
    let activeTab = 'materials';
    let editingItemId = null;

    // Read embedded server data provided by PHP
    const DATA = window.EDIT_PROPOSAL_DATA || {};
    const proposalId = DATA.proposalId || 0;
    const savedProjectId = DATA.savedProjectId || '';
    const savedPhaseId = DATA.savedPhaseId || '';

    // Load existing line items
    const existingItems = DATA.existingItems || [];
    if (existingItems) {
        existingItems.forEach(item => {
            const categoryMap = {
                'MATERIAL': 'materials',
                'LABOR': 'labor',
                'EQUIPMENT': 'equipment'
            };
            items.push({
                id: Date.now() + Math.random(),
                category: categoryMap[item.category] || item.category.toLowerCase(),
                name: item.item_name,
                quantity: parseFloat(item.quantity),
                unitCost: parseFloat(item.unit_cost),
                subtotal: parseFloat(item.subtotal)
            });
        });
    }

    // ==========================================
    // 2. PHASE AUTOMATION LOGIC
    // ==========================================
    function loadPhases(projectId, preSelectId = null) {
        const phaseSelect = document.getElementById('targetPhase');
        if (!phaseSelect) return;
        phaseSelect.innerHTML = '<option value="">Loading...</option>';
        phaseSelect.disabled = true;

        if (!projectId) {
            phaseSelect.innerHTML = '<option value="">-- Select a Project First --</option>';
            phaseSelect.disabled = false;
            return;
        }

        fetch(`get_project_phases.php?project_id=${projectId}`)
            .then(response => {
                if (!response.ok) throw new Error("API Not Found (404)");
                return response.json();
            })
            .then(data => {
                phaseSelect.innerHTML = '<option value="">-- Select Target Phase --</option>';

                if (data.success && data.phases.length > 0) {
                    data.phases.forEach(phase => {
                        const option = document.createElement('option');
                        option.value = phase.id;
                        option.textContent = phase.name;
                        option.dataset.start = phase.start_date || '';
                        option.dataset.end = phase.end_date || '';
                        option.dataset.duration = phase.duration;

                        if (preSelectId && phase.id == preSelectId) {
                            option.selected = true;
                            autoFillPhaseDetails(option);
                            const previewPhaseEl = document.getElementById('preview-phase');
                            if(previewPhaseEl) {
                                previewPhaseEl.textContent = phase.name;
                                previewPhaseEl.classList.remove('text-gray-400', 'italic');
                                previewPhaseEl.classList.add('text-gray-900');
                            }
                        }

                        phaseSelect.appendChild(option);
                    });
                } else {
                    phaseSelect.innerHTML = '<option value="">No phases found</option>';
                }
                phaseSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                phaseSelect.innerHTML = '<option value="">Error loading phases</option>';
                phaseSelect.disabled = false;
            });
    }

    function autoFillPhaseDetails(selectedOption) {
        if (!selectedOption || !selectedOption.value) return;
        const startDate = selectedOption.dataset.start;
        const endDate = selectedOption.dataset.end;
        if(startDate) document.getElementById('phaseStartDate').value = startDate;
        if(endDate) document.getElementById('phaseEndDate').value = endDate;
        document.getElementById('proposalTitle').dispatchEvent(new Event('input'));
        updateTimeline();
    }

    // ==========================================
    // 3. CORE FUNCTIONS
    // ==========================================
    function updateTimeline() {
        const startDate = document.getElementById('phaseStartDate').value;
        const endDate = document.getElementById('phaseEndDate').value;
        const previewTimeline = document.getElementById('preview-timeline');
        if (!previewTimeline) return;
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const options = { month: 'short', day: '2-digit', year: 'numeric' };
            const formattedStart = start.toLocaleDateString('en-US', options);
            const formattedEnd = end.toLocaleDateString('en-US', options);
            previewTimeline.textContent = `${formattedStart} - ${formattedEnd}`;
            previewTimeline.className = 'text-sm font-medium text-gray-900 mt-1';
        } else {
            previewTimeline.textContent = 'No timeline set';
            previewTimeline.className = 'text-sm font-medium text-gray-400 mt-1 italic';
        }
    }

    function formatPeso(amount) {
        return amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function updateGrandTotal() {
        const total = items.reduce((sum, item) => sum + item.subtotal, 0);
        const el = document.getElementById('grand-total');
        if (el) el.textContent = '₱' + formatPeso(total);
    }

    // ==========================================
    // 4. EVENT LISTENERS & UI
    // ==========================================
    function renderItems() {
        const emptyState = document.getElementById('empty-state');
        const materialsSection = document.getElementById('materials-section');
        const laborSection = document.getElementById('labor-section');
        const equipmentSection = document.getElementById('equipment-section');
        const materialsList = document.getElementById('materials-list');
        const laborList = document.getElementById('labor-list');
        const equipmentList = document.getElementById('equipment-list');

        if (!materialsSection || !laborSection || !equipmentSection) return;

        if (items.length === 0) {
            if(emptyState) emptyState.style.display = 'block';
            materialsSection.style.display = 'none';
            laborSection.style.display = 'none';
            equipmentSection.style.display = 'none';
            return;
        }

        if(emptyState) emptyState.style.display = 'none';

        const materialItems = items.filter(item => item.category === 'materials');
        const laborItems = items.filter(item => item.category === 'labor');
        const equipmentItems = items.filter(item => item.category === 'equipment');

        renderCategoryList(materialItems, materialsSection, materialsList);
        renderCategoryList(laborItems, laborSection, laborList);
        renderCategoryList(equipmentItems, equipmentSection, equipmentList);
    }

    function renderCategoryList(categoryItems, section, listContainer) {
        if (!section || !listContainer) return;
        if (categoryItems.length > 0) {
            section.style.display = 'block';
            listContainer.innerHTML = '';
            categoryItems.forEach(item => {
                listContainer.appendChild(createItemElement(item));
            });
        } else {
            section.style.display = 'none';
        }
    }

    function createItemElement(item) {
        const div = document.createElement('div');
        div.className = 'bg-gray-50 rounded-lg p-3 border border-gray-200 cursor-pointer hover:bg-gray-100 transition-colors';
        div.innerHTML = `
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1" onclick="editItem(${item.id})">
                    <p class="text-sm font-medium text-gray-900">${item.name}</p>
                    <p class="text-xs text-gray-500">${item.quantity} × ₱${formatPeso(item.unitCost)}</p>
                </div>
                <button onclick="removeItem(${item.id})" class="text-red-500 hover:text-red-700 ml-2">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="text-right">
                <span class="text-sm font-semibold text-gray-900">₱${formatPeso(item.subtotal)}</span>
            </div>
        `;
        return div;
    }

    function editItem(id) {
        const item = items.find(i => i.id === id);
        if (!item) return;
        editingItemId = id;
        document.getElementById(`tab-${item.category}`).click();
        if (item.category === 'materials') {
            document.getElementById('material-name').value = item.name;
            document.getElementById('material-quantity').value = item.quantity;
            document.getElementById('material-cost').value = item.unitCost;
        } else if (item.category === 'labor') {
            document.getElementById('labor-type').value = item.name;
            document.getElementById('labor-quantity').value = item.quantity;
            document.getElementById('labor-rate').value = item.unitCost;
        } else if (item.category === 'equipment') {
            document.getElementById('equipment-name').value = item.name;
            document.getElementById('equipment-quantity').value = item.quantity;
            document.getElementById('equipment-rate').value = item.unitCost;
        }
        updateButtonText();
    }

    function updateButtonText() {
        const action = editingItemId !== null ? 'Update' : 'Add';
        const icon = editingItemId !== null ? '<i class="fas fa-save mr-2"></i>' : '<i class="fas fa-plus mr-2"></i>';
        const mat = document.getElementById('add-material');
        const lab = document.getElementById('add-labor');
        const eq = document.getElementById('add-equipment');
        if (mat) mat.innerHTML = `${icon} ${action} Material Item`;
        if (lab) lab.innerHTML = `${icon} ${action} Labor Item`;
        if (eq) eq.innerHTML = `${icon} ${action} Equipment Item`;
    }

    function removeItem(id) {
        items = items.filter(item => item.id !== id);
        renderItems();
        updateGrandTotal();
    }

    // Live Preview
    const proposalTitleEl = document.getElementById('proposalTitle');
    if (proposalTitleEl) {
        proposalTitleEl.addEventListener('input', function(e) {
            const preview = document.getElementById('preview-title');
            if (preview) preview.textContent = e.target.value || 'No title entered';
        });
    }

    // Form Submission
    const saveDraftBtn = document.getElementById('save-draft');
    if (saveDraftBtn) saveDraftBtn.addEventListener('click', () => { if (validateForm()) submitProposal('DRAFT'); });
    const updateBtn = document.getElementById('update-proposal');
    if (updateBtn) updateBtn.addEventListener('click', () => {
        if (validateForm()) {
            const status = document.querySelector('input[name="proposalStatus"]:checked')?.value || 'PENDING';
            submitProposal(status);
        }
    });

    function validateForm() {
        if (items.length === 0) { showToastAjax('Please add at least one line item', 'warning'); return false; }
        const projectEl = document.getElementById('project');
        if (!projectEl || !projectEl.value) { showToastAjax('Please select a project', 'warning'); return false; }
        return true;
    }

    function submitProposal(status) {
        const projectSelect = document.getElementById('project');
        const projectId = projectSelect.options[projectSelect.selectedIndex].getAttribute('data-id');
        const data = {
            proposal_id: proposalId,
            project_id: projectId,
            phase_id: document.getElementById('targetPhase').value,
            title: document.getElementById('proposalTitle').value,
            phase_start_date: document.getElementById('phaseStartDate').value,
            phase_end_date: document.getElementById('phaseEndDate').value,
            scope_description: document.getElementById('scopeDescription').value,
            items: items,
            status: status,
            total_amount: items.reduce((sum, item) => sum + item.subtotal, 0)
        };

        fetch('update_proposal.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                showToastAjax(result.message || 'Proposal updated successfully!', 'success', true);
                setTimeout(() => window.location.href = '../proposals.php', 1000);
            } else {
                showToastAjax('Error: ' + result.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToastAjax('Network error occurred', 'error');
        });
    }

    // INITIALIZE
    renderItems();
    updateGrandTotal();

    // Initialize by fetching authoritative proposal details (ensures phase_id resolved by code)
    async function initProposalContext() {
        try {
            const resp = await fetch(`get_proposal_details.php?id=${proposalId}`);
            const data = await resp.json();
            if (data && data.success && data.proposal) {
                const p = data.proposal;
                const projectIdFromApi = p.project_id || savedProjectId;
                const phaseIdFromApi = p.phase_id || savedPhaseId;

                if (projectIdFromApi) {
                    const projectSelectEl = document.getElementById('project');
                    for (let i = 0; i < projectSelectEl.options.length; i++) {
                        const opt = projectSelectEl.options[i];
                        if (opt.getAttribute('data-id') == projectIdFromApi) {
                            opt.selected = true;
                            break;
                        }
                    }
                }

                if (projectIdFromApi) {
                    loadPhases(projectIdFromApi, phaseIdFromApi);
                } else if (savedProjectId) {
                    loadPhases(savedProjectId, savedPhaseId);
                }

                const phaseStartDate = p.phase_start_date;
                const phaseEndDate = p.phase_end_date;
                if (phaseStartDate) document.getElementById('phaseStartDate').value = phaseStartDate;
                if (phaseEndDate) document.getElementById('phaseEndDate').value = phaseEndDate;

                updateTimeline();
                return;
            }
        } catch (err) {
            console.error('Failed to load proposal details:', err);
        }

        if (savedProjectId) loadPhases(savedProjectId, savedPhaseId);
    }

    initProposalContext();

    // Project Changed
    const projectEl = document.getElementById('project');
    if (projectEl) projectEl.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const projectId = selectedOption.getAttribute('data-id');
        document.getElementById('phaseStartDate').value = '';
        document.getElementById('phaseEndDate').value = '';
        loadPhases(projectId, null);
    });

    // Phase Changed
    const targetPhaseEl = document.getElementById('targetPhase');
    if (targetPhaseEl) targetPhaseEl.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        autoFillPhaseDetails(selectedOption);
        const preview = document.getElementById('preview-phase');
        if (preview) preview.textContent = selectedOption.textContent;
    });

    // Date Changes
    const sd = document.getElementById('phaseStartDate');
    const ed = document.getElementById('phaseEndDate');
    if (sd) sd.addEventListener('change', updateTimeline);
    if (ed) ed.addEventListener('change', updateTimeline);

    // Tab Switching
    const tabs = ['materials', 'labor', 'equipment'];
    tabs.forEach(tab => {
        const el = document.getElementById(`tab-${tab}`);
        if (!el) return;
        el.addEventListener('click', () => {
            activeTab = tab;
            tabs.forEach(t => {
                const btn = document.getElementById(`tab-${t}`);
                const panel = document.getElementById(`panel-${t}`);
                if (!btn || !panel) return;
                if (t === tab) {
                    btn.classList.remove('border-transparent', 'text-gray-500');
                    btn.classList.add('border-[#e9922c]', 'text-[#e9922c]');
                    panel.classList.remove('hidden');
                } else {
                    btn.classList.remove('border-[#e9922c]', 'text-[#e9922c]');
                    btn.classList.add('border-transparent', 'text-gray-500');
                    panel.classList.add('hidden');
                }
            });
        });
    });

    // Add Item Listeners
    function handleAddItem(category, nameInputId, qtyInputId, costInputId) {
        const nameInput = document.getElementById(nameInputId);
        const qtyInput = document.getElementById(qtyInputId);
        const costInput = document.getElementById(costInputId);
        if (!nameInput || !qtyInput || !costInput) return;
        let name = nameInput.value;
        if(nameInput.tagName === 'SELECT') name = nameInput.value;
        name = name.trim();
        const quantity = parseFloat(qtyInput.value) || 0;
        const cost = parseFloat(costInput.value) || 0;
        if (name && quantity > 0 && cost > 0) {
            const subtotal = quantity * cost;
            if (editingItemId !== null) {
                const index = items.findIndex(i => i.id === editingItemId);
                if (index !== -1) {
                    items[index] = { id: editingItemId, category, name, quantity, unitCost: cost, subtotal };
                }
                showToastAjax('Item updated successfully', 'success');
                editingItemId = null;
            } else {
                items.push({ id: Date.now(), category, name, quantity, unitCost: cost, subtotal });
                showToastAjax('Item added successfully', 'success');
            }
            if(nameInput.tagName === 'INPUT') nameInput.value = '';
            else nameInput.selectedIndex = 0;
            qtyInput.value = '';
            costInput.value = '';
            renderItems();
            updateGrandTotal();
            updateButtonText();
        } else {
            showToastAjax('Please fill all fields', 'warning');
        }
    }

    const addMaterialBtn = document.getElementById('add-material');
    if (addMaterialBtn) addMaterialBtn.addEventListener('click', () => handleAddItem('materials', 'material-name', 'material-quantity', 'material-cost'));
    const addLaborBtn = document.getElementById('add-labor');
    if (addLaborBtn) addLaborBtn.addEventListener('click', () => handleAddItem('labor', 'labor-type', 'labor-quantity', 'labor-rate'));
    const addEquipmentBtn = document.getElementById('add-equipment');
    if (addEquipmentBtn) addEquipmentBtn.addEventListener('click', () => handleAddItem('equipment', 'equipment-name', 'equipment-quantity', 'equipment-rate'));

    // Expose edit/remove for inline handlers
    window.editItem = editItem;
    window.removeItem = removeItem;

})();
