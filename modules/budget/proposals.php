<?php
  // 1. Connection & Context - using centralized config
  include __DIR__ . '/project_context.php';
  $conn = getBudgetConnection();

  // Get selected project ID from global context
  $selected_project_id = getProjectContext($conn);

  // Fetch all projects for dropdown
  $sql_projects = "SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC";
  $result_projects = $conn->query($sql_projects);
  $projects = [];
  
  if ($result_projects && $result_projects->num_rows > 0) {
    while ($row = $result_projects->fetch_assoc()) {
      $projects[] = $row;
    }
  }

  // Build breadcrumb navigation with dropdown
  $current_page = basename($_SERVER['PHP_SELF']);
  $breadcrumbHTML = '<div class="flex items-center gap-2 text-sm">';
  
  // Project Dropdown
  $breadcrumbHTML .= '<div class="relative inline-block">';
  $breadcrumbHTML .= '<select id="projectSelector" onchange="window.location.href=\'' . $current_page . '?project_id=\' + this.value" class="appearance-none bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] pl-3 pr-8 py-1.5 hover:bg-gray-50 transition-colors cursor-pointer font-medium">';
  
  foreach ($projects as $proj) {
    $selected = ($proj['project_id'] == $selected_project_id) ? 'selected' : '';
    $breadcrumbHTML .= '<option value="' . $proj['project_id'] . '" ' . $selected . '>' . htmlspecialchars($proj['project_name']) . '</option>';
  }
  
  $breadcrumbHTML .= '</select>';
  $breadcrumbHTML .= '<svg class="w-3 h-3 text-gray-500 absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
  $breadcrumbHTML .= '</div>';
  $breadcrumbHTML .= '</div>';

  // Set Header Variables
  $pageSection = "Budget & Cost Control";
  $pageTitle = "Budget Proposals";
  $pageSubTitle = $breadcrumbHTML;

  // Fetch budget proposals for selected project
  if ($selected_project_id > 0) {
    $sql = "SELECT bp.*, p.project_name, p.project_code, 
            COALESCE(u.full_name, 'System Admin') as user_name 
            FROM budget_proposals bp 
            LEFT JOIN icmis_projects p ON bp.project_id = p.project_id 
            LEFT JOIN icmis_users u ON bp.created_by = u.user_id
            WHERE bp.project_id = ?
            ORDER BY bp.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $proposals = [];
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $proposals[] = $row;
      }
    }
    $stmt->close();
  } else {
    $proposals = [];
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Budget Proposals | ICMIS</title>
  
  <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
  
  <style>
    * { font-family: 'Inter', sans-serif; }
    @keyframes modal-slide-in {
      from { opacity: 0; transform: translateY(-20px) scale(0.95); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .animate-modal-slide-in { animation: modal-slide-in 0.3s ease-out forwards; }
  </style>
</head>
<body class="bg-gray-50">
  <?php 
    include __DIR__ . '/../../includes/sidebar.php';
    include __DIR__ . '/../../includes/toast.php';
    include __DIR__ . '/../../includes/header.php'; 
  ?>

  <main class="ml-56 mt-16 p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl text-gray-900 font-bold">Budget Proposals</h1>
        <p class="text-sm text-gray-500 mt-1">Create and manage project budget proposals</p>
      </div>
      <a href="budget_proposal/create_proposal.php" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span class="font-medium">New Proposal</span>
      </a>
    </div>

    <?php if (empty($proposals)): ?>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12">
      <div class="max-w-md mx-auto text-center">
        <div class="flex justify-center mb-6">
          <div class="bg-orange-50 rounded-full p-6">
            <svg class="w-16 h-16 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>

        <h2 class="text-xl text-gray-900 font-bold mb-3">No Budget Proposals Yet</h2>
        <p class="text-gray-500 mb-8 leading-relaxed">
          Get started by creating your first budget proposal. You can submit proposals for new projects, 
          request budget adjustments, or plan upcoming expenditures.
        </p>

        <a href="budget_proposal/create_proposal.php" class="inline-flex items-center gap-2 bg-[#e9922c] text-white px-6 py-3 rounded-lg hover:bg-[#d17f1f] transition-all duration-200 shadow-md hover:shadow-lg font-medium">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Create Your First Proposal
        </a>

        <div class="mt-8 pt-8 border-t border-gray-200">
          <p class="text-sm text-gray-600 mb-3 text-left">What you can do with budget proposals:</p>
          
          <div class="space-y-3">
            <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
              <div class="flex-shrink-0 mt-1.5">
                <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
              </div>
              <div>
                <div class="text-sm text-gray-900 font-medium">Submit new project budgets</div>
                <div class="text-xs text-gray-500 mt-0.5">Request funding for upcoming construction projects</div>
              </div>
            </div>

            <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
              <div class="flex-shrink-0 mt-1.5">
                <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
              </div>
              <div>
                <div class="text-sm text-gray-900 font-medium">Track approval workflows</div>
                <div class="text-xs text-gray-500 mt-0.5">Monitor proposal status and get stakeholder approvals</div>
              </div>
            </div>

            <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
              <div class="flex-shrink-0 mt-1.5">
                <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
              </div>
              <div>
                <div class="text-sm text-gray-900 font-medium">Manage budget revisions</div>
                <div class="text-xs text-gray-500 mt-0.5">Request adjustments based on project changes</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-linear-to-r from-slate-800 to-slate-700 px-6 py-5 relative">
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Proposal Code</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Title</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Project</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Total Amount</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Status</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Created By</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Date Created</th>
              <th class="px-6 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php foreach ($proposals as $proposal): ?>
            <tr class="hover:bg-gray-50 transition-colors duration-150">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                  <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($proposal['code']); ?></span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($proposal['title']); ?></div>
                <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($proposal['description']); ?></div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span class="text-sm text-gray-900"><?php echo htmlspecialchars($proposal['project_name'] ?? 'N/A'); ?></span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-semibold text-gray-900">₱<?php echo number_format($proposal['total_amount'], 2); ?></span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-3 py-1 text-xs font-bold rounded-full <?php 
                  echo $proposal['status'] === 'APPROVED' ? 'bg-green-100 text-green-700' : 
                      ($proposal['status'] === 'REJECTED' ? 'bg-red-100 text-red-700' : 
                      ($proposal['status'] === 'PENDING' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'));
                ?>">
                  <?php echo htmlspecialchars($proposal['status']); ?>
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($proposal['user_name'] ?? 'Unknown User'); ?></span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <span class="text-sm text-gray-600"><?php echo date('M d, Y', strtotime($proposal['created_at'])); ?></span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <div class="flex items-center justify-center">
                  <button onclick="openProposalModal(<?php echo $proposal['proposal_id']; ?>)" class="text-gray-500 p-2 rounded-lg hover:text-blue-600 transition-colors duration-200" title="View Details">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                  
                  <a href="budget_proposal/edit_proposal.php?id=<?php echo $proposal['proposal_id']; ?>" class="text-gray-500 p-2 rounded-lg hover:text-green-600 transition-colors duration-200" title="Edit">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </a>
                  
                  <button onclick="openDeleteModal(<?php echo $proposal['proposal_id']; ?>, '<?php echo htmlspecialchars($proposal['code'], ENT_QUOTES); ?>')" class="text-gray-500 p-2 rounded-lg hover:text-red-600 transition-colors duration-200" title="Delete">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/components/proposal_modal.php'; ?>

  <div id="deleteModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all animate-modal-slide-in">
      <div class="bg-linear-to-r from-red-600 to-red-700 p-6 rounded-t-2xl">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="bg-white rounded-full p-3 shadow-lg">
              <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div>
              <h3 class="text-2xl font-bold text-white">Delete Proposal</h3>
              <p class="text-red-100 text-sm mt-1">Permanent action</p>
            </div>
          </div>
          <button onclick="closeDeleteModal()" class="text-white hover:bg-white/10 hover:bg-opacity-60 p-2 rounded-lg transition-all">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <div class="p-8">
        <div class="mb-6">
          <p class="text-gray-700 text-lg font-medium mb-2">
            Are you sure you want to delete this proposal?
          </p>
          <p class="text-gray-500 text-sm">
            This action is permanent and cannot be undone. All associated data will be removed.
          </p>
        </div>
        
        <div class="bg-red-50 border-2 border-red-200 rounded-xl p-5 shadow-sm">
          <div class="flex items-start gap-4">
            <div class="bg-red-100 rounded-full p-2 shrink-0">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-sm font-bold text-red-900 mb-1.5 uppercase tracking-wide">Proposal Code</p>
              <p id="deleteProposalCode" class="text-lg font-bold text-red-700 font-mono bg-white px-3 py-2 rounded-lg border border-red-200"></p>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
        <button onclick="closeDeleteModal()" class="px-6 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 hover:border-gray-400 transition-all font-semibold shadow-sm">
          Cancel
        </button>
        <button id="confirmDeleteBtn" onclick="confirmDelete()" class="px-6 py-3 bg-linear-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl transition-all font-semibold flex items-center gap-2 shadow-lg hover:shadow-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Delete Proposal
        </button>
      </div>
    </div>
  </div>

  <style>
    @keyframes modal-slide-in {
      from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .animate-modal-slide-in {
      animation: modal-slide-in 0.3s ease-out forwards;
    }

    #deleteModal {
      transition: opacity 0.2s ease-out;
    }
  </style>

  <script>
    let proposalToDelete = null;

    function openDeleteModal(proposalId, proposalCode) {
      proposalToDelete = proposalId;
      document.getElementById('deleteProposalCode').textContent = proposalCode;
      document.getElementById('deleteModal').classList.remove('hidden');
      // Prevent body scroll when modal is open
      document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
      proposalToDelete = null;
      document.getElementById('deleteModal').classList.add('hidden');
      // Restore body scroll
      document.body.style.overflow = '';
    }

    function confirmDelete() {
      if (!proposalToDelete) return;

      const confirmBtn = document.getElementById('confirmDeleteBtn');
      const originalBtnContent = confirmBtn.innerHTML;
      
      // Disable button and show loading state
      confirmBtn.disabled = true;
      confirmBtn.innerHTML = `
        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Deleting...
      `;

      fetch('budget_proposal/delete_proposal.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          proposal_id: proposalToDelete
        })
      })
      .then(response => response.json())
      .then(result => {
        if (result.success) {
          showToast(result.message, 'success', true);
          closeDeleteModal();
          // Reload page after short delay
          setTimeout(() => {
            window.location.reload();
          }, 300);
        } else {
          showToast('Error: ' + result.message, 'error');
          // Re-enable button
          confirmBtn.disabled = false;
          confirmBtn.innerHTML = originalBtnContent;
        }
      })
      .catch(error => {
        console.error('Delete error:', error);
        showToast('Error deleting proposal: ' + error.message, 'error');
        // Re-enable button
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalBtnContent;
      });
    }

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeDeleteModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const deleteModal = document.getElementById('deleteModal');
        if (!deleteModal.classList.contains('hidden')) {
          closeDeleteModal();
        }
      }
    });
  </script>
</body>
</html>