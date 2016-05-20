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

/**
 * @since 1.5.0
 */
class InterpagosPaymentModuleFrontController extends ModuleFrontController
{
    /**
     * @see ModuleFrontController::$ssl
     */
    public $ssl = true;

    /**
     * @see ModuleFrontController::$display_column_left
     */
    public $display_column_left = false;

    /**
     * @see ModuleFrontController::$display_column_right
     */
    public $display_column_right = false;

    /**
     * @see ModuleFrontController::$bootstrap
     */
    public $bootstrap = true;

    /**
     * @see ModuleFrontController::$auth
     */
    public $auth = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        if (($action = Tools::getValue('action')) && Tools::getValue('ajax')) {
            return $this->{'ajaxProcess'.Tools::toCamelCase($action)}();
        }

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart) || !$this->module->active) {
            Tools::redirect('index.php?controller=order');
        }

        // Smarty Cache
        $image = $this->module->getPathUri().'views/img/interpagos.jpg';
        $path_image = $this->module->getLocalPath().'views/img/interpagos.jpg';
        $params = $this->module->getParams($cart);
        $this->context->smarty->assign(array(
            'form_params' => $params,
            'image_interpagos' => $image,
            'image_size' => getimagesize($path_image),
            'id_cart' => (int)$cart->id
        ));

        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'this_path' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
        ));

        $this->setTemplate('payment-execution.tpl');
    }

    public function ajaxProcessValidateInterpagos()
    {
        $id_cart = Tools::getValue('id_cart');
        $cart = new Cart($id_cart);
        if (Validate::isLoadedObject($cart)) {
            $currency = $this->context->currency;
            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
            $customer = new Customer($cart->id_customer);
            if (Validate::isLoadedObject($customer)) {
                $validate = $this->module->validateOrder(
                    $cart->id,
                    Configuration::get('INTERPAGOS_WAITING_PAYMENT'),
                    $total,
                    $this->module->displayName,
                    NULL,
                    array(),
                    (int)$currency->id,
                    false,
                    $customer->secure_key
                );
                if ($validate) {
                    die(json_encode('OK'));
                }
                die ($validate);
            }
            die(json_encode($this->module->l('Error when load customer object')));
        }
        die (json_encode($this->module->l('Error when load cart object')));
    }
}
