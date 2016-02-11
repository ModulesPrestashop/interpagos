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

<div class="box cheque-box">
    <h3 class="page-subheading">
        <i class="icon-info-sign"></i> {if isset($purchase_order) && $purchase_order}{l s='Purchase order received' mod='interpagos'}{else}{l s='Order by Interpagos received' mod='interpagos'}{/if}
    </h3>
	{if $status == 'complete'}
		<div class="alert alert-success">
			<span>{l s='Your order status is complete' mod='interpagos'}</span>
		</div>
		<p class="cheque-indent">
			{l s='Your order reference %s on' sprintf=$reference|escape:'htmlall':'UTF-8' mod='interpagos'} <span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}</span> {l s='was received.' mod='interpagos'}
			<br /><br /><span class="bold">{l s='Your order will be shipped as soon as possible.' mod='interpagos'}</span>
			<br /><br />{l s='For any questions or for further information, please contact our' mod='interpagos'} 
			<a class="btn btn-default" href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='interpagos'}</a>.
		</p>
	{elseif $status == 'pending'}
		<div class="alert alert-warning">
			<span>{l s='Your order status is pending payment' mod='interpagos'}</span>
		</div>
		<p class="cheque-indent">
			{l s='Your order reference %s on' sprintf=$reference|escape:'html':'UTF-8' mod='interpagos'} <span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}</span> {l s='was received.' mod='interpagos'}
			<br /><br /><span class="bold">{if isset($purchase_order) && $purchase_order}{l s='Our customer service will validate your order and they will generate an invoice to enable the option to make your payment.' mod='interpagos'} <a href="{$link->getModuleLink('interpagos', 'pending', [], true)}">{l s='Click here to open your Pending Payments.' mod='interpagos'}</a>{else}{l s='Your order will be shipped as soon as possible after validate your payment report.' mod='interpagos'}{/if}</span>
			<br /><br />{l s='For any questions or for further information, please contact our' mod='interpagos'} 
			<a class="btn btn-default" href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='interpagos'}</a>.
		</p>
	{else}
		<div class="alert alert-danger">
			<span>{l s='Your order status is canceled' mod='interpagos'}</span>
		</div>
		<p class="cheque-indent">
			{l s='Your order reference %s on' sprintf=$reference|escape:'html':'UTF-8' mod='interpagos'} <span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}</span> {l s='was received.' mod='interpagos'}
			<br /><br />{l s='We noticed a problem with your order. If you think this is an error or want to use other payment method, feel free to contact our' mod='interpagos'} 
			<a class="btn btn-default" href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='interpagos'}</a>.
		</p>
	{/if}
</div>