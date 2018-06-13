<?php
/**
 * Copyright (C) 2017-2018 Lyra Network.
 * This file is part of PayZen for Drupal Commerce.
 * See COPYING.md for license details.
 *
 * @author Lyra Network <contact@lyra-network.com>
 * @copyright 2017-2018 Lyra Network
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL v2)
 */
namespace Drupal\commerce_payzen\PluginForm;

use Drupal\Core\Form\FormStateInterface;

class PaypalForm extends PayzenForm
{

    protected function buildPayzenRequest(array $form, FormStateInterface $form_state)
    {
        $request = parent::buildPayzenRequest($form, $form_state);

        $test_mode = $request->get('ctx_mode') == 'TEST';
        $request->set('payment_cards', $test_mode ? 'PAYPAL_SB' : 'PAYPAL');

        return $request;
    }
}
