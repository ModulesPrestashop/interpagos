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

        $this->version = '1.0.3';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Interpagos');
        $this->description = $this->l('Online payment method Interpagos - Colombia');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('INTERPAGOS_ID_USUARIO') || !Configuration::get('INTERPAGOS_LLAVE')) {
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
        && Configuration::updateValue('INTERPAGOS_MINIMUM', '2000')
        && Configuration::updateValue('INTERPAGOS_MAXIMUM', '2000000')
        && Configuration::updateValue('INTERPAGOS_PRODUCCION', 0)) {
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
        Configuration::deleteByName('INTERPAGOS_MINIMUM');
        Configuration::deleteByName('INTERPAGOS_MAXIMUM');
        Configuration::deleteByName('INTERPAGOS_PRODUCCION');
        Configuration::deleteByName('INTERPAGOS_ID_USUARIO');
        Configuration::deleteByName('INTERPAGOS_LLAVE');
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
            if (!Tools::getIsset('INTERPAGOS_ID_USUARIO')) {
                $this->_errors[] = $this->l('Interpagos Customer ID is requiered.');
            }

            if (!Tools::getIsset('INTERPAGOS_LLAVE')) {
                $this->_errors[] = $this->l('Interpagos KEY is required.');
            }

            if (!Tools::getIsset('INTERPAGOS_MINIMUM') || !Validate::isUnsignedFloat(Tools::getValue('INTERPAGOS_MINIMUM'))) {
                $this->_errors[] = $this->l('Minimum: Error in typed value');
            }

            if (!Tools::getIsset('INTERPAGOS_MAXIMUM') || !Validate::isUnsignedFloat(Tools::getValue('INTERPAGOS_MAXIMUM'))) {
                $this->_errors[] = $this->l('Maximum: Error in typed value');
            }

            if (Tools::getValue('INTERPAGOS_MINIMUM') >= Tools::getValue('INTERPAGOS_MAXIMUM')) {
                $this->_errors[] = $this->l('Minimum value must be less than maximum');
            }
        }
    }

    private function postProcess()
    {
        if (Tools::isSubmit('submit'.$this->name)) {
            Configuration::updateValue('INTERPAGOS_ID_USUARIO', pSQL(Tools::getValue('INTERPAGOS_ID_USUARIO')));
            Configuration::updateValue('INTERPAGOS_LLAVE', pSQL(Tools::getValue('INTERPAGOS_LLAVE')));
            Configuration::updateValue('INTERPAGOS_PRODUCCION', (bool)Tools::getValue('INTERPAGOS_PRODUCCION'));
            Configuration::updateValue('INTERPAGOS_MINIMUM', (float)pSQL(Tools::getValue('INTERPAGOS_MINIMUM')));
            Configuration::updateValue('INTERPAGOS_MAXIMUM', (float)pSQL(Tools::getValue('INTERPAGOS_MAXIMUM')));
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
                        'name' => 'INTERPAGOS_ID_USUARIO',
                        'desc' => $this->l('Type your customer ID'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('KEY'),
                        'name' => 'INTERPAGOS_LLAVE',
                        'desc' => $this->l('Type your KEY'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Production mode'),
                        'name' => 'INTERPAGOS_PRODUCCION',
                        'desc' => $this->l('Set if you will work in production mode, Yes: Production, No: Test.'),
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
                        'name' => 'INTERPAGOS_MINIMUM',
                        'desc' => $this->l('Type the minimum value for the payment method is available, (e.g. 12345 or 12345.67)'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Maximum'),
                        'name' => 'INTERPAGOS_MAXIMUM',
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
            'INTERPAGOS_ID_USUARIO' => Tools::getValue('INTERPAGOS_ID_USUARIO', Configuration::get('INTERPAGOS_ID_USUARIO')),
            'INTERPAGOS_LLAVE' => Tools::getValue('INTERPAGOS_LLAVE', Configuration::get('INTERPAGOS_LLAVE')),
            'INTERPAGOS_PRODUCCION' => Tools::getValue('INTERPAGOS_PRODUCCION', Configuration::get('INTERPAGOS_PRODUCCION')),
            'INTERPAGOS_MINIMUM' => Tools::getValue('INTERPAGOS_MINIMUM', Configuration::get('INTERPAGOS_MINIMUM')),
            'INTERPAGOS_MAXIMUM' => Tools::getValue('INTERPAGOS_MAXIMUM', Configuration::get('INTERPAGOS_MAXIMUM')),
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

        $interpagos_minimum = (float)Configuration::get('INTERPAGOS_MINIMUM');
        $interpagos_maximum = (float)Configuration::get('INTERPAGOS_MAXIMUM');
        $total_cart = (float)$this->context->cart->getOrderTotal(false);
        if ($total_cart < $interpagos_minimum || $total_cart > $interpagos_maximum) {
            return;
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
            case (int)Configuration::get('PS_OS_PAYMENT'):
            case (int)Configuration::get('PS_OS_OUTOFSTOCK'):
                $this->context->smarty->assign('status', 'complete');
                break;
            case (int)Configuration::get('INTERPAGOS_WAITING_PAYMENT'):
                $this->context->smarty->assign('status', 'pending');
                break;
            case (int)Configuration::get('PS_OS_ERROR'):
            case (int)Configuration::get('PS_OS_CANCELED'):
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
        if ($id_client != Configuration::get('INTERPAGOS_ID_USUARIO')) {
            $errors[] = $this->l('Error in seller ID');
        }

        // Check for signature
        $firma_local_content = $id_client.'-'.Configuration::get('INTERPAGOS_LLAVE').'-'.$id_reference.'-'.$total_amount.'-'.$transaction_code;
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

        $message = utf8_encode(Tools::nl2br(pSQL($message)));

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
                return $this->validateOrder(
                    $id_reference,
                    $id_order_state,
                    $total_amount,
                    $module_name,
                    $message,
                    $extra_data,
                    null,
                    false,
                    $extra_data_1
                );
            } else {
                $id_order = (int)Order::getOrderByCartId($this->context->cart->id);

                $order = new Order($id_order);
                if (Validate::isLoadedObject($order)
                && $id_order_state != $order->getCurrentState()) {
                    return $order->setCurrentState($id_order_state);
                }
            }
        }
        return false;
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

        $params = array();
        // Seller
        $params['IdClient'] = Configuration::get('INTERPAGOS_ID_USUARIO');
        $params['PayMethod'] = 1;
        $params['RecurringBill'] = 0;
        $params['LanguajeInterface'] = 'SP';
        $params['Test'] = (Configuration::get('INTERPAGOS_PRODUCCION') ? 0 : 1);
        $this->context->smarty->assign('prueba', (int)$params['Test']);

        // Customer
        $params['ShopperName'] = $this->context->customer->firstname.' '.$this->context->customer->lastname;
        $params['ShopperEmail'] = $this->context->customer->email;

        // Cart
        $params['IDReference'] = (int)$cart->id;
        $params['Reference'] = $this->l('Order of cart #').$params['IDReference'];
        $params['ExtraData1'] = $cart->secure_key;
        $params['ExtraData2'] = (int)$this->id;

        // Price
        $params['TotalAmount'] = number_format((float)$total_amount, 2, '.', '');
        $params['TaxAmount'] = number_format((float)$tax_amount, 2, '.', '');
        $params['BaseAmount'] = number_format((float)$base_amount, 2, '.', '');
        $params['Currency'] = $this->context->currency->iso_code;

        // Token
        $id_client = $params['IdClient'];
        $interpagos_llave = Configuration::get('INTERPAGOS_LLAVE');
        $id_reference = $params['IDReference'];
        $total_amount = $params['TotalAmount'];
        $pol_token = "{$id_client}-{$interpagos_llave}-{$id_reference}-{$total_amount}";
        $params['Token'] = sha1($pol_token);

        // URL
        $params['cancel_url'] = $this->context->link->getPageLink('order', true);
        $params['PageAnswer'] = $this->context->link->getModuleLink('interpagos', 'answer', array(), true);
        $params['PageConfirm'] = $this->context->link->getModuleLink('interpagos', 'update', array(), true);

        // Sort
        ksort($params);

        return $params;
    }
}
