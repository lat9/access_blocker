<?php
// -----
// Part of the "Access Blocker" plugin by lat9 (https://vinosdefrutastropicales.com)
// Copyright (C) 2019-2021, Vinos de Frutas Tropicales.  All rights reserved.
//
class zcObserverAccessBlocker extends base 
{
    protected $additional_ips = array(),
              $blocked_message = '';
              
    public function __construct() 
    {
        $this->chars_to_remove = array(
            ' ',
            "\n",
            "\r",
            "\t"
        );
        if (defined('ACCESSBLOCK_ENABLED') && ACCESSBLOCK_ENABLED == 'true') {
            $this->debug = (ACCESSBLOCK_DEBUG == 'true');
            $this->logfile = DIR_FS_LOGS . '/accesses_blocked_' . date('Y_m') . '.log';
            
            $this->attach(
                $this, 
                array(
                    'NOTIFY_CONTACT_US_CAPTCHA_CHECK',
                    'NOTIFY_CREATE_ACCOUNT_CAPTCHA_CHECK',
                    'NOTIFY_PROCESS_3RD_PARTY_LOGINS',
                    'NOTIFY_OPC_GUEST_CHECKOUT_OVERRIDE',
                )
            );
        }
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7) 
    {
        switch ($eventID) {
            case 'NOTIFY_CONTACT_US_CAPTCHA_CHECK':
                // -----
                // If either the 'email' or 'enquiry' posted via the form is empty, perform a "quick return"
                // (to prevent PHP notices generated for those 'missing' values); the base header_php.php will
                // kick the request back.
                //
                if (empty($_POST['email']) || empty($_POST['enquiry'])) {
                    return;
                }
                
                if ($this->isEmailAddressBlocked($_POST['email']) || $this->isContentBlocked($_POST['enquiry']) || $this->isAccessBlocked()) {
                    $this->logBlockedAccesses('contact_us', $_POST['email']);
                    zen_redirect(zen_href_link(FILENAME_CONTACT_US, 'action=success', 'SSL'));
                }
                break;
                
            case 'NOTIFY_CREATE_ACCOUNT_CAPTCHA_CHECK':
                // -----
                // If the email address wasn't submitted as part of the create-account form, perform a "quick
                // return"; the page's header processing will disallow the access (prevents unwanted PHP notices.
                //
                if (empty($_POST['email_address'])) {
                    return;
                }
                
                if ($this->isEmailAddressBlocked($_POST['email_address']) || $this->isCompanyBlocked() || $this->isAccessBlocked()) {
                    $GLOBALS['messageStack']->add_session('header', ACCESSBLOCK_CREATE_ACCOUNT_SUBMITTED_FOR_REVIEW, 'success');
                    $this->logBlockedAccesses('create_account', $_POST['email_address']);
                    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
                }
                break;

            case 'NOTIFY_PROCESS_3RD_PARTY_LOGINS':
                // -----
                // If the login page's header processing has already determined that the access is not
                // authorized, perform a "quick return" (to prevent PHP notices generated for possibly
                // missing values and let that header processing perform its normal processing.
                //
                if ($p3 === false) {
                    return;
                }
                
                if ($this->isEmailAddressBlocked($_POST['email_address']) || $this->isAccessBlocked()) {
                    $this->logBlockedAccesses('login', $_POST['email_address']);
                    $p3 = false;
                }
                break;
                
            // -----
            // If the current IP-based access is blocked, don't offer the OPC's guest-checkout.
            //
            case 'NOTIFY_OPC_GUEST_CHECKOUT_OVERRIDE':
                if ($this->isAccessBlocked()) {
                    $p2 = false;
                    $this->logBlockedAccesses('guest_checkout', 'n/a');
                }
                break;
                
            default:
                break;
        }
    }
    
    protected function isAccessBlocked()
    {
        if (!isset($_SESSION['access_blocked'])) {
            if (empty($_SERVER['REMOTE_ADDR']) || $_SERVER['REMOTE_ADDR'] == '.') {
                $_SESSION['blocked_message'] = 'Remote address not set. ';
                $_SESSION['access_blocked'] = true;
            } elseif ($this->isIpBlocked($_SERVER['REMOTE_ADDR'])) {
                $_SESSION['blocked_message'] = $this->blocked_message;
                $_SESSION['access_blocked'] = true;
            } else {
                $access_blocked = false;
                if (ACCESSBLOCK_IPDATA_API_KEY != '') {
                    if (!class_exists('ipData')) {
                        require DIR_WS_CLASSES . 'ipData.php';
                    }
                    $ipData = new ipData(ACCESSBLOCK_IPDATA_API_KEY, $_SERVER['REMOTE_ADDR']);
                    
                    $access_blocked = $ipData->isIpThreat();
                    $this->blocked_message = 'IP address is identified as a threat by ipdata.co. ';
                    
                    if (!$access_blocked) {
                        $ip_country = $ipData->getIpCountry();
                        if ($ip_country !== false && ACCESSBLOCK_BLOCKED_COUNTRIES !== '') {
                            $blocked_countries = explode(',', str_replace($this->chars_to_remove, '', strtoupper(ACCESSBLOCK_BLOCKED_COUNTRIES)));
                            
                            $access_blocked = in_array($ip_country, $blocked_countries);
                            if ($access_blocked) {
                                $this->blocked_message = "Access blocked by IP-based country ($ip_country). ";
                            }
                        }
                        
                        $ip_organization = $ipData->getIpOrganization();
                        if ($ip_organization !== false && ACCESSBLOCK_BLOCKED_ORGS !== '') {
                            $blocked_orgs = explode(',', str_replace($this->chars_to_remove, '', ACCESSBLOCK_BLOCKED_ORGS));
                            
                            foreach ($blocked_orgs as $next_org) {
                                if (stripos($ip_organization, $next_org) !== false) {
                                    $this->blocked_message .= "Access blocked by IP-based organization ($next_org). ";
                                    $access_blocked = true;
                                    break;
                                }
                            }
                        }
                    }
                }
                if ($access_blocked) {
                    $_SESSION['blocked_message'] = $this->blocked_message;
                    $_SESSION['access_blocked'] = true;
                }
            }
        }
        return isset($_SESSION['access_blocked']);
    }
    
    protected function isIpBlocked($remote_addr)
    {
        $ip_blocked = false;
        if (ACCESSBLOCK_BLOCKED_IPS != '') {
            $blocked_ips = explode(',', str_replace($this->chars_to_remove, '', ACCESSBLOCK_BLOCKED_IPS));
            $remote_addr = (string)$remote_addr;
            foreach ($blocked_ips as $ip_address) {
                if (strpos($remote_addr, $ip_address) === 0) {
                    $this->blocked_message .= "Remote address is blocked by configuration ($ip_address). ";
                    $ip_blocked = true;
                    break;
                }
            }
        }
        return $ip_blocked;
    }
    
    protected function isEmailAddressBlocked($email_address)
    {
        $email_blocked = false;
        if (ACCESSBLOCK_BLOCKED_HOSTS != '') {
            if (empty($_SESSION['customers_host_address'])) {
                $email_host_address = (SESSION_IP_TO_HOST_ADDRESS == 'true') ? @gethostbyaddr($_SERVER['REMOTE_ADDR']) : '';
            } else {
                $email_host_address = $_SESSION['customers_host_address'];
            }
            
            $blocked_hosts = explode(',', str_replace($this->chars_to_remove, '', ACCESSBLOCK_BLOCKED_HOSTS));
            $email_host_address = (string)$email_host_address;
            foreach ($blocked_hosts as $current_host) {
                if (stripos($email_host_address, $current_host) !== false) {
                    $this->blocked_message .= "Access blocked due to IP host address ($current_host). ";
                    $email_blocked = true;
                    break;
                }
            }
        }
        
        if (ACCESSBLOCK_BLOCKED_EMAILS != '') {
            $email_address = (string)$email_address;
            $blocked_emails = explode(',', str_replace($this->chars_to_remove, '', ACCESSBLOCK_BLOCKED_EMAILS));
            foreach ($blocked_emails as $current_email) {
                if (stripos($email_address, $current_email) !== false) {
                    $this->blocked_message .= "Access blocked by email address ($email_address). ";
                    $email_blocked = true;
                    break;
                }
            }
        }
        return $email_blocked;
    }
    
    protected function isContentBlocked($enquiry)
    {
        $content_blocked = false;
        if (ACCESSBLOCK_BLOCKED_PHRASES != '') {
            $enquiry = (string)$enquiry;
            $blocked_phrases = explode(',', str_replace($this->chars_to_remove, '', ACCESSBLOCK_BLOCKED_PHRASES));
            foreach ($blocked_phrases as $current_phrase) {
                if (stripos($enquiry, $current_phrase) !== false) {
                    $this->blocked_message .= "Access blocked by message content ($current_phrase): $enquiry. ";
                    $content_blocked = true;
                    break;
                }
            }
        }
        return $content_blocked;
    }
    
    protected function isCompanyBlocked()
    {
        $company_blocked = false;
        if (!empty($_POST['company']) && defined('ACCESSBLOCK_BLOCKED_COMPANIES') && !empty(ACCESSBLOCK_BLOCKED_COMPANIES)) {
            $blocked_companies = explode(',', str_replace($this->chars_to_remove, '', ACCESSBLOCK_BLOCKED_COMPANIES));
            $create_account_company = strtolower($_POST['company']);
            foreach ($blocked_companies as $current_company) {
                if (stripos($create_account_company, $current_company) !== false) {
                    $this->blocked_message .= "Access blocked by entered company ($current_company): $create_account_company. ";
                    $company_blocked = true;
                    break;
                }
            }
        }
        return $company_blocked;
    }
    
    protected function logBlockedAccesses($blocked_page, $email_address)
    {
        if ($this->debug) {
            $ip_address = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : 'not provided';
            $blocked_session_message = (isset($_SESSION['blocked_message']) && $_SESSION['blocked_message'] != $this->blocked_message) ? $_SESSION['blocked_message'] : '';
            $message = date('Y-m-d H:i:s') . ": Access blocked on $blocked_page page for IP Address ($ip_address) and/or email ($email_address). " . $blocked_session_message . $this->blocked_message;
            error_log($message . PHP_EOL, 3, $this->logfile);
        }
    }
}
