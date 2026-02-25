<?php
// -----
// Admin-level installation script for the "encapsulated" Access Blocker plugin for Zen Cart, by lat9.
// Copyright (C) 2019-2026, Vinos de Frutas Tropicales.
//
// Last updated: v2.0.2
//
use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    private string $configGroupTitle = 'Access Blocker';

    protected function executeInstall()
    {
        if (!$this->purgeOldFiles()) {
            return false;
        }

        // -----
        // First, determine the configuration-group-id and install the settings.
        //
        $cgi = $this->getOrCreateConfigGroupId(
            $this->configGroupTitle,
            $this->configGroupTitle . ' Settings'
        );

        $sql =
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function)
             VALUES
                ('Enable Access Blocker?', 'ACCESSBLOCK_ENABLED', 'false', 'When enabled, the plugin blocks unwanted accesses to your store\'s <code>ask_a_question</code>, <code>contact_us</code>, <code>create_account</code> and <code>login</code> pages, based on &quot;threats&quot; identified by the ipdata.co service and/or additional elements identified below.<br><br>Default: <b>false</b>', $cgi, now(), 5, NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

                ('ipData Service: API Key', 'ACCESSBLOCK_IPDATA_API_KEY', '', 'Enter the API key you received from the <a href=\"https://ipdata.co/registration.html\" target=\"_blank\" rel=\"noreferrer\">ipData</a> service.  Leave the setting empty if no ipdata.co information should be used.<br>', $cgi, now(), 10, NULL, NULL),

                ('Use ipdata.co EU Endpoint?', 'ACCESSBLOCK_USE_EU_ENDPOINT', 'false', '<br>Indicate whether or not the ipdata.co EU endpoint should be used for threat requests.  If set to <em>true</em>, a dedicated EU endpoint is used to ensure that the end user data you send us stays in the EU.<br><br>Default: <b>false</b>', $cgi, now(), 11, NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

                ('Totally restrict access on threats?', 'ACCESSBLOCK_RESTRICT_THREAT_ACCESS', 'false', '<br>Indicate whether or not <em>Access Blocker</em> should <b>totally</b> restrict access by forcing an &quot;HTTP 410 (Gone)&quot; if a threat is detected.<br><br>Default: <b>false</b>', $cgi, now(), 12, NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

                ('Block by: Country', 'ACCESSBLOCK_BLOCKED_COUNTRIES', '', 'Enter, using a comma-separated list, the 2-character ISO country-codes for any countries to be blocked.  All IP addresses originating in these countries will be blocked.<br><br><b>Note:</b> This setting does not apply if the <em>ipData Service: API Key</em> is not set.', $cgi, now(), 15, NULL, NULL),

                ('Block by: Organization', 'ACCESSBLOCK_BLOCKED_ORGS', '', 'Enter, using a comma-separated list, any &quot;organizations&quot; (based on the <code>ipData</code> response) to be blocked.  If the organization associated with an IP address <em>contains</em> any of the strings entered here, the access will be blocked.<br><br><b>Note:</b> This setting does not apply if the <em>ipData Service: API Key</em> is not set.', $cgi, now(), 20, NULL, 'zen_cfg_textarea('),

                ('Block by: IP Address', 'ACCESSBLOCK_BLOCKED_IPS', '', 'Enter, using a comma-separated list, any <em>specific</em> IP addresses to block.  If you enter only the upper segments of an IP address, e.g. <code>192.168.1.</code>, all matching IP addresses, e.g. <code>192.168.1.0-192.168.1.255</code> will be blocked.', $cgi, now(), 30, NULL, 'zen_cfg_textarea('),

                ('IP Address: Whitelist', 'ACCESSBLOCK_WHITELISTED_IPS', '', 'Enter, using a comma-separated list, any <em>specific</em> IP addresses to <em>unconditionally enable</em>.  If you enter only the upper segments of an IP address, e.g. <code>192.168.1.</code>, all matching IP addresses, e.g. <code>192.168.1.0-192.168.1.255</code> will be not be blocked, even if they are identified as a thread by ipdata.co.', $cgi, now(), 31, NULL, 'zen_cfg_textarea('),

                ('Block by: Host Address', 'ACCESSBLOCK_BLOCKED_HOSTS', '', 'Enter, using a comma-separated list, any &quot;host addresses&quot; to block.  If the host-address that originates the IP address <em>contains</em> any of the strings entered here, the access will be blocked.', $cgi, now(), 40, NULL, 'zen_cfg_textarea('),

                ('Block by: Email Address', 'ACCESSBLOCK_BLOCKED_EMAILS', '', 'Enter, using a comma-separated list, any &quot;email addresses&quot; to block.  If the email-address entered <em>contains</em> any of the strings entered here, the access will be blocked.<br><br>You can block accesses for a specific email address (<code>joe@example.com</code>) or for an entire email domain (<code>@example.com</code>).', $cgi, now(), 50, NULL, 'zen_cfg_textarea('),

                ('Email Address: Whitelist', 'ACCESSBLOCK_WHITELISTED_EMAILS', '', 'Enter, using a comma-separated list, any &quot;email addresses&quot; to <em>unconditionally enable</em>.  If the email-address entered <em>contains</em> any of the strings entered here, the access will be <em>not be</em> blocked.<br><br>You can enable accesses for a specific email address (<code>joe@example.com</code>) or for an entire email domain (<code>@example.com</code>).', $cgi, now(), 51, NULL, 'zen_cfg_textarea('),

                ('Block by: Message Keywords', 'ACCESSBLOCK_BLOCKED_PHRASES', '', 'Enter, using a comma-separated list, any words in a <code>contact_us</code> message that should result in a block.  If the message contains any of the words entered here, the associated <em>contact-us</em> email will not be sent.', $cgi, now(), 60, NULL, 'zen_cfg_textarea('),

                ('Block by: Create-account Company', 'ACCESSBLOCK_BLOCKED_COMPANIES', '', 'Enter, using a comma-separated list, any <em>Company</em> entries to be blocked from creating an account.  If the company value entered on the <code>create_account</code> page <em>contains</em> any of the strings entered here, the account-creation will be blocked.', $cgi, now(), 70, NULL, 'zen_cfg_textarea('),

                ('Enable Debug?', 'ACCESSBLOCK_DEBUG', 'false', 'When enabled, the plugin creates a monthly log, <code>/logs/accesses_blocked_YYYY_mm.log</code>, of the accesses denied by the plugin.', $cgi, now(), 499, NULL, 'zen_cfg_select_option([\'true\', \'false\'],')";
        $this->executeInstallerSql($sql);

        // -----
        // Configuration updates for earlier, non-encapsulated versions.
        //
        $this->executeInstallerSql(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET set_function = 'zen_cfg_textarea('
              WHERE configuration_key IN ('ACCESSBLOCK_BLOCKED_ORGS', 'ACCESSBLOCK_BLOCKED_IPS', 'ACCESSBLOCK_BLOCKED_HOSTS', 'ACCESSBLOCK_BLOCKED_EMAILS', 'ACCESSBLOCK_BLOCKED_PHRASES')"
        );
        $this->executeInstallerSql(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_description = 'When enabled, the plugin blocks unwanted accesses to your store\'s <code>ask_a_question</code>, <code>contact_us</code>, <code>create_account</code> and <code>login</code> pages, based on &quot;threats&quot; identified by the ipdata.co service and/or additional elements identified below.<br><br>Default: <b>false</b>'
              WHERE configuration_key = 'ACCESSBLOCK_ENABLED'
              LIMIT 1"
        );
        $this->executeInstallerSql(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key = 'ACCESSBLOCK_VERSION'
              LIMIT 1"
        );

        // -----
        // Register the plugin's configuration page for the admin menus.
        //
        if (!zen_page_key_exists('configAccessBlocker')) {
            zen_register_admin_page('configAccessBlocker', 'BOX_ACCESSBLOCK_NAME', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y');
        }

        return true;
    }

    // -----
    // Note: These (https://github.com/zencart/zencart/pull/6498 and https://github.com/zencart/zencart/pull/6456) Zen Cart PRs must
    // be present in the base code or a PHP Fatal error is generated due to the
    // function signature difference and/or missing method.
    //
    protected function executeUpgrade($oldVersion)
    {
        // -----
        // Retrieve the configuration-group information for the Access Blocker settings.
        //
        $cgi = $this->getOrCreateConfigGroupId(
            $this->configGroupTitle,
            $this->configGroupTitle . ' Settings'
        );

        // -----
        // Continue with the upgrade ...
        //
        // v2.0.2+: Restore the ACCESSBLOCK_BLOCKED_COMPANIES setting.
        //
        $this->executeInstallerSql(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function)
             VALUES
                ('Block by: Create-account Company', 'ACCESSBLOCK_BLOCKED_COMPANIES', '', 'Enter, using a comma-separated list, any <em>Company</em> entries to be blocked from creating an account.  If the company value entered on the <code>create_account</code> page <em>contains</em> any of the strings entered here, the account-creation will be blocked.', $cgi, now(), 70, NULL, 'zen_cfg_textarea(')"
        );
    }

    protected function executeUninstall()
    {
        zen_deregister_admin_pages([
            'configAccessBlocker',
        ]);

        $this->deleteConfigurationGroup($this->configGroupTitle, true);
    }

    protected function purgeOldFiles(): bool
    {
        // -----
        // First, look for and remove the non-encapsulated versions' admin-directory
        // files.
        //
        $files_to_check = [
            '' => [
                'blocked_accesses.php',
            ],
            'includes/auto_loaders/' => [
                'config.access_blocker_admin.php',
            ],
            'includes/init_includes/' => [
                'init_access_blocker_admin.php',
            ],
            'includes/languages/english/' => [
                'extra_definitions/access_blocker_admin_names.php',
            ],
        ];

        $errorOccurred = false;
        foreach ($files_to_check as $dir => $files) {
            $current_dir = DIR_FS_ADMIN . $dir;
            foreach ($files as $next_file) {
                $current_file = $current_dir . $next_file;
                if (file_exists($current_file)) {
                    $result = unlink($current_file);
                    if (!$result && file_exists($current_file)) {
                        $errorOccurred = true;
                        $this->errorContainer->addError(
                            0,
                            sprintf(ERROR_UNABLE_TO_DELETE_FILE, $current_file),
                            false,
                            // this str_replace has to do DIR_FS_ADMIN before CATALOG because catalog is contained within admin, so results are wrong.
                            // also, '[admin_directory]' is used to obfuscate the admin dir name, in case the user copy/pastes output to a public forum for help.
                            sprintf(ERROR_UNABLE_TO_DELETE_FILE, str_replace([DIR_FS_ADMIN, DIR_FS_CATALOG], ['[admin_directory]/', ''], $current_file))
                        );
                    }
                }
            }
        }

        // -----
        // Next, locate and attempt to remove the storefront files.
        //
        $files_to_check = [
            'includes/classes/' => [
                'ipData.php',
                'observers/auto.access_blocker.php',
            ],
            'includes/languages/english/extra_definitions/' => [
                'access_blocker_messages.php',
            ],
        ];
        foreach ($files_to_check as $dir => $files) {
            $current_dir = DIR_FS_CATALOG . $dir;
            foreach ($files as $next_file) {
                $current_file = $current_dir . $next_file;
                if (file_exists($current_file)) {
                    $result = unlink($current_file);
                    if (!$result && file_exists($current_file)) {
                        $errorOccurred = true;
                        $this->errorContainer->addError(
                            0,
                            sprintf(ERROR_UNABLE_TO_DELETE_FILE, $current_file),
                            false,
                            // this str_replace has to do DIR_FS_ADMIN before CATALOG because catalog is contained within admin, so results are wrong.
                            // also, '[admin_directory]' is used to obfuscate the admin dir name, in case the user copy/pastes output to a public forum for help.
                            sprintf(ERROR_UNABLE_TO_DELETE_FILE, str_replace([DIR_FS_ADMIN, DIR_FS_CATALOG], ['[admin_directory]/', ''], $current_file))
                        );
                    }
                }
            }
        }

        return !$errorOccurred;
    }

    /**
     * @since ZC v2.1.0
     */
    protected function getOrCreateConfigGroupId(string $config_group_title, string $config_group_description, ?int $sort_order = 1): int
    {
        if (method_exists(get_parent_class($this), 'getOrCreateConfigGroupId')) {
            return parent::getOrCreateConfigGroupId($config_group_title, $config_group_description, $sort_order);
        }

        $config_group_title = $this->dbConn->prepare_input($config_group_title);
        $config_group_description = $this->dbConn->prepare_input($config_group_description);
        $sort_order = (int)($sort_order ?? 0);

        $sql =
            "SELECT configuration_group_id
               FROM " . TABLE_CONFIGURATION_GROUP . "
              WHERE configuration_group_title = '$config_group_title'
              LIMIT 1";
        $check = $this->executeInstallerSelectQuery($sql);
        if (!$check->EOF) {
            return (int)$check->fields['configuration_group_id'];
        }

        $sql =
            "INSERT INTO " . TABLE_CONFIGURATION_GROUP . "
                (configuration_group_title, configuration_group_description, sort_order, visible)
             VALUES
                ('$config_group_title', '$config_group_description', $sort_order, 1)";
        $this->executeInstallerSql($sql);

        $sql = "SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_title = '$config_group_title' LIMIT 1";
        $result = $this->executeInstallerSelectQuery($sql);
        $cgi = (int)$result->fields['configuration_group_id'];

        if (empty($sort_order)) {
            $sql = "UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = $cgi WHERE configuration_group_id = $cgi LIMIT 1";
            $this->executeInstallerSql($sql);
        }

        return $cgi;
    }
}
