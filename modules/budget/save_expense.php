<?php
// ============================================================
// DEPRECATED: Manual expense saving has been removed.
// Expenses are now automatically synced from the Procurement module.
// ============================================================

header('Content-Type: application/json');

echo json_encode([
    'success' => false,
    'message' => 'Manual expense entry has been disabled. Expenses are now automatically synced from completed Purchase Orders in the Procurement module.',
    'deprecated' => true
]);
exit;
?>
