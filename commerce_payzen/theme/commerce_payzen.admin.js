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

(function($) {
    payzenAddOption = function payzenAddOption(first, deleteText, contract) {
        if (first) {
            $('#commerce_payzen_multi_options_btn').css('display', 'none');
            $('#commerce_payzen_multi_options_table').css('display', '');
        }

        var timestamp = new Date().getTime();
        var inputName = 'parameter[payment_method][settings][payment_method][settings][payment_options][payzen_multi_options]';
        var optionLine = '<tr id="commerce_payzen_multi_option_' + timestamp + '">' +
                         '    <td><input name="' + inputName + '[' + timestamp + '][label]" style="width: 150px;" type="text" /></td>' + 
                         '    <td><input name="' + inputName + '[' + timestamp + '][amount_min]" style="width: 80px;" type="text" /></td>' +
                         '    <td><input name="' + inputName + '[' + timestamp + '][amount_max]" style="width: 80px;" type="text" /></td>';

        if (contract) {
        	optionLine += '   <td><input name="' + inputName + '[' + timestamp + '][contract]" style="width: 70px;" type="text" /></td>';
        }

        optionLine += '       <td><input name="' + inputName + '[' + timestamp + '][count]" style="width: 70px;" type="text" /></td>' +
                      '       <td><input name="' + inputName + '[' + timestamp + '][period]" style="width: 70px;" type="text" /></td>' +
                      '       <td><input name="' + inputName + '[' + timestamp + '][first]" style="width: 70px;" type="text" /></td>' +
                      '       <td><button type="button" onclick= "payzenDeleteOption(' + timestamp + ');">'+deleteText+' </td>' +
                      '   </tr>';

        $(optionLine).insertBefore('#commerce_payzen_multi_option_add');
    };

    payzenDeleteOption = function(key) {
        $('#commerce_payzen_multi_option_' + key).remove();

        if ($('#commerce_payzen_multi_options_table tbody tr').length == 1) {
            $('#commerce_payzen_multi_options_btn').css('display', '');
            $('#commerce_payzen_multi_options_table').css('display', 'none');
        }
    };
})(jQuery);
