// create_proposal.js - moved from inline script in create_proposal.php
(function () {
    // AJAX Toast Function
    function showToastAjax(message, type = 'success', persist = false) {
        if (persist) {
            sessionStorage.setItem('pendingToast', JSON.stringify({ message, type }));
            return;
        }
        fetch('/includes/toast.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, type })
        }).then(r => r.json()).then(data => {
            document.getElementById('toast-container').insertAdjacentHTML('beforeend', data.html);
            setTimeout(() => dismissToast(data.id), 4000);
        }).catch(err => console.error('Toast error:', err));
    }
    // State Management
    let items = [];
    let activeTab = 'materials';

    // Tab Switching
    const tabs = ['materials', 'labor', 'equipment'];
    tabs.forEach(tab => {
        const el = document.getElementById(`tab-${tab}`);
        if (el) el.addEventListener('click', () => {
            switchTab(tab);
        });
    });

    function switchTab(tab) {
        activeTab = tab;
        // Update tab buttons
        tabs.forEach(t => {
            const tabBtn = document.getElementById(`tab-${t}`);
            const panel = document.getElementById(`panel-${t}`);
            if (!tabBtn || !panel) return;
            if (t === tab) {
                tabBtn.classList.remove('border-transparent', 'text-gray-500');
                tabBtn.classList.add('border-[#e9922c]', 'text-[#e9922c]');
                panel.classList.remove('hidden');
            } else {
                tabBtn.classList.remove('border-[#e9922c]', 'text-[#e9922c]');
                tabBtn.classList.add('border-transparent', 'text-gray-500');
                panel.classList.add('hidden');
            }
        });
    }

    // Add Item Functions
    const addMaterialBtn = document.getElementById('add-material');
    if (addMaterialBtn) addMaterialBtn.addEventListener('click', () => {
        const materialInput = document.getElementById('material-name');
        const name = materialInput ? materialInput.value.trim() : '';
        const quantity = parseFloat(document.getElementById('material-quantity').value) || 0;
        const cost = parseFloat(document.getElementById('material-cost').value) || 0;

        if (name && quantity > 0 && cost > 0) {
            addItem('materials', name, quantity, cost);
            // Clear inputs
            if (materialInput) materialInput.value = '';
            document.getElementById('material-quantity').value = '';
            document.getElementById('material-cost').value = '';
            document.getElementById('material-total-cost').textContent = '₱0.00';
            showToastAjax('Material item added successfully', 'success');
        } else {
            showToastAjax('Please fill in all fields with valid values', 'warning');
        }
    });

    const addLaborBtn = document.getElementById('add-labor');
    if (addLaborBtn) addLaborBtn.addEventListener('click', () => {
        const laborInput = document.getElementById('labor-type');
        const name = laborInput ? laborInput.value.trim() : '';
        const quantity = parseFloat(document.getElementById('labor-quantity').value) || 0;
        const cost = parseFloat(document.getElementById('labor-daily-rate').value) || 0;

        if (name && quantity > 0 && cost > 0) {
            addItem('labor', name, quantity, cost);
            // Clear inputs
            if (laborInput) laborInput.value = '';
            document.getElementById('labor-quantity').value = '';
            document.getElementById('labor-daily-rate').value = '';
            document.getElementById('labor-hourly-rate').value = '';
            document.getElementById('labor-total-cost').textContent = '₱0.00';
            showToastAjax('Labor item added successfully', 'success');
        } else {
            showToastAjax('Please fill in all fields with valid values', 'warning');
        }
    });

    const addEquipmentBtn = document.getElementById('add-equipment');
    if (addEquipmentBtn) addEquipmentBtn.addEventListener('click', () => {
        const equipmentInput = document.getElementById('equipment-name');
        const name = equipmentInput ? equipmentInput.value.trim() : '';
        
        let quantity = 0;
        let totalCost = 0;
        let acquisitionType = '';

        // Elements used for equipment
        const acqRentalRadio = document.getElementById('acq-rental');
        const equipmentQuantity = document.getElementById('equipment-quantity');
        const equipmentDays = document.getElementById('equipment-days');
        const equipmentRentalRate = document.getElementById('equipment-rental-rate');
        const equipmentPurchaseQuantity = document.getElementById('equipment-purchase-quantity');
        const equipmentPurchaseCost = document.getElementById('equipment-purchase-cost');
        const equipmentTotalCost = document.getElementById('equipment-total-cost');

        // Check which acquisition type is selected
        if (acqRentalRadio && acqRentalRadio.checked) {
            acquisitionType = 'RENTAL';
            const qty = parseFloat(equipmentQuantity.value) || 0;
            const days = parseFloat(equipmentDays.value) || 0;
            const rate = parseFloat(equipmentRentalRate.value) || 0;
            quantity = qty;
            totalCost = qty * days * rate;

            if (name && qty > 0 && days > 0 && rate > 0) {
                addItem('equipment', name, quantity, totalCost / quantity); // Use average cost per unit
                // Clear inputs
                if (equipmentInput) equipmentInput.value = '';
                equipmentQuantity.value = '';
                equipmentDays.value = '';
                equipmentRentalRate.value = '';
                if (equipmentTotalCost) equipmentTotalCost.textContent = '₱0.00';
                showToastAjax('Equipment (Rental) added successfully', 'success');
            } else {
                showToastAjax('Please fill in all rental fields with valid values', 'warning');
            }
        } else {
            acquisitionType = 'PURCHASE';
            const qty = parseFloat(equipmentPurchaseQuantity.value) || 0;
            const cost = parseFloat(equipmentPurchaseCost.value) || 0;
            quantity = qty;
            totalCost = qty * cost;

            if (name && qty > 0 && cost > 0) {
                addItem('equipment', name, quantity, cost);
                // Clear inputs
                if (equipmentInput) equipmentInput.value = '';
                equipmentPurchaseQuantity.value = '';
                equipmentPurchaseCost.value = '';
                if (equipmentTotalCost) equipmentTotalCost.textContent = '₱0.00';
                showToastAjax('Equipment (Purchase) added successfully', 'success');
            } else {
                showToastAjax('Please fill in all purchase fields with valid values', 'warning');
            }
        }
    });

    function addItem(category, name, quantity, unitCost) {
        const subtotal = quantity * unitCost;
        const item = {
            id: Date.now(),
            category: category,
            name: name,
            quantity: quantity,
            unitCost: unitCost,
            subtotal: subtotal
        };

        items.push(item);
        renderItems();
        updateGrandTotal();
    }

    function removeItem(id) {
        items = items.filter(item => item.id !== id);
        renderItems();
        updateGrandTotal();
        showToastAjax('Item removed successfully', 'success');
    }

    function renderItems() {
        const emptyState = document.getElementById('empty-state');
        const materialsSection = document.getElementById('materials-section');
        const laborSection = document.getElementById('labor-section');
        const equipmentSection = document.getElementById('equipment-section');
        const materialsList = document.getElementById('materials-list');
        const laborList = document.getElementById('labor-list');
        const equipmentList = document.getElementById('equipment-list');

        if (!emptyState || !materialsSection || !laborSection || !equipmentSection) return;

        if (items.length === 0) {
            emptyState.style.display = 'block';
            materialsSection.style.display = 'none';
            laborSection.style.display = 'none';
            equipmentSection.style.display = 'none';
            return;
        }

        emptyState.style.display = 'none';

        // Separate items by category
        const materialItems = items.filter(item => item.category === 'materials');
        const laborItems = items.filter(item => item.category === 'labor');
        const equipmentItems = items.filter(item => item.category === 'equipment');

        // Render Materials
        if (materialItems.length > 0) {
            materialsSection.style.display = 'block';
            if (materialsList) {
                materialsList.innerHTML = '';
                materialItems.forEach((item) => {
                    const element = createItemElement(item);
                    materialsList.appendChild(element);
                });
            }
        } else {
            materialsSection.style.display = 'none';
        }

        // Render Labor
        if (laborItems.length > 0) {
            laborSection.style.display = 'block';
            if (laborList) {
                laborList.innerHTML = '';
                laborItems.forEach((item) => {
                    const element = createItemElement(item);
                    laborList.appendChild(element);
                });
            }
        } else {
            laborSection.style.display = 'none';
        }

        // Render Equipment
        if (equipmentItems.length > 0) {
            equipmentSection.style.display = 'block';
            if (equipmentList) {
                equipmentList.innerHTML = '';
                equipmentItems.forEach((item) => {
                    const element = createItemElement(item);
                    equipmentList.appendChild(element);
                });
            }
        } else {
            equipmentSection.style.display = 'none';
        }
    }

    function createItemElement(item) {
        const div = document.createElement('div');
        div.className = 'bg-gray-50 rounded-lg p-3 border border-gray-200';
        
        const flexContainer = document.createElement('div');
        flexContainer.className = 'flex justify-between items-start mb-2';
        
        const leftContent = document.createElement('div');
        leftContent.className = 'flex-1';
        
        const itemName = document.createElement('p');
        itemName.className = 'text-sm font-medium text-gray-900';
        itemName.textContent = item.name;
        
        const itemDetails = document.createElement('p');
        itemDetails.className = 'text-xs text-gray-500';
        itemDetails.textContent = `${item.quantity} × ₱${formatPeso(item.unitCost)}`;
        
        leftContent.appendChild(itemName);
        leftContent.appendChild(itemDetails);
        
        const removeBtn = document.createElement('button');
        removeBtn.className = 'text-red-500 hover:text-red-700 transition-colors ml-2';
        removeBtn.onclick = () => removeItem(item.id);
        removeBtn.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        `;
        
        flexContainer.appendChild(leftContent);
        flexContainer.appendChild(removeBtn);
        
        const totalDiv = document.createElement('div');
        totalDiv.className = 'text-right';
        totalDiv.innerHTML = `<span class="text-sm font-semibold text-gray-900">₱${formatPeso(item.subtotal)}</span>`;
        
        div.appendChild(flexContainer);
        div.appendChild(totalDiv);
        
        return div;
    }

    function updateGrandTotal() {
        const total = items.reduce((sum, item) => sum + item.subtotal, 0);
        const el = document.getElementById('grand-total');
        if (el) el.textContent = '₱' + formatPeso(total);
    }

    function formatPeso(amount) {
        return amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // 1. Listen for Project Selection
    const projectSelectEl = document.getElementById('project');
    if (projectSelectEl) projectSelectEl.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const projectId = selectedOption ? selectedOption.getAttribute('data-id') : '';
        const phaseSelect = document.getElementById('targetPhase');

        // Reset fields
        if (phaseSelect) phaseSelect.innerHTML = '<option value="">Loading phases...</option>';
        const sd = document.getElementById('phaseStartDate'); if (sd) sd.value = '';
        const ed = document.getElementById('phaseEndDate'); if (ed) ed.value = '';
        const pt = document.getElementById('proposalTitle'); if (pt) pt.value = '';

        if (!projectId) {
            if (phaseSelect) phaseSelect.innerHTML = '<option value="">-- Select a Project First --</option>';
            return;
        }

        // Fetch Phases from Backend
        fetch(`${window.GATEWAY_URL}project/phases?project_id=${projectId}`, {
            headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
        })
            .then(response => response.json())
            .then(data => {
                if (phaseSelect) {
                    if (data.success && data.phases.length > 0) {
                        phaseSelect.innerHTML = '<option value="">-- Select Target Phase --</option>';
                        data.phases.forEach(phase => {
                            const option = document.createElement('option');
                            option.value = phase.id;
                            option.textContent = phase.name;
                            option.dataset.start = phase.start_date;
                            option.dataset.end = phase.end_date;
                            option.dataset.id = phase.id;
                            phaseSelect.appendChild(option);
                        });
                    } else {
                        phaseSelect.innerHTML = '<option value="">No phases found for this project</option>';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (phaseSelect) phaseSelect.innerHTML = '<option value="">Error loading phases</option>';
            });
    });

    // 2. Listen for Phase Selection (Auto-fill Title & Dates)
    const targetPhaseEl = document.getElementById('targetPhase');
    if (targetPhaseEl) targetPhaseEl.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const previewPhase = document.getElementById('preview-phase');
        
        if (selectedOption && selectedOption.value) {
            const startDate = selectedOption.dataset.start;
            const endDate = selectedOption.dataset.end;
            const phaseName = (selectedOption.textContent || '').trim().replace(/\s+/g, ' ');

            if (previewPhase) {
                previewPhase.textContent = phaseName;
                previewPhase.classList.remove('italic', 'text-gray-500');
                previewPhase.classList.add('text-gray-900');
            }

            const sd = document.getElementById('phaseStartDate'); if (sd) sd.value = startDate;
            const ed = document.getElementById('phaseEndDate'); if (ed) ed.value = endDate;
            const titleInput = document.getElementById('proposalTitle');
            if (titleInput) {
                titleInput.value = `${phaseName} — Budget Proposal`;
                titleInput.dispatchEvent(new Event('input'));
            }
            updateTimeline();
        } else {
            if (previewPhase) {
                previewPhase.textContent = 'No phase specified';
                previewPhase.classList.add('italic');
            }
        }
    });

    // 3. Listen for Title Input (Live Preview)
    const proposalTitleEl = document.getElementById('proposalTitle');
    if (proposalTitleEl) proposalTitleEl.addEventListener('input', function(e) {
        const previewTitle = document.getElementById('preview-title');
        const val = e.target.value.trim();

        if (previewTitle) {
            if (val) {
                previewTitle.textContent = val;
                previewTitle.classList.remove('italic');
                previewTitle.classList.add('text-gray-900');
            } else {
                previewTitle.textContent = 'No title entered';
                previewTitle.classList.add('italic');
                previewTitle.classList.remove('text-gray-900');
            }
        }
    });

    function updateTimeline() {
        const startDate = document.getElementById('phaseStartDate') ? document.getElementById('phaseStartDate').value : '';
        const endDate = document.getElementById('phaseEndDate') ? document.getElementById('phaseEndDate').value : '';
        const previewTimeline = document.getElementById('preview-timeline');
        
        if (startDate && endDate && previewTimeline) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const formattedStart = start.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            const formattedEnd = end.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            previewTimeline.textContent = `${formattedStart} - ${formattedEnd}`;
            previewTimeline.className = 'text-sm font-medium text-gray-900 mt-1';
        } else if (previewTimeline) {
            previewTimeline.textContent = 'No timeline set';
            previewTimeline.className = 'text-sm font-medium text-gray-400 mt-1 italic';
        }
    }

    const phaseStartDateEl = document.getElementById('phaseStartDate'); if (phaseStartDateEl) phaseStartDateEl.addEventListener('change', updateTimeline);
    const phaseEndDateEl = document.getElementById('phaseEndDate'); if (phaseEndDateEl) phaseEndDateEl.addEventListener('change', updateTimeline);

    const scopeDescriptionEl = document.getElementById('scopeDescription');
    if (scopeDescriptionEl) scopeDescriptionEl.addEventListener('input', (e) => {
        const previewScope = document.getElementById('preview-scope');
        const previewScopeContainer = document.getElementById('preview-scope-container');
        const text = e.target.value.trim();
        
        if (text) {
            if (previewScope) previewScope.textContent = text;
            if (previewScopeContainer) previewScopeContainer.style.display = 'block';
        } else {
            if (previewScopeContainer) previewScopeContainer.style.display = 'none';
        }
    });

    // Initialize previews when page loads if context selected
    document.addEventListener('DOMContentLoaded', function() {
        const projectSelect = document.getElementById('project');
        if (projectSelect) {
            const sel = projectSelect.options[projectSelect.selectedIndex];
            if (sel && sel.getAttribute('data-id')) {
                const previewProject = document.getElementById('preview-project');
                if (previewProject) {
                    previewProject.textContent = sel.textContent || 'No project selected';
                    previewProject.classList.remove('italic');
                }
            }
        }

        const phaseSelect = document.getElementById('targetPhase');
        if (phaseSelect) {
            const selPhase = phaseSelect.options[phaseSelect.selectedIndex];
            if (selPhase && selPhase.value) {
                phaseSelect.dispatchEvent(new Event('change'));
            }
        }
    });

    // Labor Rate Auto-Calculation (Two-Way Binding)
    const laborDailyRateInput = document.getElementById('labor-daily-rate');
    const laborHourlyRateInput = document.getElementById('labor-hourly-rate');
    const STANDARD_WORK_HOURS = 8;

    // Flag to prevent infinite loop
    let isCalculating = false;

    if (laborDailyRateInput) laborDailyRateInput.addEventListener('input', function() {
        if (isCalculating) return;
        const dailyRate = parseFloat(this.value);
        if (isNaN(dailyRate) || dailyRate <= 0 || this.value.trim() === '') {
            if (laborHourlyRateInput) laborHourlyRateInput.value = '';
            return;
        }
        isCalculating = true;
        const hourlyRate = dailyRate / STANDARD_WORK_HOURS;
        if (laborHourlyRateInput) laborHourlyRateInput.value = hourlyRate.toFixed(2);
        isCalculating = false;
    });

    if (laborHourlyRateInput) laborHourlyRateInput.addEventListener('input', function() {
        if (isCalculating) return;
        const hourlyRate = parseFloat(this.value);
        if (isNaN(hourlyRate) || hourlyRate <= 0 || this.value.trim() === '') {
            if (laborDailyRateInput) laborDailyRateInput.value = '';
            return;
        }
        isCalculating = true;
        const dailyRate = hourlyRate * STANDARD_WORK_HOURS;
        if (laborDailyRateInput) laborDailyRateInput.value = dailyRate.toFixed(2);
        isCalculating = false;
    });

    // Equipment Acquisition Type Toggle and Auto-Calculation
    const acqRentalRadio = document.getElementById('acq-rental');
    const acqPurchaseRadio = document.getElementById('acq-purchase');
    const rentalFields = document.getElementById('rental-fields');
    const purchaseFields = document.getElementById('purchase-fields');
    const equipmentTotalCost = document.getElementById('equipment-total-cost');

    // Rental fields
    const equipmentQuantity = document.getElementById('equipment-quantity');
    const equipmentDays = document.getElementById('equipment-days');
    const equipmentRentalRate = document.getElementById('equipment-rental-rate');

    // Purchase fields
    const equipmentPurchaseQuantity = document.getElementById('equipment-purchase-quantity');
    const equipmentPurchaseCost = document.getElementById('equipment-purchase-cost');

    function toggleAcquisitionType() {
        if (acqRentalRadio && acqRentalRadio.checked) {
            if (rentalFields) rentalFields.classList.remove('hidden');
            if (purchaseFields) purchaseFields.classList.add('hidden');
            if (equipmentPurchaseQuantity) equipmentPurchaseQuantity.value = '';
            if (equipmentPurchaseCost) equipmentPurchaseCost.value = '';
            calculateEquipmentTotal();
        } else {
            if (rentalFields) rentalFields.classList.add('hidden');
            if (purchaseFields) purchaseFields.classList.remove('hidden');
            if (equipmentQuantity) equipmentQuantity.value = '';
            if (equipmentDays) equipmentDays.value = '';
            if (equipmentRentalRate) equipmentRentalRate.value = '';
            calculateEquipmentTotal();
        }
    }

    function calculateEquipmentTotal() {
        let total = 0;
        if (acqRentalRadio && acqRentalRadio.checked) {
            const quantity = parseFloat(equipmentQuantity.value) || 0;
            const days = parseFloat(equipmentDays.value) || 0;
            const rate = parseFloat(equipmentRentalRate.value) || 0;
            total = quantity * days * rate;
        } else {
            const quantity = parseFloat(equipmentPurchaseQuantity.value) || 0;
            const cost = parseFloat(equipmentPurchaseCost.value) || 0;
            total = quantity * cost;
        }
        if (equipmentTotalCost) equipmentTotalCost.textContent = '₱' + formatPeso(total);
    }

    if (acqRentalRadio) acqRentalRadio.addEventListener('change', toggleAcquisitionType);
    if (acqPurchaseRadio) acqPurchaseRadio.addEventListener('change', toggleAcquisitionType);

    if (equipmentQuantity) equipmentQuantity.addEventListener('input', calculateEquipmentTotal);
    if (equipmentDays) equipmentDays.addEventListener('input', calculateEquipmentTotal);
    if (equipmentRentalRate) equipmentRentalRate.addEventListener('input', calculateEquipmentTotal);
    if (equipmentPurchaseQuantity) equipmentPurchaseQuantity.addEventListener('input', calculateEquipmentTotal);
    if (equipmentPurchaseCost) equipmentPurchaseCost.addEventListener('input', calculateEquipmentTotal);

    // Material Total Cost Calculation
    const materialQuantityInput = document.getElementById('material-quantity');
    const materialCostInput = document.getElementById('material-cost');
    const materialTotalCost = document.getElementById('material-total-cost');

    function calculateMaterialTotal() {
        const quantity = parseFloat(materialQuantityInput.value) || 0;
        const cost = parseFloat(materialCostInput.value) || 0;
        const total = quantity * cost;
        if (materialTotalCost) materialTotalCost.textContent = '₱' + formatPeso(total);
    }

    if (materialQuantityInput) materialQuantityInput.addEventListener('input', calculateMaterialTotal);
    if (materialCostInput) materialCostInput.addEventListener('input', calculateMaterialTotal);

    // Labor Total Cost Calculation
    const laborQuantityInput = document.getElementById('labor-quantity');
    const laborTotalCost = document.getElementById('labor-total-cost');

    function calculateLaborTotal() {
        const quantity = parseFloat(laborQuantityInput.value) || 0;
        const dailyRate = parseFloat(laborDailyRateInput.value) || 0;
        const total = quantity * dailyRate;
        if (laborTotalCost) laborTotalCost.textContent = '₱' + formatPeso(total);
    }

    if (laborQuantityInput) laborQuantityInput.addEventListener('input', calculateLaborTotal);
    if (laborDailyRateInput) laborDailyRateInput.addEventListener('input', calculateLaborTotal);
    if (laborHourlyRateInput) laborHourlyRateInput.addEventListener('input', calculateLaborTotal);

    // Calculate initial labor total on page load
    calculateLaborTotal();

    // Form Submission
    const saveDraftBtn = document.getElementById('save-draft');
    const submitProposalBtn = document.getElementById('submit-proposal');
    if (saveDraftBtn) saveDraftBtn.addEventListener('click', () => {
        if (validateForm()) submitProposal('DRAFT');
    });
    if (submitProposalBtn) submitProposalBtn.addEventListener('click', () => {
        if (validateForm()) submitProposal('PENDING');
    });

    function validateForm() {
        const project = document.getElementById('project') ? document.getElementById('project').value : '';
        const title = document.getElementById('proposalTitle') ? document.getElementById('proposalTitle').value.trim() : '';
        const targetPhaseSelect = document.getElementById('targetPhase');
        const selectedPhaseOption = targetPhaseSelect ? targetPhaseSelect.options[targetPhaseSelect.selectedIndex] : null;
        const targetPhaseId = selectedPhaseOption && selectedPhaseOption.dataset && selectedPhaseOption.dataset.id ? selectedPhaseOption.dataset.id : (selectedPhaseOption ? selectedPhaseOption.value : '');
        const startDate = document.getElementById('phaseStartDate') ? document.getElementById('phaseStartDate').value : '';
        const endDate = document.getElementById('phaseEndDate') ? document.getElementById('phaseEndDate').value : '';
        const scopeDescription = document.getElementById('scopeDescription') ? document.getElementById('scopeDescription').value.trim() : '';

        if (!project) { showToastAjax('Please select a project', 'warning'); return false; }
        if (!title) { showToastAjax('Please enter a proposal title', 'warning'); return false; }
        if (!targetPhaseId) { showToastAjax('Please select a target phase', 'warning'); return false; }
        if (!startDate) { showToastAjax('Please set phase start date', 'warning'); return false; }
        if (!endDate) { showToastAjax('Please set phase end date', 'warning'); return false; }
        if (new Date(endDate) <= new Date(startDate)) { showToastAjax('End date must be after start date', 'warning'); return false; }
        if (!scopeDescription) { showToastAjax('Please provide scope description', 'warning'); return false; }
        if (items.length === 0) { showToastAjax('Please add at least one item to the proposal', 'warning'); return false; }

        return true;
    }

    function submitProposal(status) {
        const projectSelect = document.getElementById('project');
        const selectedOption = projectSelect ? projectSelect.options[projectSelect.selectedIndex] : null;
        const projectId = selectedOption ? selectedOption.getAttribute('data-id') : '';
        const title = document.getElementById('proposalTitle') ? document.getElementById('proposalTitle').value.trim() : '';
        const startDate = document.getElementById('phaseStartDate') ? document.getElementById('phaseStartDate').value : '';
        const endDate = document.getElementById('phaseEndDate') ? document.getElementById('phaseEndDate').value : '';
        const scopeDescription = document.getElementById('scopeDescription') ? document.getElementById('scopeDescription').value.trim() : '';
        const totalAmount = items.reduce((sum, item) => sum + item.subtotal, 0);

        const targetPhaseSelect = document.getElementById('targetPhase');
        const selectedPhaseOption = targetPhaseSelect ? targetPhaseSelect.options[targetPhaseSelect.selectedIndex] : null;
        const targetPhaseId = selectedPhaseOption && selectedPhaseOption.dataset && selectedPhaseOption.dataset.id ? selectedPhaseOption.dataset.id : (selectedPhaseOption ? selectedPhaseOption.value : '');
        const targetPhaseName = selectedPhaseOption ? selectedPhaseOption.textContent.trim().replace(/\s+/g, ' ') : '';

        const data = {
            project_id: projectId,
            title: title,
            phase_id: targetPhaseId,
            target_phase: targetPhaseName,
            phase_start_date: startDate,
            phase_end_date: endDate,
            scope_description: scopeDescription,
            items: items,
            status: status,
            total_amount: totalAmount
        };

        if (saveDraftBtn) saveDraftBtn.disabled = true;
        if (submitProposalBtn) submitProposalBtn.disabled = true;

        console.log('Submitting data:', data);

        fetch(`${window.GATEWAY_URL}budget/proposals`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${window.AUTH_TOKEN}`
            },
            body: JSON.stringify(data)
        })
        .then(response => response.text())
        .then(text => {
            try {
                if (!text || text.trim() === "") {
                    throw new Error("Empty response from server");
                }
                const result = JSON.parse(text);
                if (result && result.success) {
                    showToastAjax(result.message + ' - Code: ' + result.code, 'success', true);
                    setTimeout(() => { window.location.href = '../proposals.php'; }, 300);
                } else {
                    const errorMsg = result ? result.message : "Unknown error (null response)";
                    showToastAjax('Error: ' + errorMsg, 'error');
                    if (saveDraftBtn) saveDraftBtn.disabled = false;
                    if (submitProposalBtn) submitProposalBtn.disabled = false;
                }
            } catch (e) {
                console.error('Response parsing error:', e);
                console.error('Raw response text:', text);
                showToastAjax('Server returned invalid response. Check console for details.', 'error');
                if (saveDraftBtn) saveDraftBtn.disabled = false;
                if (submitProposalBtn) submitProposalBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showToastAjax('Error submitting proposal: ' + error.message, 'error');
            if (saveDraftBtn) saveDraftBtn.disabled = false;
            if (submitProposalBtn) submitProposalBtn.disabled = false;
        });
    }

    // Make removeItem available globally
    window.removeItem = removeItem;

})();
