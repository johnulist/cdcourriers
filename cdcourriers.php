<?php
/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Dominique <dominique@chez-dominique.fr>
 * @copyright 2007-2016 Chez-Dominique
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_'))
    exit;

require_once(dirname(__FILE__) . '/classes/MypdfClass.php');

class Cdcourriers extends Module
{

    protected $_errors = array();
    protected $_html = '';

    protected $_config = array(
        'cdcourriers' => 1,
    );

    protected $_config_lang = array(
        'cdcourriers' => array()
    );


    public function __construct()
    {
        $this->name = 'cdcourriers';
        $this->tab = 'administration';
        $this->version = '1.0';
        $this->author = 'Dominique';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Module Courriers');
        $this->description = $this->l('Courriers de relance clients');
        $this->confirmUninstall = $this->l('Are you sure you want to delete this module?');
    }

    public function install()
    {
        if (!parent::install() OR
            !$this->_installConfig()
        )
            return false;
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() OR
            !$this->_eraseConfig()
        )
            return false;
        return true;
    }

    private function _installConfig()
    {
        foreach ($this->_config as $keyname => $value) {
            Configuration::updateValue($keyname, $value);
        }
        return true;
    }


    private function _eraseConfig()
    {
        foreach ($this->_config as $keyname => $value) {
            Configuration::deleteByName($keyname);
        }
        return true;
    }


    public function getContent()
    {
        $this->generatePDF();
        return $this->_html;
    }

    private function _displayForm()
    {
        $this->_html .= $this->_generateForm();
        // With Template
        $this->context->smarty->assign(array(
            'variable' => 1
        ));
        $this->_html .= $this->display(__FILE__, 'backoffice.tpl');
    }

    private function _generateForm()
    {
        $inputs = array();

        $inputs[] = array(
            'type' => 'textarea',
            'label' => $this->l('Test Me'),
            'name' => 'BAREBONELANG',
            'desc' => 'Description',
            'autoload_rte' => true,
            'lang' => true
        );


        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitUpdate'
                )
            )
        );


        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper = new HelperForm();
        $helper->default_form_language = $lang->id;
        // $helper->submit_action = 'submitUpdate';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFull(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        return $helper->generateForm(array($fields_form));
    }


    private function _postProcess()
    {
        if (Tools::isSubmit('submitUpdate')) // handles the basic config update
        {
            // Do anything, like updating config

            // Error handling
            if ($this->_errors) {
                $this->_html .= $this->displayError(implode($this->_errors, '<br />'));
            } else $this->_html .= $this->displayConfirmation($this->l('Settings Updated!'));
        }
    }

    public function getConfigFull()
    {
        // join lang and normal config
        $config = $this->getConfig();
        $config_lang = $this->getConfigLang();
        return array_merge($config, $config_lang);
    }

    public function getConfig()
    {
        $config_keys = array_keys($this->_config);
        return Configuration::getMultiple($config_keys);
    }

    public function getConfigLang($id_lang = false)
    {
        if (!$id_lang) {
            foreach ($this->_config_lang as $key => $value) {
                $results[$key] = Configuration::getInt($key);
            }
            return $results;
        } else {
            $config_keys = array_keys($this->_config_lang);
            return Configuration::getMultiple($config_keys, $id_lang);
        }
    }

    private function generatePDF()
    {
        $pdf = new MYPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('L&Sens');
        $pdf->SetTitle('Module Courriers');
        $pdf->SetSubject('Module Courriers');

        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);


        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

        // set auto page breaks
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $l = '';
        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/fr.php')) {
            require_once(dirname(__FILE__) . '/lang/fr.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('dejavusans', 'BI', 20);
        $pdf->SetMargins(7, 10, 7, true);
        // add a page
        $pdf->AddPage();

        // set some text to print
        $html_content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'cdcourriers/views/templates/hook/pdf/content.tpl');
        $pdf->writeHTML($html_content);

        // ---------------------------------------------------------

        //Close and output PDF document
        $pdf->Output('courriers' . '.pdf', 'I');
    }

}