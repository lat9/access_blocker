<?php
require 'includes/application_top.php';

$blocked_ips = explode(',', str_replace(' ', '', ACCESSBLOCK_BLOCKED_IPS));
$blocked_ips = array_unique($blocked_ips);

$blocked_accesses = file(DIR_FS_LOGS . '/accesses_blocked_2020_02.log');
$ips_handled = array();
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
                if (!$blocked) {
                    echo $ip_address . ' is not currently being blocked.<br>';
                    $blocked_ips[] = $ip_address;
                }
            }
            $ips_handled[$ip_address] = array(
                'count' => 0,
            );
        }
        $ips_handled[$ip_address]['count']++;
    }
}

// -----
// Remove duplicates from the blocked-ip list, then sort the addresses for output.
//
$blocked_ips = array_unique($blocked_ips);
natsort($blocked_ips);
echo implode(', ', $blocked_ips);

error_log(implode(PHP_EOL, $blocked_ips), 3, DIR_FS_LOGS . '/accesses_blocked_update_' . date('Y-m-d-H-I-s') . '.log');

// -----
// Display a table showing which IPs were blocked (and how often).
//
uksort($ips_handled, 'strnatcmp');
?>
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
