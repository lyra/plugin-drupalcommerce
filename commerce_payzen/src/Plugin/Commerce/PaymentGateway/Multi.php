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
use Drupal\commerce_payzen\Tools;

/**
 * Provides payment in installments with the PayZen payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payzen_multi",
 *   label = @Translation("PayZen - Payment in installments"),
 *   display_label = @Translation("Payment by credit card in installments"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_payzen\PluginForm\MultiForm"
 *   },
 *   modes = {
 *     "TEST" = @Translation("TEST"),
 *     "PRODUCTION" = @Translation("PRODUCTION")
 *   }
 * )
 */
class Multi extends Payzen
{

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        return [
            'payment_options' => [
                'first' => '',
                'count' => '3',
                'period' => '30'
            ]
        ] + parent::defaultConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        // configure multiple payment options
        $form['payment_options'] = [
            '#type' => 'details',
            '#open' => true,
            '#title' => $this->t('PAYMENT IN INSTALLMENTS OPTIONS')
        ];

        $form['payment_options']['first'] = [
            '#type' => 'textfield',
            '#title' => $this->t('First payment'),
            '#description' => $this->t('Amount of first payment, in percentage of total amount. If empty, all payments will have the same amount.'),
            '#default_value' => $this->configuration['payment_options']['first']
        ];

        $form['payment_options']['count'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Number of payments'),
            '#description' => $this->t('Total number of payments.'),
            '#default_value' => $this->configuration['payment_options']['count']
        ];

        $form['payment_options']['period'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Period'),
            '#description' => $this->t('Delay in days between payments.'),
            '#default_value' => $this->configuration['payment_options']['period']
        ];

        if (Tools::$pluginFeatures['restrictmulti']) {
            $msg = '<p class="payzen-multi-warn">';
            $msg .= $this->t('ATTENTION: The payment in installments feature activation is subject to the prior agreement of Société Générale.<br />If you enable this feature while you have not the associated option, an error 10000 – INSTALLMENTS_NOT_ALLOWED or 07 - PAYMENT_CONFIG will occur and the buyer will not be able to pay.');
            $msg .= '</p>';

            $warn = [
                'error' => [
                    '#type' => 'item',
                    '#title' => '',
                    '#markup' => $msg
                ]
            ];

            $form = $warn + $form;
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateConfigurationForm($form, $form_state);

        if ($form_state->getErrors() || ! $form_state->isSubmitted()) {
            return;
        }

        $values = $form_state->getValue($form['#parents']);

        $first = $values['payment_options']['first'];
        $count = $values['payment_options']['count'];
        $period = $values['payment_options']['period'];

        if ($first && (! is_numeric($first) || $first < 0 || $first > 100)) {
            $field = $form['payment_options']['first'];
            $value = $first;
        } elseif (! is_numeric($count) || $count <= 1) {
            $field = $form['payment_options']['count'];
            $value = $count;
        } elseif (! is_numeric($period) || $period <= 0) {
            $field = $form['payment_options']['period'];
            $value = $period;
        }

        if (isset($field)) {
            $label = $field['#title']->render();

            $form_state->setError($field, sprintf($this->t('Invalid value « %1$s » for field « %2$s ».'), $value, $label));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitConfigurationForm($form, $form_state);

        if ($form_state->getErrors()) {
            return;
        }

        $values = $form_state->getValue($form['#parents']);
        $this->configuration['payment_options']['first'] = $values['payment_options']['first'];
        $this->configuration['payment_options']['count'] = $values['payment_options']['count'];
        $this->configuration['payment_options']['period'] = $values['payment_options']['period'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedPaymentMeans()
    {
        $multi_cards = [
            'AMEX', 'CB', 'DINERS', 'DISCOVER', 'E-CARTEBLEUE', 'JCB', 'MASTERCARD',
            'PRV_BDP', 'PRV_BDT', 'PRV_OPT', 'PRV_SOC', 'VISA', 'VISA_ELECTRON', 'VPAY'
        ];

        $cards = [];
        foreach (\PayzenApi::getSupportedCardTypes() as $code => $label) {
            if (in_array($code, $multi_cards)) {
                $cards[$code] = $label;
            }
        }

        return $cards;
    }
}
