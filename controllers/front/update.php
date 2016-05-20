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

class InterpagosUpdateModuleFrontController extends ModuleFrontController
{
    /**
     * @see parent::$conten_only
     */
    public $content_only = true;

    public function initContent()
    {
        parent::initContent();
        header('Content-Type: application/json');
        if ($this->module->validation()) {
            http_response_code(200);
            die(json_encode('OK'));
        }
        http_response_code(500);
        die(json_encode('ERROR'));
    }
}
