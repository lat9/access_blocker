# Access Blocker for Zen Cart v1.5.5 and Later, v1.5.2

Download from Zen Cart Plugins: https://www.zen-cart.com/downloads.php?do=file&id=2237

This drop-in plugin provides your store with admin-level controls to block (or limit) actions provided by the contact_us, create_account and login pages and/or limit access to the guest-checkout provided by [One-Page Checkout](https://vinosdefrutastropicales.com/index.php?main_page=product_info&cPath=2_7&products_id=69). If access is blocked via admin-level configuration:

1. **contact_us**. The message appears to be sent, but no email is actually generated.
1. **ask_a_question**.  For *zc157 and later*, the message appears to be sent, but no email is actually generated.
1. **create_account**. An account appears to have been created, but it's not (and no emails are sent). The message (defined in the plugin's message-file) is displayed to give the illusion of an account having been created.
1. **login**. The login is denied, with the "standard" _Error: Sorry, there is no match for that email address and/or password._ message being displayed.
1. **guest-checkout**.  Guest checkout is disabled, so it is not offered as a choice on the `login` page.  Any active guest checkout on a blocked IP address reverts to the 3-page version.

Some features of the plugin require that you request a free API key from the [ipdata.co](https://ipdata.co) service. That service identifies "known" threats, based on a supplied IP address — Access Blocker makes that request based on the IP address used to access your site.

**Note**: Starting with v1.5.0, you can configure *Access Blocker* to issue an `HTTP 410 (Gone)` response on all follow-on accesses to the site where a threat is detected.

