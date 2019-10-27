<?php
	/*
	*	Payment Plugin
	*	---------------------------------------------------------------------
	*	creating the wechat payment option
	*	---------------------------------------------------------------------
	*/

	add_filter('goodlayers_wechat_payment_gateway_options', 'goodlayers_wechat_payment_gateway_options');
	if( !function_exists('goodlayers_wechat_payment_gateway_options') ){
		function goodlayers_wechat_payment_gateway_options( $options ){
			$options['wechat'] = esc_html__('wechat', 'tourmaster');

			return $options;
		}
	}
	add_filter('goodlayers_plugin_payment_option', 'goodlayers_wechat_payment_option');
	if( !function_exists('goodlayers_wechat_payment_option') ){
		function goodlayers_wechat_payment_option( $options ){

			$options['wechat'] = array(
				'title' => esc_html__('wechat', 'tourmaster'),
				'options' => array(
					'wechat-secret-key' => array(
						'title' => __('Wechat Secret Key', 'tourmaster'),
						'type' => 'text'
					),
					'wechat-publishable-key' => array(
						'title' => __('Wechat Publishable Key', 'tourmaster'),
						'type' => 'text'
					),
					'wechat-currency-code' => array(
						'title' => __('Wechat Currency Code', 'tourmaster'),
						'type' => 'text',
						'default' => 'usd'
					),
				)
			);

			return $options;
		} // goodlayers_wechat_payment_option
	}

	$current_payment_gateway = apply_filters('goodlayers_payment_get_option', '', 'wechat-payment-gateway');
	if( $current_payment_gateway == 'wechat' ){
		if( !class_exists('Stripe\Stripe') ){
			include_once(TOURMASTER_LOCAL . '/include/stripe/init.php');
		}

		add_action('goodlayers_payment_page_init', 'goodlayers_wechat_payment_page_init');
		add_filter('goodlayers_plugin_payment_attribute', 'goodlayers_wechat_payment_attribute');
		add_filter('goodlayers_wechat_payment_form', 'goodlayers_wechat_payment_form', 10, 2);

		add_action('wp_ajax_wechat_payment_charge', 'goodlayers_wechat_payment_charge');
		add_action('wp_ajax_nopriv_wechat_payment_charge', 'goodlayers_wechat_payment_charge');
	}

	// init the script on payment page head
	if( !function_exists('goodlayers_wechat_payment_page_init') ){
		function goodlayers_wechat_payment_page_init( $options ){
			add_action('wp_head', 'goodlayers_wechat_payment_script_include');
		}
	}
	if( !function_exists('goodlayers_wechat_payment_script_include') ){
		function goodlayers_wechat_payment_script_include( $options ){
			echo '<script src="https://js.stripe.com/v3/"></script>';
		}
	}

	// add attribute for payment button
	if( !function_exists('goodlayers_wechat_payment_attribute') ){
		function goodlayers_wechat_payment_attribute( $attributes ){
			return array('method' => 'ajax', 'type' => 'wechat');
		}
	}

	// payment form
	if( !function_exists('goodlayers_wechat_payment_form') ){
		function goodlayers_wechat_payment_form( $ret = '', $tid = '' ){

			// get the price
			$api_key = trim(apply_filters('goodlayers_payment_get_option', '', 'wechat-secret-key'));
			$currency = trim(apply_filters('goodlayers_payment_get_option', 'usd', 'wechat-currency-code'));

			$t_data = apply_filters('goodlayers_payment_get_transaction_data', array(), $tid, array('price'));
			$price = '';
			if( $t_data['price']['deposit-price'] ){
				$price = $t_data['price']['deposit-price'];
				if( !empty($t_data['price']['deposit-price-raw']) ){
					$deposit_amount = $t_data['price']['deposit-price-raw'];
				}
			}else if( !empty($t_data['price']['pay-amount']) ){
				$price = $t_data['price']['pay-amount'];
			}
			$price = round(floatval($price) * 100);

			// set payment intent

			$publishable_key = apply_filters('goodlayers_payment_get_option', '', 'wechat-publishable-key');

			ob_start();
?>





<div class="goodlayers-payment-form goodlayers-with-border" >


	<form action="" method="POST" id="goodlayers-wechat-payment-form" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" >


<div id="main">
		<div class="payment-info wechat">
			<div id="wechat-qrcode"></div>
      <div id="confirm"></div>
			<p class="notice">Click the button below to generate a QR code for WeChat.</p>
</div>
</div>
		<div class="goodlayers-payment-form-field">
			<label>
				<span class="goodlayers-payment-field-head" ><?php esc_html_e('Card Information', 'tourmaster'); ?></span>
			</label>
			<div id="card-element"></div>
		</div>

		<input type="hidden" name="tid" value="<?php echo esc_attr($tid) ?>" />

		<!-- error message -->
		<div class="payment-errors"></div>
		<div class="goodlayers-payment-req-field" ><?php esc_html_e('Please fill all required fields', 'tourmaster'); ?></div>

		<!-- submit button -->
		<button id="card-button" data-secret="<?= $intent->client_secret ?>"><?php esc_html_e('Submit Payment', 'tourmaster'); ?></button>

		<!-- for proceeding to last step -->
		<div class="goodlayers-payment-plugin-complete" ></div>
	</form>
</div>

<!-- <script src="QRCODEGENERATOR/qrcode.min.js"></script> -->
 <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js" integrity="sha384-3zSEDfvllQohrq0PHL1fOXJuC/jSOO34H46t6UQfobFOmxE5BpjjaIJY5F2/bMnU" crossorigin="anonymous"></script>
<script src="https://js.stripe.com/v3/"></script>

<script type="text/javascript">


  /**
  * Monitor the status of a source after a redirect flow.
  *
  * This means there is a `source` parameter in the URL, and an active PaymentIntent.
  * When this happens, we'll monitor the status of the PaymentIntent and present real-time
  * information to the user.
  */

  let paymentIntent;
	var stripe = Stripe('pk_test_BmKhssAJPSS3fmBcnS2TfJMW00qmrEmeMo');
	var elements = stripe.elements();

  const sourceData = {
   type: 'wechat',
   amount: <?php echo $price;?>,
   currency: 'eur',
   owner: {
     'Lucas',
     'email',
   },
   redirect: {
     return_url: window.location.href,
   },
   statement_descriptor: 'Stripe Payments Demo',
   metadata: {
     paymentIntent: paymentIntent.id,
   },
};



  // Handle activation of payment sources not yet supported by PaymentIntents
  const handleSourceActiviation = source => {
    const mainElement = document.getElementById('main');
    const confirmationElement = document.getElementById('confirmation');
    switch (source.flow) {
      case 'none':
        // Normally, sources with a `flow` value of `none` are chargeable right away,
        // but there are exceptions, for instance for WeChat QR codes just below.
        if (source.type === 'wechat') {
          // Display the QR code.
          const qrCode = new QRCode('wechat-qrcode', {
            text: source.wechat.qr_code_url,
            width: 128,
            height: 128,
            colorDark: '#424770',
            colorLight: '#f8fbfd',
            correctLevel: QRCode.CorrectLevel.H,
          });
          // Hide the previous text and update the call to action.
          form.querySelector('.payment-info.wechat p').style.display = 'none';
          let amount = store.formatPrice(
            store.getPaymentTotal(),
            config.currency
          );
          submitButton.textContent = `Scan this QR code on WeChat to pay ${amount}`;
          // Start polling the PaymentIntent status.
          pollPaymentIntentStatus(paymentIntent.id, 300000);
        } else {
          console.log('Unhandled none flow.', source);
        }
        break;
      case 'redirect':
        // Immediately redirect the customer.
        submitButton.textContent = 'Redirecting…';
        window.location.replace(source.redirect.url);
        break;
      case 'code_verification':
        // Display a code verification input to verify the source.
        break;
      case 'receiver':
        // Display the receiver address to send the funds to.
        mainElement.classList.add('success', 'receiver');
        const receiverInfo = confirmationElement.querySelector(
          '.receiver .info'
        );
        let amount = store.formatPrice(source.amount, config.currency);
        switch (source.type) {
          case 'ach_credit_transfer':
            // Display the ACH Bank Transfer information to the user.
            const ach = source.ach_credit_transfer;
            receiverInfo.innerHTML = `
              <ul>
                <li>
                  Amount:
                  <strong>${amount}</strong>
                </li>
                <li>
                  Bank Name:
                  <strong>${ach.bank_name}</strong>
                </li>
                <li>
                  Account Number:
                  <strong>${ach.account_number}</strong>
                </li>
                <li>
                  Routing Number:
                  <strong>${ach.routing_number}</strong>
                </li>
              </ul>`;
            break;
          case 'multibanco':
            // Display the Multibanco payment information to the user.
            const multibanco = source.multibanco;
            receiverInfo.innerHTML = `
              <ul>
                <li>
                  Amount (Montante):
                  <strong>${amount}</strong>
                </li>
                <li>
                  Entity (Entidade):
                  <strong>${multibanco.entity}</strong>
                </li>
                <li>
                  Reference (Referencia):
                  <strong>${multibanco.reference}</strong>
                </li>
              </ul>`;
            break;
          default:
            console.log('Unhandled receiver flow.', source);
        }
        // Poll the PaymentIntent status.
        pollPaymentIntentStatus(paymentIntent.id);
        break;
      default:
        // Customer's PaymentIntent is received, pending payment confirmation.
        break;
    }
  };

  /**
   * Monitor the status of a source after a redirect flow.
   *
   * This means there is a `source` parameter in the URL, and an active PaymentIntent.
   * When this happens, we'll monitor the status of the PaymentIntent and present real-time
   * information to the user.
   */

  const pollPaymentIntentStatus = async (
    paymentIntent,
    timeout = 30000,
    interval = 500,
    start = null
  ) => {
    start = start ? start : Date.now();
    const endStates = ['succeeded', 'processing', 'canceled'];
    // Retrieve the PaymentIntent status from our server.
    const rawResponse = await fetch(`payment_intents/${paymentIntent}/status`);
    const response = await rawResponse.json();
    if (
      !endStates.includes(response.paymentIntent.status) &&
      Date.now() < start + timeout
    ) {
      // Not done yet. Let's wait and check again.
      setTimeout(
        pollPaymentIntentStatus,
        interval,
        paymentIntent,
        timeout,
        interval,
        start
      );
    } else {
      handlePayment(response);
      if (!endStates.includes(response.paymentIntent.status)) {
        // Status has not changed yet. Let's time out.
        console.warn(new Error('Polling timed out.'));
      }
    }
  };

  const url = new URL(window.location.href);
  const mainElement = document.getElementById('main');
  if (url.searchParams.get('source') && url.searchParams.get('client_secret')) {
    // Update the interface to display the processing screen.
    mainElement.classList.add('checkout', 'success', 'processing');

    const {source} =  stripe.retrieveSource({
      id: url.searchParams.get('source'),
      client_secret: url.searchParams.get('client_secret'),
    });

    // Poll the PaymentIntent status.
    pollPaymentIntentStatus(source.metadata.paymentIntent);
  } else {
    // Update the interface to display the checkout form.
    mainElement.classList.add('checkout');

    // Create the PaymentIntent with the cart details.
    // const response = await store.createPaymentIntent(
    //   config.currency,
    //   store.getLineItems()
    // );
    paymentIntent = response.paymentIntent;
  }
document.getElementById('main').classList.remove('loading');




const {source} = stripe.createSource(sourceData);
handleSourceActiviation(source);


</script>


<script type="text/javascript">

	(function($){
		var form = $('#goodlayers-wechat-payment-form');
		var tid = form.find('input[name="tid"]').val();

		var wechat = Stripe('<?php echo esc_js(trim($publishable_key)); ?>');
		var elements = wechat.elements();
		var cardElement = elements.create('card');
		cardElement.mount('#card-element');

		var cardholderName = document.getElementById('cardholder-name');
		var cardButton = document.getElementById('card-button');
		var clientSecret = cardButton.dataset.secret;
		cardButton.addEventListener('click', function(ev){
			form.find('.payment-errors, .goodlayers-payment-req-field').slideUp(200);

			// validate empty input field
			if( !form.find('#cardholder-name').val() ){
				var req = true;
			}else{
				var req = false;
			}

			// make the payment
			if( req ){
				form.find('.goodlayers-payment-req-field').slideDown(200);
			}else{

				// prevent multiple submission
				if( $(cardButton).hasClass('now-loading') ){
					return;
				}else{
					$(cardButton).prop('disabled', true).addClass('now-loading');
				}

				// made a payment
				wechat.handleCardPayment(
					clientSecret, cardElement, {
						payment_method_data: {
							billing_details: {name: cardholderName.value}
						}
					}
				).then(function(result){
					if( result.error ){

						$(cardButton).prop('disabled', false).removeClass('now-loading');

						// Display error.message in your UI.
						form.find('.payment-errors').text(result.error.message).slideDown(200);

					}else{

						// The payment has succeeded. Display a success message.
						$.ajax({
							type: 'POST',
							url: form.attr('data-ajax-url'),
							data: { 'action':'wechat_payment_charge', 'tid': tid, 'paymentIntent': result.paymentIntent },
							dataType: 'json',
							error: function(a, b, c){
								console.log(a, b, c);

								// display error messages
								form.find('.payment-errors').text('<?php echo esc_html__('An error occurs, please refresh the page to try again.', 'tourmaster'); ?>').slideDown(200);
								form.find('.submit').prop('disabled', false).removeClass('now-loading');
							},
							success: function(data){
								if( data.status == 'success' ){
									form.find('.goodlayers-payment-plugin-complete').trigger('click');
								}else if( typeof(data.message) != 'undefined' ){
									form.find('.payment-errors').text(data.message).slideDown(200);
								}
							}
						});

					}
				});
			}
		});
		$(cardButton).on('click', function(){
			return false;
		});
	})(jQuery);
</script>
<?php
			$ret = ob_get_contents();
			ob_end_clean();
			return $ret;
		}
	}

	// ajax for payment submission
	if( !function_exists('goodlayers_wechat_payment_charge') ){
		function goodlayers_wechat_payment_charge(){

			$ret = array();

			if( !empty($_POST['paymentIntent']) && !empty($_POST['tid']) ){
				$payment_intent = tourmaster_process_post_data($_POST['paymentIntent']);

				if( !empty($payment_intent['id']) ){
					$api_key = trim(apply_filters('goodlayers_payment_get_option', '', 'wechat-secret-key'));

					\stripe\Stripe::setApiKey($api_key);
					$pi = \stripe\PaymentIntent::retrieve($payment_intent['id']);

					if( $pi['status'] == 'succeeded' && $pi['metadata']->tid == $_POST['tid'] ){

						// collect payment information
						$payment_info = array(
							'payment_method' => 'wechat',
							'amount' => ($pi['amount'] / 100),
							'transaction_id' => $pi['id'],
							'payment_status' => 'paid',
							'submission_date' => current_time('mysql')
						);

						// additional data for payment fee
						$t_data = apply_filters('goodlayers_payment_get_transaction_data', array(), $_POST['tid'], array('price', 'email'));
						if( $t_data['price']['deposit-price'] ){
							if( !empty($t_data['price']['deposit-price-raw']) ){
								$deposit_amount = $t_data['price']['deposit-price-raw'];
							}
						}else{
							if( !empty($t_data['price']['pay-amount-raw']) ){
								$pay_amount = $t_data['price']['pay-amount-raw'];
							}
						}
						if( !empty($deposit_amount) ){
							$payment_info['deposit_amount'] = $deposit_amount;
						}
						if( !empty($pay_amount) ){
							$payment_info['pay_amount'] = $pay_amount;
						}

						// update data
						do_action('goodlayers_set_payment_complete', $_POST['tid'], $payment_info);

						$ret['status'] = 'success';

					}
				}
			}

			die(json_encode($ret));

		} // goodlayers_stripe_payment_charge
	}
