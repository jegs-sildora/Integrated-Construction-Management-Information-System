<?php
/**
 * Payroll Management - Workforce Module
 * Location: /workforce/payroll.php
 */
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../../config/database.php';
include __DIR__ . '/project_context.php';

$selected_project_id = getProjectContext($conn);
$selected_project_name = 'All Projects';

if ($selected_project_id && $selected_project_id > 0) {
    $sq = $conn->prepare("SELECT project_name FROM icmis_projects WHERE project_id = ? LIMIT 1");
    $sq->bind_param('i', $selected_project_id);
    $sq->execute();
    if ($row = $sq->get_result()->fetch_assoc()) $selected_project_name = $row['project_name'];
    $sq->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll | ICMIS</title>
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <!-- Removed jsPDF includes: using window print with HTML instead -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .modal-overlay { background-color: rgba(31,41,55,0.45); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); }
        .modal-content { transform: scale(0.95); opacity: 0; transition: all 0.2s ease-out; }
        .modal-content.modal-open { transform: scale(1); opacity: 1; }
        .tab-btn.active { border-color: #e9922c; color: #e9922c; background-color: #fff7ed; }
        .tab-btn.inactive { border-color: transparent; color: #6b7280; }
        .loader-line { height: 3px; width: 100%; position: relative; overflow: hidden; background-color: #f3f4f6; }
        .loader-line:before { display: block; position: absolute; content: ""; left: -200px; width: 200px; height: 3px; background-color: #e9922c; animation: loading 2s linear infinite; }
        @keyframes loading { from {left: -200px; width: 30%;} 50% {width: 30%;} 70% {width: 70%;} 80% { left: 50%;} 95% {left: 120%;} to {left: 100%;} }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../../includes/toast.php'; ?>
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <main class="ml-56 mt-20 p-6 transition-all duration-300 animate-fade-in">
        <div class="max-w-[90rem] mx-auto">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 no-print border-b border-gray-200 pb-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Payroll Management</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Manage <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($selected_project_name); ?></span> financials
                    </p>
                </div>
                <div class="flex bg-gray-100 p-1 rounded-lg">
                    <button onclick="switchPayrollTab('worksheet')" id="tab-btn-worksheet" class="tab-btn active px-4 py-2 text-sm font-bold rounded-md transition-all flex items-center gap-2">
                        <i data-lucide="calculator" class="w-4 h-4"></i> Worksheet
                    </button>
                    <button onclick="switchPayrollTab('periods')" id="tab-btn-periods" class="tab-btn inactive px-4 py-2 text-sm font-medium rounded-md transition-all flex items-center gap-2">
                        <i data-lucide="history" class="w-4 h-4"></i> History
                    </button>
                </div>
            </div>

            <div id="view-worksheet" class="tab-view">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 no-print bg-white p-3 rounded-xl shadow-sm border border-gray-200 sticky top-20 z-20">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center bg-gray-50 rounded-lg border border-gray-200 p-1">
                            <input type="month" id="payrollMonth" class="pl-3 pr-2 py-1.5 border-none bg-transparent focus:ring-0 text-sm font-bold text-gray-800 cursor-pointer outline-none" value="<?= date('Y-m') ?>">
                            <div class="h-5 w-px bg-gray-300 mx-1"></div>
                            <select id="payrollPeriod" class="pl-2 pr-8 py-1.5 border-none bg-transparent focus:ring-0 text-sm font-bold text-[#e9922c] cursor-pointer outline-none">
                                <option value="1">1st Period (1st-15th)</option>
                                <option value="2">2nd Period (16th-End)</option>
                            </select>
                        </div>
                        <span id="periodStatusBadge" class="hidden px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide border"></span>
                    </div>

                    <div class="flex items-center gap-3">
                        <button onclick="Payroll.loadData()" class="p-2 text-gray-500 hover:text-[#e9922c] hover:bg-orange-50 rounded-lg transition-colors"><i data-lucide="refresh-cw" class="w-5 h-5"></i></button>
                        <div class="h-6 w-px bg-gray-200"></div>
                        <button onclick="Payroll.exportPDF()" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
                        <button onclick="Payroll.openLockModal()" id="btnLockPayroll" disabled class="flex items-center gap-2 bg-[#e9922c] text-white px-5 py-2 rounded-lg hover:bg-[#d17f1f] disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm font-bold text-sm">
                            <i data-lucide="lock" class="w-4 h-4"></i> <span id="btnLockText">Finalize Payroll</span>
                        </button>
                    </div>
                </div>

                <div id="healthCheckAlert" class="hidden mb-6 p-4 rounded-xl border-l-4 border-yellow-400 bg-yellow-50 flex items-start gap-3 no-print">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-600 mt-0.5"></i>
                    <div>
                        <h4 class="text-sm font-bold text-yellow-800">Data Warning</h4>
                        <p class="text-sm text-yellow-700 mt-1" id="healthCheckMsg"></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 no-print">
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm transition-shadow hover:shadow-md">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Workforce</p>
                        <div class="flex justify-between items-end mt-2">
                            <p class="text-2xl font-black text-gray-900" id="statEmployees">0</p>
                            <i data-lucide="users" class="w-6 h-6 text-gray-300 mb-1"></i>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm transition-shadow hover:shadow-md">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Gross Pay</p>
                        <div class="flex justify-between items-end mt-2">
                            <p class="text-2xl font-black text-gray-900" id="statGross">₱0.00</p>
                            <i data-lucide="bar-chart-3" class="w-6 h-6 text-blue-200 mb-1"></i>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm transition-shadow hover:shadow-md">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Deductions</p>
                        <div class="flex justify-between items-end mt-2">
                            <p class="text-2xl font-black text-red-600" id="statDeductions">₱0.00</p>
                            <i data-lucide="scissors" class="w-6 h-6 text-red-200 mb-1"></i>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-[#e9922c] to-[#d17f1f] rounded-xl border border-[#e9922c] p-5 shadow-md text-white transition-transform hover:-translate-y-1">
                        <p class="text-xs font-bold text-white/80 uppercase tracking-wider">Net Pay</p>
                        <div class="flex justify-between items-end mt-2">
                            <p class="text-2xl font-black" id="statNet">₱0.00</p>
                            <i data-lucide="wallet" class="w-6 h-6 text-white/50 mb-1"></i>
                        </div>
                    </div>
                </div>

                <div id="loadingBar" class="hidden w-full mb-4 no-print"><div class="loader-line rounded-full"></div></div>
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center no-print">
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-gray-800">Payroll Worksheet</h3>
                            <span class="text-xs font-medium text-gray-500 bg-white border border-gray-200 px-2 py-0.5 rounded-md shadow-sm" id="tablePeriodLabel">---</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto min-h-[300px]">
                        <table class="w-full text-left border-collapse" id="payrollTable">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-bold sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-4 border-b text-left">Employee</th>
                                    <th class="px-4 py-4 border-b text-center">Days</th>
                                    <th class="px-4 py-4 border-b text-center">OT (Hrs)</th>
                                    <th class="px-4 py-4 border-b text-right">Rate</th>
                                    <th class="px-6 py-4 border-b text-right text-blue-800">Gross</th>
                                    <th class="px-6 py-4 border-b text-right text-red-800">(-) Ded</th>
                                    <th class="px-6 py-4 border-b text-right text-[#e9922c]">Net Pay</th>
                                    <th class="px-4 py-4 border-b text-center w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="payrollTableBody" class="divide-y divide-gray-100 text-sm bg-white"></tbody>
                            <tfoot id="payrollTableFoot" class="bg-gray-100 font-bold text-gray-700 border-t-2 border-gray-200 hidden">
                                <tr>
                                    <td class="px-6 py-4 uppercase text-xs tracking-wider">Grand Total</td>
                                    <td class="px-4 py-4 text-center">-</td>
                                    <td class="px-4 py-4 text-center" id="footOtHrs">0.0</td>
                                    <td class="px-4 py-4 text-right">-</td>
                                    <td class="px-6 py-4 text-right text-blue-800" id="footGross">₱0.00</td>
                                    <td class="px-6 py-4 text-right text-red-600" id="footDeductions">-₱0.00</td>
                                    <td class="px-6 py-4 text-right text-[#e9922c] text-lg" id="footNet">₱0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div id="view-periods" class="tab-view hidden">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-gray-800">Historical Records</h3>
                            <span class="text-xs text-gray-500 font-medium">Finalized & Posted Payrolls</span>
                        </div>
                        <button onclick="Payroll.loadHistory()" class="text-gray-500 hover:text-[#e9922c]"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
                    </div>
                    <table class="w-full text-left text-sm" id="historyTable">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-bold border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left">Period Covered</th>
                                <th class="px-6 py-4 text-center">Pay Date</th>
                                <th class="px-6 py-4 text-center">Employees</th>
                                <th class="px-6 py-4 text-right">Total Net Pay</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center w-20">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody" class="divide-y divide-gray-100 bg-white">
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Loading history...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <div id="historyDetailsModal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 modal-overlay transition-opacity" onclick="Payroll.closeHistoryModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="modal-content inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl w-full">
                <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900" id="histModalTitle">Payroll Details</h3>
                        <p class="text-sm text-gray-500" id="histModalSubtitle">View historical record</p>
                    </div>
                    <button onclick="Payroll.closeHistoryModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-500 rounded-lg p-2 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-bold sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-3 border-b text-left">Employee</th>
                                <th class="px-4 py-3 border-b text-center">Days</th>
                                <th class="px-4 py-3 border-b text-center">OT (Hrs)</th>
                                <th class="px-4 py-3 border-b text-right">Rate</th>
                                <th class="px-6 py-3 border-b text-right text-blue-800">Gross</th>
                                <th class="px-6 py-3 border-b text-right text-red-800">(-) Ded</th>
                                <th class="px-6 py-3 border-b text-right text-[#e9922c]">Net Pay</th>
                            </tr>
                        </thead>
                        <tbody id="histModalBody" class="divide-y divide-gray-100 text-sm bg-white">
                            </tbody>
                        <tfoot class="bg-gray-50 font-bold text-gray-700 border-t border-gray-200">
                            <tr>
                                <td class="px-6 py-3 uppercase text-xs tracking-wider">Total</td>
                                <td colspan="3"></td>
                                <td class="px-6 py-3 text-right text-blue-800" id="histTotalGross">0.00</td>
                                <td class="px-6 py-3 text-right text-red-600" id="histTotalDed">0.00</td>
                                <td class="px-6 py-3 text-right text-[#e9922c]" id="histTotalNet">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="bg-gray-50 px-6 py-3 flex justify-end">
                    <button onclick="Payroll.closeHistoryModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="payslipModal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 modal-overlay transition-opacity" onclick="Payroll.closePayslip()"></div>
            <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-2xl relative z-50 p-0">
                <div class="bg-gray-900 px-8 py-5 flex justify-between items-center rounded-t-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[#e9922c] rounded flex items-center justify-center text-white font-bold">P</div>
                        <h3 class="text-lg font-bold text-white">PAYSLIP PREVIEW</h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="Payroll.printPayslip()" id="btnPrintPayslip" class="text-white hover:text-gray-300" title="Print Payslip"><i data-lucide="printer" class="w-5 h-5"></i></button>
                        <button onclick="Payroll.closePayslip()" class="text-white hover:text-gray-300"><i data-lucide="x" class="w-6 h-6"></i></button>
                    </div>
                </div>
                <div class="p-8">
                    <div class="flex justify-between border-b border-gray-100 pb-4 mb-4">
                        <div>
                            <p class="text-xl font-bold text-gray-900" id="psName">-</p>
                            <p class="text-sm text-gray-500 font-mono" id="psId">-</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-[#e9922c]" id="psPeriod">-</p>
                            <p class="text-xs text-gray-400 uppercase">Period Covered</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <p class="font-bold text-green-700 text-xs uppercase mb-3 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-green-500"></span> Earnings</p>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm"><span>Basic Pay</span><span id="psBasic" class="font-mono">0.00</span></div>
                                <div class="flex justify-between text-sm"><span>Overtime</span><span id="psOtPay" class="font-mono">0.00</span></div>
                                <div class="flex justify-between font-bold mt-2 pt-2 border-t border-dashed"><span>Total Gross</span><span id="psTotalGross" class="font-mono">0.00</span></div>
                            </div>
                        </div>
                        <div>
                            <p class="font-bold text-red-700 text-xs uppercase mb-3 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-red-500"></span> Deductions</p>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm"><span>SSS</span><span id="psSSS" class="font-mono text-red-600">0.00</span></div>
                                <div class="flex justify-between text-sm"><span>PhilHealth</span><span id="psPhilHealth" class="font-mono text-red-600">0.00</span></div>
                                <div class="flex justify-between text-sm"><span>Pag-IBIG</span><span id="psPagIbig" class="font-mono text-red-600">0.00</span></div>
                                <div class="flex justify-between font-bold mt-2 pt-2 border-t border-dashed"><span>Total Ded.</span><span id="psTotalDed" class="font-mono text-red-600">0.00</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 bg-orange-50 p-4 rounded-xl border border-orange-100 flex justify-between items-center">
                        <span class="text-sm font-bold text-orange-800">NET PAYABLE</span>
                        <p class="text-3xl font-black text-[#e9922c] font-mono" id="psNet">₱0.00</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="lockPayrollModal" class="hidden fixed inset-0 z-[60]" onclick="if(event.target===this) Payroll.closeLockModal()">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 modal-overlay transition-opacity"></div>
            <div class="modal-content bg-white rounded-xl shadow-2xl max-w-md w-full p-6 relative z-[60] text-center transform transition-all">
                
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-orange-100 mb-4">
                    <i data-lucide="lock" class="w-7 h-7 text-[#e9922c]"></i>
                </div>

                <h3 class="text-2xl font-black text-gray-900 tracking-tight">Finalize Payroll?</h3>
                <p class="text-md text-gray-600 mt-2">
                    You are about to post the payroll for <span id="lockPeriodLabel" class="font-bold text-gray-900">---</span>.
                </p>

                <div class="mt-4 mb-6 bg-red-50 border border-red-100 rounded-lg p-3 text-left flex items-start gap-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"></i>
                    <div class="text-md text-red-800">
                        <span class="font-bold block mb-0.5">This action cannot be undone.</span>
                        Once finalized, all attendance records, rates, and deductions for this period will be permanently locked.
                    </div>
                </div>

                <div class="flex gap-3">
                    <button onclick="Payroll.closeLockModal()" class="flex-1 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-colors shadow-sm">
                        Cancel
                    </button>
                    <button onclick="Payroll.confirmLock()" class="flex-1 py-2.5 bg-[#e9922c] text-white rounded-lg text-sm font-bold hover:bg-[#d17f1f] shadow-sm transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Confirm & Lock
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/payroll.js"></script>
    <script>
        window.SELECTED_PROJECT_ID = <?php echo json_encode($selected_project_id); ?>;

        function switchPayrollTab(tabName) {
            document.querySelectorAll('.tab-view').forEach(el => el.classList.add('hidden'));
            document.getElementById('view-' + tabName).classList.remove('hidden');
            
            ['worksheet', 'periods'].forEach(t => {
                const btn = document.getElementById('tab-btn-' + t);
                if (t === tabName) {
                    btn.classList.add('active');
                    btn.classList.remove('inactive');
                } else {
                    btn.classList.add('inactive');
                    btn.classList.remove('active');
                }
            });
            
            if(tabName === 'periods') Payroll.loadHistory();
        }

        document.addEventListener('DOMContentLoaded', () => {
            if(typeof lucide !== 'undefined') lucide.createIcons();
            Payroll.init();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>