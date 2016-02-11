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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2016 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
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
        $pol_params = $this->module->getParams($cart);
        $this->context->smarty->assign(array(
            'pol_params' => $pol_params,
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
