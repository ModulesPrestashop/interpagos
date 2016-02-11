{**
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