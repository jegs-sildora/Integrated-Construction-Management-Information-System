<div id="proposalModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden animate-zoom-in">
        
        <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-6 py-5 relative">
            <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-[#e9922c] via-orange-300 to-[#e9922c]"></div>
            
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shadow-lg p-1.5">
                        <img src="<?php echo BASE_URL; ?>assets/images/nobg_logo.png" alt="ICMIS Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Budget Proposal Details</h2>
                        <p class="text-gray-300 text-sm">Complete breakdown and summary</p>
                    </div>
                </div>
                <button onclick="closeProposalModal()" class="text-white hover:bg-white/10 rounded-lg p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="overflow-y-auto max-h-[calc(90vh-80px)] p-6 space-y-6">
            
            <div class="text-center pb-6 border-b-2 border-dashed border-gray-200">
                <div class="flex justify-center mb-3">
                    <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-lg border border-gray-100 p-2">
                        <img src="<?php echo BASE_URL; ?>assets/images/nobg_logo.png" alt="ICMIS Logo" class="w-full h-full object-contain">
                    </div>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-1">ICMIS</h1>
                <p class="text-sm text-gray-500 mb-3">Integrated Construction Management Information System</p>
                <div class="inline-block px-4 py-2 bg-gradient-to-r from-orange-100 to-amber-100 border-2 border-orange-300 rounded-full">
                    <span class="text-sm font-semibold text-orange-700">BUDGET PROPOSAL</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white border-2 border-orange-200 rounded-xl p-4 hover:shadow-lg transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Proposal Code</p>
                            <p id="modal-code" class="text-lg font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border-2 border-blue-200 rounded-xl p-4 hover:shadow-lg transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Status</p>
                            <div id="modal-status" class="mt-1">-</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white border-2 border-purple-200 rounded-xl p-4 hover:shadow-lg transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Date Created</p>
                            <p id="modal-date" class="text-lg font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border-2 border-green-200 rounded-xl p-4 hover:shadow-lg transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center text-white shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Created By</p>
                            <p id="modal-user" class="text-lg font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-300 rounded-xl p-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-blue-700 uppercase font-semibold mb-1">Project</p>
                        <p id="modal-project" class="text-xl font-bold text-blue-900">-</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-50 to-orange-100 border-2 border-orange-300 rounded-xl p-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#e9922c] to-[#d17f1f] rounded-full flex items-center justify-center text-white shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-orange-700 uppercase font-semibold mb-1">Proposal Title</p>
                        <p id="modal-title" class="text-xl font-bold text-orange-900">-</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 border-2 border-purple-300 rounded-xl p-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-purple-700 uppercase font-semibold mb-1">Target Phase / Milestone</p>
                        <p id="modal-phase" class="text-xl font-bold text-purple-900">-</p>
                    </div>
                </div>
            </div>

            <div class="border-t-4 border-dashed border-gray-300 pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 uppercase">Line Items Breakdown</h3>
                    <span id="modal-item-count" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-semibold">0 items</span>
                </div>
                
                <div id="modal-items-container" class="space-y-3">
                    </div>
            </div>

            <div class="border-t-4 border-dashed border-gray-300 pt-6 text-center">
                <div class="bg-gradient-to-r from-[#e9922c] to-[#d17f1f] rounded-2xl shadow-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-orange-100 uppercase text-sm font-semibold">Grand Total</p>
                                <p id="modal-total" class="ml-4 text-4xl font-bold text-white">₱0.00</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 pt-4">
                <a id="downloadPdfBtn" href="#" target="_blank" class="flex items-center justify-center gap-2 px-4 py-3 bg-white border-2 border-blue-500 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V3h12v6M6 13h12v8H6v-8z" />
                    </svg>
                    Print PDF
                </a>
            </div>

            <div class="text-center pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-500">This is an official budget proposal document from ICMIS</p>
                <p class="text-xs text-gray-400 mt-1">Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
            </div>
        </div>

        <div class="h-0.5 bg-gradient-to-r from-[#e9922c] via-orange-300 to-[#e9922c]"></div>
    </div>
</div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes zoomIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }
    .animate-zoom-in {
        animation: zoomIn 0.3s ease-out;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .animate-pulse-slow {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>

<script>
    let currentProposalId = null;

    function openProposalModal(proposalId) {
        currentProposalId = proposalId;
        
        // Fetch proposal details via AJAX
        fetch(`/modules/budget/budget_proposal/get_proposal_details.php?id=${proposalId}`)
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        populateModalData(data.proposal, data.items);
                        document.getElementById('proposalModal').classList.remove('hidden');
                        document.body.style.overflow = 'hidden'; // Prevent background scrolling
                    } else {
                        showToast('Failed to load proposal details: ' + data.message, 'error');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', text);
                    showToast('Invalid response from server', 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showToast('Error loading proposal details: ' + error.message, 'error');
            });
    }

    function populateModalData(proposal, items) {
        // Populate basic info
        document.getElementById('modal-code').textContent = proposal.code;
        document.getElementById('modal-date').textContent = proposal.created_at;
        document.getElementById('modal-user').textContent = proposal.user_name || 'System';
        document.getElementById('modal-project').textContent = proposal.project_name;
        document.getElementById('modal-title').textContent = proposal.title;
        document.getElementById('modal-phase').textContent = proposal.phase_name || '-';
        document.getElementById('modal-total').textContent = '₱' + formatPeso(parseFloat(proposal.total_amount));

        // Populate status badge
        const statusBadges = {
            'APPROVED': '<span class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-full text-sm font-semibold">Approved</span>',
            'PENDING': '<span class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-full text-sm font-semibold">Pending</span>',
            'REJECTED': '<span class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-full text-sm font-semibold">Rejected</span>',
            'DRAFT': '<span class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-gray-400 to-gray-500 text-white rounded-full text-sm font-semibold">Draft</span>'
        };
        document.getElementById('modal-status').innerHTML = statusBadges[proposal.status] || statusBadges['DRAFT'];

        // Populate line items
        const container = document.getElementById('modal-items-container');
        container.innerHTML = '';
        
        document.getElementById('modal-item-count').textContent = `${items.length} item${items.length !== 1 ? 's' : ''}`;

        items.forEach((item, index) => {
            const categoryConfig = {
                'MATERIAL': { color: 'purple', bgColor: 'bg-purple-500', dotColor: 'bg-purple-500' },
                'LABOR': { color: 'amber', bgColor: 'bg-amber-500', dotColor: 'bg-amber-500' },
                'EQUIPMENT': { color: 'green', bgColor: 'bg-green-500', dotColor: 'bg-green-500' }
            };
            
            const config = categoryConfig[item.category] || categoryConfig['MATERIAL'];
            
            const itemHTML = `
                <div class="bg-white border-2 border-gray-200 rounded-xl p-4 hover:border-[#e9922c] hover:shadow-lg transition-all group">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3 flex-1">
                            <div class="relative">
                                <span class="absolute -left-1 top-1 w-2 h-2 ${config.dotColor} rounded-full animate-pulse-slow"></span>
                                <span class="${config.bgColor} text-white text-xs px-2 py-1 rounded-full font-semibold uppercase">${item.category}</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded font-medium">#${index + 1}</span>
                                    <h4 class="text-base font-bold text-gray-900">${item.item_name}</h4>
                                </div>
                                <p class="text-sm text-gray-600">Qty: <span class="font-semibold">${item.quantity}</span> × Unit: <span class="font-semibold">₱${formatPeso(parseFloat(item.unit_cost))}</span></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-[#e9922c]">₱${formatPeso(parseFloat(item.subtotal))}</p>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', itemHTML);
        });

        // Set download link for PDF (open in new tab)
        const downloadBtn = document.getElementById('downloadPdfBtn');
        if (downloadBtn && currentProposalId) {
            downloadBtn.href = `./budget_proposal/download_proposal_pdf.php?id=${currentProposalId}`;
        }
    }

    function closeProposalModal() {
        document.getElementById('proposalModal').classList.add('hidden');
        document.body.style.overflow = ''; // Restore scrolling
        currentProposalId = null;
    }

    function printProposal() {
        window.print();
        showToast('Print dialog opened', 'success');
    }

    function downloadPDF() {
        if (currentProposalId) {
            window.open(`./budget_proposal/download_proposal_pdf.php?id=${currentProposalId}`, '_blank');
            showToast('Downloading PDF...', 'success');
        }
    }

    function formatPeso(amount) {
        return amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // Close modal when clicking outside
    document.getElementById('proposalModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeProposalModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('proposalModal').classList.contains('hidden')) {
            closeProposalModal();
        }
    });
</script>