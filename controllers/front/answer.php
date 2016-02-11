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

class InterpagosAnswerModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;
    public $ssl = true;
    public $auth = true;

    /**
    * @see FrontController::initContent()
    */

    public function initContent()
    {
        parent::initContent();
        $this->module->validation();

        if (Tools::getIsset('IDReference') && Tools::getIsset('ExtraData2') && Tools::getIsset('ExtraData1')) {
            $id_module = (int)Tools::getValue('ExtraData2');
            $key = pSQL(Tools::getValue('ExtraData1'));
            $id_cart = pSQL(Tools::getValue('IDReference'));
            $id_order = $this->module->currentOrder;
            $request = "id_order={$id_order}&id_cart={$id_cart}&id_module={$id_module}&key={$key}";

            $url = $this->context->link->getPageLink('order-confirmation', true, null, $request);
        } else {
            $url = 'index.php?controller=order';
        }

        Tools::redirect($url);
    }
}
