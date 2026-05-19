<?php
$apiKey = "rnd_jKDCmSsOCMxcLgBmCLecg9Dzkj1o";
$services = [
    "icmis-gateway" => "srv-d85pj5vavr4c73d5nsag",
    "icmis-admin" => "srv-d85phinavr4c73d5mmr0",
    "icmis-reports" => "srv-d85ph7favr4c73d5mdd0",
    "icmis-workforce" => "srv-d85pfkfavr4c73d5lcvg",
    "icmis-procurement" => "srv-d85pf97avr4c73d5l52g",
    "icmis-budget" => "srv-d85pe9fdl75s7394lrig",
    "icmis-project" => "srv-d85pbkfdl75s7394jbtg",
    "icmis-auth" => "srv-d85p9htckfvc73e3t5mg",
    "icmis-frontend" => "srv-d85pjp8js32c73am8aog"
];

function triggerDeploy($id) {
    global $apiKey;
    $ch = curl_init("https://api.render.com/v1/services/$id/deploys");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["clearCache" => "clear"]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ["code" => $code, "res" => $res];
}

foreach ($services as $name => $id) {
    echo "Deploying $name ($id)... ";
    $resp = triggerDeploy($id);
    echo "HTTP {$resp['code']}\n";
}
