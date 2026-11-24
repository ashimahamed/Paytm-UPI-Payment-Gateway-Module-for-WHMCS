<?php
/**
 * Paytm Gateway cURL Connectivity Diagnostic Tool
 * 
 * Verifies your web server can connect to Paytm gateway endpoints.
 * Run in browser: https://yourdomain.com/modules/gateways/paytm-sdk/curltest.php
 */

include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = "paytm"; 
$GATEWAY = getGatewayVariables($gatewaymodule);

$debug = array();

if (!function_exists("curl_init")) {
    $debug[0]["info"][] = "cURL extension is either not available or disabled in PHP.";
} else { 
    $testing_urls = array();
    if (!empty($_GET)) {
        foreach ($_GET as $key => $value) {
            $testing_urls[] = $value;
        }
    } else {
        $systemUrl = !empty($GATEWAY['systemurl']) ? $GATEWAY['systemurl'] : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/");
        $testing_urls = array(
            $systemUrl,
            "https://www.google.com",
            !empty($GATEWAY['transaction_status_url']) ? html_entity_decode($GATEWAY['transaction_status_url']) : "https://securegw.paytm.in/order/status",
        );
    }

    foreach ($testing_urls as $key => $url) {
        $debug[$key]["info"][] = "Connecting to <b>" . htmlspecialchars($url) . "</b> using cURL";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $res = curl_exec($ch);
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $debug[$key]["info"][] = "<span style='color:green;'>✓ cURL executed successfully.</span>";
            $debug[$key]["info"][] = "HTTP Response Code: <b>" . $http_code . "</b>";
        } else {
            $debug[$key]["info"][] = "<span style='color:red;'>✗ Connection Failed!</span>";
            $debug[$key]["info"][] = "Error Code: <b>" . curl_errno($ch) . "</b>";
            $debug[$key]["info"][] = "Error: <b>" . htmlspecialchars(curl_error($ch)) . "</b>";
        }
        curl_close($ch);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Paytm Connectivity Diagnostic</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 30px; background: #f8fafc; color: #1e293b; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px; max-width: 700px; margin: 0 auto; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        h2 { margin-top: 0; color: #002e6e; }
        ul { background: #f1f5f9; border-radius: 6px; padding: 14px 24px; margin-bottom: 16px; }
        li { margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Paytm Gateway Connectivity Diagnostic</h2>
        <p>Testing server-to-server outbound cURL connectivity:</p>
        <?php foreach ($debug as $k => $v): ?>
            <ul>
                <?php foreach ($v["info"] as $info): ?>
                    <li><?= $info ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </div>
</body>
</html>
