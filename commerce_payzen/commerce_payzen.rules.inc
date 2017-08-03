<?php
#####################################################################################################
#
#					Module pour la plateforme de paiement PayZen
#						Version : 1.1a (révision 50229)
#									########################
#					Développé pour Drupal_Commerce
#						Version : 7.x-1.1
#						Compatibilité plateforme : V2
#									########################
#					Développé par Lyra Network
#						http://www.lyra-network.com/
#						12/08/2013
#						Contact : support@payzen.eu
#
#####################################################################################################

function commerce_payzen_rules_action_info() {
	$actions = array();
	
	$payment_method = commerce_payment_method_load('payzen');
	
	// Add an action for enable or disable PayZen payment method.
	$actions['commerce_payment_enable_payzen'] = array(
		'label' => t('Enable payment method: @method', array('@method' => $payment_method['title'])),
	    'parameter' => array(
	    	'commerce_order' => array('type' => 'commerce_order', 'label' => t('Order', array(), array('context' => 'a drupal commerce order'))),
	        'payment_method' => array(
	        	'type' => 'commerce_payment_settings',
	        	'restriction' => 'input',
	        	'label' => t('Payment settings'),
	        	'payment_method' => $payment_method['method_id'],
			),
		),
	   	'group' => t('Commerce Payment'),
		'parent' => 'payment',
	    'base' => $payment_method['base'],
	    'callbacks' => array(
	    	'execute' => 'commerce_payzen_enable_method',
		)
	);
	
	return $actions;
}

/**
* Specific execution callback for PayZen payment method.
*/
function commerce_payzen_enable_method($order, $payment_method, $action_settings, $rule_state, $action, $callback_type) {
	$payzen = new PayzenApi();
	
	// price array
	$price = $order->commerce_order_total['und'][0];
	
	$currency_obj = $payzen->findCurrencyByAlphaCode($price['currency_code']);
	if($currency_obj == null) {
		watchdog('commerce_payzen',
			'The current currency is not supported by PayZen payment method :  %currency',
			array('%currency' => $price['currency_code']),
			WATCHDOG_INFO
		);
		
		return ;
	}
	
	// Initialize variables for the payment method ID and settings.
	if (is_array($payment_method)) {
		$method_id = $payment_method['method_id'];
		$settings = !empty($payment_method['settings']) ? $payment_method['settings'] : array();
	} else {
		$method_id = $payment_method;
		$settings = array();
	}
	
	$amount_min = !empty($settings) ? $settings['payzen_amount_restrictions']['payzen_amount_min'] : '';
	$amount_max = !empty($settings) ? $settings['payzen_amount_restrictions']['payzen_amount_max'] : '';

	$amount = commerce_currency_amount_to_decimal($price['amount'], $price['currency_code']);
	
	if((isset($amount_min) && $amount_min !== '' && $amount < $amount_min)
		|| (isset($amount_max) && $amount_max !== '' && $amount > $amount_max)) {
		
		watchdog('commerce_payzen',
			'This payment method is not available for the current cart amount.',
			array(),
			WATCHDOG_INFO
		);
		
		return ;
	}
	
	// ... else call default execution callback
	
	// Find the Rule that contains this action.
	$rule = $action->parentElement();
	
	while ($rule->getPluginName() != 'reaction rule') {
		$rule = $rule->parentElement();
	}
	
	// Create a unique key for the instance of the payment method represented by
	// this action.
	$instance_id = commerce_payment_method_instance_id($method_id, $rule);
	
	// Set the payment method to the order along with its settings and context.
	$order->payment_methods[$instance_id] = array(
	    'method_id' => $method_id,
	    'settings' => $settings,
	    'rule_name' => $rule->name,
	    'weight' => $rule->weight
	);
}

?>