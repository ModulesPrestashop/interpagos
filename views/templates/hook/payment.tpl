{*
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
*}

<p class="payment_module">
    <a href="{$link->getModuleLink('interpagos', 'payment')|escape:'html':'UTF-8'}" title="{l s='Pay by Interpagos' mod='interpagos'}">
        <strong><i class="fa fa-credit-card fa-4x pull-left"></i> {l s='Pay by Interpagos' mod='interpagos'}</strong>
        <br />
        <span>{l s='Credit card, debit card, cash on Exito, Carulla, Vivero and Baloto' mod='interpagos'}</span>
    </a>
</p>