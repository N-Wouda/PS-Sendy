<?php
/**
 *  PrestaShop Sendy newsletter integration. Allows your customers to sign up for the Sendy newsletter from the FO.
 *
 * @author    Apium | Niels Wouda <n.wouda@apium.nl>
 * @copyright 2016, Apium
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

defined('_PS_VERSION_') || exit;


class SendyNewsletterFreeSubscribeModuleFrontController extends ModuleFrontController
{
    public function displayAjax()
    {
        $email = Tools::getValue('email');

        // we need cURL to make this work
        if (Validate::isEmail($email) && function_exists('curl_init')) {
            $return = $this->processSubscribe($email);
        } else {
            $return = false;
        }

        die(Tools::jsonEncode($return));
    }

    private function processSubscribe($email)
    {
        $sendy_url = Configuration::get('SENDY_URL');
        $sendy_list = Configuration::get('SENDY_LIST');

        $data = array(
            'list' => $sendy_list,
            'email' => $email,
            'boolean' => true
        );

        if (Configuration::get('SENDY_NAME') && $this->context->customer) {
            $data['name'] = $this->context->customer->firstname . ' ' . $this->context->customer->lastname;
        }

        if ($sendy_url && $sendy_list) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, rtrim($sendy_url, '\/') . '/subscribe');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

            $res = curl_exec($ch);
            curl_close($ch);

            if ($res) {
                return true;
            }
        }

        return false;
    }
}
