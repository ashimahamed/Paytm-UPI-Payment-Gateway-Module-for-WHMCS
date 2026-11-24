<?php
/**
 * WHMCS Paytm Payment Gateway Module (Updated & Hardened)
 * 
 * Supports: UPI (Google Pay, PhonePe, Paytm), Net Banking, Debit/Credit Cards & Wallets
 * Compatible with: WHMCS 8.x, 9.x | PHP 7.4, 8.1, 8.2+
 * 
 * @license MIT
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/paytm-sdk/encdec_paytm.php';

function paytm_MetaData() {
    return array(
        'DisplayName' => 'Paytm & UPI Payment Gateway',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function paytm_config() {
    return array(
        "FriendlyName" => array(
            "Type" => "System",
            "Value" => "UPI / Net Banking / Paytm (India)",
        ),
        "merchant_id" => array(
            "FriendlyName" => "Merchant ID (MID)",
            "Type" => "text",
            "Size" => "30",
            "Description" => "Your production or staging Merchant ID from Paytm Dashboard.",
        ),
        "merchant_key" => array(
            "FriendlyName" => "Merchant Key",
            "Type" => "password",
            "Size" => "40",
            "Description" => "Your production or staging Merchant Secret Key.",
        ),
        "website" => array(
            "FriendlyName" => "Website Name",
            "Type" => "text",
            "Size" => "25",
            "Default" => "DEFAULT",
            "Description" => "Usually 'DEFAULT' for Production, or 'WEBSTAGING' for Sandbox.",
        ),
        "industry_type" => array(
            "FriendlyName" => "Industry Type ID",
            "Type" => "text",
            "Size" => "25",
            "Default" => "Retail",
            "Description" => "Provided by Paytm (usually 'Retail' or 'Retail109').",
        ),
        "transaction_url" => array(
            "FriendlyName" => "Transaction URL",
            "Type" => "text",
            "Size" => "90",
            "Default" => "https://securegw.paytm.in/order/process",
            "Description" => "Production: https://securegw.paytm.in/order/process<br>Staging: https://securegw-stage.paytm.in/order/process",
        ),
        "transaction_status_url" => array(
            "FriendlyName" => "Transaction Status URL",
            "Type" => "text",
            "Size" => "90",
            "Default" => "https://securegw.paytm.in/order/status",
            "Description" => "Production: https://securegw.paytm.in/order/status<br>Staging: https://securegw-stage.paytm.in/order/status",
        ),
    );
}

function paytm_link($params) {
    $merchant_id = trim($params['merchant_id']);
    $secret_key = trim($params['merchant_key']);
    $order_id = $params['invoiceid'] . '_' . time();
    $website = trim($params['website']);
    $industry_type = trim($params['industry_type']);
    $channel_id = "WEB";
    $transaction_url = trim($params['transaction_url']);
    $amount = number_format($params['amount'], 2, '.', '');
    $email = !empty($params['clientdetails']['email']) ? $params['clientdetails']['email'] : 'customer@ashhost.in';

    // Canonical callback link
    $systemUrl = !empty($params['systemurl']) ? rtrim($params['systemurl'], '/') . '/' : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/");
    $callBackLink = $systemUrl . 'modules/gateways/callback/paytm.php';

    $post_variables = array(
        "MID" => $merchant_id,
        "ORDER_ID" => $order_id,
        "CUST_ID" => $email,
        "TXN_AMOUNT" => $amount,
        "CHANNEL_ID" => $channel_id,
        "INDUSTRY_TYPE_ID" => $industry_type,
        "CALLBACK_URL" => $callBackLink,
        "WEBSITE" => $website,
    );

    $checksum = getChecksumFromArray($post_variables, $secret_key);

    $code = '<form method="post" action="' . htmlspecialchars($transaction_url) . '" style="text-align: center; margin: 15px 0;">';
    foreach ($post_variables as $key => $value) {
        $code .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '"/>';
    }
    $code .= '<input type="hidden" name="CHECKSUMHASH" value="' . htmlspecialchars($checksum) . '"/>';
    $code .= '
        <button type="submit" class="btn btn-primary" style="
            background: linear-gradient(135deg, #002E6E 0%, #00BAF2 100%);
            color: #FFFFFF;
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0, 46, 110, 0.25);
            transition: all 0.2s ease;
        " onmouseover="this.style.opacity=\'0.95\';this.style.transform=\'translateY(-1px)\';" onmouseout="this.style.opacity=\'1\';this.style.transform=\'none\';">
            <span>Pay with UPI / Paytm &rarr;</span>
        </button>';
    $code .= '</form>';

    return $code;
}
