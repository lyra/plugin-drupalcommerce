<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen for Drupal Commerce. See COPYING.md for license details.
 *
 * @package   Payzen
 * @author    Lyra Network <contact@lyra-network.com>
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL v2)
 */
namespace Drupal\commerce_payzen\Plugin\Commerce\PaymentGateway;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides PayPal payment through the PayZen payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payzen_paypal",
 *   label = @Translation("PayZen - PayPal Payment"),
 *   display_label = @Translation("Payment with PayPal"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_payzen\PluginForm\PaypalForm"
 *   },
 *   modes = {
 *     "TEST" = @Translation("TEST"),
 *     "PRODUCTION" = @Translation("PRODUCTION")
 *   }
 * )
 */
class Paypal extends Payzen
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        // cannot configure payment cards for PayPal payment
        unset($form['payment_page']['payment_cards']);

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedPaymentMeans()
    {
        return [
            'PAYPAL' => 'PayPal',
            'PAYPAL_SB' => 'PayPal - Sandbox'
        ];
    }
}
