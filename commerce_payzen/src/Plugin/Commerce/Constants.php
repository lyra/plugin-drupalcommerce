<?php
/**
 * Copyright (C) 2017-2018 Lyra Network.
 * This file is part of Systempay for Drupal Commerce.
 * See COPYING.md for license details.
 *
 * @author Lyra Network <contact@lyra-network.com>
 * @copyright 2017-2018 Lyra Network
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL v2)
 */
namespace Drupal\commerce_payzen\Plugin\Commerce;

class Constants
{
    const LANGUAGE = 'fr';
    const SITE_ID = '12345678';
    const KEY_TEST = '1111111111111111';
    const KEY_PROD = '2222222222222222';
    const CTX_MODE = 'TEST';
    const SIGN_ALGO = 'SHA-1';
    const GATEWAY_URL = 'https://secure.payzen.eu/vads-payment/';
    const SUPPORT_EMAIL = 'support@payzen.eu';

    const GATEWAY_CODE = 'PayZen';
    const PRODFAQ_URL = 'https://secure.payzen.eu/html/faq/prod';
    const GATEWAY_VERSION = 'V2';
    const CMS_NAME = 'Drupal_Commerce';
    const CMS_VERSION = '8.x-2.x';
}
