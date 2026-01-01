<?php
// 1. Load Config & Session
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Masterlist - ICMIS</title>
    
    <?php include '../../includes/head_assets.php'; ?>

    <link rel="stylesheet" href="css/style.css"> 
    
    <style>
        @media print {
            @page { margin: 0.5in; size: auto; }
            body { 
                background-color: white !important; 
                color: black !important;
                -webkit-print-color-adjust: exact; 
            }
            
            /* HIDE UI ELEMENTS */
            header, aside, .sidebar, .top-bar, .no-print, button { 
                display: none !important; 
            }
            
            /* SHOW REPORT ELEMENTS */
            .print-only { 
                display: block !important; 
            }
            
            /* RESET LAYOUT */
            main { 
                margin: 0 !important; 
                padding: 0 !important; 
                width: 100% !important; 
                min-height: auto !important;
            }
            .content-wrapper { margin: 0 !important; }
            
            /* TABLE PRINT STYLING */
            .bg-white { box-shadow: none !important; border: none !important; }
            .overflow-x-auto { overflow: visible !important; }
            table { width: 100% !important; border-collapse: collapse !important; font-size: 10pt !important; }
            
            thead tr { background-color: #f3f4f6 !important; }
            thead th { 
                border: 1px solid #9ca3af !important; 
                padding: 8px !important; 
                color: black !important;
            }
            tbody td { 
                border: 1px solid #e5e7eb !important; 
                padding: 8px !important; 
                color: black !important;
            }
            
            /* LOGO SIZING */
            .print-logo {
                max-height: 80px;
                width: auto;
                margin: 0 auto 10px auto;
                display: block;
            }
            
            /* FOOTER SPACING */
            .print-footer {
                margin-top: 50px !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <div class="no-print">
        <?php include '../../includes/sidebar.php'; ?>
        <?php include '../../includes/header.php'; ?>
    </div>

    <main class="ml-56 pt-24 p-8 min-h-screen transition-all duration-300">
        
        <div class="print-only hidden mb-6">
            <div class="print-logo-area text-center border-b pb-4 mb-4">
                <img src="../../assets/images/logo.png" alt="ICMIS Logo" class="print-logo" onerror="this.style.display='none';">
                
                <h1 class="text-2xl font-bold uppercase tracking-wide">Inventory Masterlist Report</h1>
                <p class="text-sm text-gray-500">ICMIS Construction Management System</p>
                <p class="text-xs mt-1">Generated on: <span id="print-date"></span></p>
            </div>
        </div>

        <div class="content-wrapper space-y-6">
            
            <div class="flex justify-between items-end bg-white p-6 rounded-xl shadow-sm border border-slate-100 no-print">
                <div>
                    <h1 class="text-2xl font-black text-navy-dark">Inventory Masterlist</h1>
                    <p class="text-slate-500 mt-1">Real-time view of current stock levels.</p>
                </div>
                <button class="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2" onclick="printReport()">
                    <i class="fa-solid fa-print"></i> Print Report
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 no-print">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wider">Total Items</h3>
                        <h2 id="total-items-count" class="text-3xl font-black text-navy-dark mt-1">0</h2>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center text-xl">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-red-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-red-500 text-sm font-bold uppercase tracking-wider">Low Stock Alerts</h3>
                        <h2 id="low-stock-count" class="text-3xl font-black text-red-600 mt-1">0</h2>
                    </div>
                    <div class="w-12 h-12 bg-red-50 text-red-600 rounded-lg flex items-center justify-center text-xl">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Item Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Total Stock</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Unit</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Last Updated</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody id="inventory-table-body" class="divide-y divide-slate-100">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="print-only print-footer hidden">
            <div class="grid grid-cols-3 gap-8">
                <div class="text-center">
                    <p class="text-xs font-bold text-gray-500 uppercase mb-8">Prepared By:</p>
                    <div class="border-b border-black w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 pt-4"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                    <p class="text-xs text-gray-500">Inventory Manager</p>
                </div>
                <div class="text-center">
                    <p class="text-xs font-bold text-gray-500 uppercase mb-8">Verified By:</p>
                    <div class="border-b border-black w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 pt-4">Engr. Jane Doe</p> 
                    <p class="text-xs text-gray-500">Project Engineer</p>
                </div>
                <div class="text-center">
                    <p class="text-xs font-bold text-gray-500 uppercase mb-8">Approved By:</p>
                    <div class="border-b border-black w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 pt-4">John Smith</p> 
                    <p class="text-xs text-gray-500">Project Manager</p>
                </div>
            </div>
        </div>

    </main>

    <script src="js/inventory.js"></script>
    <script>
        function printReport() {
            const dateEl = document.getElementById('print-date');
            if(dateEl) {
                // Professional Date Format
                const now = new Date();
                dateEl.innerText = now.toLocaleDateString('en-US', { 
                    year: 'numeric', month: 'long', day: 'numeric', 
                    hour: '2-digit', minute: '2-digit' 
                });
            }
            window.print();
        }
    </script>
</body>
</html>