<?php
/**
* 2015 Jorge Vargas
*
* NOTICE OF LICENSE
*
* This source file is subject to the End User License Agreement (EULA)
*
* See attachmente file LICENSE
*
* @author    Jorge Vargas <jorgevargaslarrota@hotmail.com>
* @copyright 2012-2015 Jorge Vargas
* @license   End User License Agreement (EULA)
* @package   interpagos
* @version   1.0
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Interpagos extends PaymentModule
{
    protected $_errors = array();
    protected $_html = '';

    public function __construct()
    {
        $this->name = 'interpagos';
        $this->tab = 'payments_gateways';
        $this->author = 'Jorge Vargas';
        $this->module_key = '9b48301fab5847f1a36b3ec5a4cddf68';
        $this->controllers = array('answer', 'update', 'payment');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->version = '1.0.2';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Interpagos');
        $this->description = $this->l('Online payment method Interpagos - Colombia');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('POL_IDUSUARIO') || !Configuration::get('POL_LLAVE')) {
            $this->warning = $this->l('No authentication provided');
        }

        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        $this->dependencies = array('blockcart');
    }

    /* Install */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!Configuration::get('INTERPAGOS_WAITING_PAYMENT')) {
            Configuration::updateValue('INTERPAGOS_WAITING_PAYMENT', $this->addState());
        }

        if (parent::install()
        && $this->registerHook('payment')
        && $this->registerHook('orderConfirmation')
        && $this->registerHook('displayCustomerAccount')
        && Configuration::updateValue('POL_MINIMUM', '2000')
        && Configuration::updateValue('POL_MAXIMUM', '2000000')
        && Configuration::updateValue('POL_MODO', 0)) {
            return true;
        }
        return false;
    }

    /* Add order state */
    public function addState()
    {
        $order_state = new OrderState();
        $order_state->name = array();
        foreach (Language::getLanguages() as $language) {
            $order_state->name[$language['id_lang']] = $this->l('Interpagos: Waiting payment');
        }
        $order_state->send_email = false;
        $order_state->color = 'RoyalBlue';
        $order_state->hidden = false;
        $order_state->delivery = false;
        $order_state->logable = false;
        $order_state->module_name = $this->name;
        if ($order_state->add()) {
            Tools::copy(
                dirname(__FILE__).'/views/img/icon.gif',
                dirname(__FILE__).'/../../img/os/'.(int)$order_state->id.'.gif'
            );
        }

        return $order_state->id;
    }

    /* Uninstall */
    public function uninstall()
    {
        $order_state = new OrderState((int)Configuration::get('INTERPAGOS_WAITING_PAYMENT'));
        $order_state->delete();

        Configuration::deleteByName('INTERPAGOS_WAITING_PAYMENT');
        Configuration::deleteByName('POL_MINIMUM');
        Configuration::deleteByName('POL_MAXIMUM');
        Configuration::deleteByName('POL_MODO');
        Configuration::deleteByName('POL_IDUSUARIO');
        Configuration::deleteByName('POL_LLAVE');
        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    /* Get content in back office */
    public function getContent()
    {
        $this->_html = null;

        if (Tools::isSubmit('submit'.$this->name)) {
            $this->postValidation();
            if (!count($this->_errors)) {
                $this->postProcess();
            } else {
                foreach ($this->_errors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->displayHelp();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    private function postValidation()
    {
        if (Tools::isSubmit('submit'.$this->name)) {
            if (!Tools::getIsset('POL_IDUSUARIO')) {
                $this->_errors[] = $this->l('Interpagos Customer ID is requiered.');
            }

            if (!Tools::getIsset('POL_LLAVE')) {
                $this->_errors[] = $this->l('Interpagos KEY is required.');
            }

            if (!Tools::getIsset('POL_MINIMUM') || !Validate::isUnsignedFloat(Tools::getValue('POL_MINIMUM'))) {
                $this->_errors[] = $this->l('Minimum: Error in typed value');
            }

            if (!Tools::getIsset('POL_MAXIMUM') || !Validate::isUnsignedFloat(Tools::getValue('POL_MAXIMUM'))) {
                $this->_errors[] = $this->l('Maximum: Error in typed value');
            }

            if (Tools::getValue('POL_MINIMUM') >= Tools::getValue('POL_MAXIMUM')) {
                $this->_errors[] = $this->l('Minimum value must be less than maximum');
            }
        }
    }

    private function postProcess()
    {
        if (Tools::isSubmit('submit'.$this->name)) {
            Configuration::updateValue('POL_IDUSUARIO', pSQL(Tools::getValue('POL_IDUSUARIO')));
            Configuration::updateValue('POL_LLAVE', pSQL(Tools::getValue('POL_LLAVE')));
            Configuration::updateValue('POL_MODO', (bool)Tools::getValue('POL_MODO'));
            Configuration::updateValue('POL_MINIMUM', (float)pSQL(Tools::getValue('POL_MINIMUM')));
            Configuration::updateValue('POL_MAXIMUM', (float)pSQL(Tools::getValue('POL_MAXIMUM')));
        }

        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    private function displayHelp()
    {
        return $this->context->smarty->fetch($this->local_path.'views/templates/admin/info.tpl');
    }

    public function renderForm()
    {
        // Init Fields form array
        $fields_form = array();
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-wrench'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Customer ID'),
                        'name' => 'POL_IDUSUARIO',
                        'desc' => $this->l('Type your customer ID'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('KEY'),
                        'name' => 'POL_LLAVE',
                        'desc' => $this->l('Type your KEY'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Production mode'),
                        'name' => 'POL_MODO',
                        'desc' => $this->l('Set if you will work in test or production mode'),
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'production',
                                'value' => 1,
                                'label' => $this->l('Production')
                            ),
                            array(
                                'id' => 'test',
                                'value' => 0,
                                'label' => $this->l('Test')
                            ),
                        ),
                        'class' => 't'
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Minimum'),
                        'name' => 'POL_MINIMUM',
                        'desc' => $this->l('Type the minimum value for the payment method is available, (e.g. 12345 or 12345.67)'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Maximum'),
                        'name' => 'POL_MAXIMUM',
                        'desc' => $this->l('Type the maximum value for the payment method is available, (e.g. 12345 or 12345.67)'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Update settings'),
                )
            )
        );

        // Get default Language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper = new HelperForm();

        // Module, t    oken and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'POL_IDUSUARIO' => Tools::getValue('POL_IDUSUARIO', Configuration::get('POL_IDUSUARIO')),
            'POL_LLAVE' => Tools::getValue('POL_LLAVE', Configuration::get('POL_LLAVE')),
            'POL_MODO' => Tools::getValue('POL_MODO', Configuration::get('POL_MODO')),
            'POL_MINIMUM' => Tools::getValue('POL_MINIMUM', Configuration::get('POL_MINIMUM')),
            'POL_MAXIMUM' => Tools::getValue('POL_MAXIMUM', Configuration::get('POL_MAXIMUM')),
        );
    }

    public function hookDisplayPayment($params)
    {
        if (!$this->active || !$this->checkCurrency($params['cart']))
            return;

        //Validate currencies
        $supported_currencies = array('COP', 'USD', 'EUR');
        if (!in_array($this->context->currency->iso_code, $supported_currencies)) {
            return;
        }

        $pol_minimum = (float)Configuration::get('POL_MINIMUM');
        $pol_maximum = (float)Configuration::get('POL_MAXIMUM');
        if ($pol_minimum || $pol_maximum) {
            $total_cart = (float)$this->context->cart->getOrderTotal(false);
            if ($total_cart < $pol_minimum || $total_cart > $pol_maximum) {
                return;
            }
        }

        return $this->display(__FILE__, 'payment.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hookDisplayPaymentReturn($params)
    {
        return $this->hookDisplayOrderConfirmation($params);
    }

    public function hookDisplayOrderConfirmation($params)
    {
        if (!isset($params['objOrder']) || ($params['objOrder']->module != $this->name)) {
            return false;
        }

        if (!$this->active) {
            return;
        }

        $status = $params['objOrder']->getCurrentState();
        switch ($status) {
            case _PS_OS_PAYMENT_:
            case _PS_OS_OUTOFSTOCK_:
                $this->context->smarty->assign('status', 'complete');
                break;
            case Configuration::get('INTERPAGOS_WAITING_PAYMENT'):
                $this->context->smarty->assign('status', 'pending');
                break;
            case _PS_OS_ERROR_:
            case _PS_OS_CANCELED_:
            default:
                $this->context->smarty->assign('status', 'failed');
                break;
        }

        if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference)) {
            $this->smarty->assign('reference', $params['objOrder']->reference);
        }

        return $this->display(__FILE__, 'confirmation.tpl');
    }

    public function validation()
    {
        $errors = array();

        // First we need to check var presence
        $needed_vars = array(
            'IdClient',
            'Token',
            'IDReference',
            'Reference',
            'Currency',
            'BaseAmount',
            'TaxAmount',
            'TotalAmount',
            'ShopperName',
            'ShopperEmail',
            'LanguajeInterface',
            'PayMethod',
            'RecurringBill',
            'Test',
            'TransactionId',
            'TransactionCode',
            'TransactionMessage',
            'TokenTransactionCode'
        );
        foreach ($needed_vars as $k) {
            if (!Tools::getIsset($k)) {
                $errors[] = $this->l('Missing parameter:').' '.$k;
            }
        }

        // Received variable
        $id_client = (int)Tools::getValue('IdClient');
        $id_reference = (int)Tools::getValue('IDReference');
        $total_amount = pSQL(Tools::getValue('TotalAmount'));
        $transaction_code = pSQL(Tools::getValue('TransactionCode'));
        $transaction_id = pSQL(Tools::getValue('TransactionId'));
        $token_transaction_code = pSQL(Tools::getValue('TokenTransactionCode'));

        // Check local and remote variables
        if ($id_client != Configuration::get('POL_IDUSUARIO')) {
            $errors[] = $this->l('Error in seller ID');
        }

        // Check for signature
        $firma_local_content = $id_client.'-'.Configuration::get('POL_LLAVE').'-'.$id_reference.'-'.$total_amount.'-'.$transaction_code;
        $firma_local = sha1($firma_local_content);
        if ($firma_local != $token_transaction_code) {
            $errors[] = $this->l('Error in token transaction code.  Local token is: ').$firma_local;
        }

        $message = '';
        foreach ($_POST as $key => $value) {
            $message .= $key.' : '.$value.'\n';
        }

        if (count($errors)) {
            $message .= count($errors).' '.$this->l('error(es):').'\n';
        }

        foreach ($errors as $error){
            $message .= $error.'\n';
        }

        $message = Tools::nl2br(strip_tags(utf8_encode($message)));

        // Then, load the customer cart and perform some checks
        $this->context->cart = new Cart($id_reference);

        if (Validate::isLoadedObject($this->context->cart)) {
            switch ($transaction_code) {
                case '00': // Transaccion aprobada tarjeta credito
                case '02': // Transaccion aprobada tarjeta debito
                case '16': // Pago en efectivo aprobado pendiente de abonar
                    $id_order_state = (int)Configuration::get('PS_OS_PAYMENT');
                    break;
                case '10': // Pendiente confirmacion telefonica
                case '11': // Pendiente confirmacion PSE
                case '15': // Pago en efectivo pendiente de pago
                    $id_order_state = (int)Configuration::get('INTERPAGOS_WAITING_PAYMENT');
                    break;
                case '01': // Transaccion abandonada
                    $id_order_state = (int)Configuration::get('PS_OS_CANCELED');
                    break;
                case '03': // Negada respuestas de seguridad no aprobadas
                case '04': // Negada sistema antifraude, pendiente de confirmacion telefonica
                case '05': // Negada sistema antifraude, transaccion de alto riesgo
                case '06': // Negada tarjeta de credito negada por la entidad
                case '07': // Negada tarjeta de credito alto riesgo
                case '08': // Negada tarjeta debito negada por la entidad
                case '09': // Negada tarjeta debito alto riesgo
                default: // otros valores
                    $id_order_state = (int)Configuration::get('PS_OS_ERROR');
                    break;
            }

            // Update or create a new order state
            $extra_data_1 = pSQL(Tools::getValue('ExtraData1'));
            $extra_data = array('transaction_id' => $transaction_id);
            $module_name = $this->displayName;

            if (!$this->context->cart->OrderExists()) {
                $this->validateOrder($id_reference, $id_order_state, $total_amount, $module_name, $message, $extra_data, null, false, $extra_data_1);
            } else {
                $id_order = (int)Order::getOrderByCartId($this->context->cart->id);

                $order = new Order($id_order);
                if (Validate::isLoadedObject($order) && $id_order_state != $order->getCurrentState()) {
                    $order->setCurrentState($id_order_state);
                }
            }
        }
    }

    public function hookDisplayCustomerAccount()
    {
        return $this->display(__FILE__, 'my-account.tpl');
    }

    /**
     * @since 1.0.1
     */
    public function getParams(Cart $cart)
    {
        if (!Validate::isLoadedObject($cart)) {
            $cart = $this->context->cart;
        }

        $base_amount = (float)$cart->getOrderTotal(false);
        $total_amount = (float)$cart->getOrderTotal();
        $tax_amount = (float)$total_amount - (float)$base_amount;

        $pol_params = array();
        // Seller
        $pol_params['IdClient'] = Configuration::get('POL_IDUSUARIO');
        $pol_params['PayMethod'] = 1;
        $pol_params['RecurringBill'] = 0;
        $pol_params['LanguajeInterface'] = 'SP';
        $pol_params['Test'] = (Configuration::get('POL_MODO') ? 0 : 1);
        if ($pol_params['Test']) {
            //$this->context->smarty->assign('URL_POL', Configuration::get('POL_URLPRUEBA'));
            $this->context->smarty->assign('prueba', 1);
        } else {
            //$this->context->smarty->assign('URL_POL', Configuration::get('POL_URLPRODUCCION'));
            $this->context->smarty->assign('prueba', 0);
        }

        // Customer
        $pol_params['ShopperName'] = $this->context->customer->firstname.' '.$this->context->customer->lastname;
        $pol_params['ShopperEmail'] = $this->context->customer->email;

        // Cart
        $pol_params['IDReference'] = (int)$cart->id;
        $pol_params['Reference'] = $this->l('Order of cart #').$pol_params['IDReference'];
        $pol_params['ExtraData1'] = $cart->secure_key;
        $pol_params['ExtraData2'] = (int)$this->id;

        // Price
        $pol_params['TotalAmount'] = number_format((float)$total_amount, 2, '.', '');
        $pol_params['TaxAmount'] = number_format((float)$tax_amount, 2, '.', '');
        $pol_params['BaseAmount'] = number_format((float)$base_amount, 2, '.', '');
        $pol_params['Currency'] = $this->context->currency->iso_code;

        // Token
        $id_client = $pol_params['IdClient'];
        $pol_llave = Configuration::get('POL_LLAVE');
        $id_reference = $pol_params['IDReference'];
        $total_amount = $pol_params['TotalAmount'];
        $pol_token = "{$id_client}-{$pol_llave}-{$id_reference}-{$total_amount}";
        $pol_params['Token'] = sha1($pol_token);

        // URL
        $pol_params['cancel_url'] = $this->context->link->getPageLink('order', true);
        $pol_params['PageAnswer'] = $this->context->link->getModuleLink('interpagos', 'answer', array(), true);
        $pol_params['PageConfirm'] = $this->context->link->getModuleLink('interpagos', 'update', array(), true);

        // Sort
        ksort($pol_params);

        return $pol_params;
    }
}
