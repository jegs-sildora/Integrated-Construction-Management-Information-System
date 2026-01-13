<?php
/**
 * Reports API - Download Previously Generated Report
 * ICMIS - Integrated Construction Management Information System
 */

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$report_id = intval($_GET['id'] ?? 0);

if ($report_id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Report ID is required';
    exit;
}

// Fetch report details from budget_generated_reports table
$stmt = $conn->prepare("SELECT report_id AS id, report_type AS category, report_name, project_id, generated_by, created_at FROM budget_generated_reports WHERE report_id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.1 404 Not Found');
    echo 'Report not found';
    exit;
}

$report = $result->fetch_assoc();
$stmt->close();

// Get project info
$project = null;
if ($report['project_id'] > 0) {
    $stmt = $conn->prepare("SELECT * FROM icmis_projects WHERE project_id = ?");
    $stmt->bind_param("i", $report['project_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $project = $res->fetch_assoc();
    $stmt->close();
}

$userName = $report['generated_by'] ?? '';
if (empty($userName)) {
    if (!empty($_SESSION['user_name'])) {
        $userName = $_SESSION['user_name'];
    } elseif (!empty($_SESSION['user_id']) && defined('DB_HOST')) {
        $uid = intval($_SESSION['user_id']);
        $uconn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($uconn && !$uconn->connect_error) {
            $q = $uconn->prepare('SELECT full_name FROM icmis_users WHERE user_id = ? LIMIT 1');
            if ($q) {
                $q->bind_param('i', $uid);
                $q->execute();
                $res = $q->get_result();
                if ($res && $res->num_rows > 0) {
                    $r = $res->fetch_assoc();
                    if (!empty($r['full_name'])) $userName = $r['full_name'];
                }
                $q->close();
            }
            $uconn->close();
        }
    }
}
if (empty($userName)) $userName = 'Admin';

// Ensure project_name is available in report array for template
if (!empty($project) && isset($project['project_name'])) {
    $report['project_name'] = $project['project_name'];
} else {
    $report['project_name'] = 'All Projects';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report['report_name']); ?> | ICMIS</title>
    
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

            .no-print { display: none !important; }
            
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

            .print-footer {
                margin-top: 50px !important;
                page-break-inside: avoid;
            }
        }

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
        
        <!-- Report Header -->
        <div class="text-center border-b-2 border-slate-800 pb-6 mb-8">
            <img src="../../../assets/images/nobg_logo.png" alt="ICMIS Logo" class="print-logo">
            
            <h1 class="text-2xl font-black uppercase tracking-wide text-slate-900 mt-2">
                <?php echo htmlspecialchars($report['report_name']); ?>
            </h1>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-widest">
                Integrated Construction Management Information System
            </p>
            <p class="text-xs text-slate-400 mt-1">
                Generated on: <?php echo date('F j, Y h:i A', strtotime($report['created_at'])); ?>
            </p>
        </div>

        <!-- Report Info -->
        <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
            <div>
                <table class="w-full">
                    <tr>
                        <td class="font-bold text-slate-500 py-1 w-32">Project:</td>
                        <td class="font-bold text-slate-900 py-1"><?php echo htmlspecialchars($report['project_name'] ?? 'All Projects'); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Category:</td>
                        <td class="text-slate-700 py-1">
                            <span class="px-2 py-0.5 rounded border text-xs font-bold uppercase bg-gray-100 text-gray-700">
                                <?php echo htmlspecialchars(ucfirst($report['category'])); ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="w-full">
                    <tr>
                        <td class="font-bold text-slate-500 py-1 w-32">Generated By:</td>
                        <td class="font-bold text-slate-900 py-1"><?php echo htmlspecialchars($report['generated_by']); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Report ID:</td>
                        <td class="font-mono text-slate-700 py-1">#<?php echo $report['id']; ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Report Content -->
        <div class="mb-8">
            <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Report Details</h3>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
                <p class="text-gray-500 mb-4">This is a previously generated report reference.</p>
                <p class="text-sm text-gray-400">To regenerate with fresh data, please use the Reports Center.</p>
            </div>
        </div>

        <!-- Footer Signatures -->
        <div class="print-footer mt-12 pt-8">
            <div class="grid grid-cols-3 gap-8">
                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Prepared By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase"><?php echo htmlspecialchars($userName); ?></p>
                    <p class="text-xs text-slate-500">Reports Administrator</p>
                </div>

                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Verified By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase">___________</p>
                    <p class="text-xs text-slate-500">Project Engineer</p>
                </div>

                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Approved By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase">___________</p>
                    <p class="text-xs text-slate-500">Project Manager</p>
                </div>
            </div>
        </div>

        <!-- Print Button -->
        <div class="no-print mt-8 text-center">
            <p class="text-sm text-gray-500 mb-4">Press the button below to print this report.</p>
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-md transition-colors">
                Print Report
            </button>
            <a href="../index.php" class="ml-4 px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition-colors inline-block">
                Back to Reports
            </a>
        </div>

    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
<?php $conn->close(); ?>
