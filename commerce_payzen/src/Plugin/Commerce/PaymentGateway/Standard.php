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

/**
 * Provides the PayZen payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payzen_standard",
 *   label = @Translation("PayZen - One-time payment"),
 *   display_label = @Translation("Payment by credit card"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_payzen\PluginForm\StandardForm"
 *   },
 *   modes = {
 *     "TEST" = @Translation("TEST"),
 *     "PRODUCTION" = @Translation("PRODUCTION")
 *   }
 * )
 */
class Standard extends Payzen
{

    /**
     * {@inheritdoc}
     */
    protected function getSupportedPaymentMeans()
    {
        return \PayzenApi::getSupportedCardTypes();
    }
}
