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
namespace Drupal\commerce_payzen;

class Tools
{
    const LANGUAGE = 'fr';
    const SITE_ID = '12345678';
    const KEY_TEST = '1111111111111111';
    const KEY_PROD = '2222222222222222';
    const CTX_MODE = 'TEST';
    const SIGN_ALGO = 'SHA-256';
    const GATEWAY_URL = 'https://secure.payzen.eu/vads-payment/';
    const SUPPORT_EMAIL = 'support@payzen.eu';

    const GATEWAY_CODE = 'PayZen';
    const GATEWAY_VERSION = 'V2';
    const CMS_IDENTIFIER = 'Drupal_Commerce_2.x';
    const PLUGIN_VERSION = '2.0.3';
    const DOC_PATTERN = '${doc.pattern}';

    public static $pluginFeatures = array(
        'qualif' => false,
        'prodfaq' => true,
        'restrictmulti' => false,
        'shatwo' => true,

        'multi' => true,
        'paypal' => true
    );
}
