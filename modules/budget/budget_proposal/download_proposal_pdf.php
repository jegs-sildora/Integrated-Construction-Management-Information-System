<?php
// modules/budget/budget_proposal/download_proposal_pdf.php
require_once __DIR__ . '/../../../config/config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Proposal ID is required');
}

$proposal_id = intval($_GET['id']);

// Fetch proposal details with user info
$sql = "SELECT bp.*, p.project_name, p.project_code, p.location,
        pp.phase_name, pp.start_date as phase_start_date, pp.end_date as phase_end_date,
        COALESCE(u.full_name, 'Authorized Staff') as creator_name
        FROM budget_proposals bp 
        LEFT JOIN icmis_projects p ON bp.project_id = p.project_id 
        LEFT JOIN icmis_project_phases pp ON bp.phase_id = pp.phase_id
        LEFT JOIN icmis_users u ON bp.created_by = u.user_id
        WHERE bp.proposal_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Proposal not found');
}

$proposal = $result->fetch_assoc();

// Fetch line items
$sql_items = "SELECT * FROM budget_line_items WHERE proposal_id = ? ORDER BY line_item_id ASC";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $proposal_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

$items = [];
while ($row = $result_items->fetch_assoc()) {
    $items[] = $row;
}

// Helper for status badge color in print
$statusColor = match($proposal['status']) {
    'APPROVED' => 'text-green-700 bg-green-50 border-green-200',
    'PENDING' => 'text-amber-700 bg-amber-50 border-amber-200',
    'REJECTED' => 'text-red-700 bg-red-50 border-red-200',
    default => 'text-gray-700 bg-gray-50 border-gray-200',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Proposal - <?php echo htmlspecialchars($proposal['code']); ?></title>
    
    <?php include __DIR__ . '/../../../includes/head_assetsv2.php'; ?>

    <style>
        /* Base Styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            padding: 40px;
        }

        .report-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        /* PRINT SPECIFIC STYLES */
        @media print {
            @page { margin: 0.5in; size: auto; }
            body { 
                background-color: white !important; 
                color: black !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact; 
            }
            
            .report-container {
                box-shadow: none !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: none !important;
            }

            /* Hide UI elements */
            .no-print { display: none !important; }
            
            /* Table Styling for Print */
            table { width: 100% !important; border-collapse: collapse !important; font-size: 10pt !important; }
            
            thead tr { background-color: #f3f4f6 !important; }
            thead th { 
                border: 1px solid #9ca3af !important; 
                padding: 8px !important; 
                color: black !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
            }
            tbody td { 
                border: 1px solid #e5e7eb !important; 
                padding: 8px !important; 
                color: black !important;
            }

            /* Footer Spacing */
            .print-footer {
                margin-top: 50px !important;
                page-break-inside: avoid;
            }
        }

        /* Logo Sizing */
        .print-logo {
            height: 80px;
            width: auto;
            margin: 0 auto 10px auto;
            display: block;
        }
    </style>
</head>
<body>

    <div class="report-container">
        
        <div class="text-center border-b-2 border-slate-800 pb-6 mb-8">
            <img src="../../../assets/images/nobg_logo.png" alt="ICMIS Logo" class="print-logo">
            
            <h1 class="text-2xl font-black uppercase tracking-wide text-slate-900 mt-2">Budget Proposal Report</h1>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-widest">Integrated Construction Management Information System</p>
            <p class="text-xs text-slate-400 mt-1">Generated on: <?php echo date('F j, Y h:i A'); ?></p>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
            <div>
                <table class="w-full">
                    <tr>
                        <td class="font-bold text-slate-500 py-1 w-32">Project:</td>
                        <td class="font-bold text-slate-900 py-1"><?php echo htmlspecialchars($proposal['project_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Location:</td>
                        <td class="text-slate-700 py-1"><?php echo htmlspecialchars($proposal['location'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Proposal Code:</td>
                        <td class="font-mono font-bold text-slate-900 py-1"><?php echo htmlspecialchars($proposal['code']); ?></td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="w-full">
                    <tr>
                        <td class="font-bold text-slate-500 py-1 w-32">Title:</td>
                        <td class="font-bold text-slate-900 py-1"><?php echo htmlspecialchars($proposal['title']); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Status:</td>
                        <td class="py-1">
                            <span class="px-2 py-0.5 rounded border text-xs font-bold uppercase <?php echo $statusColor; ?>">
                                <?php echo htmlspecialchars($proposal['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Description:</td>
                        <td class="text-slate-700 py-1 italic"><?php echo htmlspecialchars($proposal['description']); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mb-8">
            <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Line Items Breakdown</h3>
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center w-12">#</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Category</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Item Name</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center">Qty</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Unit Cost</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    if (count($items) > 0): 
                        foreach ($items as $index => $item): 
                            $grand_total += $item['subtotal'];
                    ?>
                    <tr>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-500"><?php echo $index + 1; ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 font-medium text-slate-700"><?php echo htmlspecialchars($item['category']); ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800"><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-700"><?php echo $item['quantity']; ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-slate-700">₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900">₱<?php echo number_format($item['subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500 italic border border-slate-200">No items found in this proposal.</td>
                    </tr>
                    <?php endif; ?>
                    
                    <tr class="bg-slate-50">
                        <td colspan="5" class="px-4 py-3 text-right text-sm font-black uppercase text-slate-600 border border-slate-300">Total Budget Requested:</td>
                        <td class="px-4 py-3 text-right text-lg font-black text-slate-900 border border-slate-300 font-mono">₱<?php echo number_format($grand_total, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="print-footer mt-12 pt-8">
            <div class="grid grid-cols-3 gap-8">
                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Prepared By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase"><?php echo htmlspecialchars($proposal['creator_name']); ?></p>
                    <p class="text-xs text-slate-500">Requestor</p>
                </div>

                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Verified By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase">Engr. Jane Doe</p> 
                    <p class="text-xs text-slate-500">Project Engineer</p>
                </div>

                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Approved By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase">John Smith</p> 
                    <p class="text-xs text-slate-500">Project Manager</p>
                </div>
            </div>
        </div>

        <div class="no-print mt-8 text-center">
            <p class="text-sm text-gray-500 mb-4">Press the button below if printing does not start automatically.</p>
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-md transition-colors">
                Print Report
            </button>
        </div>

    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Small delay to ensure styles and images are loaded
            setTimeout(() => {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
<?php
$conn->close();
?>