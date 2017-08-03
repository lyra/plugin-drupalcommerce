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

/**
 * Implements hook_default_rules_configuration().
 */
function commerce_payzen_default_rules_configuration() {
	$rules = array();
	
	// Add a reaction rule to update the order status to processing status upon checkout completion.
	$rule = rules_reaction_rule();
	
	$rule->label = commerce_payzen_t('PayZen - Update the order status on checkout completion');
	$rule->active = TRUE;
	
	$rule
		->event('commerce_checkout_complete')
		->action('commerce_order_update_status', array(
	      'commerce_order:select' => 'commerce-order',
	      'order_status' => 'processing',
	));
	
	$rules['commerce_payzen_order_status_update'] = $rule;
	
	return $rules;
}

?>