<?php
// modules/procurement/purchase_order/save_order.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); 

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
require_once __DIR__ . '/../../../core/Logger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }

        // --- Validate Required Fields ---
        $required = ['project_id', 'phase', 'supplier', 'items'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $project_id = intval($data['project_id']);
        $supplier_input = $data['supplier'];
        $phase_input = $data['phase'];

        // --- RESOLVE SUPPLIER ID ---
        $supplier_id = 0;
        if (is_numeric($supplier_input)) {
            $supplier_id = intval($supplier_input);
        } else {
            $res_sup = ApiHelper::get("procurement/suppliers");
            if ($res_sup['status'] === 200) {
                foreach ($res_sup['data'] as $s) {
                    if ($s['supplier_name'] === $supplier_input) {
                        $supplier_id = $s['supplier_id'];
                        break;
                    }
                }
            }
            if ($supplier_id === 0) throw new Exception("Supplier '$supplier_input' not found.");
        }

        // --- RESOLVE PHASE ID ---
        $phase_id = 0;
        if (is_numeric($phase_input)) {
            $phase_id = intval($phase_input);
        } else {
            $res_phase = ApiHelper::get("project/phases?project_id=$project_id");
            if ($res_phase['status'] === 200) {
                foreach ($res_phase['data']['phases'] as $p) {
                    if ($p['phase_name'] === $phase_input) {
                        $phase_id = $p['phase_id'];
                        break;
                    }
                }
            }
        }

        // --- PREPARE DATA FOR MICROSERVICE ---
        $api_data = [
            'project_id' => $project_id,
            'phase_id' => $phase_id,
            'supplier_id' => $supplier_id,
            'order_title' => $data['title'] ?? 'Untitled Order',
            'status' => 'PENDING',
            'items' => $data['items'],
            'user_id' => $_SESSION['user_id'] ?? null
        ];

        // --- SEND TO MICROSERVICE ---
        $response = ApiHelper::post('procurement/orders', $api_data);

        if ($response['status'] !== 200 || !($response['data']['success'] ?? false)) {
            throw new Exception($response['data']['message'] ?? 'Failed to save order via API');
        }

        $new_po_id = $response['data']['po_id'];
        $po_reference = $response['data']['po_reference'];

        // Log the audit trail (Logger is now API-based)
        Logger::log('CREATE', 'Procurement', "Purchase Order Created: $po_reference - " . ($data['title'] ?? ''), $new_po_id);

        ob_clean(); 
        echo json_encode([
            'success' => true,
            'message' => 'Purchase Order created successfully!',
            'po_reference' => $po_reference,
            'po_id' => $new_po_id
        ]);

    } catch (Exception $e) {
        ob_clean(); 
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
}
