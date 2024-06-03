<?php
/*
Plugin Name: Stripe Payment
Description: Handle Stripe payments and display payment form via shortcode.
Version: 1.0
Author: Mehdi Akbar
*/

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

register_activation_hook(__FILE__, 'stripe_payment_activate');

function stripe_payment_activate() {
    create_payment_table();
}

function create_payment_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stripe_payments';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        payment_intent_id varchar(255) NOT NULL,
        amount decimal(10,2) NOT NULL,
        payment_status varchar(50) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function enqueue_stripe_scripts() {
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null);
    wp_enqueue_script('stripe-payment-script', plugin_dir_url(__FILE__) . 'stripe-payment-script.js', array('jquery'), null, true);
    wp_localize_script('stripe-payment-script', 'stripe_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('stripe-nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_stripe_scripts');

// Shortcode for displaying payment form
function stripe_payment_form_shortcode($atts) {
    // Start session if not already started
    if (!session_id()) {
        session_start();
    }

    $atts = shortcode_atts(array(
        'amount' => '0',
    ), $atts);

    $amount = isset($_GET['amount']) ? $_GET['amount'] : $atts['amount'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; // Retrieve user ID from session

    ob_start(); ?>
    <form action="" method="POST" id="payment-form">
        <div class = 'amt'>  
        <p>Amount to Pay: <b>$</b><?php echo '<b>'.$amount.'</b>'; ?></p>
</div>

        <label for="card_number">Credit Card Number:</label>
        <div id="card-number-element"><!-- Stripe.js injects the Card Element --></div>
<div class = 'credit'>
    <div class="expire">
        <label for="expiry_date">Expiry Date:</label>
        <div id="card-expiry-element"><!-- Stripe.js injects the Card Element --></div>
        
        </div>
<div class="cvv">
        <label for="cvv">CVV:</label>
        <div id="card-cvc-element"><!-- Stripe.js injects the Card Element --></div>
        </div>
</div>
        <!-- Hidden input field to pass the amount to the payment processing page -->
        <input type="hidden" name="amount" value="<?php echo $amount; ?>">
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

        <button type="submit" id="submit-button">Pay Now</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('stripe_payment_form', 'stripe_payment_form_shortcode');

// AJAX handler for processing payments
add_action('wp_ajax_process_stripe_payment', 'process_stripe_payment');
add_action('wp_ajax_nopriv_process_stripe_payment', 'process_stripe_payment');

function process_stripe_payment() {
    check_ajax_referer('stripe-nonce', 'security');

    $payment_method = isset($_POST['paymentMethod']) ? $_POST['paymentMethod'] : '';
    $amount = isset($_POST['amount']) ? $_POST['amount'] : '';
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    
        $return_url = home_url(); // Change this to the appropriate return URL for your site


    require_once WP_CONTENT_DIR . '/libs/stripe/init.php';

    \Stripe\Stripe::setApiKey('sk_test_51PGJqfSFxurPD9mdwjvZIDFv9Ux02WkreOlgYz8j5jC3J565FAhZSx7se7CNFTTTYPF7pZFBxMGR2GSS6BETohNK00D12nuSPO');

    try {
        $payment_intent = \Stripe\PaymentIntent::create([
            'amount' => $amount * 100,
            'currency' => 'usd',
            'payment_method' => $payment_method,
            'confirmation_method' => 'manual',
            'confirm' => true,
            'return_url' => $return_url, // Specify the return URL here

        ]);
       // print_r($payment_intent);
               insert_payment_details($payment_intent->id, $amount,$user_id);


        wp_send_json_success($payment_intent);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        wp_send_json_error($e->getMessage());
    }

    wp_die();
}


// Function to insert payment details into the database
function insert_payment_details($payment_intent_id, $amount,$user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stripe_payments';
    $user_id = $_SESSION['user_id']; // Get current user ID
    $payment_status = 'Pending'; // Initial payment status

    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'payment_intent_id' => $payment_intent_id,
            'amount' => $amount,
            'payment_status' => $payment_status,
        )
    );
}



