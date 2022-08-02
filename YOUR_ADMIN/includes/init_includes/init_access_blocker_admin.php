<?php
// -----
// Part of the Access Blocker plugin, created by lat9 (https://vinosdefrutastropicales.com)
// Copyright (c) 2019-2022, Vinos de Frutas Tropicales.
//
define('ACCESSBLOCK_CURRENT_VERSION', '1.5.0-beta1');
define('ACCESSBLOCK_LAST_UPDATE_DATE', '2022-08-02');

// -----
// Wait until an admin is logged in before installing or updating ...
//
if (!isset($_SESSION['admin_id'])) {
    return;
}

// -----
// Determine the configuration-group id to use for the plugin's settings, creating that
// group if it's not currently present.
//
$configurationGroupTitle = 'Access Blocker';
$configuration = $db->Execute(
    "SELECT configuration_group_id 
       FROM " . TABLE_CONFIGURATION_GROUP . " 
      WHERE configuration_group_title = '$configurationGroupTitle' 
      LIMIT 1"
);
if ($configuration->EOF) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION_GROUP . " 
            (configuration_group_title, configuration_group_description, sort_order, visible) 
         VALUES 
            ('$configurationGroupTitle', '$configurationGroupTitle', '1', '1');"
    );
    $cgi = $db->Insert_ID(); 
    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION_GROUP . " 
            SET sort_order = $cgi 
          WHERE configuration_group_id = $cgi
          LIMIT 1"
    );
} else {
    $cgi = $configuration->fields['configuration_group_id'];
}

// -----
// If the plugin's configuration settings aren't present, add them now.
//
if (!defined('ACCESSBLOCK_VERSION')) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function)
         VALUES
            ('Plugin Version', 'ACCESSBLOCK_VERSION', '0.0.0', 'The <em>Access Blocker</em> installed version.', $cgi, now(), 1, NULL, 'trim('),

            ('Enable Access Blocker?', 'ACCESSBLOCK_ENABLED', 'false', 'When enabled, the plugin blocks unwanted accesses to your store\'s <code>contact_us</code>, <code>create_account</code> and <code>login</code> pages, based on &quot;threats&quot; identified by the ipdata.co service and/or additional elements identified below.<br /><br />Default: <b>false</b>', $cgi, now(), 5, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),

            ('ipData Service: API Key', 'ACCESSBLOCK_IPDATA_API_KEY', '', 'Enter the API key you received from the <a href=\"https://ipdata.co/registration.html\" target=\"_blank\" rel=\"noreferrer\">ipData</a> service.  Leave the setting empty if no ipdata.co information should be used.<br />', $cgi, now(), 10, NULL, NULL),

            ('Block by: Country', 'ACCESSBLOCK_BLOCKED_COUNTRIES', '', 'Enter, using a comma-separated list, the 2-character ISO country-codes for any countries to be blocked.  All IP addresses originating in these countries will be blocked.<br /><br /><b>Note:</b> This setting does not apply if the <em>ipData Service: API Key</em> is not set.', $cgi, now(), 15, NULL, NULL),

            ('Block by: Organization', 'ACCESSBLOCK_BLOCKED_ORGS', '', 'Enter, using a comma-separated list, any &quot;organizations&quot; (based on the <code>ipData</code> response) to be blocked.  If the organization associated with an IP address <em>contains</em> any of the strings entered here, the access will be blocked.<br /><br /><b>Note:</b> This setting does not apply if the <em>ipData Service: API Key</em> is not set.', $cgi, now(), 20, NULL, NULL),

            ('Block by: IP Address', 'ACCESSBLOCK_BLOCKED_IPS', '', 'Enter, using a comma-separated list, any <em>specific</em> IP addresses to block.  If you enter only the upper segments of an IP address, e.g. <code>192.168.1.</code>, all matching IP addresses, e.g. <code>192.168.1.0-192.168.1.255</code> will be blocked.', $cgi, now(), 30, NULL, NULL),

            ('Block by: Host Address', 'ACCESSBLOCK_BLOCKED_HOSTS', '', 'Enter, using a comma-separated list, any &quot;host addresses&quot; to block.  If the host-address that originates the IP address <em>contains</em> any of the strings entered here, the access will be blocked.', $cgi, now(), 40, NULL, NULL),

            ('Block by: Email Address', 'ACCESSBLOCK_BLOCKED_EMAILS', '', 'Enter, using a comma-separated list, any &quot;email addresses&quot; to block.  If the email-address entered <em>contains</em> any of the strings entered here, the access will be blocked.<br /><br />You can block accesses for a specific email address (<code>joe@example.com</code>) or for an entire email domain (<code>@example.com</code>).', $cgi, now(), 50, NULL, NULL),

            ('Block by: Message Keywords', 'ACCESSBLOCK_BLOCKED_PHRASES', '', 'Enter, using a comma-separated list, any words in a <code>contact_us</code> message that should result in a block.  If the message contains any of the words entered here, the associated <em>contact-us</em> email will not be sent.', $cgi, now(), 60, NULL, NULL),

            ('Enable Debug?', 'ACCESSBLOCK_DEBUG', 'false', 'When enabled, the plugin creates a monthly log, <code>/logs/accesses_blocked_YYYY_mm.log</code>, of the accesses denied by the plugin.', $cgi, now(), 499, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
    );

    // -----
    // Register the plugin's configuration page for the admin menus.
    //
    zen_register_admin_page('configAccessBlocker', 'BOX_ACCESSBLOCK_NAME', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y');

    // -----
    // Let the logged-in admin know that the plugin's been installed.
    //
    define('ACCESSBLOCK_VERSION', '0.0.0');
    $messageStack->add(sprintf(ACCESSBLOCK_INSTALL_SUCCESS, ACCESSBLOCK_CURRENT_VERSION), 'success');
}

// -----
// Update the plugin's version and release date (saved as last_modified), if the version has changed.
//
if (ACCESSBLOCK_VERSION !== ACCESSBLOCK_CURRENT_VERSION) {
    switch (true) {
        case version_compare(ACCESSBLOCK_VERSION, '1.0.1', '<'):
            $db->Execute(
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET set_function = 'zen_cfg_textarea('
                  WHERE configuration_key IN ('ACCESSBLOCK_BLOCKED_ORGS', 'ACCESSBLOCK_BLOCKED_IPS', 'ACCESSBLOCK_BLOCKED_HOSTS', 'ACCESSBLOCK_BLOCKED_EMAILS', 'ACCESSBLOCK_BLOCKED_PHRASES')"
            );
        case version_compare(ACCESSBLOCK_VERSION, '1.1.0', '<'):    //- Fall-through from above processing
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function)
                 VALUES
                    ('Block by: Create-account Company', 'ACCESSBLOCK_BLOCKED_COMPANIES', '', 'Enter, using a comma-separated list, any <em>Company</em> entries to be blocked from creating an account.  If the company value entered on the <code>create_account</code> page <em>contains</em> any of the strings entered here, the account-creation will be blocked.', $cgi, now(), 70, NULL, 'zen_cfg_textarea(')"
            );
        case version_compare(ACCESSBLOCK_VERSION, '1.4.0', '<'):    //- Fall-through from above processing
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function)
                 VALUES
                    ('IP Address: Whitelist', 'ACCESSBLOCK_WHITELISTED_IPS', '', 'Enter, using a comma-separated list, any <em>specific</em> IP addresses to <em>unconditionally enable</em>.  If you enter only the upper segments of an IP address, e.g. <code>192.168.1.</code>, all matching IP addresses, e.g. <code>192.168.1.0-192.168.1.255</code> will be not be blocked, even if they are identified as a thread by ipdata.co.', $cgi, now(), 31, NULL, 'zen_cfg_textarea('),

                    ('Email Address: Whitelist', 'ACCESSBLOCK_WHITELISTED_EMAILS', '', 'Enter, using a comma-separated list, any &quot;email addresses&quot; to <em>unconditionally enable</em>.  If the email-address entered <em>contains</em> any of the strings entered here, the access will be <em>not be</em> blocked.<br><br>You can enable accesses for a specific email address (<code>joe@example.com</code>) or for an entire email domain (<code>@example.com</code>).', $cgi, now(), 51, NULL, 'zen_cfg_textarea(')"
            );
        case version_compare(ACCESSBLOCK_VERSION, '1.5.0', '<'):    //- Fall-through from above processing
            $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET set_function = 'zen_cfg_read_only('
              WHERE configuration_key = 'ACCESSBLOCK_VERSION'
              LIMIT 1"
            );
        default:                                                    //- Fall-through from above processing
            break;
    }

    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION . "
            SET configuration_value = '" . ACCESSBLOCK_CURRENT_VERSION . "',
                last_modified = '" . ACCESSBLOCK_LAST_UPDATE_DATE . " 00:00:00'
          WHERE configuration_key = 'ACCESSBLOCK_VERSION'
          LIMIT 1"
    );
    if (ACCESSBLOCK_VERSION !== '0.0.0') {
        $messageStack->add(sprintf(ACCESSBLOCK_UPDATE_SUCCESS, ACCESSBLOCK_VERSION, ACCESSBLOCK_CURRENT_VERSION), 'success');
    }
}
