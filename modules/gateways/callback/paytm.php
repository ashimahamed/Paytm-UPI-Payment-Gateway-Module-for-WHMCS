<?php
/**
 * WHMCS Paytm Callback & Webhook Handler (Hardened & Reliable)
 * 
 * Verifies Paytm SHA-256 Checksum, marks invoice paid, logs transaction,
 * and seamlessly redirects back to viewinvoice.php.
 * 
 * @license MIT
 */

include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

require_once __DIR__ . '/../paytm-sdk/encdec_paytm.php';

$gatewaymodule = "paytm";
$GATEWAY = getGatewayVariables($gatewaymodule);

if (!$GATEWAY["type"]) {
    die("Module Not Activated");
}

$response = $_POST;

if (isset($response['ORDERID']) && isset($response['STATUS']) && isset($response['RESPCODE']) && $response['RESPCODE'] != 325) {

    // Extract exact numeric invoice ID (e.g., from '3679_1795540351' => '3679')
    $txnid_arr = explode('_', $response['ORDERID']);
    $rawInvoiceId = $txnid_arr[0];
    $txnid = checkCbInvoiceID($rawInvoiceId, $GATEWAY["name"]);

    $status = $response['STATUS'];
    $paytm_trans_id = $response['TXNID'];
    $amount = $response['TXNAMOUNT'];
    $checksum_recv = isset($response['CHECKSUMHASH']) ? $response['CHECKSUMHASH'] : '';

    // Prevent duplicate processing of the same transaction ID
    checkCbTransID($paytm_trans_id);

    // Verify cryptographic SHA-256 signature using merchant key
    $checksum_status = verifychecksum_e($response, html_entity_decode($GATEWAY['merchant_key']), $checksum_recv);

    if ($status == 'TXN_SUCCESS' && $checksum_status == "TRUE") {
        // Optional secondary server-to-server status check
        $check_status_url = !empty($GATEWAY['transaction_status_url']) ? trim($GATEWAY['transaction_status_url']) : '';
        $apiVerified = false;

        if (!empty($check_status_url)) {
            $requestParamList = array("MID" => $GATEWAY['merchant_id'], "ORDERID" => $response['ORDERID']);
            $StatusCheckSum = getChecksumFromArray($requestParamList, html_entity_decode($GATEWAY['merchant_key']));
            $requestParamList['CHECKSUMHASH'] = $StatusCheckSum;

            $responseParamList = callNewAPI($check_status_url, $requestParamList);
            if (is_array($responseParamList) && isset($responseParamList['STATUS']) && $responseParamList['STATUS'] == 'TXN_SUCCESS' && isset($responseParamList['TXNAMOUNT']) && (float)$responseParamList['TXNAMOUNT'] == (float)$amount) {
                $apiVerified = true;
            }
        }

        // Accept payment if status API verified OR if callback has valid cryptographic checksum and RESPCODE 01
        if ($apiVerified || ($response['RESPCODE'] == '01' && $checksum_status == "TRUE")) {
            addInvoicePayment($txnid, $paytm_trans_id, $amount, "0.0", $gatewaymodule);
            logTransaction($GATEWAY["name"], $response, "Successful: " . (isset($response['RESPMSG']) ? $response['RESPMSG'] : 'Txn Success'));
        } else {
            logTransaction($GATEWAY["name"], $response, "It seems some issue in server to server communication. Kindly connect with administrator.");
        }
    } elseif ($status == "TXN_SUCCESS" && $checksum_status != "TRUE") {
        logTransaction($GATEWAY["name"], $response, "Checksum Mismatch");
    } else {
        logTransaction($GATEWAY["name"], $response, isset($response['RESPMSG']) ? $response['RESPMSG'] : "Transaction Failed");
    }

    $systemUrl = !empty($GATEWAY['systemurl']) ? rtrim($GATEWAY['systemurl'], '/') . '/' : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/");
    $filename = $systemUrl . 'viewinvoice.php?id=' . $txnid . '&paymentsuccess=true';
    header("Location: $filename");
    exit;
} else {
    $systemUrl = !empty($GATEWAY['systemurl']) ? rtrim($GATEWAY['systemurl'], '/') . '/' : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/");
    header("Location: " . $systemUrl . "clientarea.php");
    exit;
}
?>
