<?php
/**
 * Copyright (C) 2017  Easify Ltd (email:support@easify.co.uk)
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
require_once ( plugin_dir_path(__FILE__) . '/easify_functions.php' );
require_once ( 'class-easify-generic-easify-server.php' );
require_once ( 'class-easify-generic-easify-server-discovery.php' );
require_once ( 'class-easify-wc-easify-options.php' );

/**
 * This class represents the Easify WooCommerce Plugin Settings page in 
 * WordPress.
 * 
 * It handles creating the various settings pages, as well as dealing with 
 * updates to the settings and validation.
 * 
 * @class       Easify_WC__Plugin_Settings_Page
 * @version     4.0
 * @package     easify-woocommerce-connector
 * @author      Easify 
 */
class Easify_WC__Plugin_Settings_Page {

    private $options; // Easify settings stored in the WordPress database
    private $order_statuses; // Order statuses imported from Easify 
    private $order_types; // Order types imported from Easify 
    private $customer_types; // Customer types imported from Easify 
    private $customer_relationships; // Customer relationships imported from Easify 
    private $payment_methods; // Payment methods imported from Easify 
    private $payment_accounts; // Payment accounts imported from Easify 
    private $isWooCommercePluginActive = false;
    private $isEasifyServerReachable = false;
    private $arePermalinksEnabled = false;
    private $easify_discovery_server;
    private $easify_server;
    private $easify_options;

    /**
     * Constructor
     * 
     * Note: This is called *every* time the WordPress admin page loads, so don't do anything
     * heavyweight in here, just keep it to initialisation of the menu wiring etc...
     * 
     * If you need to do any initialisation when the actual settings page loads, 
     * place it in create_easify_options_page().
     */
    public function __construct() {
        // add Easify options menu under WordPress settings menu and wire
        // up callback to build page...
        add_action('admin_menu', array($this, 'add_easify_plugin_page'));
    }

    // Add Easify options menu into WordPress
    public function add_easify_plugin_page() {
        // adding Easify options page under "Settings"
        add_options_page(
                'Easify', 'Easify Settings', 'manage_options', 'easify_options_page', array($this, 'create_easify_options_page')
        );

        // initialise Easify option pages
        add_action('admin_init', array($this, 'init_easify_pages'));

        // initialise tooltipster (javascript in page header)
        add_action('admin_head', array($this, 'init_easify_header'));

        // initialise Easify javascript and css
        add_action('admin_enqueue_scripts', array($this, 'init_scripts'));
    }

    /**
     * initialise Easify option pages
     */
    public function init_easify_pages() {
        $this->init_setup();
        $this->init_orders();
        $this->init_customers();
        $this->init_coupons();
        $this->init_shipping();
        $this->init_payment();
        $this->init_logging();

        // Add a filter so that we can encrypt the password as it is sent to the DB
        add_filter('pre_update_option_easify_password', array($this, 'easify_update_field_easify_password'), 10, 1);
    }

    /**
     * access Easify REST API for uncached entities
     */
    private function get_easify_web_service_data($web_service_method) {

        // don't get value if we already have it in cache 
        if (isset($this->{ $web_service_method }) || !empty($this->{ $web_service_method }))
            return;

        try {

            switch ($web_service_method) {
                case 'order_statuses':
                    $return_value = $this->easify_server->GetEasifyOrderStatuses();
                    break;

                case 'order_types':
                    $return_value = $this->easify_server->GetEasifyOrderTypes();
                    break;

                case 'customer_types':
                    $return_value = $this->easify_server->GetEasifyCustomerTypes();
                    break;

                case 'customer_relationships':
                    $return_value = $this->easify_server->GetEasifyCustomerRelationships();
                    break;

                case 'payment_methods':
                    $return_value = $this->easify_server->GetEasifyPaymentMethods();
                    break;

                case 'payment_accounts':
                    $return_value = $this->easify_server->GetEasifyPaymentAccounts();
                    break;

                case 'tax_rates':
                    //TODO: Has dependency on WordPress - can't port immediately...
                    $return_value = $this->easify_server->GetEasifyTaxRates();
                    break;
            }

            // return false if we didn't get anything back
            if (empty($return_value))
                return false;

            // cache value which reduces trips to the Easify REST API
            $this->{ $web_service_method } = $return_value;
        } catch (Exception $ex) {

            Easify_Logging::Log("get_easify_web_service_data error: " . $ex->getMessage());
            return false;
        }
    }

    /**
     * create Easify options page tabs
     */
    private function create_easify_options_tabs($current = 'setup') {       
        // a list of the Easify option pages 
        $tabs = array(
            'setup' => 'Credentials',
            'orders' => 'Orders',
            'customers' => 'Customers',
            'coupons' => 'Coupons',
            'shipping' => 'Shipping',
            'payment' => 'Payment',
            'logging' => 'Logging'
        );

        // Only display configuration warnings once Easify Server is reachable...
        if ($this->isEasifyServerReachable) {
            // Warn user if no shipping options have been configured...
            if (!$this->easify_options->are_shipping_skus_present()) {
                $this->display_shipping_options_warning();
            }

            // Warn user if no coupon options have been configured...
            if (!$this->easify_options->is_discount_sku_present()) {
                $this->display_coupons_options_warning();
            }
        }


        // Easify icon and start tab wrapper
        ?>        
        <div id="icon-themes" class="easify_icon">
            <a class="easify_link" target="_blank" href="http://www.easify.co.uk/">&nbsp;</a>
        </div>
        <h2 class="nav-tab-wrapper">
            <?php
            // check if we can communicate with Easify web services
            if (!$this->isEasifyServerReachable) {
                // only display Credentials and logging tab if we do not have comms
                $class = ( 'setup' == $current ) ? ' nav-tab-active' : '';
                echo "<a class='nav-tab$class' href='?page=easify_options_page&tab=setup'>Setup</a>";
                $class = ( 'logging' == $current ) ? ' nav-tab-active' : '';
                echo "<a class='nav-tab$class' href='?page=easify_options_page&tab=logging'>Logging</a>";
            } else {
                // create each tab
                foreach ($tabs as $tab => $name) {
                    // if tab represents current page, highlight it
                    $class = ( $tab == $current ) ? ' nav-tab-active' : '';

                    // Display warning on shipping tab if no shipping mappings created yet
                    if (strtolower($name) == 'shipping') {
                        // For Shipping tab - display warning if shipping options not set...
                        if (!$this->easify_options->are_shipping_skus_present()) {
                            echo "<a class='nav-tab$class' href='?page=easify_options_page&tab=$tab'>$name&nbsp;<img src='"
                            . plugin_dir_url(__FILE__) . "../assets/images/warning.png' title='Shipping options not configured!'/></a>";
                        } else {
                            echo "<a class='nav-tab$class' href='?page=easify_options_page&tab=$tab'>$name</a>";
                        }
                    } elseif  (strtolower($name) == 'coupons') {
                        // For coupons tab - display warning if coupon options not set...
                        if (!$this->easify_options->is_discount_sku_present()) {
                            echo "<a class='nav-tab$class' href='?page=easify_options_page&tab=$tab'>$name&nbsp;<img src='"
                            . plugin_dir_url(__FILE__) . "../assets/images/warning.png' title='Coupon options not configured!'/></a>";
                        } else {
                            echo "<a class='nav-tab$class' href='?page=easify_options_page&tab=$tab'>$name</a>";
                        }                        
                    } else {
                        echo "<a class='nav-tab$class' href='?page=easify_options_page&tab=$tab'>$name</a>";
                    }
                }
            }

            // close wrapper
            echo '</h2></div>';
        }

        private function display_shipping_options_warning() {
            ?>
            <div>
                <h3>
                    <img src='<?= plugin_dir_url(__FILE__) ?>../assets/images/warning.png' title='Shipping options not configured!'/>
                    Shipping options not yet configured.
                </h3>
                <p>
                    Until you configure Easify shipping options, any orders placed via WooCommerce will 
                    be sent to your Easify Server without shipping charges attached!
                </p>
            </div>

            <?php
        }

        private function display_coupons_options_warning() {
            ?>
            <div>
                <h3>
                    <img src='<?= plugin_dir_url(__FILE__) ?>../assets/images/warning.png' title='Coupon options not configured!'/>
                    Coupon options not yet configured.
                </h3>
                <p>
                    Until you configure Easify coupon options, any orders placed via WooCommerce that use WooCommerce coupons will 
                    be sent to your Easify Server without the discount applied!
                </p>
            </div>

            <?php
        }

        /**
         * create Easify options page
         * 
         * Called by WordPress when the Easify plugin settings page needs to be displayed.
         */
        public function create_easify_options_page() {
            // Initialise page data...
            $this->init_settings_page_data();

            // Initialise Easify Options class so we can easily grab Easify Plugin
            // options...
            $this->easify_options = new Easify_WC_Easify_Options();


            // If we have a username and password set, initialise easify server classes
            if ($this->options['easify_username'] && $this->options['easify_password']) {
                // Prepare Easify Server class so we can talk to Easify Server...                
                $this->easify_server = new Easify_Generic_Easify_Server(
                        get_option('easify_web_service_location'), $this->options['easify_username'], $this->options['easify_password']);

                // Prepare Easify Discovery Server for use...
                $this->easify_discovery_server = new Easify_Generic_Easify_Server_Discovery(
                        EASIFY_DISCOVERY_SERVER_ENDPOINT_URI, $this->options['easify_username'], $this->options['easify_password']);
            }


            // Check to see if we have comms with Easify.
            $this->check_easify_server_comms();

            if ($this->isEasifyServerReachable) {
                // And update the tax values from the Easify Server if reachable
                update_option('easify_tax_rates', $this->easify_server->GetEasifyTaxRates());
            }

            $this->hidden_tooltip_html();

            // get current tab
            if (isset($_GET['tab'])) {
                $tab = $_GET['tab'];
            }

            // start page wrapper
            ?>

            <div class="wrap">
                <h2>Easify Settings</h2> 
                <?php
                if (!$this->isWooCommercePluginActive) {
                    // If WooCommerce not active then warn user...      
                    ?>            
                    <h3 class="nav-tab-wrapper easify-warning">WooCommerce Plugin is not installed, or has not been activated.</h2>
                        <div>
                            <p>You will need to make sure the WooCommerce plugin has been installed 
                                and activated before you can configure Easify settings.</p>
                        </div> 
                </div> <!-- wrap -->
                <?php
                return;
            }

            // Check whether permalinks are enabled - warn if not...
            if (!$this->arePermalinksEnabled) {
                // Permalinks not enabled...      
                ?>            
                <h3 class="nav-tab-wrapper easify-warning">Permalinks NOT enabled...</h2>
            <div>
                <p>The Easify WooCommerce plugin requires that Permalinks be enabled in WordPress settings.</p>
                <p>Make sure Permalinks are set to anything other than <b>Plain</b>.</p>
                <p><a href="<?php echo get_admin_url() . 'options-permalink.php' ?>">Click here to view WordPress Permalink Settings.</a></p>
            </div> 
            </div> <!-- wrap -->
            <?php
            return;
        }

        // WooCommerce is active & permalinks enabled - build out options page...
        // set default tab
        if (!isset($tab) || empty($tab)) {
            $tab = 'setup';
        }

        // draw the tabs, highlighting current tab (if no Easify comms only creates first setup tab)
        $this->create_easify_options_tabs($tab);

        // start form wrapper 
        echo '<form method="post" action="options.php">';

        // print hidden setting fields
        settings_fields('easify_options_' . $tab . '_group');

        // print current page 
        do_settings_sections('easify_options_page_' . $tab);

        // print submit / save button
        submit_button();

        // close page wrapper 
        echo '</form></div>';
    }

    private function init_settings_page_data() {
        // Load Easify plugin options from db
        $this->cache_options();

        // Determine whether permalinks are enabled in WordPress (required by Easify Plugin)
        $this->check_permalinks_enabled();

        // Check if WooCommerce is activated
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $this->isWooCommercePluginActive = true;
        }
    }

    /**
     * register Easify tooltipster javascript
     * 
     * Tells ToolTipster to render tooltips as HTML
     */
    public function init_easify_header() {
        echo "<script type='text/javascript'>
		jQuery(document).ready(function() {
                    jQuery('.easify_tip').tooltipster({
                        contentAsHTML: true,
                        interactive: true,
                        theme: 'tooltipster-noir'
                    });
                });
            </script>";
    }

    /**
     * register javascript and css scripts
     */
    public function init_scripts() {
        // Easify CSS
        wp_enqueue_style('easify-style', plugins_url('../assets/css/easify.css', __FILE__));

        // Tooltipster CSS
        wp_enqueue_style('tooltipster-style', plugins_url('../assets/css/tooltipster.bundle.min.css', __FILE__));
        wp_enqueue_style('tooltipster-theme', plugins_url('../assets/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-noir.min.css', __FILE__));

        // Tooltipster v4
        wp_enqueue_script('tooltipster', plugins_url('../assets/js/tooltipster.bundle.min.js', __FILE__), array('jquery'));
    }

    /**
     * modifies database value before it hits the database
     */
    public function easify_update_field_easify_password($new_password) {
        // We always encrypt the password before it hits the database...
        return Encrypt($new_password);
    }

    /**
     * initialise Easify Setup page 
     */
    private function init_setup() {
        // register username option
        register_setting(
                'easify_options_setup_group', // group
                'easify_username', // name
                array($this, 'sanitise') // sanitise method
        );

        // register password option
        register_setting(
                'easify_options_setup_group', // group
                'easify_password', // name
                array($this, 'sanitise') // sanitise method
        );

        // add Setup page 
        add_settings_section(
                'easify_options_section_setup', // id
                'Easify Subscription Credentials', // title
                array($this, 'settings_html_easify_setup_message'), // callback
                'easify_options_page_setup' // page
        );

        // EASIFY_USERNAME
        add_settings_field(
                'easify_username', // id
                'Easify Subscription Username', // title
                array($this, 'setting_html_easify_username'), // callback
                'easify_options_page_setup', // page
                'easify_options_section_setup' // section
        );

        // EASIFY_PASSWORD
        add_settings_field(
                'easify_password', // id
                'Easify Subscription Password', // title
                array($this, 'setting_html_easify_password'), // callback
                'easify_options_page_setup', // page
                'easify_options_section_setup' // section
        );
    }

    /**
     * HTML to display the server status on the Setup page 
     */
    public function settings_html_easify_setup_message() {
        // check if user has saved API credentials
        if (empty($this->options['easify_username']) || empty($this->options['easify_password'])) {
            // easify_username or easify_username has not been saved, prompt user to enter API credentials
            $blurb = "No credentials found...";
            ?>
            <table class="easify_server_status">
                <tr>
                    <td><img class="easify_server_status" src="<?= plugin_dir_url(__FILE__) . '../assets/images/warning.png' ?>" 
                             alt="<?= $blurb; ?>" title="<?= $blurb; ?>"/> Please enter your Easify Subscription username and password
                        <?= $this->tool_tip('status-easify-server-connection-not-configured-tip'); ?></td> 
                </tr>
            </table>
            <?php
        } else {

            // check if we can authenticate against the Easify API using provided credentials
            if ($this->isEasifyServerReachable) {

                $blurb = "Connection Established";
                ?>
                <table class="easify_server_status">
                    <tr>
                        <td><img class="easify_server_status" src="<?= plugin_dir_url(__FILE__); ?>../assets/images/success.png" 
                                 alt="<?= $blurb; ?>" title="<?= $blurb; ?>"/> Easify server is online and accessible
                            <?= $this->tool_tip('status-easify-server-online-tip'); ?></td>                                               
                    </tr>
                </table>
                <?php
            } else {
                $blurb = "Connection Failure";
                ?>
                <table class="easify_server_status">
                    <tr>
                        <td><img class="easify_server_status" src="<?= plugin_dir_url(__FILE__); ?>../assets/images/failure.png" 
                                 alt="<?= $blurb; ?>" title="<?= $blurb; ?>"/> Login failed, check username and password and ensure Easify Server is online and accessible.
                            <?= $this->tool_tip('status-easify-server-connection-failure-tip'); ?></td>    
                    </tr>
                </table>
                <?php
            }
        }
    }

    /**
     * HTML to display the EASIFY_USERNAME config option
     */
    public function setting_html_easify_username() {
        printf('<input type="text" id="easify_username" name="easify_username" style="width: 350px;" value="%s" />', isset($this->options['easify_username']) ?
                        esc_attr($this->options['easify_username']) : ''
        );
        $this->tool_tip('credentials-easify-username-tip');
    }

    /**
     * HTML to display the EASIFY_PASSWORD config option
     */
    public function setting_html_easify_password() {
        printf('<input type="password" id="easify_password" name="easify_password" style="width: 350px;" value="%s" />', isset($this->options['easify_password']) ?
                        esc_attr($this->options['easify_password']) : ''
        );
        $this->tool_tip('credentials-easify-password-tip');
    }

    /**
     * initialise Easify Orders page 
     */
    private function init_orders() {

        // register Orders options group 
        register_setting(
                'easify_options_orders_group', // group
                'easify_options_orders', // name
                array($this, 'sanitise') // sanitise method
        );

        // add Orders page 
        add_settings_section(
                'easify_options_section_orders', // id
                'Easify Orders Options', // title
                array($this, 'settings_html_easify_orders_message'), // callback
                'easify_options_page_orders' // page
        );

        // EASIFY_ORDER_STATUS_ID
        add_settings_field(
                'easify_order_status_id', // id 
                'Easify Order Status', // title
                array($this, 'setting_html_easify_order_status_id'), //callback
                'easify_options_page_orders', // page
                'easify_options_section_orders' // section
        );

        // EASIFY_ORDER_TYPE_ID
        add_settings_field(
                'easify_order_type_id', 'Easify Order Type', array($this, 'setting_html_easify_order_type_id'), 'easify_options_page_orders', 'easify_options_section_orders'
        );

        // EASIFY_ORDER_COMMENT
        add_settings_field(
                'easify_order_comment', 'Easify Order Comment', array($this, 'setting_html_easify_order_comment'), 'easify_options_page_orders', 'easify_options_section_orders'
        );
    }

    /**
     * HTML to display for the Orders page
     */
    public function settings_html_easify_orders_message() {
        ?>
        <p>When an order is placed via WooCommerce, it will be sent to your Easify Server with the values that you specify here.</p>
        <?php
    }

    /**
     * HTML to display the EASIFY_ORDER_STATUS_ID config option
     */
    public function setting_html_easify_order_status_id() {
        // select control start tag
        print '<select name="easify_options_orders[easify_order_status_id]" style="width: 350px;">';

        // get Easify order statuses if they've not been cached
        $this->get_easify_web_service_data('order_statuses');

        // print each select option
        foreach ($this->order_statuses as $status) {

            // print option, mark as selected if needed
            printf('<option value="%s"%s>%s</option>', $status['OrderStatusId'], ( $this->options['easify_options_orders']['easify_order_status_id'] == $status['OrderStatusId'] ) ||
                    (!isset($this->options['easify_options_orders']['easify_order_status_id']) &&
                    $status['Description'] == 'New Order' ) ? ' selected' : '', $status['Description']
            );
        }

        // select control close tag
        print '</select>';

        $this->tool_tip('orders-order-status-tip');
    }

    /**
     * HTML to display the EASIFY_ORDER_TYPE_ID config option
     */
    public function setting_html_easify_order_type_id() {
        // select control start tag
        print '<select name="easify_options_orders[easify_order_type_id]" style="width: 350px;">';

        // get Easify order types if they've not been cached
        $this->get_easify_web_service_data('order_types');

        // print each select option
        foreach ($this->order_types as $type) {

            // print option, mark as selected if needed
            printf(
                    '<option value="%s"%s>%s</option>', $type['OrderTypeId'], ($this->options['easify_options_orders']['easify_order_type_id'] == $type['OrderTypeId'] ) ||
                    (!isset($this->options['easify_options_orders']['easify_order_type_id']) &&
                    $type['Description'] == 'Internet' ) ? ' selected' : '', $type['Description']
            );
        }

        // select control close tag
        print '</select>';

        $this->tool_tip('orders-order-type-tip');
    }

    /**
     * HTML to display the EASIFY_ORDER_COMMENT config option
     */
    public function setting_html_easify_order_comment() {
        printf(
                '<input type="text" id="easify_order_comment" name="easify_options_orders[easify_order_comment]" style="width: 350px;" value="%s" />', isset($this->options['easify_options_orders']['easify_order_comment']) ?
                        esc_attr($this->options['easify_options_orders']['easify_order_comment']) : 'Internet Order'
        );

        $this->tool_tip('orders-order-comment-tip');
    }

    /**
     * Initialise Easify Customers Settings
     */
    private function init_customers() {

        // register Customers options group 
        register_setting(
                'easify_options_customers_group', // group
                'easify_options_customers', // name
                array($this, 'sanitise') // sanitise method
        );

        // add Customers page 
        add_settings_section(
                'easify_options_section_customers', // id
                'Easify Customers Options', // title
                array($this, 'settings_html_easify_customers_message'), // callback
                'easify_options_page_customers' // page
        );

        // EASIFY_CUSTOMER_TYPE_ID
        add_settings_field(
                'easify_customer_type_id', // id
                'Easify Customer Type', // display title
                array($this, 'setting_html_easify_customer_type_id'), // callback
                'easify_options_page_customers', // page
                'easify_options_section_customers' // section
        );

        // EASIFY_CUSTOMER_RELATIONSHIP_ID
        add_settings_field(
                'easify_customer_relationship_id', 'Easify Customer Relationship', array($this, 'setting_html_easify_customer_relationship_id'), 'easify_options_page_customers', 'easify_options_section_customers'
        );
    }

    /**
     * HTML to display for the Customers section
     */
    public function settings_html_easify_customers_message() {
        ?>
        <p>When an order is placed via WooCommerce, the Easify customer related to the order will be given the values that you specify here.</p>
        <?php
    }

    /**
     * Initialise Easify Coupons Settings
     */
    private function init_coupons() {

        // register coupons options group 
        register_setting(
                'easify_options_coupons_group', // group
                'easify_options_coupons', // name
                array($this, 'validate_discount_mapping') // Validation method
        );

        // add coupons page 
        add_settings_section(
                'easify_options_section_coupons', // id
                'Easify Coupons Options', // title
                array($this, 'settings_html_easify_coupons_message'), // callback
                'easify_options_page_coupons' // page
        );

        // EASIFY_DISCOUNT_SKU
        add_settings_field(
                'easify_discount_sku', // id
                'Easify Discount SKU', // display title
                array($this, 'setting_html_easify_discount_sku'), // callback
                'easify_options_page_coupons', // page
                'easify_options_section_coupons' // section
        );
    }

    /**
     * HTML to display for the Coupons section
     */
    public function settings_html_easify_coupons_message() {
        ?>
        <p>If you want to support WooCommerce coupons in Easify, create a product in Easify named (say) <b>Discount</b> and enter the 
            Easify SKU of it below.</p>
        <p>When an order is placed via WooCommerce, any coupons that were applied to the WooCommerce order will be added to the order
            in your Easify Server as the product specified below.</p>        
        <?php
    }

    /**
     * HTML to display the EASIFY_DISCOUNT_SKU config option
     */
    public function setting_html_easify_discount_sku() {
        printf('<input type="text" id="easify_discount_sku" name="easify_options_coupons[easify_discount_sku]" style="width: 350px;"  value="%s" />', isset($this->options['easify_options_coupons']['easify_discount_sku']) ?
                        esc_attr($this->options['easify_options_coupons']['easify_discount_sku']) : ''
        );

        $this->tool_tip('coupons-discount-sku-tip');
    }

    /**
     * HTML to display the EASIFY_CUSTOMER_TYPE_ID config option
     */
    public function setting_html_easify_customer_type_id() {
        // select control start tag
        print '<select name="easify_options_customers[easify_customer_type_id]" style="width: 350px;">';

        // get Easify customer types if they've not been cached
        $this->get_easify_web_service_data('customer_types');

        // print each select option
        foreach ($this->customer_types as $type) {
            // print option, mark as selected if needed
            printf(
                    '<option value="%s"%s>%s</option>', $type['CustomerTypeId'], ( $this->options['easify_options_customers']['easify_customer_type_id'] == $type['CustomerTypeId'] ) ||
                    (!isset($this->options['easify_options_customers']['easify_customer_type_id']) &&
                    $type['Description'] == 'Not Known' ) ? ' selected' : '', $type['Description']
            );
        }

        // select control close tag
        print '</select>';

        $this->tool_tip('customers-customer-type-tip');
    }

    /**
     * HTML to display the EASIFY_CUSTOMER_RELATIONSHIP_ID config option
     */
    public function setting_html_easify_customer_relationship_id() {
        // select control start tag
        print '<select name="easify_options_customers[easify_customer_relationship_id]" style="width: 350px;">';

        // get Easify customer relationships if they've not been cached
        $this->get_easify_web_service_data('customer_relationships');

        // print each select option
        foreach ($this->customer_relationships as $relationship) {

            // print option, mark as selected if needed
            printf(
                    '<option value="%s"%s>%s</option>', $relationship['CustomerRelationshipId'], ( $this->options['easify_options_customers']['easify_customer_relationship_id'] == $relationship['CustomerRelationshipId'] ) ||
                    (!isset($this->options['easify_options_customers']['easify_customer_relationship_id']) &&
                    $relationship['Description'] == 'Active' ) ? ' selected' : '', $relationship['Description']
            );
        }

        // select control close tag
        print '</select>';

        $this->tool_tip('customers-customer-relationship-tip');
    }

    /**
     * initialise Easify Shipping page
     */
    private function init_shipping() {

        // register Shipping options group 
        register_setting(
                'easify_options_shipping_group', // group
                'easify_options_shipping', // name
                array($this, 'validate_shipping_mappings') // validation method
        );

        // add Shipping page 
        add_settings_section(
                'easify_options_section_shipping', // id
                'Easify Shipping Options', // title
                array($this, 'settings_html_easify_shipping_message'), // callback
                'easify_options_page_shipping' // page
        );

        // EASIFY_DELIVERY_SKUS
        add_settings_field(
                'easify_shipping_mapping', 'Shipping Mapping', array($this, 'setting_html_easify_shipping_mapping'), 'easify_options_page_shipping', 'easify_options_section_shipping'
        );
    }

    /**
     * We call this in place of the sanitize method to validate the shipping
     * method SKUs.
     * 
     * Each shipping method will need a valid Easify SKU. Here we check the SKU
     * value to make sure it actually exists in the Easify Server before we 
     * allow it to be saved to the Wordpress options db.
     * 
     * @param object $input The $input param contains the form data. You can 
     * modify the contents of $input which will modify what is sent to the db.
     * Here, to prevent invalid values being saved we just null out any wrong
     * values. Not optimal from a UX point of view, but safe.
     * @return object
     */
    public function validate_shipping_mappings($input) {
        foreach ($input['easify_shipping_mapping'] as $mapping_name => $value) {
            // If value not null then validate that SKU exists in Easify...
            if ($value != null) {

                // Validate that SKU is a number...
                if (!is_numeric($value)) {
                    // Easify SKU must be a number, if not a number warn user and don't save value in database
                    $type = 'error';
                    $message = __($mapping_name . ' Easify SKU ' . $value . ' is not a valid number.', 'require-featured-image');
                    add_settings_error(
                            'easify_shipping_mapping', esc_attr('settings_updated'), $message, $type
                    );

                    $input['easify_shipping_mapping'][$mapping_name] = '';
                    continue;
                }


                // Validate SKU exists in Easify
                if (is_null($this->easify_server)) {
                    // Make sure we have initialised the Easify Server object...
                    $this->easify_server = new Easify_Generic_Easify_Server(
                            get_option('easify_web_service_location'), get_option('easify_username'), Decrypt(get_option('easify_password')));
                }

                $product = $this->easify_server->GetProductFromEasify($value);

                // If SKU not found in Easify Server, warn the user and don't save the value to the database
                if (empty($product->SKU)) {
                    $type = 'error';
                    $message = __($mapping_name . ' Easify SKU ' . $value . ' was not found in Easify Server. Make sure a product with this SKU exists in Easify.', 'require-featured-image');
                    add_settings_error(
                            'easify_shipping_mapping', esc_attr('settings_updated'), $message, $type
                    );

                    $input['easify_shipping_mapping'][$mapping_name] = '';
                }
            }
        }

        return $input;
    }

    /**
     * We call this in place of the sanitize method to validate the discount SKU.
     * 
     * The discount sku will need a valid Easify SKU. Here we check the SKU
     * value to make sure it actually exists in the Easify Server before we 
     * allow it to be saved to the Wordpress options db.
     * 
     * @param object $input The $input param contains the form data. You can 
     * modify the contents of $input which will modify what is sent to the db.
     * Here, to prevent invalid values being saved we just null out any wrong
     * values. Not optimal from a UX point of view, but safe.
     * @return object
     */
    public function validate_discount_mapping($input) {
        $value = $input['easify_discount_sku'];

        // If value not null then validate that SKU exists in Easify...
        if ($value != null) {

            // Validate that SKU is a number...
            if (!is_numeric($value)) {
                // Easify SKU must be a number, if not a number warn user and don't save value in database
                $type = 'error';
                $message = __($mapping_name . ' Easify SKU ' . $value . ' is not a valid number.', 'require-featured-image');
                add_settings_error(
                        'easify_discount_sku', esc_attr('settings_updated'), $message, $type
                );

                $input['easify_discount_sku'] = '';
                return $input;
            }


            // Validate SKU exists in Easify
            if (is_null($this->easify_server)) {
                // Make sure we have initialised the Easify Server object...
                $this->easify_server = new Easify_Generic_Easify_Server(
                        get_option('easify_web_service_location'), get_option('easify_username'), Decrypt(get_option('easify_password')));
            }

            $product = $this->easify_server->GetProductFromEasify($value);

            // If SKU not found in Easify Server, warn the user and don't save the value to the database
            if (empty($product->SKU)) {
                $type = 'error';
                $message = __($mapping_name . ' Easify SKU ' . $value . ' was not found in Easify Server. Make sure a product with this SKU exists in Easify.', 'require-featured-image');
                add_settings_error(
                        'easify_discount_sku', esc_attr('settings_updated'), $message, $type
                );

                $input['easify_discount_sku'][$mapping_name] = '';
            }
        }

        return $input;
    }

    /**
     * HTML to display for the Shipping section
     */
    public function settings_html_easify_shipping_message() {
        ?>
        <p>When an order is placed via WooCommerce, it will be automatically sent to your Easify Server.</p>
        <p>Here you can associate delivery
            charges in Easify with delivery charges in WooCommerce by mapping a WooCommerce Shipping Option to 
            the Easify SKU of the corresponding delivery item in Easify.</p>
        <?php
    }

    /**
     * HTML to display the EASIFY_SHIPPING_MAPPING config option
     */
    public function setting_html_easify_shipping_mapping() {
        ?>
        <table class="wc_shipping widefat">
            <thead>
                <tr>
                    <th class='easify-options-shipping-table-col1'>WooCommerce Shipping Method</th>
                    <th class='easify-options-shipping-table-col2'>Easify SKU</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Flat Rate (cost per order)
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_shipping_sku('flat_rate');
                        $this->tool_tip('shipping-flat-rate-tip');
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        Free Shipping (minimum order amount)
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_shipping_sku('free_shipping');
                        $this->tool_tip('shipping-free-tip');
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        International Delivery
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_shipping_sku('international_delivery');
                        $this->tool_tip('shipping-international-tip');
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        Local Delivery (cost per order)
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_shipping_sku('local_delivery');
                        $this->tool_tip('shipping-local-delivery-tip');
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        Default
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_shipping_sku('default');
                        $this->tool_tip('shipping-default-tip');
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * HTML to display the EASIFY_DELIVERY_SKUS config option
     */
    public function setting_html_easify_shipping_sku($shipping_type = 'default') {
        printf('<input type="text" id="easify_shipping_mapping" name="easify_options_shipping[easify_shipping_mapping][' . $shipping_type . ']" style="width: 150px;" value="%s" />', isset($this->options['easify_options_shipping']['easify_shipping_mapping'][$shipping_type]) ?
                        esc_attr($this->options['easify_options_shipping']['easify_shipping_mapping'][$shipping_type]) : ''
        );
    }

    /**
     * initialise Easify Payment page
     */
    private function init_payment() {

        // register Payments options group 
        register_setting(
                'easify_options_payment_group', // group
                'easify_options_payment', // name
                array($this, 'sanitise') // sanitise method
        );

        // add Payments page 
        add_settings_section(
                'easify_options_section_payment', // id
                'Easify Payment Options', // title
                array($this, 'settings_html_easify_payment_message'), // callback
                'easify_options_page_payment' // page
        );

        // EASIFY_PAYMENT_COMMENT
        add_settings_field(
                'easify_payment_comment', // id
                'Easify Payment Comment', // title
                array($this, 'setting_html_easify_payment_comment'), // callback
                'easify_options_page_payment', // page
                'easify_options_section_payment' // section
        );

        // EASIFY_PAYMENT_MAPPING
        add_settings_field(
                'easify_payment_mapping', 'Payment Mapping', array($this, 'setting_html_easify_payment_mapping'), 'easify_options_page_payment', 'easify_options_section_payment'
        );
    }

    /**
     * HTML to display for the Payment section
     */
    public function settings_html_easify_payment_message() {
        ?>
        <p>When an order is placed via WooCommerce, it will be sent to your Easify Server along with a payment record that describes
            how the order was paid for.</p>
        <p>Here you can configure how the payment records are sent to your Easify server.</p>  
        <?php
    }

    /**
     * HTML to display the EASIFY_PAYMENT_MAPPING config option
     */
    public function setting_html_easify_payment_mapping() {
        ?>
        <table class="wc_gateways widefat">
            <thead>
                <tr>
                    <th>WooCommerce Payment Method</th>
                    <th>Easify Payment Method</th>
                    <th>Easify Payment Account</th>
                    <th>Enable</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        PayPal
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_method_id('paypal');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_account_id('paypal');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_raise('paypal');
                        $this->tool_tip('payment-mapping-paypal-tip');
                        ?>				
                    </td>
                </tr>
                <tr>
                    <td>
                        SagePay
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_method_id('sagepayform');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_account_id('sagepayform');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_raise('sagepayform');
                        $this->tool_tip('payment-mapping-sagepay-tip');
                        ?>				
                    </td>
                </tr>
                <tr>
                    <td>
                        WorldPay
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_method_id('worldpay');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_account_id('worldpay');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_raise('worldpay');
                        $this->tool_tip('payment-mapping-worldpay-tip');
                        ?>				
                    </td>
                </tr>
                <tr>
                    <td>
                        Cash on Delivery
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_method_id('cod');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_account_id('cod');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_raise('cod');
                        $this->tool_tip('payment-mapping-cod-tip');
                        ?>				
                    </td>
                </tr>
                <tr>
                    <td>
                        Cheque Payment
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_method_id('cheque');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_account_id('cheque');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_raise('cheque');
                        $this->tool_tip('payment-mapping-cheque-tip');
                        ?>				
                    </td>
                </tr>
                <tr>
                    <td>
                        Direct Bank Transfer
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_method_id('bacs');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_account_id('bacs');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_raise('bacs');
                        $this->tool_tip('payment-mapping-bacs-tip');
                        ?>				
                    </td>
                </tr>
                <tr>
                    <td>
                        Default
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_method_id('default');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_account_id('default');
                        ?>
                    </td>
                    <td>
                        <?php
                        $this->setting_html_easify_payment_raise('default');
                        $this->tool_tip('payment-mapping-default-tip');
                        ?>				
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * HTML to display the EASIFY_PAYMENT_COMMENT config option
     */
    public function setting_html_easify_payment_comment() {
        printf('<input type="text" id="easify_payment_comment" name="easify_options_payment[easify_payment_comment]" style="width: 350px;"  value="%s" />', isset($this->options['easify_options_payment']['easify_payment_comment']) ?
                        esc_attr($this->options['easify_options_payment']['easify_payment_comment']) : 'Payment via website'
        );

        $this->tool_tip('payment-comment-tip');
    }

    /**
     * HTML to display the EASIFY_PAYMENT_METHOD_ID config option
     */
    public function setting_html_easify_payment_method_id($payment_type = 'default') {

        // select control start tag
        print '<select name="easify_options_payment[easify_payment_mapping][' . $payment_type . '][method_id]" style="width: 150px;">';

        // get Easify payment methods if they've not been cached
        $this->get_easify_web_service_data('payment_methods');

        // get current setting from WordPress Easify options
        $current_value = isset($this->options['easify_options_payment']['easify_payment_mapping'][$payment_type]['method_id']) ?
                $this->options['easify_options_payment']['easify_payment_mapping'][$payment_type]['method_id'] : null;

        foreach ($this->payment_methods as $method) {
            // if option isn't active, skip
            if ($method['Active'] != 'true')
                continue;

            // print option, mark as selected if needed
            printf('<option value="%s"%s>%s</option>', $method['PaymentMethodsId'], ( $current_value == $method['PaymentMethodsId'] ) ? ' selected' : '', $method['Description']);
        }

        // select control end tag
        print '</select>';
    }

    /**
     * HTML to display the EASIFY_PAYMENT_ACCOUNT_ID config option
     */
    public function setting_html_easify_payment_account_id($payment_type = 'default') {

        // select control start tag
        print '<select name="easify_options_payment[easify_payment_mapping][' . $payment_type . '][account_id]" style="width: 150px;">';

        // get Easify payment accounts if they've not been cached
        $this->get_easify_web_service_data('payment_accounts');

        // get current setting from WordPress Easify options
        $current_value = isset($this->options['easify_options_payment']['easify_payment_mapping'][$payment_type]['account_id']) ?
                $this->options['easify_options_payment']['easify_payment_mapping'][$payment_type]['account_id'] : null;

        foreach ($this->payment_accounts as $account) {

            // if option isn't active, skip
            if ($account['Active'] != 'true')
                continue;

            // print option, mark as selected if needed
            printf(
                    '<option value="%s"%s>%s</option>', $account['PaymentAccountId'], ( $current_value == $account['PaymentAccountId'] ) ? ' selected' : '', $account['Description']
            );
        }

        // select control end tag
        print '</select>';
    }

    /**
     * HTML to display the EASIFY_PAYMENT_RAISE config option
     */
    public function setting_html_easify_payment_raise($payment_type = 'default') {

        printf(
                '<input type="checkbox" name="easify_options_payment[easify_payment_mapping][' . $payment_type . '][raise]" value="true" %s/>', isset($this->options['easify_options_payment']['easify_payment_mapping'][$payment_type]['raise']) &&
                $this->options['easify_options_payment']['easify_payment_mapping'][$payment_type]['raise'] == 'true' ? 'checked="checked"' : ''
        );
    }

    /**
     * initialise Easify Logging page
     */
    private function init_logging() {

        // register Logging options group 
        register_setting(
                'easify_options_logging_group', // group
                'easify_options_logging', // name
                array($this, 'sanitise') // sanitise method
        );

        // Logging Options
        add_settings_section(
                'easify_options_section_logging', // id
                'Easify Logging Options', // title
                array($this, 'settings_html_easify_logging_message'), // callback
                'easify_options_page_logging' // page
        );

        // EASIFY_LOGGING_ENABLED
        add_settings_field(
                'easify_logging_enabled', // id
                'Easify Diagnostic Logging', // title
                array($this, 'setting_html_easify_enable_logging'), // callback
                'easify_options_page_logging', // page
                'easify_options_section_logging' // section
        );
    }

    /**
     * HTML to display for the Logging section
     */
    public function settings_html_easify_logging_message() {
        ?>
        <p>Enable Easify plugin diagnostic logging.</p>
        <p>The log file will be saved to: <?= plugin_dir_url(__FILE__) . 'logs/easify_log.txt' ?></p>  
        <p class="easify-warning"><b>NOTE:</b> We recommend only enabling logging for troubleshooting problems, as the log file can 
        very rapidly grow to take up a lot of disk space.</p>        
        <?php
    }

    /**
     * HTML to display the EASIFY_LOGGING_ENABLED config option
     */
    public function setting_html_easify_enable_logging() {
        printf('<div class="easify-loggin-option">Enable Logging <input type="checkbox" name="easify_options_logging[easify_logging_enabled]" value="true" %s/>', isset($this->options['easify_options_logging']['easify_logging_enabled']) &&
                $this->options['easify_options_logging']['easify_logging_enabled'] == 'true' ? 'checked="checked"' : ''
        );

        $this->tool_tip('logging-tip');
        echo '</div>';
    }

    /**
     * sanitise each setting field as needed
     */
    public function sanitise($input) {
        return $input;
    }

    private function cache_options() {
        // cache Easify options from WordPress database 
        $this->options['easify_options_orders'] = get_option('easify_options_orders');
        $this->options['easify_options_customers'] = get_option('easify_options_customers');
        $this->options['easify_options_shipping'] = get_option('easify_options_shipping');
        $this->options['easify_options_payment'] = get_option('easify_options_payment');
        $this->options['easify_options_logging'] = get_option('easify_options_logging');
        $this->options['easify_username'] = get_option('easify_username');
        $this->options['easify_options_coupons'] = get_option('easify_options_coupons');

        $easifyPassword = get_option('easify_password');
        if (!empty($easifyPassword)) {
            $this->options['easify_password'] = Decrypt(get_option('easify_password')); // Decrypt password from DB
        }
    }

    /**
     * Check to see if we have comms with the Easify Server
     * 
     * If no subscription credentials present yet, returns false.
     * 
     * If subscription credentials present, attempts to acquire the Easify Server
     * endpoint settings.
     * 
     * When settings acquired, attempts to communicate with Easify Server.
     */
    private function check_easify_server_comms() {
        // Required credentials to be present
        if (!$this->options['easify_username'] || !$this->options['easify_password']) {
            return;
        }

        // Re-acquire Easify Server settings and see if we can communicate with Easify Server
        update_option('easify_web_service_location', $this->easify_discovery_server->get_easify_server_endpoint());

        // Need to re-initialise easify_server url as URL may have changed...
        $this->easify_server->UpdateServerUrl(get_option('easify_web_service_location'));

        // Check comms...               
        $this->isEasifyServerReachable = $this->easify_server->HaveComsWithEasify();
    }

    /**
     * Determines whether Permalinks are enabled in WordPress.
     * 
     * Permalinks are required by the Easify Plugin so that we can recognise the 
     * /Easify path in the REQUEST_URI server variable and redirect that traffic
     * to the Easify receiver class. 
     */
    private function check_permalinks_enabled() {
        $permalink_structure = get_option('permalink_structure');
        $this->arePermalinksEnabled = !empty($permalink_structure);
    }

    /**
     * Creates a help icon with an associated tooltip.
     * 
     * Tooltips are implemented using ToolTipster v4.
     * 
     * The easify_tip class makes the image into a tooltip (see init_easify_header() function)
     * for attachment of ToolTipster.
     * 
     * data-tooltip-content contains the #id of the div in hidden_tooltip_html() to be 
     * displayed as the tooltip.
     * 
     * Usage: $this->tool_tip('#my-tool-tip-id'); where #my-tool-tip-id is the #id of the 
     * div to be displayed as a tooltip.
     * 
     * @param type $tooltipId
     */
    private function tool_tip($tooltipId) {
        ?>
        <img class="easify_tip easify_tip_image" data-tooltip-content="#<?= $tooltipId ?>" src="<?= plugin_dir_url(__FILE__) . '../assets/images/help.png' ?>" />        
        <?php
    }

    /**
     * html tool tips
     * 
     * The main DIV .tooltip_templates is hidden via CSS so it won't display. 
     * 
     * Each inner DIV represents a tool tip related to a tooltip help icon on the 
     * settings page.
     * 
     * Divs are displayed using the tool_tip() function.
     * 
     */
    private function hidden_tooltip_html() {
        ?>
        <div class="tooltip_templates">
            <!-- CONNECTION STATUS -->
            <div id="status-easify-server-online-tip">
                <h3>Easify Server Online and Accessible</h3>
                <p>The Easify plugin has contacted your Easify Server and is able to communicate with it normally.</p>
                <p>Product changes on your Easify Server will be uploaded to WooCommerce.</p>
                <p>Orders placed in WooCommerce will be sent to your Easify Server.</p>
            </div>            

            <div id="status-easify-server-connection-not-configured-tip">
                <h3>Enter Your Subscription Credentials</h3>
                <p>You have not yet entered your Easify WooCommerce Plugin Subscription credentials.</p>
                <p>Enter your Easify Subscription username and password below and click the <b>Save Changes</b> button.</p>
                <?= $this->tooltip_click_here_link('credentials') ?>                              
            </div>               

            <div id="status-easify-server-connection-failure-tip">
                <h3>Could Not Contact Easify Server</h3>
                <p>Unable to communicate with your Easify Server.</p>
                <p>Make sure your Easify WooCommerce Plugin Subscription username and password are correct.</p>
                <p>Make sure your Easify WooCommerce Plugin Subscription has not expired.</p>
                <p>Check that your Easify Server is online and is accessible.</p>
                <a href="<?= EASIFY_HELP_BASE_URL . '/Help/ecommerce_woocommerce_plugin_troubleshooting' ?>" 
                   target="_blank" 
                   title ="Opens in a new tab..." >Click here for troubleshooting tips...</a>                                 
            </div>        


            <!-- OPTIONS - CREDENTIALS -->                    
            <div id="credentials-easify-username-tip">
                <h3>Easify Subscription Username</h3>
                <p>This is the username that you setup when you purchased your Easify WooCommerce Plugin Subscription.</p>
                <p>It will be in the form of an email address i.e. MyWebsite@MyCompanyName.easify.co.uk.</p>
                <?= $this->tooltip_click_here_link('credentials-username') ?>      
            </div>     

            <div id="credentials-easify-password-tip">
                <h3>Easify Subscription Password</h3>
                <p>This is the password that you setup when you purchased your Easify WooCommerce Plugin Subscription.</p>
                <?= $this->tooltip_click_here_link('credentials-password') ?>                               
            </div>             


            <!-- OPTIONS ORDERS -->
            <div id="orders-order-status-tip">
                <h3>Easify Order Status</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server with the Easify Order Status you specify here.</p>
                <p>We recommend you set this value to <b>New Order</b> so that you know you have a new order to process in Easify.</p>
                <?= $this->tooltip_click_here_link('orders-status') ?>                               
            </div>              

            <div id="orders-order-type-tip">
                <h3>Easify Order Type</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server with the Easify Order Type you specify here.</p>
                <p>We recommend you set this value to <b>Internet</b> so that you know that the order came from the Internet in Easify.</p>
                <p><b>Note: </b><i>If you have created your own Order Types in Easify, you can select one of those if you wish. For example you could create an 
                        Order type of <b>'WooCommerce'</b> in Easify and select that here so that you know the order came from your WooCommerce site.</i></p>
                <?= $this->tooltip_click_here_link('order-type') ?>                                     
            </div>                

            <div id="orders-order-comment-tip">
                <h3>Easify Order Comment</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server and the Easify order comment will be set to the value you specify here.</p>
                <p>The WooCommerce Order Number will be automatically appended to the comment that you enter here.</p>
                <?= $this->tooltip_click_here_link('order-comment') ?>                        
            </div>        


            <!-- OPTIONS CUSTOMERS -->
            <div id="customers-customer-type-tip">
                <h3>Easify Customer Type</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server and the related 
                    Easify Customer will be given the Customer Type you specify here.</p>
                <p>If you do not make use of customer types in Easify you can leave this as the default value (Not Known).</p>
                <?= $this->tooltip_click_here_link('customer-type') ?>                                   
            </div>             

            <div id="customers-customer-relationship-tip">
                <h3>Easify Customer Relationship</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server and the related 
                    Easify Customer will be given the Customer Relationship you specify here.</p>
                <p>We recommend you set this to <b>Active</b> unless you have created your own custom Customer Types in Easify.</p>
                <?= $this->tooltip_click_here_link('customer-relationship') ?>                
            </div>      

            <!-- OPTIONS COUPONS -->
            <div id="coupons-discount-sku-tip">
                <h3>WooCommerce Coupons</h3>
                <p>If you want to support WooCommerce coupons in Easify, create a product in Easify named (say) <b>Discount</b> and enter the 
                    Easify SKU of it here.</p>
                <p>When an order is placed via WooCommerce, any coupons that were applied to the WooCommerce order will be added to the order
                    in your Easify Server as the product specified here.</p>     
                <p>If you do not use WooCommerce coupons you can leave this field blank.</p>
                <p>If you do use WooCommerce coupons and do not create a Discount product in Easify and enter its SKU here,
                    any WooCommerce orders that have coupons applied will be sent to your Easify Server without a discount.</p>             
                <?= $this->tooltip_click_here_link('coupons-discount-sku') ?>           
            </div>   

            <!-- OPTIONS SHIPPING -->
            <div id="shipping-flat-rate-tip">
                <h3>Flat Rate Shipping Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server and if the WooCommerce order
                    has a shipping method of <b>Flat Rate</b>, the Easify SKU you specify here will be added to the order in Easify.</p>
                <p>If you want to support Flat Rate Shipping in Easify, create a new product in Easify named "Flat Rate Shipping" and 
                    enter the Easify SKU of that product here.</p>
                <p>When the <b>Flat Rate</b> Shipping item is added to the order in Easify, its value will be set to the shipping cost that
                    was calculated by WooCommerce.</p>
                <p><b>Note: </b>You don't have to create a separate Easify Product for each shipping method. If you prefer 
                    you can create one product in Easify for all of your WooCommerce shipping methods and use the same 
                    Easify SKU in each box here.</p>                
                <?= $this->tooltip_click_here_link('shipping-flat-rate') ?>               
            </div>            

            <div id="shipping-free-tip">
                <h3>Free Shipping Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server and if the WooCommerce order
                    has a shipping method of <b>Free Shipping</b>, the Easify SKU you specify here will be added to the order in Easify.</p>
                <p>If you want to support Free Shipping in Easify, create a new product in Easify named "Free Shipping" and 
                    enter the Easify SKU of that product here.</p>
                <p><b>Note: </b>You don't have to create a separate Easify Product for each shipping method. If you prefer 
                    you can create one product in Easify for all of your WooCommerce shipping methods and use the same 
                    Easify SKU in each box here.</p>   
                <?= $this->tooltip_click_here_link('shipping-free') ?>                  
            </div>              

            <div id="shipping-international-tip">
                <h3>International Delivery Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server and if the WooCommerce order
                    has a shipping method of <b>International Delivery</b>, the Easify SKU you specify here will be added to the order in Easify.</p>
                <p>If you want to support International Delivery in Easify, create a new product in Easify named "International Delivery" and 
                    enter the Easify SKU of that product here.</p>
                <p>When the <b>International Delivery</b> Shipping item is added to the order in Easify, its value will be set to the shipping cost that
                    was calculated by WooCommerce.</p>
                <p><b>Note: </b>You don't have to create a separate Easify Product for each shipping method. If you prefer 
                    you can create one product in Easify for all of your WooCommerce shipping methods and use the same 
                    Easify SKU in each box here.</p>   
                <?= $this->tooltip_click_here_link('shipping-international') ?>                 
            </div>             

            <div id="shipping-local-delivery-tip">
                <h3>Local Delivery Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server and if the WooCommerce order
                    has a shipping method of <b>Local Delivery</b>, the Easify SKU you specify here will be added to the order in Easify.</p>
                <p>If you want to support Local Delivery in Easify, create a new product in Easify named "Local Delivery" and 
                    enter the Easify SKU of that product here.</p>
                <p>When the <b>Local Delivery</b> Shipping item is added to the order in Easify, its value will be set to the shipping cost that
                    was calculated by WooCommerce.</p>
                <p><b>Note: </b>You don't have to create a separate Easify Product for each shipping method. If you prefer 
                    you can create one product in Easify for all of your WooCommerce shipping methods and use the same 
                    Easify SKU in each box here.</p>   
                <?= $this->tooltip_click_here_link('shipping-local') ?>                 
            </div>             

            <div id="shipping-default-tip">
                <h3>Default Delivery Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server and if the WooCommerce order
                    has a shipping method that is unsupported by the Easify plugin, the Easify SKU you specify here will be added to the order in Easify.</p>
                <p>To allow for unsupported delivery options in Easify, create a new product in Easify named "Standard Delivery" and 
                    enter the Easify SKU of that product here.</p>
                <p>When the <b>Standard Delivery</b> Shipping item is added to the order in Easify, its value will be set to the shipping cost that
                    was calculated by WooCommerce.</p>
                <p><b>Note: </b>You don't have to create a separate Easify Product for each shipping method. If you prefer 
                    you can create one product in Easify for all of your WooCommerce shipping methods and use the same 
                    Easify SKU in each box here.</p>   
                <?= $this->tooltip_click_here_link('shipping-default') ?>              
            </div>              

            <!-- OPTIONS PAYMENTS -->
            <div id="payment-comment-tip">
                <h3>Easify Payment Comment</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server and a payment record will be created 
                    in Easify.</p>
                <p>Here you can enter the comment that will be added to the payment record in Easify.</p>                
                <?= $this->tooltip_click_here_link('payments-comment') ?>              
            </div>                        

            <div id="payment-mapping-paypal-tip">
                <h3>PayPal Payment Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server along with a payment record that describes
                    how the order was paid for. Here you can configure how the payment records are sent to your Easify server.</p>   
                <p><b>Easify Payment Method:</b> Select which payment method will be 
                    recorded in Easify for payments received via PayPal. <i>We recommend 
                        selecting <b>Bank Transfer</b></i>.<p>
                <p><b>Easify Payment Account:</b> Select which account the payment will 
                    be associated with in Easify for payments received via PayPal. <i>We recommend 
                        selecting <b>PayPal</b></i>.<p>                   
                <p><b>Enabled:</b> Tick this to enable payments to be recorded in Easify when a 
                    payment is received via PayPal. <i>We recommend <b>enabling</b> PayPal payments to be sent to 
                        Easify as the payment will have been approved.</i><p>                 
                    <?= $this->tooltip_click_here_link('payments-paypal') ?>                 
            </div>             

            <div id="payment-mapping-sagepay-tip">
                <h3>SagePay Payment Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server along with a payment record that describes
                    how the order was paid for. Here you can configure how the payment records are sent to your Easify server.</p>   
                <p><b>Easify Payment Method:</b> Select which payment method will be 
                    recorded in Easify for payments received via SagePay. <i>We recommend 
                        selecting <b>Credit Card</b></i>.<p>
                <p><b>Easify Payment Account:</b> Select which account the payment will 
                    be associated with in Easify for payments received via SagePay. <i>We recommend 
                        selecting <b>Current</b></i>.<p>                   
                <p><b>Enabled:</b> Tick this to enable payments to be recorded in Easify when a 
                    payment is received via SagePay. <i>We recommend <b>enabling</b> SagePay payments to be sent to 
                        Easify as the payment will have been approved.</i><p>                 
                    <?= $this->tooltip_click_here_link('payments-sagepay') ?>               
            </div>             

            <div id="payment-mapping-worldpay-tip">
                <h3>WorldPay Payment Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server along with a payment record that describes
                    how the order was paid for. Here you can configure how the payment records are sent to your Easify server.</p>   
                <p><b>Easify Payment Method:</b> Select which payment method will be 
                    recorded in Easify for payments received via WorldPay. <i>We recommend 
                        selecting <b>Credit Card</b></i>.<p>
                <p><b>Easify Payment Account:</b> Select which account the payment will 
                    be associated with in Easify for payments received via WorldPay. <i>We recommend 
                        selecting <b>Current</b></i>.<p>                   
                <p><b>Enabled:</b> Tick this to enable payments to be recorded in Easify when a 
                    payment is received via WorldPay. <i>We recommend <b>enabling</b> WorldPay payments to be sent to 
                        Easify as the payment will have been approved.</i><p>                 
                    <?= $this->tooltip_click_here_link('payments-worldpay') ?>                 
            </div>             

            <div id="payment-mapping-cod-tip">
                <h3>Cash on Delivery Payment Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server along with a payment record that describes
                    how the order was paid for. Here you can configure how the payment records are sent to your Easify server.</p>   
                <p><b>Easify Payment Method:</b> Select which payment method will be 
                    recorded in Easify for payments received via Cash on Delivery. <i>We recommend 
                        selecting <b>Cash</b></i>.<p>
                <p><b>Easify Payment Account:</b> Select which account the payment will 
                    be associated with in Easify for payments received via Cash on Delivery. <i>We recommend 
                        selecting <b>Cash</b></i>.<p>                   
                <p><b>Enabled:</b> Tick this to enable payments to be recorded in Easify when a 
                    payment is received via Cash on Delivery. <i>We recommend <b>disabling</b> Cash on Delivery payments from being sent to 
                        Easify as the payment will not have yet been received.</i><p>                 
                    <?= $this->tooltip_click_here_link('payments-cod') ?>                 
            </div>            

            <div id="payment-mapping-cheque-tip">
                <h3>Cheque Payment Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server along with a payment record that describes
                    how the order was paid for. Here you can configure how the payment records are sent to your Easify server.</p>   
                <p><b>Easify Payment Method:</b> Select which payment method will be 
                    recorded in Easify for payments received via Cheque. <i>We recommend 
                        selecting <b>Cheque</b></i>.<p>
                <p><b>Easify Payment Account:</b> Select which account the payment will 
                    be associated with in Easify for payments received via Cheque. <i>We recommend 
                        selecting <b>Current</b></i>.<p>                   
                <p><b>Enabled:</b> Tick this to enable payments to be recorded in Easify when a 
                    payment is received via Cheque. <i>We recommend <b>disabling</b> Cheque payments from being sent to 
                        Easify as the payment will not have yet been received.</i><p>                 
                    <?= $this->tooltip_click_here_link('payments-cheque') ?>                 
            </div>             

            <div id="payment-mapping-bacs-tip">
                <h3>Direct Bank Transfer Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server along with a payment record that describes
                    how the order was paid for. Here you can configure how the payment records are sent to your Easify server.</p>   
                <p><b>Easify Payment Method:</b> Select which payment method will be 
                    recorded in Easify for payments received via Direct Bank Transfer. <i>We recommend 
                        selecting <b>Bank Transfer</b></i>.<p>
                <p><b>Easify Payment Account:</b> Select which account the payment will 
                    be associated with in Easify for payments received via Direct Bank Transfer. <i>We recommend 
                        selecting <b>Current</b></i>.<p>                   
                <p><b>Enabled:</b> Tick this to enable payments to be recorded in Easify when a 
                    payment is received via Direct Bank Transfer. <i>We recommend <b>disabling</b> Direct Bank Transfer payments from being sent to 
                        Easify as the payment will not have yet been received.</i><p>                 
                    <?= $this->tooltip_click_here_link('payments-bacs') ?>                
            </div>              

            <div id="payment-mapping-default-tip">
                <h3>Default Payment Mapping</h3>
                <p>When an order is placed via WooCommerce, it will be sent to your Easify Server along with a payment record that describes
                    how the order was paid for. Here you can configure how the payment records are sent to your Easify server.</p>   
                <p><b>Easify Payment Method:</b> Select which payment method will be 
                    recorded in Easify for payments where the payment method is not recognised by the Easify plugin. <i>We recommend 
                        selecting <b>Other</b></i>.<p>
                <p><b>Easify Payment Account:</b> Select which account the payment will 
                    be associated with in Easify for payments where the payment method is not recognised by the Easify plugin. <i>We recommend 
                        selecting <b>Current</b></i>.<p>                   
                <p><b>Enabled:</b> Tick this to enable payments to be recorded in Easify when a 
                    payment is received where the payment method is not recognised by the Easify plugin. <i>We 
                        recommend <b>disabling</b> default payments from being sent to 
                        Easify as the payment may not have yet been received.</i><p>   
                <p><i><b>Note: </b>If you have a payment method in WooCommerce that is not listed here, you 
                        can modify the Default Payment Mapping to suit the unknown payment method. For example 
                        if the unknown payment method is an unrecognised credit card processor you could 
                        set the default payment mapping to use the Credit Card payment method, the Current payment
                        account and to be enabled.</i></p>
                <?= $this->tooltip_click_here_link('payments-default') ?>                
            </div>  


            <!-- OPTIONS LOGGING -->
            <div id="logging-tip">
                <h3>Easify Plugin Logging</h3>
                <p>Here you can enable logging for the Easify WooCommerce Plugin.</p>
                <p>When this option is enabled the Easify Plugin will record diagnostic logs to help you troubleshoot
                    problems with your Easify WooCommerce integration.</p>
                <p>The log file will be saved to: <?= plugin_dir_url(__FILE__) . 'logs/easify_log.txt' ?></p>       
                <p class="easify-warning"><b>NOTE:</b> We recommend only enabling logging for troubleshooting problems, as the log file can 
                very rapidly grow to take up a lot of disk space.</p>
                <?= $this->tooltip_click_here_link('logging-logging') ?>                
            </div> 



        </div>
        <?php
    }

    /**
     * Renders a hyperlink to the specified url which will open a new tab with the 
     * corresponding Easify help article.
     * 
     * id string $url The id of the part of the help page that the link should take you to
     */
    private function tooltip_click_here_link($id) {
        ?>
        <a href="<?= EASIFY_HELP_BASE_URL . '/Help/ecommerce_woocommerce_plugin_settings#' . $id ?>" target="_blank" title ="Opens in a new tab..." >Click here for more info...</a>  
        <?php
    }

}
?>