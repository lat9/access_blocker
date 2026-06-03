<?php
// -----
// Part of the Access Blocker plugin, created by lat9 (https://vinosdefrutastropicales.com)
// Copyright (c) 2019-2024, Vinos de Frutas Tropicales.
//
// This developer tool inspects a given logs/accesses_blocked_YYYY_MM.log to see if additional
// "threat" IP addresses have been blocked, over-and-above those currently present in the site's
// "Blocked IPs" list.
//
// Usage:
//
// 1. Sign into the Zen Cart admin.
// 2. Hand-enter the URL in the browser's address: https://mysite.com/admin/blocked_accesses.php?suffix=YYYY_MM,
//    where YYYY_MM is the 'suffix' on the existing log to inspect.
//
// The tool gathers all IP addresses that were found to be a threat but aren't currently registered in the
// Access Blocker's "Block by: IP Address" list. Upon completion, the tool creates a file named
// logs/blocked_accesses_update_YYYY-MM-DD-HH-MM-SS.log that combines the IP threats found and those currently
// configured.  The contents of that updated log is suitable to copy/paste into the "Block by: IP Address" setting.
//
require 'includes/application_top.php';

$blocked_ips = explode(',', str_replace(' ', '', ACCESSBLOCK_BLOCKED_IPS));
$blocked_ips = array_unique($blocked_ips);

if (empty($_GET['suffix'])) {
    echo 'Please supply the desired suffix.<br>';
    require DIR_WS_INCLUDES . 'application_bottom.php';
    die();
}

// should be in the format YYYY_mm
$logfile_name = DIR_FS_LOGS . '/accesses_blocked_' . $_GET['suffix'] . '.log';
if (!is_file($logfile_name)) {
    echo 'The specified file was not found.';
    require DIR_WS_INCLUDES . 'application_bottom.php';
    die();
}

$blocked_accesses = file($logfile_name);
$ips_handled = [];
foreach ($blocked_accesses as $current) {
    // -----
    // Look for content enclosed by parentheses, e.g. (xx).  The first one is the IP address
    // for which the access was blocked.
    //
    if (preg_match('/IP Address \(([^)]+)\)/', $current, $matches)) {
        $ip_address = $matches[1];
        if (!array_key_exists($ip_address, $ips_handled)) {
            if (!in_array($ip_address, $blocked_ips)) {
                $blocked = false;
                foreach ($blocked_ips as $blocked_ip) {
                    if (strpos($ip_address, $blocked_ip) === 0) {
                        $blocked = true;
                        break;
                    }
                }
                if ($blocked === false && !in_array($ip_address, $blocked_ips)) {
                    echo $ip_address . ' is not currently being blocked.<br>';
                    $blocked_ips[] = $ip_address;
                }
            }
            $ips_handled[$ip_address] = [
                'count' => 0,
            ];
        }
        $ips_handled[$ip_address]['count']++;
    }
}

// -----
// Sort the addresses for output.
//
natsort($blocked_ips);
echo '<br><br><b>Updated IPS to block:</b><br>' . implode(', ', $blocked_ips);

error_log(implode(', ', $blocked_ips), 3, DIR_FS_LOGS . '/accesses_blocked_update_' . date('Y-m-d-H-I-s') . '.log');

// -----
// Display a table showing which IPs were blocked (and how often).
//
uksort($ips_handled, 'strnatcmp');
?>
<br>
<br>
<table>
    <tr>
        <th>IP Address</th>
        <th>Access Count</th>
    </tr>
<?php
foreach ($ips_handled as $ip => $ip_info) {
?>
    <tr>
        <td><?php echo $ip; ?></td>
        <td><?php echo $ip_info['count']; ?></td>
    </tr>
<?php
}
?>
</table>
<?php

require DIR_WS_INCLUDES . 'application_bottom.php';
