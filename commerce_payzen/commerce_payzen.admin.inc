<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen for Drupal Commerce. See COPYING.md for license details.
 *
 * @author    Lyra Network <https://www.lyra.com>
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL v3)
 */

function theme_commerce_payzen_backend_multi_options($variables)
{
    drupal_add_js(drupal_get_path('module', 'commerce_payzen') . '/theme/commerce_payzen.admin.js');

    $multi_options = commerce_payzen_multi_options($variables['element']['#value']);
    $cb_avail = key_exists('CB', commerce_payzen_multi_cards());

    $output = '';

    $output .= '<div>';
    $output .= '<button id="commerce_payzen_multi_options_btn"' . (! empty($multi_options) ? ' style="display: none;"' : '')
                    . ' type="button"  onclick= "payzenAddOption(true, \'' . payzen_t('Delete') . '\', ' . ($cb_avail ? 'true' : 'false') . ')" />' . payzen_t('Add') . '</button>';

    $output .= '<table id="commerce_payzen_multi_options_table"' . (empty($multi_options) ? ' style="display: none;"' : '') . ' cellpadding="0" cellspacing="0" class="commerce-payzen-table" >
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
                            <th>' . payzen_t('1st installment') . '</th>
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

function commerce_payzen_create_text($name, $value, $extra_attributes = '')
{
    $output = '<input type="text" name="parameter[payment_method][settings][payment_method][settings][payment_options][payzen_multi_options]' . $name . '" value="' . $value . '" ' . $extra_attributes . '>';
    return $output;
}
