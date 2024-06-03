document.addEventListener("DOMContentLoaded", function() {
    var stripe = Stripe('pk_test_51PGJqfSFxurPD9md9l33r6RQ77px0KREGQGkP53wPdCceEuphuZB0y5h9VIgJZVqVAgHP8qnN0vN2X75iE20HdBz00qR2iKrTP');
    var elements = stripe.elements();

    var style = {
        base: {
            fontSize: '16px',
            color: '#32325d',
        }
    };

    var cardNumberElement = elements.create('cardNumber', {
        style: style
    });
    cardNumberElement.mount('#card-number-element');

    var cardExpiryElement = elements.create('cardExpiry', {
        style: style
    });
    cardExpiryElement.mount('#card-expiry-element');

    var cardCvcElement = elements.create('cardCvc', {
        style: style
    });
    cardCvcElement.mount('#card-cvc-element');

    var form = document.getElementById('payment-form');

    form.addEventListener('submit', function(event) {
        event.preventDefault();

        stripe.createPaymentMethod({
            type: 'card',
            card: cardNumberElement,
            billing_details: {
                // Include any additional billing details here
            }
        }).then(function(result) {
            if (result.error) {
                console.error(result.error.message);
            } else {
                handlePayment(result.paymentMethod.id);
            }
        });
    });

    function handlePayment(paymentMethod) {
        var amount = document.querySelector('input[name="amount"]').value;
        var user_id = document.querySelector('input[name="user_id"]').value;
        
        jQuery.post(stripe_params.ajax_url, {
            action: 'process_stripe_payment',
            security: stripe_params.security,
            paymentMethod: paymentMethod,
            amount: amount,
            user_id:user_id
        }, function(response) {
            console.log(response);
            // Handle response from server
        });
    }
});

// jQuery to handle click event of "For businesses" button
    jQuery(document).ready(function($) {
        $('#busin').on('click', function(event) {
            event.preventDefault(); // Prevent default action of the anchor tag
            
            // Hide the Enter_popup container
            $('#Enter_popup').hide();
        });
    });
