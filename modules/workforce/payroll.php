<?php
/**
 * Payroll Management - Workforce Module
 * Frontend View - AJAX Driven
 */
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../../config/database.php';
include __DIR__ . '/project_context.php';

// Initial data for dropdowns
$projects = $conn->query("SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC");
$selected_project_id = getProjectContext($conn);

// Determine selected project name
$selected_project_name = 'All Projects';
if ($selected_project_id && $selected_project_id > 0) {
    $sq = $conn->prepare("SELECT project_name FROM icmis_projects WHERE project_id = ? LIMIT 1");
    $sq->bind_param('i', $selected_project_id);
    $sq->execute();
    $r = $sq->get_result();
    if ($row = $r->fetch_assoc()) $selected_project_name = $row['project_name'];
    $sq->close();
}

$pageSection = "Labor & Workforce";
$pageTitle = "Payroll";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll | ICMIS</title>
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* --- MODAL OVERLAY (BACKGROUND) --- */
        /* Transparent white overlay with frosted blur */
        #payslipModal { background-color: transparent; }
        #payslipModal .fixed.inset-0 {
            background-color: rgba(31,41,55,0.45) !important; /* translucent gray */
            -webkit-backdrop-filter: blur(6px);
            backdrop-filter: blur(6px);
        }

        /* --- MODAL ANIMATION STYLES (Required for Pop-up) --- */
        .modal-content { 
            transform: scale(0.95); 
            opacity: 0; 
            transition: all 0.2s ease-out; 
        }
        .modal-content.modal-open { 
            transform: scale(1); 
            opacity: 1; 
        }

        /* --- PRINT STYLES (Payslip Mode) --- */
        @media print {
            @page { margin: 0.5in; size: portrait; }
            
            /* Hide Interface Elements */
            header, aside, .sidebar, .no-print, #loadingBar, .print-hidden { 
                display: none !important; 
            }
            
            /* Hide Main Page Content when printing a specific payslip */
            body.printing-payslip main, 
            body.printing-payslip .print-footer { 
                display: none !important; 
            }
            
            /* Reset Body */
            body { 
                background-color: white !important; 
                -webkit-print-color-adjust: exact; 
                overflow: visible !important;
            }

            /* --- MODAL ANIMATION STYLES --- */
            .modal-content { 
                transform: scale(0.95); 
                opacity: 0; 
                transition: all 0.2s ease-out; 
            }
            .modal-content.modal-open { 
                transform: scale(1); 
                opacity: 1; 
            }
            
            /* --- PRINT STYLES --- */
            /* Ensure the modal background is REMOVED when printing */
            #payslipModal { 
                position: static !important; 
                display: block !important;
                overflow: visible !important; 
                background: none !important; /* Removes blue background on print */
                z-index: 9999 !important; 
            }
            #payslipModal .modal-content { 
                box-shadow: none !important; 
                border: 1px solid #ddd !important; 
                width: 100% !important; 
                opacity: 1 !important; 
                transform: none !important; 
            }
            #payslipModal .close-btn, #payslipModal .modal-actions { display: none !important; }
        }

        /* Loader Animation */
        .loader-line { height: 3px; width: 100%; position: relative; overflow: hidden; background-color: #f3f4f6; }
        .loader-line:before { display: block; position: absolute; content: ""; left: -200px; width: 200px; height: 3px; background-color: #e9922c; animation: loading 2s linear infinite; }
        @keyframes loading { from {left: -200px; width: 30%;} 50% {width: 30%;} 70% {width: 70%;} 80% { left: 50%;} 95% {left: 120%;} to {left: 100%;} }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../../includes/toast.php'; ?>
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <main class="ml-56 mt-20 p-6 transition-all duration-300">
        <div class="max-w-7xl mx-auto">
            
            <div class="border-b border-gray-200 pb-6 mb-8 no-print">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl text-gray-900 font-bold">Payroll Management</h1>
                        <p class="text-sm text-gray-500 mt-1">Manage payroll periods for <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($selected_project_name); ?></span></p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center bg-white border border-gray-300 rounded-lg shadow-sm p-1">
                            <input type="month" id="payrollMonth" 
                                   class="pl-2 pr-2 py-1.5 border-none bg-transparent focus:ring-0 text-sm font-medium text-gray-700 cursor-pointer"
                                   value="<?= date('Y-m') ?>">
                            <div class="h-6 w-px bg-gray-200 mx-1"></div>
                            <select id="payrollPeriod" class="pl-2 pr-8 py-1.5 border-none bg-transparent focus:ring-0 text-sm font-bold text-[#e9922c] cursor-pointer outline-none">
                                <option value="1">1st Period (1-15)</option>
                                <option value="2">2nd Period (16-End)</option>
                            </select>
                        </div>
                        
                        <div class="h-8 w-px bg-gray-300 mx-1"></div>
                        
                        <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm text-sm font-medium text-gray-700">
                            <i data-lucide="printer" class="w-4 h-4"></i> Print Report
                        </button>
                        
                        <button onclick="Payroll.exportPDF()" class="flex items-center gap-2 px-4 py-2 bg-[#e9922c] text-white rounded-lg hover:bg-[#d17f1f] transition-colors shadow-sm text-sm font-medium">
                            <i data-lucide="download" class="w-4 h-4"></i> Export PDF
                        </button>
                    </div>
                </div>
            </div>

            <div id="loadingBar" class="hidden w-full mb-6 no-print">
                <div class="loader-line rounded-full"></div>
                <p class="text-xs text-center text-gray-400 mt-1">Processing...</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 no-print">
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Total Employees</span>
                        <span class="p-2 bg-blue-100 rounded-lg"><i data-lucide="users" class="w-5 h-5 text-blue-600"></i></span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900" id="statEmployees">0</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Gross Pay</span>
                        <span class="p-2 bg-green-100 rounded-lg"><i data-lucide="banknote" class="w-5 h-5 text-green-600"></i></span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900" id="statGross">₱0.00</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Total Deductions</span>
                        <span class="p-2 bg-red-100 rounded-lg"><i data-lucide="minus-circle" class="w-5 h-5 text-red-600"></i></span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900" id="statDeductions">₱0.00</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm ring-1 ring-[#e9922c]/20">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Net Pay</span>
                        <span class="p-2 bg-orange-100 rounded-lg"><i data-lucide="wallet" class="w-5 h-5 text-orange-600"></i></span>
                    </div>
                    <p class="text-3xl font-bold text-[#e9922c]" id="statNet">₱0.00</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center no-print">
                    <div class="flex items-center gap-3">
                        <h3 class="font-bold text-gray-800">Payroll Sheet</h3>
                        <span class="text-xs font-medium text-gray-500 bg-white border border-gray-200 px-2 py-0.5 rounded-full" id="tablePeriodLabel">---</span>
                    </div>
                    <p class="text-xs text-gray-400 italic"><i data-lucide="mouse-pointer-click" class="w-3 h-3 inline"></i> Click a row to view Payslip</p>
                </div>
                
                <div class="overflow-x-auto min-h-[300px]">
                    <table class="w-full text-left border-collapse" id="payrollTable">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-3 border-b border-gray-200">Employee</th>
                                <th class="px-6 py-3 border-b border-gray-200">Role</th>
                                <th class="px-6 py-3 border-b border-gray-200 text-center">Days</th>
                                <th class="px-6 py-3 border-b border-gray-200 text-center">OT Hrs</th>
                                <th class="px-6 py-3 border-b border-gray-200 text-right">Daily Rate</th>
                                <th class="px-6 py-3 border-b border-gray-200 text-right">Gross Pay</th>
                                <th class="px-6 py-3 border-b border-gray-200 text-right">Deductions</th>
                                <th class="px-6 py-3 border-b border-gray-200 text-right">Net Pay</th>
                                <th class="px-6 py-3 border-b border-gray-200 text-center w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="payrollTableBody" class="divide-y divide-gray-100 text-sm bg-white">
                            <tr><td colspan="9" class="px-6 py-12 text-center text-gray-400">Select a Project to view payroll.</td></tr>
                        </tbody>
                        <tfoot id="payrollTableFoot" class="bg-gray-50 font-bold hidden">
                             <tr>
                                <td colspan="5" class="px-6 py-4 text-right uppercase text-gray-600">Total</td>
                                <td class="px-6 py-4 text-right font-mono" id="footGross">₱0.00</td>
                                <td class="px-6 py-4 text-right font-mono text-red-600" id="footDeductions">-₱0.00</td>
                                <td class="px-6 py-4 text-right font-mono text-[#e9922c]" id="footNet">₱0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div id="payrollPagination" class="px-6 py-4 flex justify-end items-center gap-2"></div>
            </div>
        </div>
    </main>

    <div id="payslipModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity no-print" onclick="Payroll.closePayslip()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="modal-content inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                
                <div class="bg-white px-8 py-6 border-b border-gray-200">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center gap-4">
                            <img src="../../assets/images/logo.png" alt="ICMIS Logo" class="w-12 h-12 object-contain" />
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 tracking-tight">PAYSLIP</h3>
                                <p class="text-xs text-gray-500 uppercase tracking-widest mt-1">ICMIS Construction Inc.</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-[#e9922c]" id="psPeriod">---</p>
                            <p class="text-xs text-gray-400" id="psDate">Date Generated: ---</p>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Employee Name</p>
                        <p class="text-lg font-bold text-gray-800" id="psName">---</p>
                        <p class="text-xs text-gray-500 font-mono" id="psId">---</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 uppercase">Designation</p>
                        <p class="text-sm font-semibold text-gray-700" id="psRole">---</p>
                        <p class="text-xs text-gray-500">Daily Rate: <span id="psRate" class="font-mono font-medium">0.00</span></p>
                    </div>
                </div>

                <div class="px-8 py-6">
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <h4 class="text-xs font-bold text-green-600 uppercase tracking-wider mb-3 border-b border-green-100 pb-1 flex justify-between">
                                <span>Earnings</span>
                                <span>Amount (₱)</span>
                            </h4>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Basic Salary (<span id="psDays">0</span> days)</span>
                                    <span class="font-mono text-gray-900" id="psBasic">0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Overtime Pay (<span id="psOtHrs">0</span> hrs)</span>
                                    <span class="font-mono text-gray-900" id="psOtPay">0.00</span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-dashed border-gray-200 mt-4">
                                    <span class="font-bold text-gray-800">Gross Pay</span>
                                    <span class="font-mono font-bold text-gray-900" id="psTotalGross">0.00</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold text-red-600 uppercase tracking-wider mb-3 border-b border-red-100 pb-1 flex justify-between">
                                <span>Deductions</span>
                                <span>Amount (₱)</span>
                            </h4>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">SSS</span>
                                    <span class="font-mono text-red-600" id="psSSS">0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">PhilHealth</span>
                                    <span class="font-mono text-red-600" id="psPhilHealth">0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Pag-IBIG</span>
                                    <span class="font-mono text-red-600" id="psPagIbig">0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Other Deductions</span>
                                    <span class="font-mono text-red-600" id="psOtherDed">0.00</span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-dashed border-gray-200 mt-4">
                                    <span class="font-bold text-gray-800">Total Deductions</span>
                                    <span class="font-mono font-bold text-red-600" id="psTotalDed">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-900 px-8 py-4 flex justify-between items-center">
                    <div class="text-white text-xs uppercase tracking-wider">Net Payable Amount</div>
                    <div class="text-2xl font-black text-[#e9922c] font-mono" id="psNet">₱0.00</div>
                </div>

                <div class="bg-gray-50 px-8 py-4 flex justify-end gap-3 border-t border-gray-200 modal-actions no-print">
                    <button onclick="Payroll.closePayslip()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 close-btn">Close</button>
                    <button onclick="window.print()" class="px-4 py-2 bg-[#e9922c] text-white rounded-lg text-sm font-medium hover:bg-[#d17f1f] flex items-center gap-2">
                        <i data-lucide="printer" class="w-4 h-4"></i> Print Payslip
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/payroll.js"></script>
    <script>
        window.SELECTED_PROJECT_ID = <?php echo json_encode($selected_project_id); ?>;
        document.addEventListener('DOMContentLoaded', () => {
            if(typeof lucide !== 'undefined') lucide.createIcons();
            Payroll.init();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>