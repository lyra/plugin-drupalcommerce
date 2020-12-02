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

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\AuthenticationException;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payzen\Tools;
use Drupal\commerce_price\Price;

abstract class Payzen extends OffsitePaymentGatewayBase
{

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        return [
            'gateway_access' => [
                'site_id' => Tools::SITE_ID,
                'key_test' => Tools::KEY_TEST,
                'key_prod' => Tools::KEY_PROD,
                'ctx_mode' => Tools::CTX_MODE,
                'sign_algo' => Tools::SIGN_ALGO,
                'platform_url' => Tools::GATEWAY_URL
            ],
            'payment_page' => [
                'language' => Tools::LANGUAGE,
                'available_languages' => [],
                'capture_delay' => '',
                'validation_mode' => '',
                'payment_cards' => []
            ],
            'selective_threeds' => [
                'threeds_min_amount' => ''
            ],
            'return_to_shop' => [
                'redirect_enabled' => '0',
                'redirect_success_timeout' => '5',
                'redirect_success_message' => $this->t('Redirection to shop in a few seconds...'),
                'redirect_error_timeout' => '5',
                'redirect_error_message' => $this->t('Redirection to shop in a few seconds...'),
                'return_mode' => 'GET'
            ]

        ] + parent::defaultConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function getNotifyUrl()
    {
        return Url::fromRoute(
            'commerce_payzen.notify',
            [],
            ['absolute' => true]
        );
    }

    /**
     * Get all supported payment means.
     *
     * @return array[string][string]
     */
    protected abstract function getSupportedPaymentMeans();

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        require_once(drupal_get_path('module', 'commerce_payzen') . '/includes/PayzenApi.php');

        $form['#attached']['library'] = 'commerce_payzen/admin';

        // managed in gateway access section
        unset($form['mode']);

        // module information
        $form['module_info'] = [
            '#type' => 'details',
            '#open' => true,
            '#title' => $this->t('MODULE INFORMATION')
        ];
        $form['module_info']['developed_by'] = [
            '#type' => 'item',
            '#title' => $this->t('Developed by'),
            '#markup' => '<a target="_blank" href="http://www.lyra-network.com">Lyra Network</a>'
        ];
        $form['module_info']['contact_us'] = [
            '#type' => 'item',
            '#title' => $this->t('Contact us'),
            '#markup' => '<a href="mailto:' . Tools::SUPPORT_EMAIL . '">' . Tools::SUPPORT_EMAIL . '</a>'
        ];

        // Get current gateway plugin version
        $info = system_get_info('module', 'commerce_payzen');
        $version = substr($info['version'], strpos($info['version'], '-') + 1);

        $form['module_info']['contrib_version'] = [
            '#type' => 'item',
            '#title' => $this->t('Module version'),
            '#markup' => $version
        ];
        $form['module_info']['gateway_version'] = [
            '#type' => 'item',
            '#title' => $this->t('Gateway version'),
            '#markup' => Tools::GATEWAY_VERSION
        ];

        // Get documentation links
        $filenames = glob(drupal_get_path('module', 'commerce_payzen') . '/installation_doc/' . Tools::DOC_PATTERN);

        $doc_langs = array(
            'fr' => 'Français',
            'en' => 'English',
            'es' => 'Español'
            // complete when more languages are managed
        );

        $doc_files = array();
        foreach ($filenames as $filename) {
            $base_filename = basename($filename, '.pdf');
            $lang = substr($base_filename, -2); // extract language code

            $doc_files[$base_filename . '.pdf'] = $doc_langs[$lang];
        }

        if (! empty($doc_files)) {
            $doc = '<span class="payzen-doc-links">' . $this->t('Click to view the module configuration documentation :');
            foreach ($doc_files as $file => $lang) {
                $doc .= '<a href="' . base_path() . drupal_get_path('module', 'commerce_payzen') . '/installation_doc/' . $file . '" target="_blank">' . $lang . '</a>';
            }

            $doc .= '</span>';

            $form['module_info']['doc_links'] = [
                '#type' => 'item',
                '#title' => '',
                '#markup' => $doc
            ];
        }

        // payment gateway access
        $form['gateway_access'] = [
            '#type' => 'details',
            '#open' => true,
            '#title' => $this->t('PAYMENT GATEWAY ACCESS')
        ];
        $form['gateway_access']['site_id'] = [
            '#type' => 'textfield',
            '#payzen_field' => true,
            '#title' => $this->t('Shop ID'),
            '#description' => $this->t('The identifier provided by PayZen.'),
            '#default_value' => $this->configuration['gateway_access']['site_id'],
            '#attributes' => ['autocomplete' => 'off'],
            '#required' => true
        ];

        if (! Tools::$pluginFeatures['qualif']) {
            $form['gateway_access']['key_test'] = [
                '#type' => 'textfield',
                '#payzen_field' => true,
                '#title' => $this->t('Key in test mode'),
                '#description' => $this->t('Key provided by PayZen for test mode (available in PayZen Back Office).'),
                '#default_value' => $this->configuration['gateway_access']['key_test'],
                '#attributes' => ['autocomplete' => 'off'],
                '#required' => true
            ];
        }

        $form['gateway_access']['key_prod'] = [
            '#type' => 'textfield',
            '#payzen_field' => true,
            '#title' => $this->t('Key in production mode'),
            '#description' => $this->t('Key provided by PayZen (available in PayZen Back Office after enabling production mode).'),
            '#default_value' => $this->configuration['gateway_access']['key_prod'],
            '#attributes' => ['autocomplete' => 'off'],
            '#required' => true
        ];
        $form['gateway_access']['ctx_mode'] = [
            '#type' => 'select',
            '#payzen_field' => true,
            '#title' => $this->t('Mode'),
            '#description' => $this->t('The context mode of this module.'),
            '#options' => [
                'TEST' => $this->t('TEST'),
                'PRODUCTION' => $this->t('PRODUCTION')
            ],
            '#default_value' => $this->configuration['gateway_access']['ctx_mode']
        ];

        if (Tools::$pluginFeatures['qualif']) {
            $form['gateway_access']['ctx_mode']['#attributes']['disabled'] = 'disabled';
        }

        $form['gateway_access']['sign_algo'] = [
            '#type' => 'select',
            '#payzen_field' => true,
            '#title' => $this->t('Signature algorithm'),
            '#description' => $this->t('Algorithm used to compute the payment form signature. Selected algorithm must be the same as one configured in the PayZen Back Office.<br /><b>The HMAC-SHA-256 algorithm should not be activated if it is not yet available in the PayZen Back Office, the feature will be available soon.</b>'),
            '#options' => [
                'SHA-1' => 'SHA-1',
                'SHA-256' => 'HMAC-SHA-256'
            ],
            '#default_value' => $this->configuration['gateway_access']['sign_algo']
        ];

        if (Tools::$pluginFeatures['shatwo']) {
            $form['gateway_access']['sign_algo']['#description'] = preg_replace('#<br /><b>[^<>]+</b>#', '', $form['gateway_access']['sign_algo']['#description']);
        }

        $form['gateway_access']['ipn_url'] = [
            '#type' => 'item',
            '#title' => $this->t('Instant Payment Notification URL'),
            '#description' => '<span class="payzen-ipn-desc">
                    <img src="' . base_path() . drupal_get_path('module', 'commerce_payzen') . '/images/warn.png">' .
                    $this->t('URL to copy into your PayZen Back Office > Settings > Notification rules.') .
                '</span>',
            '#markup' => $this->getNotifyUrl()->toString()
        ];
        $form['gateway_access']['platform_url'] = [
            '#type' => 'textfield',
            '#payzen_field' => true,
            '#title' => $this->t('Payment page URL'),
            '#description' => $this->t('Link to the payment page.'),
            '#default_value' => $this->configuration['gateway_access']['platform_url'],
            '#required' => true
        ];

        // payment page settings
        $languages = array_map([$this, 't'], \PayzenApi::getSupportedLanguages()); // translate language labels

        $form['payment_page'] = [
            '#type' => 'details',
            '#title' => $this->t('PAYMENT PAGE')
        ];
        $form['payment_page']['language'] = [
            '#type' => 'select',
            '#payzen_field' => true,
            '#title' => $this->t('Default language'),
            '#description' => $this->t('Default language on the payment page.'),
            '#options' => $languages,
            '#default_value' => $this->configuration['payment_page']['language']
        ];
        $form['payment_page']['available_languages'] = [
            '#type' => 'select',
            '#payzen_field' => true,
            '#multiple' => true,
            '#title' => $this->t('Available languages'),
            '#description' => $this->t('Languages available on the payment page. If you do not select any, all the supported languages will be available.'),
            '#options' => $languages,
            '#default_value' => $this->configuration['payment_page']['available_languages']
        ];
        $form['payment_page']['capture_delay'] = [
            '#type' => 'textfield',
            '#payzen_field' => true,
            '#title' => $this->t('Capture delay'),
            '#description' => $this->t('The number of days before the bank capture (adjustable in your PayZen Back Office).'),
            '#default_value' => $this->configuration['payment_page']['capture_delay']
        ];
        $form['payment_page']['validation_mode'] = [
            '#type' => 'select',
            '#payzen_field' => true,
            '#title' => $this->t('Validation mode'),
            '#description' => $this->t('If manual is selected, you will have to confirm payments manually in your PayZen Back Office.'),
            '#options' => [
                '' => $this->t('PayZen Back Office configuration'),
                '0' => $this->t('Automatic'),
                '1' => $this->t('Manual')
            ],
            '#default_value' => $this->configuration['payment_page']['validation_mode']
        ];
        $form['payment_page']['payment_cards'] = [
            '#type' => 'select',
            '#payzen_field' => true,
            '#multiple' => true,
            '#title' => $this->t('Card Types'),
            '#description' => $this->t('The card type(s) that can be used for the payment. Select none to use gateway configuration.'),
            '#options' => $this->getSupportedPaymentMeans(),
            '#default_value' => $this->configuration['payment_page']['payment_cards']
        ];

        // prepare case method has payment options
        $form['payment_options'] = [];

        // selective 3DS
        $form['selective_threeds'] = [
            '#type' => 'details',
            '#title' => $this->t('SELECTIVE 3DS')
        ];
        $form['selective_threeds']['threeds_min_amount'] = [
            '#title' => $this->t('Disable 3DS'),
            '#type' => 'textfield',
            '#description' => $this->t('Amount below which 3DS will be disabled. Needs subscription to selective 3DS option. For more information, refer to the module documentation.'),
            '#default_value' => $this->configuration['selective_threeds']['threeds_min_amount']
        ];

        // return to shop settings
        $form['return_to_shop'] = [
            '#type' => 'details',
            '#title' => $this->t('RETURN TO SHOP')
        ];
        $form['return_to_shop']['redirect_enabled'] = [
            '#type' => 'select',
            '#payzen_field' => true,
            '#title' => $this->t('Automatic redirection'),
            '#description' => $this->t('If enabled, the buyer is automatically redirected to your site at the end of the payment.'),
            '#options' => [
                '0' => $this->t('Disabled'),
                '1' => $this->t('Enabled')
            ],
            '#default_value' => $this->configuration['return_to_shop']['redirect_enabled']
        ];
        $form['return_to_shop']['redirect_success_timeout'] = [
            '#type' => 'textfield',
            '#payzen_field' => true,
            '#title' => $this->t('Redirection timeout on success'),
            '#description' => $this->t('Time in seconds (0-300) before the buyer is automatically redirected to your website after a successful payment.'),
            '#states' => [
                'visible' => [
                    ':input[name="configuration[' . $this->pluginId . '][return_to_shop][redirect_enabled]"]' => ['value' => '1']
                ]
            ],
            '#default_value' => $this->configuration['return_to_shop']['redirect_success_timeout']
        ];
        $form['return_to_shop']['redirect_success_message'] = [
            '#type' => 'textfield',
            '#payzen_field' => true,
            '#title' => $this->t('Redirection message on success'),
            '#description' => $this->t('Message displayed on the payment page prior to redirection after a successful payment.'),
            '#states' => [
                'visible' => [
                    ':input[name="configuration[' . $this->pluginId . '][return_to_shop][redirect_enabled]"]' => ['value' => '1']
                ]
            ],
            '#default_value' => $this->configuration['return_to_shop']['redirect_success_message']
        ];
        $form['return_to_shop']['redirect_error_timeout'] = [
            '#type' => 'textfield',
            '#payzen_field' => true,
            '#title' => $this->t('Redirection timeout on failure'),
            '#description' => $this->t('Time in seconds (0-300) before the buyer is automatically redirected to your website after a declined payment.'),
            '#states' => [
                'visible' => [
                    ':input[name="configuration[' . $this->pluginId . '][return_to_shop][redirect_enabled]"]' => ['value' => '1']
                ]
            ],
            '#default_value' => $this->configuration['return_to_shop']['redirect_error_timeout']
        ];
        $form['return_to_shop']['redirect_error_message'] = [
            '#type' => 'textfield',
            '#payzen_field' => true,
            '#title' => $this->t('Redirection message on failure'),
            '#description' => $this->t('Message displayed on the payment page prior to redirection after a declined payment.'),
            '#states' => [
                'visible' => [
                    ':input[name="configuration[' . $this->pluginId . '][return_to_shop][redirect_enabled]"]' => ['value' => '1']
                ]
            ],
            '#default_value' => $this->configuration['return_to_shop']['redirect_error_message']
        ];
        $form['return_to_shop']['return_mode'] = [
            '#type' => 'select',
            '#payzen_field' => true,
            '#title' => $this->t('Return mode'),
            '#description' => $this->t('Method that will be used for transmitting the payment result from the payment page to your shop.'),
            '#options' => [
                'GET' => 'GET',
                'POST' =>'POST'
            ],
            '#default_value' => $this->configuration['return_to_shop']['return_mode']
        ];

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

        require_once(drupal_get_path('module', 'commerce_payzen') . '/includes/PayzenRequest.php');

        $grouped_values = $form_state->getValue($form['#parents']);
        $request = new \PayzenRequest(); // new instance for parameters validation

        foreach ($grouped_values as $key1 => $group) {
            if (! is_array($group) || empty($group)) {
                continue;
            }

            foreach ($group as $key2 => $value) {
                $field = isset($form[$key1][$key2]) ? $form[$key1][$key2] : null;

                if (! $field || ! isset($field['#payzen_field']) || ! $field['#payzen_field']) {
                    continue;
                }

                $value = is_array($value) ? implode(';', $value) : $value;
                $label = $field['#title']->render();

                if (! $request->set($key2, $value)) {
                    if (empty($value)) {
                        $form_state->setError($field, sprintf($this->t('The field « %s » is mandatory.'), $label));
                    } else {
                        $form_state->setError($field, sprintf($this->t('Invalid value « %1$s » for field « %2$s ».'), $value, $label));
                    }

                    break;
                }
            }

            if ($form_state->getErrors()) {
                break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValue($form['#parents']);

        // recover mode param to avoir parent validation errors.
        $keys = $form['#parents'];
        $keys[] = 'mode';

        $form_state->setValue($keys, $values['gateway_access']['ctx_mode']);

        parent::submitConfigurationForm($form, $form_state);

        if ($form_state->getErrors()) {
            return;
        }

        $this->configuration['gateway_access']['site_id'] = $values['gateway_access']['site_id'];
        $this->configuration['gateway_access']['key_test'] = $values['gateway_access']['key_test'];
        $this->configuration['gateway_access']['key_prod'] = $values['gateway_access']['key_prod'];
        $this->configuration['gateway_access']['ctx_mode'] = $values['gateway_access']['ctx_mode'];
        $this->configuration['gateway_access']['sign_algo'] = $values['gateway_access']['sign_algo'];
        $this->configuration['gateway_access']['platform_url'] = $values['gateway_access']['platform_url'];

        $this->configuration['payment_page']['language'] = $values['payment_page']['language'];
        $this->configuration['payment_page']['available_languages'] = $values['payment_page']['available_languages'];
        $this->configuration['payment_page']['capture_delay'] = $values['payment_page']['capture_delay'];
        $this->configuration['payment_page']['validation_mode'] = $values['payment_page']['validation_mode'];

        if (isset($values['payment_page']['payment_cards'])) {
            $this->configuration['payment_page']['payment_cards'] = $values['payment_page']['payment_cards'];
        }

        $this->configuration['selective_threeds']['threeds_min_amount'] = $values['selective_threeds']['threeds_min_amount'];

        $this->configuration['return_to_shop']['redirect_enabled'] = $values['return_to_shop']['redirect_enabled'];
        $this->configuration['return_to_shop']['redirect_success_timeout'] = $values['return_to_shop']['redirect_success_timeout'];
        $this->configuration['return_to_shop']['redirect_success_message'] = $values['return_to_shop']['redirect_success_message'];
        $this->configuration['return_to_shop']['redirect_error_timeout'] = $values['return_to_shop']['redirect_error_timeout'];
        $this->configuration['return_to_shop']['redirect_error_message'] = $values['return_to_shop']['redirect_error_message'];
        $this->configuration['return_to_shop']['return_mode'] = $values['return_to_shop']['return_mode'];
    }

    /**
     * {@inheritdoc}
     */
    public function onReturn(OrderInterface $order, Request $request)
    {
        parent::onReturn($order, $request);

        require_once(drupal_get_path('module', 'commerce_payzen') . '/includes/PayzenResponse.php');

        // get logger instance
        $logger = \Drupal::logger('commerce_payzen');

        $params = $request->isMethod('POST') ? $request->request->all() : $request->query->all();
        $response = new \PayzenResponse(
            $params,
            $this->configuration['gateway_access']['ctx_mode'],
            $this->configuration['gateway_access']['key_test'],
            $this->configuration['gateway_access']['key_prod'],
            $this->configuration['gateway_access']['sign_algo']
        );

        // check response authenticity
        if (! $response->isAuthentified()) {
            $logger->error(
                '%ip tries to access return script without valid signature with parameters : %params.',
                [ '%ip' => $request->getClientIp(), '%params' => print_r($params, true) ]
            );

            $logger->error('Signature algorithm selected in module settings must be the same as one selected in PayZen Back Office.');

            throw new AuthenticationException();
        }

        // check if existing order
        $order = $this->entityTypeManager->getStorage('commerce_order')->load($response->get('order_id'));
        if (empty($order) || ! $order->id()) {
            $logger->error(
                'Order with ID #%orderId was not found while processing payment result.',
                [ '%orderId' => $response->get('order_id') ]
            );

            throw new InvalidResponseException();
        }

        // go into production message
        if (Tools::$pluginFeatures['prodfaq'] && ($this->configuration['gateway_access']['ctx_mode'] === 'TEST')) {
            $message = '<b><u>' . $this->t('GOING INTO PRODUCTION') . '</u></b>';
            $message .= '<p>' . $this->t('You want to know how to put your shop into production mode, please read chapters « Proceeding to test phase » and « Shifting the shop to production mode » in the documentation of the module.');

            drupal_set_message(Markup::create($message), 'status');
        }

        if ($order->getState()->value === 'draft') {
            // order waiting for payment confirmation
            $logger->info("Order #{$order->id()} not registered yet or payment retry. Let's save payment result now.");
            $logger->info("Payment results for order #{$order->id()} : " . $response->getLogMessage());

            $result = $this->savePayment($order, $response);

            if ($result) {
                $logger->info("Payment result successfully registered for order #{$order->id()}. Let Drupal Commerce complete the order.");

                if ($this->configuration['gateway_access']['ctx_mode'] === 'TEST') {
                    // test mode warning : IPN URL not correctly called
                    $logger->warning(
                        "Payment for order #{$order->id()} has been processed by client return ! This means the IPN URL did not work."
                    );

                    if (\Drupal::state()->get('system.maintenance_mode')) {
                        $message = $this->t('The shop is in maintenance mode. The automatic notification cannot work.');
                    } else {
                        $message = $this->t('The automatic validation has not worked. Have you correctly set up the notification URL in your PayZen Back Office ?');
                        $message .= '<br />';
                        $message .= $this->t('For understanding the problem, please read the documentation of the module : <br />&nbsp;&nbsp;&nbsp;- Chapter « To read carefully before going further »<br />&nbsp;&nbsp;&nbsp;- Chapter « Notification URL settings »');
                    }

                    drupal_set_message(Markup::create($message), 'warning');
                }
            } else {
                throw new DeclineException($response->getLogMessage());
            }
        } else {
            // order already processed
            if (! $response->isAcceptedPayment()) {
                throw new DeclineException($response->getLogMessage());
            }

            $checkout_flow = $order->get('checkout_flow')->entity;
            $checkout_flow_plugin = $checkout_flow->getPlugin();
            $checkout_flow_plugin->redirectToStep('complete');
            die();
        }
    }

    public function onNotify(Request $request)
    {
        parent::onNotify($request);

        require_once(drupal_get_path('module', 'commerce_payzen') . '/includes/PayzenResponse.php');

        // get logger instance
        $logger = \Drupal::logger('commerce_payzen');

        $params = $request->request->all();
        $response = new \PayzenResponse(
            $params,
            $this->configuration['gateway_access']['ctx_mode'],
            $this->configuration['gateway_access']['key_test'],
            $this->configuration['gateway_access']['key_prod'],
            $this->configuration['gateway_access']['sign_algo']
        );

        // check response authenticity
        if (! $response->isAuthentified()) {
            $logger->error(
                '%ip tries to access notify script without valid signature with parameters : %params.',
                [ '%ip' => $request->getClientIp(), '%params' => print_r($params, true) ]
            );

            $logger->error('Signature algorithm selected in module settings must be the same as one selected in PayZen Back Office.');

            die($response->getOutputForPlatform('auth_fail'));
        }

        // check if existing order
        $order = $this->entityTypeManager->getStorage('commerce_order')->load($response->get('order_id'));
        if (empty($order) || ! $order->id()) {
            $logger->error(
                'Order with ID #%orderId was not found while processing payment result.',
                [ '%orderId' => $response->get('order_id') ]
            );

            die($response->getOutputForPlatform('order_not_found'));
        }

        if ($order->getState()->value === 'draft') {
            // order waiting for payment confirmation
            $logger->info("Order #{$order->id()} not registered yet or payment retry. Let's save payment result now.");
            $logger->info("Payment results for order #{$order->id()} : " . $response->getLogMessage());

            $result = $this->savePayment($order, $response);

            if ($result) {
                // payment succes, complete order
                $order->set('checkout_step', 'complete');
                $order->unlock();

                $transition = $order->getState()->getWorkflow()->getTransition('place');
                $order->getState()->applyTransition($transition);
                $order->save();
                echo($response->getOutputForPlatform('payment_ok'));
            } else {
                echo($response->getOutputForPlatform('payment_ko'));
            }
        } else {
            // order already processed
            if (($order->getState()->value === 'completed') && $response->isAcceptedPayment()) {
                $this->savePayment($order, $response);

                die($response->getOutputForPlatform('payment_ok_already_done'));
            } else {
                die($response->getOutputForPlatform('payment_ko_on_order_ok'));
            }
        }
    }


    private function savePayment($order, $response)
    {
        if ($response->isCancelledPayment()) {
            return false;
        }

        $trans_uuid = $response->get('trans_uuid');

        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        if ($payments = $payment_storage->loadByProperties([ 'order_id' => $order->id(), 'remote_id' => $trans_uuid ])) {
            $payment = reset($payments);
        } else {
            $payment = $payment_storage->create([
                'order_id' => $order->id(),
                'payment_gateway' => $this->entityId
            ]);
        }

        $state = '';

        switch ($response->getTransStatus()) {
            case 'AUTHORISED' :
            case 'CAPTURE_FAILED' :
            case 'ACCEPTED' :
                $state = 'authorization';
                break;

            case 'CAPTURED' :
                $state = 'completed';
                break;

            case 'AUTHORISED_TO_VALIDATE' :
            case 'WAITING_AUTHORISATION_TO_VALIDATE' :
            case 'WAITING_AUTHORISATION' :
            case 'UNDER_VERIFICATION' :
            case 'INITIAL' :
            case 'WAITING_FOR_PAYMENT' :
                $state = 'pending';
                break;

            default:
                $state = 'voided';
                break;
        }

        $payment->setState($state);

        $payment->setRemoteId($trans_uuid);
        $payment->setRemoteState($response->getTransStatus());

        $currency_code = $response->get('effective_currency');
        $amount_in_cents = $response->get('effective_amount');

        if (! $currency_code || ! $amount_in_cents) {
            $currency_code = $response->get('currency');
            $amount_in_cents = $response->get('amount');
        }

        $currency = \PayzenApi::findCurrencyByNumCode($currency_code);
        $amount = strval($currency->convertAmountToFloat($amount_in_cents));

        $payment->setAmount(new Price($amount, $currency->getAlpha3()));

        $payment->save();

        return $state !== 'voided';
    }
}
