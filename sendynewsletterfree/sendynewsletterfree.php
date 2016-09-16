<?php
/**
 *  PrestaShop Sendy newsletter integration. Allows your customers to sign up for the Sendy newsletter from the FO.
 *
 * @author    Apium | Niels Wouda <n.wouda@apium.nl>
 * @copyright 2016, Apium
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

defined('_PS_VERSION_') || exit;


class SendyNewsletterFree extends Module
{
    public function __construct()
    {
        $this->name = 'sendynewsletterfree';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Apium | Niels Wouda';
        $this->need_instance = true;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Sendy Newsletter Free');
        $this->description = $this->l('Adds a Sendy newsletter block to your shop.');

        $this->ps_versions_compliancy = array('min' => '1.5.2.0', 'max' => _PS_VERSION_);

        // we need cURL to connect to the Sendy installation
        if (!function_exists('curl_init')) {
            $this->warning .= $this->l('To be able to use this module, please activate cURL (PHP extension).');
        }
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install()) {
            return false;
        }

        return $this->registerHook('header')
            && $this->registerHook('backOfficeHeader')
            && Configuration::updateValue('SENDY_URL', $this->l('Your Sendy installation URL'))
            && Configuration::updateValue('SENDY_LIST', $this->l('Your Sendy list ID'))
            && Configuration::updateValue('SENDY_NAME', true);
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        return $this->unregisterHook('header')
            && $this->unregisterHook('backOfficeHeader')
            && Configuration::deleteByName('SENDY_URL')
            && Configuration::deleteByName('SENDY_LIST')
            && Configuration::deleteByName('SENDY_NAME');
    }

    public function getContent()
    {
        $output = '';

        // no need to reinvent the wheel - we use the layout (frond-end) of the default newsletter module.
        // this also means *most* themes should work properly, as this module is often restyled.
        if (!Module::isEnabled('blocknewsletter')) {
            $output .= $this->displayError($this->l("This module needs the PrestaShop 'blocknewsletter' to function!"));
        }

        // update the form / settings
        if (Tools::isSubmit('sendySubmit')) {
            // if all succesfully updated, display success message.
            if (Configuration::updateValue('SENDY_URL', Tools::getValue('SENDY_URL'))
                && Configuration::updateValue('SENDY_LIST', Tools::getValue('SENDY_LIST'))
                && Configuration::updateValue('SENDY_NAME', Tools::getValue('SENDY_NAME'))) {
                $output .= $this->displayConfirmation($this->l('Settings have been successfully updated!'));
            } else {
                $output .= $this->displayError($this->l('Something went wrong while updating the settings!'));
            }
        }

        $output .= $this->display(__FILE__, 'views/templates/admin/whatdoes.tpl');
        $output .= $this->renderForm();

        return $output;
    }

    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJquery();
            $this->context->controller->addCSS($this->_path . 'views/css/sendynewsletter_backoffice.css');
        }
    }

    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/sendynewsletter.js');
    }

    private function renderForm()
    {
        $form_settings = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cog'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sendy URL'),
                        'name' => 'SENDY_URL',
                        'desc' => $this->l('The URL to your Sendy installation, e.g. "http://www.your_sendy_installation.com/".'),
                        'required' => true,
                        'value' => Configuration::get('SENDY_URL')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sendy list ID'),
                        'name' => 'SENDY_LIST',
                        'desc' => $this->l('The list ID you want to subscribe your users to. This can be found under "View all lists" in Sendy.'),
                        'required' => true,
                        'value' => Configuration::get('SENDY_LIST')
                    ),
                    array(
                        'type' => version_compare(_PS_VERSION_, '1.6', '<') ? 'radio' : 'switch',
                        'label' => $this->l('Toggle name'),
                        'name' => 'SENDY_NAME',
                        'class' => 't',
                        'desc' => $this->l(
                            'If the client signing up for the newsletter is a currently logged-in customer, we also send their name to Sendy, formatted as "firstname lastname".'
                        ),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            )
        );
        return $this->renderFormUtil($form_settings, 'sendySubmit');
    }

    private function renderFormUtil($form, $submit_action)
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?
            Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = $submit_action;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => array(
                'SENDY_URL' => Tools::getValue('SENDY_URL', Configuration::get('SENDY_URL')),
                'SENDY_LIST' => Tools::getValue('SENDY_LIST', Configuration::get('SENDY_LIST')),
                'SENDY_NAME' => Tools::getValue('SENDY_NAME', Configuration::get('SENDY_NAME'))
            ),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($form));
    }
}
