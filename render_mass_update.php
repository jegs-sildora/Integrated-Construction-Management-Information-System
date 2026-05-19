<?php
$services = [
    "srv-d85pjp8js32c73am8aog", // icmis-frontend
    "srv-d85pj5vavr4c73d5nsag", // icmis-gateway
    "srv-d85phinavr4c73d5mmr0", // icmis-admin
    "srv-d85ph7favr4c73d5mdd0", // icmis-reports
    "srv-d85pfkfavr4c73d5lcvg", // icmis-workforce
    "srv-d85pf97avr4c73d5l52g", // icmis-procurement
    "srv-d85pe9fdl75s7394lrig", // icmis-budget
    "srv-d85pbkfdl75s7394jbtg", // icmis-project
    "srv-d85p9htckfvc73e3t5mg"  // icmis-auth
];

$apiKey = "rnd_jKDCmSsOCMxcLgBmCLecg9Dzkj1o";

function callRender($url, $method, $data = null) {
    global $apiKey;
    $ch = curl_init($url);
    $headers = [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ["code" => $code, "res" => $res];
}

echo "--- Render Mass Update (PORT=80) ---\n";
foreach ($services as $id) {
    echo "Updating $id... ";
    $resp = callRender("https://api.render.com/v1/services/$id/env-vars", "PATCH", [
        ["key" => "PORT", "value" => "80"]
    ]);
    echo "HTTP {$resp['code']}\n";
}

echo "\n--- Render Mass Deploy (Clear Cache) ---\n";
foreach ($services as $id) {
    echo "Deploying $id... ";
    $resp = callRender("https://api.render.com/v1/services/$id/deploys", "POST", [
        "clearCache" => "clear"
    ]);
    echo "HTTP {$resp['code']}\n";
}
