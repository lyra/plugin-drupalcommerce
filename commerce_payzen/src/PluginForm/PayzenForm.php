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
namespace Drupal\commerce_payzen\PluginForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_payzen\Tools;

class PayzenForm extends PaymentOffsiteForm
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $request = $this->buildPayzenRequest($form, $form_state);

        return $this->buildRedirectForm(
            $form,
            $form_state,
            $request->get('platform_url'),
            $request->getRequestFieldsArray(false, false),
            PaymentOffsiteForm::REDIRECT_POST
        );
    }

    protected function getPluginConfiguration()
    {
        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;

        /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
        $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
        return $payment_gateway_plugin->getConfiguration();
    }

    protected function buildPayzenRequest(array $form, FormStateInterface $form_state)
    {
        $logger = \Drupal::logger('commerce_payzen');

        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;

        $configuration = $this->getPluginConfiguration();

        require_once(drupal_get_path('module', 'commerce_payzen') . '/includes/PayzenRequest.php');

        $request = new \PayzenRequest();

        /** @var \PayzenCurrency $currency */
        $currency = \PayzenApi::findCurrencyByAlphaCode($payment->getAmount()->getCurrencyCode());
        if (! $currency) {
            $msg = "The used currency {$payment->getAmount()->getCurrencyCode()} is not supported. PayZen module cannot be used.";

            throw new PaymentGatewayException($msg);
        }

        // Set payment gateway access parameters.
        foreach ($configuration['gateway_access'] as $name => $value) {
            $request->set($name, $value);
        }

        // Set return to shop parameters.
        foreach ($configuration['return_to_shop'] as $name => $value) {
            $request->set($name, $value);
        }

        // return URL
        $request->set('url_return', $form['#return_url']);

        // Cancel URL.
        $request->set('url_cancel', $form['#cancel_url']);

        // Set payment page parameters.

        // Set payment page language.
        $default_lang = $configuration['payment_page']['language'];
        $current_lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $lang = \PayzenApi::isSupportedLanguage($current_lang) ? $current_lang : $default_lang;
        $request->set('language', $lang);

        // Set available languages.
        $languages = $configuration['payment_page']['available_languages'];
        if ($languages) {
            $available_languages = is_array($languages) ? implode(';', $languages) : $languages;
            $request->set('available_languages', $available_languages);
        }

        // Set payment card types.
        $cards = $configuration['payment_page']['payment_cards'];
        if ($cards) {
            $request->set('payment_cards', is_array($cards) ? implode(';', $cards) : $cards);
        }

        // Set other page parameters.
        $request->set('capture_delay', $configuration['payment_page']['capture_delay'] );
        $request->set('validation_mode', $configuration['payment_page']['validation_mode']);

        // Set misc parameters.

        // Get current Drupal Commerce version.
        if (function_exists('system_get_info')) {
            $info = system_get_info('module', 'commerce');
        } else {
            $info = \Drupal::service('extension.list.module')->getExtensionInfo('commerce');
        }

        $version = \DRUPAL::VERSION . '_' . substr($info['version'], strpos($info['version'], '-') + 1);

        $order = $payment->getOrder();

        $order_params = [
            'amount' => $currency->convertAmountToInteger($payment->getAmount()->getNumber()),
            'currency' => $currency->getNum(),
            'contrib' => Tools::CMS_IDENTIFIER . '_' . Tools::PLUGIN_VERSION . '/' . $version . '/' . PHP_VERSION,
            'order_id' => $order->id(),
            'cust_email' => $order->getEmail(),
            'cust_id' => $order->getCustomerId()
       ];

        $address = $order->getBillingProfile()->get('address')->first();

        // Billing address info.
        if ($address) {
            $order_params += [
                'cust_first_name' => $address->getGivenName(),
                'cust_last_name' => $address->getFamilyName(),
                'cust_address' => $address->getAddressLine1() . ' ' . $address->getAddressLine2(),
                'cust_state' => $address->getAdministrativeArea(),
                'cust_city' => $address->getLocality(),
                'cust_zip' => $address->getPostalCode(),
                'cust_country' => $address->getCountryCode()
            ];
        }

        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('commerce_shipping')) {
            // Check if the order references shipments
            if ($order->hasField('shipments') && !$order->get('shipments')->isEmpty()) {
                // Gather the shipping profiles and only send shipping information if
                // there's only one shipping profile referenced by the shipments.
                $shipping_profiles = [];

                // Loop over the shipments to collect shipping profiles
                foreach ($order->get('shipments')->referencedEntities() as $shipment) {
                    if ($shipment->get('shipping_profile')->isEmpty()) {
                        continue;
                    }

                    $shipping_profiles[] = $shipment->getShippingProfile();
                }

                // Don't send the shipping profile if we find more than one.
                if ($shipping_profiles && count($shipping_profiles) === 1) {
                    $shipping_profile = reset($shipping_profiles);

                    /** @var \Drupal\address\AddressInterface $address */
                    $address = $shipping_profile->address->first();

                    // Shipping address info.
                    if ($address) {
                        $order_params += [
                            'ship_to_first_name' => $address->getGivenName(),
                            'ship_to_last_name' => $address->getFamilyName(),
                            'ship_to_street' => $address->getAddressLine1(),
                            'ship_to_street2' => $address->getAddressLine2(),
                            'ship_to_state' => $address->getAdministrativeArea(),
                            'ship_to_city' => $address->getLocality(),
                            'ship_to_zip' => $address->getPostalCode(),
                            'ship_to_country' => $address->getCountryCode()
                        ];
                    }
                }
            }
        }

        $request->setFromArray($order_params);

        // Activate 3DS ?
        $decimal_amount = (int) $payment->getAmount()->getNumber();
        $threeds_mpi = null;
        $threeds_min_amount = $configuration['selective_threeds']['threeds_min_amount'];
        if ($threeds_min_amount && ($decimal_amount < $threeds_min_amount)) {
            $threeds_mpi = '2';
        }

        $request->set('threeds_mpi', $threeds_mpi);

        $logger->info("Client #{$order->getCustomerId()} will be sent to payment gateway for order #{$order->id()}.");

        return $request;
    }
}
