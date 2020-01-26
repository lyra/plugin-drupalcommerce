<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen for Drupal Commerce. See COPYING.md for license details.
 *
 * @author    Lyra Network <https://www.lyra.com>
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL v3)
 */

class PayzenTools
{
    private static $LANGUAGE = 'fr';
    private static $SITE_ID = '12345678';
    private static $KEY_TEST = '1111111111111111';
    private static $KEY_PROD = '2222222222222222';
    private static $CTX_MODE = 'TEST';
    private static $GATEWAY_URL = 'https://secure.payzen.eu/vads-payment/';
    private static $SUPPORT_EMAIL = 'support@payzen.eu';

    private static $GATEWAY_VERSION = 'V2';
    private static $CMS_IDENTIFIER = 'Drupal_Commerce_7.x-1.x';
    private static $PLUGIN_VERSION = '1.2.1';
    private static $DOC_PATTERN = 'PayZen_Drupal_Commerce_7.x-1.x_v1.2_*.pdf';


    public static $pluginFeatures = array(
        'qualif' => false,
        'prodfaq' => true,
        'restrictmulti' => false,

        'multi' => true,
        'paypal' => true
    );

    public static function getDefault($name)
    {
        if (!is_string($name)) {
            return '';
        }

        if (!isset(self::$$name)) {
            return '';
        }

        return self::$$name;
    }
}
