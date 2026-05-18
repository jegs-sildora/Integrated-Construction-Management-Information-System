<?php
/**
 * ICMIS Migration Audit Script
 * Scans modules/ and services/ to identify migration progress.
 */

$root = dirname(__DIR__, 4);
$modulesPath = $root . DIRECTORY_SEPARATOR . 'modules';
$servicesPath = $root . DIRECTORY_SEPARATOR . 'services';

function getDirectories($path) {
    if (!is_dir($path)) return [];
    return array_filter(glob($path . '/*'), 'is_dir');
}

$legacyModules = array_map('basename', getDirectories($modulesPath));
$microServices = array_map('basename', getDirectories($servicesPath));

$report = [
    'summary' => [
        'total_legacy' => count($legacyModules),
        'total_services' => count($microServices),
        'migrated_count' => 0,
        'remaining_count' => 0,
    ],
    'modules' => []
];

foreach ($legacyModules as $module) {
    $hasService = in_array($module, $microServices);
    
    $legacyApi = $modulesPath . "/$module/api";
    $serviceApi = $servicesPath . "/$module/api/v1";
    
    $legacyFiles = is_dir($legacyApi) ? array_map('basename', glob("$legacyApi/*.php")) : [];
    $serviceFiles = is_dir($serviceApi) ? array_map('basename', glob("$serviceApi/*.php")) : [];
    
    $parity = [];
    foreach ($legacyFiles as $file) {
        $parity[$file] = in_array($file, $serviceFiles);
    }
    
    $status = 'Pending';
    if ($hasService) {
        $migratedFiles = count(array_filter($parity));
        if ($migratedFiles === count($legacyFiles) && count($legacyFiles) > 0) {
            $status = 'Completed';
            $report['summary']['migrated_count']++;
        } else {
            $status = 'In Progress';
        }
    } else {
        $report['summary']['remaining_count']++;
    }
    
    $report['modules'][$module] = [
        'status' => $status,
        'has_service' => $hasService,
        'legacy_api_files' => count($legacyFiles),
        'service_api_files' => count($serviceFiles),
        'parity' => $parity
    ];
}

echo json_encode($report, JSON_PRETTY_PRINT);
