DELETE FROM admin_pages WHERE page_key = 'configAccessBlocker' LIMIT 1;
DELETE FROM configuration WHERE configuration_key LIKE 'ACCESSBLOCK%';
DELETE FROM configuration_group WHERE configuration_group_title = 'Access Blocker' LIMIT 1;