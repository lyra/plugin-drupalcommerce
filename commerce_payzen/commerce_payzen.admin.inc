<?php
/**
 * PayZen V2-Payment Module version 1.2.0 for Drupal_Commerce 7.x-1.x. Support contact : support@payzen.eu.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public License (GPL v3)
 * @category  payment
 * @package   payzen
 */

function theme_commerce_payzen_backend_multi_options($variables) {
    drupal_add_js(drupal_get_path('module', 'commerce_payzen') . '/theme/commerce_payzen.admin.js');
    drupal_add_css(drupal_get_path('module', 'commerce_payzen') . '/theme/commerce_payzen.admin.css');

    $multi_options = commerce_payzen_multi_options($variables['element']['#value']);
    $cb_avail = key_exists('CB', commerce_payzen_multi_cards());

    $output = '';

    $output .= '<div>';
    $output .= '<button id="commerce_payzen_multi_options_btn"' . (! empty($multi_options) ? ' style="display: none;"' : '')
                    . ' type="button"  onclick= "payzenAddOption(true, \'' . payzen_t('Delete') . '\', ' . ($cb_avail ? 'true' : 'false') . ')" />' . payzen_t('Add') . '</button>';

    $output .= '<table id="commerce_payzen_multi_options_table"' . (empty($multi_options) ? ' style="display: none;"' : '') . ' cellpadding="0" cellspacing="0" class="uc-payzen-table" >
                    <thead>
                        <tr>
                            <th>' . payzen_t('Label') . '</th>
                            <th>' . payzen_t('Min. amount') . '</th>
                            <th>' . payzen_t('Max. amount') . '</th>';

    if ($cb_avail) {
        $output .= '        <th>' . payzen_t('Contract') . '</th>';
    }

    $output .= '            <th>' . payzen_t('Count') . '</th>
                            <th>' . payzen_t('Period') . '</th>
                            <th>' . payzen_t('1st payment') . '</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>';


    if (! empty($multi_options)) {
        foreach ($multi_options as $key => $option) {
            $output .= '<tr id="commerce_payzen_multi_option_' . $key . '">
                            <td>' . commerce_payzen_create_text('[' . $key . '][label]', $option['label'], 'style="width: 150px;"') . '</td>
                            <td>' . commerce_payzen_create_text('[' . $key . '][amount_min]', $option['amount_min'], 'style="width: 80px;"') . '</td>
                            <td>' . commerce_payzen_create_text('[' . $key . '][amount_max]', $option['amount_max'], 'style="width: 80px;"') . '</td>';

            if ($cb_avail) {
                $output  .= '<td>' . commerce_payzen_create_text('[' . $key . '][contract]', $option['contract'], 'style="width: 70px;"') . '</td>';
            }

            $output  .= '    <td>' . commerce_payzen_create_text('[' . $key . '][count]', $option['count'], 'style="width: 70px;"') . '</td>
                            <td>' . commerce_payzen_create_text('[' . $key . '][period]', $option['period'], 'style="width: 70px;"') . '</td>
                            <td>' . commerce_payzen_create_text('[' . $key . '][first]', $option['first'], 'style="width: 70px;"') . '</td>
                            <td> <button type="button" onclick="payzenDeleteOption(' . $key . ');">' . payzen_t('Delete') . '</button> </td>
                        </tr>';
        }
    }

    $output .= '        <tr id="commerce_payzen_multi_option_add">
                            <td colspan="' . ($cb_avail ? '7' : '6') . '"></td>
                            <td><button type="button" onclick="payzenAddOption(false, \'' . payzen_t('Delete') . '\', ' . ($cb_avail ? 'true' : 'false') . ');" >' . payzen_t('Add') . '</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>';

    return $output;
}

function commerce_payzen_create_text($name, $value, $extra_attributes = '') {
    $output = '<input type="text" name="parameter[payment_method][settings][payment_method][settings][payment_options][payzen_multi_options]' . $name . '" value="' . $value . '" ' . $extra_attributes . '>';
    return $output;
}
