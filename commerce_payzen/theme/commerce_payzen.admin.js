/**
 * Copyright © Lyra Network.
 * This file is part of PayZen for Drupal Commerce. See COPYING.md for license details.
 *
 * @author    Lyra Network <https://www.lyra.com>
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL v3)
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

        if ($('#commerce_payzen_multi_options_table tbody tr').length === 1) {
            $('#commerce_payzen_multi_options_btn').css('display', '');
            $('#commerce_payzen_multi_options_table').css('display', 'none');
        }
    };
})(jQuery);