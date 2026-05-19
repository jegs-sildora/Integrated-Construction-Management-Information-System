// Dashboard JS moved from dashboard.php
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

  // Initialize Lucide icons
  if (typeof lucide !== 'undefined') lucide.createIcons();

  // ============================================
  // PHASE DETAIL MODAL FUNCTIONALITY
  // ============================================

  const phaseModal = document.getElementById('phase-detail-modal');
  const closePhaseModalBtn = document.getElementById('close-phase-modal-btn');
  const closePhaseModalBtn2 = document.getElementById('close-phase-modal-btn-2');

  // Phase data storage
  let currentPhaseData = null;

  // Phase colors mapping
  const phaseColors = {
    'Phase 1: Mobilization': { color: 'blue', bg: 'bg-blue-500', border: 'border-blue-500' },
    'Phase 2: Structural': { color: 'purple', bg: 'bg-purple-500', border: 'border-purple-500' },
    'Phase 3: MEPFS': { color: 'orange', bg: 'bg-orange-500', border: 'border-orange-500' },
    'Phase 4: Finishing': { color: 'green', bg: 'bg-green-500', border: 'border-green-500' }
  };

  // Open phase detail modal
  function openPhaseModal(phaseName) {
    if (typeof SELECTED_PROJECT_ID !== 'undefined' && SELECTED_PROJECT_ID > 0) {
      const phaseData = typeof PHASES_DATA !== 'undefined' ? PHASES_DATA : {};

      if (!phaseData[phaseName]) {
        showToastAjax('Phase data not found', 'error');
        return;
      }

      currentPhaseData = phaseData[phaseName];
      const colors = phaseColors[phaseName];

      // Update modal styling
      document.querySelectorAll('.phase-modal-border').forEach(el => {
        el.className = 'absolute top-0 left-0 right-0 h-1 phase-modal-border ' + (colors ? colors.bg : 'bg-gray-500');
      });
      document.querySelectorAll('.phase-border').forEach(el => {
        el.className = 'border-t-4 phase-border ' + (colors ? colors.border : 'border-gray-500');
      });
      document.querySelectorAll('.phase-total-bg').forEach(el => {
        el.className = 'border-t-2 border-dashed border-gray-300 phase-total-bg p-6 ' + (colors ? colors.bg : 'bg-gray-200');
      });

      // Update receipt content
      document.getElementById('receipt-phase-name').textContent = phaseName;
      document.getElementById('receipt-date-range').textContent = currentPhaseData.date_range;
      document.getElementById('receipt-project-name').textContent = typeof PROJECT_NAME !== 'undefined' ? PROJECT_NAME : '';

      // Status badge
      const statusBadge = document.getElementById('receipt-phase-status');
      const statusColors = {
        'Completed': 'bg-green-100 text-green-700 border-green-300',
        'Active': 'bg-blue-100 text-blue-700 border-blue-300',
        'Upcoming': 'bg-gray-100 text-gray-600 border-gray-300',
        'Over Budget': 'bg-red-100 text-red-700 border-red-300'
      };
      statusBadge.className = 'inline-block px-3 py-1 text-xs font-semibold rounded-full border ' + (statusColors[currentPhaseData.status] || 'bg-gray-100 text-gray-600 border-gray-300');
      statusBadge.textContent = currentPhaseData.status;

      // Financial summary - Display full amounts without M suffix
      document.getElementById('receipt-allocated').textContent = '₱' + currentPhaseData.allocated.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('receipt-spent').textContent = '₱' + currentPhaseData.spent.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('receipt-remaining').textContent = '₱' + currentPhaseData.remaining.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('receipt-utilization').textContent = currentPhaseData.utilization.toFixed(1) + '% utilized';
      document.getElementById('receipt-util-percent').textContent = currentPhaseData.utilization.toFixed(1) + '%';
      document.getElementById('receipt-grand-total').textContent = '₱' + currentPhaseData.spent.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

      // Update spent card color
      const spentCard = document.querySelector('.receipt-spent-card');
      if (spentCard) {
        if (currentPhaseData.utilization > 100) {
          spentCard.className = 'bg-red-50 border border-red-200 rounded-lg p-4 text-center receipt-spent-card';
          if (spentCard.querySelector('p:first-child')) spentCard.querySelector('p:first-child').className = 'text-xs text-red-600 font-medium mb-1';
          if (spentCard.querySelector('p:nth-child(2)')) spentCard.querySelector('p:nth-child(2)').className = 'text-2xl font-black text-red-700';
        } else {
          spentCard.className = 'bg-blue-50 border border-blue-200 rounded-lg p-4 text-center receipt-spent-card';
          if (spentCard.querySelector('p:first-child')) spentCard.querySelector('p:first-child').className = 'text-xs text-blue-600 font-medium mb-1';
          if (spentCard.querySelector('p:nth-child(2)')) spentCard.querySelector('p:nth-child(2)').className = 'text-2xl font-black text-blue-700';
        }
      }

      // Progress bar
      const progressBar = document.getElementById('receipt-progress-bar');
      if (progressBar) {
        let progressColor = 'bg-green-500';
        if (currentPhaseData.utilization > 100) {
          progressColor = 'bg-red-500';
        } else if (currentPhaseData.utilization >= 90) {
          progressColor = 'bg-orange-500';
        } else if (currentPhaseData.utilization >= 70) {
          progressColor = 'bg-yellow-500';
        }
        progressBar.className = 'h-4 rounded-full transition-all duration-300 ' + progressColor;
        progressBar.style.width = Math.min(currentPhaseData.utilization, 100) + '%';
      }

      // Fetch budget proposals and line items
      fetchPhaseBudgetProposals(phaseName);
      fetchPhaseLineItems(phaseName);

      // Show modal
      phaseModal.classList.remove('hidden');
      document.body.classList.add('modal-open');
      if (typeof lucide !== 'undefined') lucide.createIcons();
    } else {
      showToastAjax('Please select a project first', 'error');
    }
  }

  async function fetchPhaseBudgetProposals(phaseName) {
    const proposalsContainer = document.getElementById('receipt-budget-proposals');
    if (!proposalsContainer) return;
    proposalsContainer.innerHTML = '<div class="text-center py-4"><i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto text-gray-400"></i></div>';
    if (typeof lucide !== 'undefined') lucide.createIcons();

    try {
      const response = await fetch(`api/get_phase_budget_proposals.php?project_id=${SELECTED_PROJECT_ID}&phase=${encodeURIComponent(phaseName)}`);
      const result = await response.json();

      if (result.success && result.proposals.length > 0) {
        const proposals = result.proposals;
        let html = '';

        proposals.forEach(proposal => {
          const statusColors = {
            'APPROVED': 'bg-green-100 text-green-700 border-green-300'
          };
          const badgeClass = statusColors[proposal.status] || 'bg-gray-100 text-gray-700 border-gray-300';
          const formattedDate = new Date(proposal.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

          html += `
              <div class="border border-gray-200 rounded-lg p-4 hover:border-${phaseColors[phaseName].color}-400 hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between mb-3">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                      <span class="${badgeClass} px-2 py-1 rounded border text-xs font-semibold">${proposal.status}</span>
                      <span class="text-xs text-gray-500">${formattedDate}</span>
                    </div>
                    <div class="flex items-center gap-2 mb-1">
                      <span class="text-xs font-mono text-gray-600 bg-gray-100 px-2 py-1 rounded">${proposal.code}</span>
                      <h4 class="text-sm font-bold text-gray-900">${proposal.title}</h4>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">${proposal.description}</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                      <span><i data-lucide="calendar" class="w-3 h-3 inline"></i> ${proposal.phase_start_date} to ${proposal.phase_end_date}</span>
                      <span><i data-lucide="layers" class="w-3 h-3 inline"></i> ${proposal.line_item_count} line items</span>
                      <span><i data-lucide="user" class="w-3 h-3 inline"></i> ${proposal.user_name}</span>
                    </div>
                  </div>
                  <div class="text-right ml-4">
                    <p class="text-lg font-bold text-gray-900">₱${parseFloat(proposal.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    <span class="text-xs text-gray-500">Total Budget</span>
                  </div>
                </div>
              </div>
            `;
        });

        proposalsContainer.innerHTML = html;
      } else {
        proposalsContainer.innerHTML = '<div class="text-center py-8 text-gray-500"><p>No approved budget proposals for this phase yet.</p></div>';
      }

      if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (error) {
      console.error('Error fetching phase budget proposals:', error);
      proposalsContainer.innerHTML = '<div class="text-center py-8 text-red-500"><p>Failed to load budget proposals. Please try again.</p></div>';
    }
  }

  async function fetchPhaseLineItems(phaseName) {
    const lineItemsContainer = document.getElementById('receipt-line-items');
    if (!lineItemsContainer) return;
    lineItemsContainer.innerHTML = '<div class="text-center py-4"><i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto text-gray-400"></i></div>';
    if (typeof lucide !== 'undefined') lucide.createIcons();

    try {
      const response = await fetch(`api/get_phase_expenses.php?project_id=${SELECTED_PROJECT_ID}&phase=${encodeURIComponent(phaseName)}`);
      const result = await response.json();

      if (result.success && result.expenses.length > 0) {
        const expenses = result.expenses;
        let html = '';

        expenses.forEach(expense => {
          const categoryColors = {
            'MATERIALS': 'bg-purple-100 text-purple-700 border-purple-300',
            'LABOR': 'bg-amber-100 text-amber-700 border-amber-300',
            'EQUIPMENT': 'bg-green-100 text-green-700 border-green-300'
          };
          const badgeClass = categoryColors[expense.category] || 'bg-gray-100 text-gray-700 border-gray-300';

          html += `
              <div class="border border-gray-200 rounded-lg p-4 hover:border-${phaseColors[phaseName].color}-400 hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between mb-2">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="${badgeClass} px-2 py-1 rounded border text-xs font-semibold">${expense.category}</span>
                      <span class="text-xs text-gray-500">${new Date(expense.expense_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                    </div>
                    <p class="text-sm font-medium text-gray-900">${expense.description}</p>
                    <p class="text-xs text-gray-600 mt-1">Supplier: ${expense.supplier_name || 'N/A'}</p>
                  </div>
                  <div class="text-right ml-4">
                    <p class="text-lg font-bold text-gray-900">₱${parseFloat(expense.amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    <span class="text-xs ${expense.status === 'APPROVED' ? 'text-green-600' : 'text-amber-600'}">${expense.status}</span>
                  </div>
                </div>
              </div>
            `;
        });

        lineItemsContainer.innerHTML = html;
      } else {
        lineItemsContainer.innerHTML = '<div class="text-center py-8 text-gray-500"><p>No expenses recorded for this phase yet.</p></div>';
      }

      if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (error) {
      console.error('Error fetching phase expenses:', error);
      lineItemsContainer.innerHTML = '<div class="text-center py-8 text-red-500"><p>Failed to load expenses. Please try again.</p></div>';
    }
  }

  function closePhaseModal() {
    if (phaseModal) phaseModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    currentPhaseData = null;
  }

  // Event listeners
  if (closePhaseModalBtn) closePhaseModalBtn.addEventListener('click', closePhaseModal);
  if (closePhaseModalBtn2) closePhaseModalBtn2.addEventListener('click', closePhaseModal);
  if (phaseModal) phaseModal.addEventListener('click', (e) => {
    if (e.target === phaseModal) {
      closePhaseModal();
    }
  });

  // Phase card click handlers
  document.querySelectorAll('.phase-card').forEach(card => {
    card.addEventListener('click', function() {
      const phaseName = this.dataset.phase;
      openPhaseModal(phaseName);
    });
  });

  // Print function
  function printPhaseDetails() {
    window.print();
  }

  // Download PDF function
  function downloadPhasePDF() {
    if (!currentPhaseData) return;
    const pdfUrl = `download_phase_pdf.php?project_id=${SELECTED_PROJECT_ID}&phase=${encodeURIComponent(currentPhaseData.phase)}`;
    window.open(pdfUrl, '_blank');
  }

  // Expose functions that are used by inline attributes
  window.printPhaseDetails = printPhaseDetails;
  window.downloadPhasePDF = downloadPhasePDF;

  // Add print styles
  const printStyles = document.createElement('style');
  printStyles.textContent = `
    @media print {
      body * { visibility: hidden; }
      #receipt-content, #receipt-content * { visibility: visible; }
      #receipt-content { position: absolute; left: 0; top: 0; width: 100%; }
      .no-print { display: none !important; }
    }
  `;
  document.head.appendChild(printStyles);

  // Prevent background scroll when modal is open
  const style = document.createElement('style');
  style.textContent = `
    body.modal-open {
      overflow: hidden;
    }
  `;
  document.head.appendChild(style);
})();
    
