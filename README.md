# WHMCS Paytm & UPI Payment Gateway (Hardened & Reliable)

[![WHMCS Compatibility](https://img.shields.io/badge/WHMCS-8.x%20%7C%209.x-blue.svg)](https://whmcs.com)
[![PHP Compatibility](https://img.shields.io/badge/PHP-7.4%20%7C%208.1%20%7C%208.2-green.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

An executive, production-ready **Paytm & UPI Payment Gateway Module** for **WHMCS**.

Seamlessly accept **UPI (Google Pay, PhonePe, Paytm, BHIM)**, **Net Banking**, **Debit/Credit Cards**, and **Paytm Wallet** directly within WHMCS with **instant automated payment confirmation**.

---

## ⚡ Why This Module?

Many existing WHMCS Paytm modules suffer from a critical flaw:
When a customer pays via UPI/Paytm, the module attempts a secondary background HTTP cURL query back to Paytm. Due to API schema deprecations or network lag, that secondary query frequently fails with:
> *"It seems some issue in server to server communication. Kindly connect with administrator."*

When that occurs, **the legacy modules drop the payment**, leaving the customer's invoice **UNPAID** even though the customer's money was deducted.

### 🌟 Key Improvements in This Release:
1. **Guaranteed Payment Confirmation:** When Paytm returns with `STATUS = TXN_SUCCESS`, `RESPCODE = 01`, and a valid cryptographic SHA-256 signature (`CHECKSUMHASH`), the invoice is **immediately marked as PAID**.
2. **Fixed Invoice ID Parsing:** Corrects a legacy bug where order IDs like `3679_1795540351` failed lookup in WHMCS.
3. **Fixed cURL Headers:** Outgoing status requests use correct `application/x-www-form-urlencoded` payloads with a 10s connection safety timeout.
4. **Instant Success Redirect:** Redirects customers back to `viewinvoice.php?id=XXXX&paymentsuccess=true` to trigger the confirmation banner immediately.
5. **Full PHP 8.1 / 8.2 Compatibility:** Safe for modern WHMCS 9.x environments.

---

## 📂 Directory Structure

```text
whmcs-paytm-gateway/
├── modules/
│   └── gateways/
│       ├── callback/
│       │   └── paytm.php          # Webhook & return callback handler
│       ├── paytm-sdk/
│       │   ├── curltest.php       # Diagnostic connectivity test tool
│       │   └── encdec_paytm.php   # Cryptography & SHA-256 SDK
│       └── paytm.php              # WHMCS Gateway configuration & checkout button
├── LICENSE
└── README.md
```

---

## 🚀 Installation

1. Download the latest release zip from GitHub.
2. Extract the contents directly into your **WHMCS root directory** (where `init.php` and `configuration.php` are located).
3. Confirm files are placed in:
   * `modules/gateways/paytm.php`
   * `modules/gateways/callback/paytm.php`
   * `modules/gateways/paytm-sdk/encdec_paytm.php`
   * `modules/gateways/paytm-sdk/curltest.php`

---

## ⚙️ WHMCS Configuration

1. In your WHMCS Admin Area, navigate to:
   * **WHMCS 8.x / 9.x:** **Configuration** &rarr; **System Settings** &rarr; **Payment Gateways** (or **Setup** &rarr; **Payments** &rarr; **Payment Gateways**).
2. Go to the **All Payment Gateways** tab and click on **Paytm** to activate it.
3. Fill in your Paytm Merchant details:

| Field | Description / Value |
|---|---|
| **Display Name** | `UPI / Net Banking / Paytm (India)` |
| **Merchant ID (MID)** | Your Paytm Production Merchant ID |
| **Merchant Key** | Your Paytm Production Merchant Secret Key |
| **Website Name** | `DEFAULT` *(or `WEBPROD` as given in your Paytm dashboard)* |
| **Industry Type ID** | `Retail` *(or `Retail109`)* |
| **Transaction URL** | `https://securegw.paytm.in/order/process` |
| **Transaction Status URL** | `https://securegw.paytm.in/order/status` |

4. Click **Save Changes**.

---

## 🛡️ Cloudflare WAF Recommendation (Important)

If your website uses **Cloudflare**, automated gateway webhooks can occasionally trigger Cloudflare Bot Fight Mode.

To prevent any interruptions:
1. In Cloudflare Dashboard, go to **Security** &rarr; **WAF** &rarr; **Custom Rules**.
2. Create a rule:
   * **URI Path** `contains` `/modules/gateways/callback/`
   * **Action**: **Skip** (Skip all WAF components, Managed Challenges & Bot Fight Mode).

---

## 🧪 Outbound Connectivity Test

To verify that your hosting server can reach Paytm's servers, navigate in your browser to:
```
https://yourdomain.com/modules/gateways/paytm-sdk/curltest.php
```
It will display the cURL connection status and HTTP response codes.

---

## 📄 License

This project is open-source software licensed under the [MIT License](LICENSE).
