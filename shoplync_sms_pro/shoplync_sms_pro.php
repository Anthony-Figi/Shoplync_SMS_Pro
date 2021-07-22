<?php
/**
* 2007-2021 PrestaShop
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
*  @author    Anthony Figueroa - Shoplync Inc <sales@shoplync.com>
*  @copyright 2007-2021 Shoplync Inc
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of Shoplync Inc
*
* =================================================================
*
* SMS Pro for PrestaShop by Shoplync is a Windows-based desktop application, empowering you to 
* manage all aspects of your business under one ecosystem. SMS Pro features a new intuitive interface 
* that enables you to manage and process sales orders in record time, while also allowing you to set pricing models, 
* generate invoices, import parts list and update your online catalog. SMS Pro not only manages your Prestashop store 
* but also bridges the gap between your online and physical store.
* 
* Keep costs low and profits high by allowing SMS Pro todo all the heavy lifting. 
*
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Shoplync_sms_pro extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'shoplync_sms_pro';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Shoplync';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('SMS Pro ');
        $this->description = $this->l('Enables all functionality for SMS Pro. Empowering you to manage all aspects of your business under one ecosystem.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module? (You will loose all SMS Pro functionality)');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('SHOPLYNC_SMS_PRO_API_KEY', null);

        include(dirname(__FILE__).'/sql/install.php');
			
		if (!parent::install() || !$this->registerHook('displayBackOfficeHeader')) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('SHOPLYNC_SMS_PRO_API_KEY');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitShoplync_sms_proModule')) == true) {
            $this->postProcess();
        }
        else if (((bool)Tools::isSubmit('submitShoplync_sms_proNewKey')) == true) {
            $this->updateWebserviceStatus();
            $this->createNewWebserviceKey();
            error_log('Finished Generating Key');
        }

        $this->context->smarty->assign('module_dir', $this->_path);
		
		$form = $this->renderForm();
		$this->context->smarty->assign('render_form', $form);
        
        $newKeyForm = $this->renderNewKeyForm();
		$this->context->smarty->assign('newKey_form', $newKeyForm);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output;
    }
    
    /**
    * Created A form that is used by this module to generate a new key
    */
    protected function renderNewKeyForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitShoplync_sms_proNewKey';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            //'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        $formConfig = array(
                'form' => array(
                    'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => '',
                    'class' => 'hidden',
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                        'class' => 'hidden',
                    ),
                ),
            );

        return $helper->generateForm(array($formConfig));
        
    }
    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitShoplync_sms_proModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $values = $this->getConfigFormValues();
        if(array_key_exists('SHOPLYNC_SMS_PRO_API_KEY', $values) && !WebserviceKey::keyExists($values['SHOPLYNC_SMS_PRO_API_KEY']))
        {
            $values['SHOPLYNC_SMS_PRO_API_KEY'] = '';
        }

        $helper->tpl_vars = array(
            'fields_value' => $values, /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of the settings form.
     */
    protected function getConfigForm()
    {
        $key = Configuration::get('SHOPLYNC_SMS_PRO_API_KEY', null);
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
					array(
						'col' => 4,
                        'type' => 'text',
						'prefix' => '<i class="icon icon-key"></i>',
						'suffix' => '<a href="#" id="mybutton" class="btn btn-default" '.(WebserviceKey::keyExists($key) ? ' disabled ' : '').' onclick="GetNewKey()"><i class="icon-refresh"></i> Generate New Key</a>',
                        'desc' => $this->l('Enter this API key inside the SMS Pro settings page. ').'<a href="https://www.shoplync.com/help/">Learn more</a>',
                        'name' => 'SHOPLYNC_SMS_PRO_API_KEY',
						'class' => 'h-100',
                        'label' => $this->l('Prestashop API Key').' '.(WebserviceKey::keyExists($key) ? ' <i class="icon icon-check text-success"></i>' : '<i class="icon icon-close text-danger"></i>'),
						'disabled' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
				'buttons' => array(
					[
						'href' => 'https://www.shoplync.com/contact/',          // If this is set, the button will be an <a> tag
						//'js'   => 'someFunction()', // Javascript to execute on click
						'class' => '',              // CSS class to add
						'type' => 'button',         // Button type
						//'id'   => 'mybutton',
						'name' => 'helpButton',       // If not defined, this will take the value of "submitOptions{$table}"
						'icon' => 'process-icon-envelope',       // Icon to show, if any
						'title' => 'Contact Us',      // Button label
					],
				),
				
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        
        return array( 
        
		    'SHOPLYNC_SMS_PRO_API_KEY' => Configuration::get('SHOPLYNC_SMS_PRO_API_KEY', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
	 *
	 * Ensure you register the hook $this->registerHook('displayHeader')
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
	/**
	 * Forked From: https://stackoverflow.com/a/31107425
	 *
	 * Generate a random string, using a cryptographically secure 
	 * pseudorandom number generator (random_int)
	 *
	 * This function uses type hints now (PHP 7+ only), but it was originally
	 * written for PHP 5 as well.
	 * 
	 * For PHP 7, random_int is a PHP core function
	 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
	 * 
	 * @param int $length      How many characters do we want?
	 * @param string $keyspace A string of all possible characters
	 *                         to select from
	 * @return string
	 */
	protected function apiKeyGenerator(
		int $length = 32,
		string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
	): string {
		if ($length < 1) {
			throw new \RangeException("Length must be a positive integer");
		}
		$pieces = [];
		$max = mb_strlen($keyspace, '8bit') - 1;
		for ($i = 0; $i < $length; ++$i) {
			$pieces []= $keyspace[random_int(0, $max)];
		}
		return implode('', $pieces);
	}
    
	/**
    * Updates the Photoshop webservice that enables usage of the API key
    */
	public function updateWebserviceStatus(bool $status = true)
	{
		Configuration::updateValue('PS_WEBSERVICE', $status);
	}
	
    /**
    * Creates the Webservice, sets the permissions and 
    * stores key in modules configuration settings
    */
	public function createNewWebserviceKey()
	{
        $key = Configuration::get('SHOPLYNC_SMS_PRO_API_KEY', null);
        if(!WebserviceKey::keyExists($key)) 
        {
            $apiAccess = new WebserviceKey();
            $apiAccess->key = $this->apiKeyGenerator();//Default is a string of 32-alphanumerical chars
            $apiAccess->description = 'Enables all the functions used by SMS Pro. For more information please visit https://www.shoplync.com/help/';
            $apiAccess->save();
            
            //Set API permissions
            $permissions = [
                'addresses' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'carriers' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'cart_rules' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'carts' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'categories' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'combinations' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'configurations' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'contacts' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'content_management_system' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'countries' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'currencies' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'customer_messages' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'customer_threads' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'customers' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'customizations' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'deliveries' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'employees' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'groups' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'guests' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'image_types' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'images' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'languages' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'manufacturers' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'messages' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'order_carriers' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'order_details' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'order_histories' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'order_invoices' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'order_payments' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'order_slip' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'order_states' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'orders' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'price_ranges' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'product_customization_fields' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'product_feature_values' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'product_features' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'product_option_values' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'product_options' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'product_suppliers' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'products' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'search' => ['GET' => 1, 'POST' => 0, 'PUT' => 0, 'DELETE' => 0, 'HEAD' => 1],//
                'shop_groups' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'shop_urls' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'shops' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'specific_price_rules' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'specific_prices' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'states' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'stock_availables' => ['GET' => 1, 'POST' => 1, 'PUT' => 0, 'DELETE' => 0, 'HEAD' => 1],//
                'stock_movement_reasons' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'stock_movements' => ['GET' => 1, 'POST' => 0, 'PUT' => 0, 'DELETE' => 0, 'HEAD' => 1],//
                'stocks' => ['GET' => 1, 'POST' => 0, 'PUT' => 0, 'DELETE' => 0, 'HEAD' => 1],//
                'stores' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'suppliers' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'supply_order_details' => ['GET' => 1, 'POST' => 0, 'PUT' => 0, 'DELETE' => 0, 'HEAD' => 1],//
                'supply_order_histories' => ['GET' => 1, 'POST' => 0, 'PUT' => 0, 'DELETE' => 0, 'HEAD' => 1],//
                'supply_order_receipt_histories' => ['GET' => 1, 'POST' => 0, 'PUT' => 0, 'DELETE' => 0, 'HEAD' => 1],//
                'supply_order_states' => ['GET' => 1, 'POST' => 0, 'PUT' => 0, 'DELETE' => 0, 'HEAD' => 1],//
                'supply_orders' => ['GET' => 1, 'POST' => 0, 'PUT' => 0, 'DELETE' => 0, 'HEAD' => 1],//
                'tags' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'tax_rule_groups' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'tax_rules' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'taxes' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'translated_configurations' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'warehouse_product_locations' => ['GET' => 1, 'POST' => 0, 'PUT' => 0, 'DELETE' => 0, 'HEAD' => 1],//
                'warehouses' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 0, 'HEAD' => 1],//
                'weight_ranges' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
                'zones' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
            ];

            WebserviceKey::setPermissionForAccount($apiAccess->id, $permissions);
            
            //either return key or update key within the configuration code
            Configuration::updateValue('SHOPLYNC_SMS_PRO_API_KEY', $apiAccess->key);            
        }
        

	}
}
