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

{capture name=path}
    {l s='Interpagos payment.' mod='interpagos'}
{/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='interpagos'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='interpagos'}
    </p>
{else}
    <div class="box">
        <h3 class="page-subheading">
            <i class="icon-lock"></i> {l s='Interpagos payment.' mod='interpagos'}
        </h3>
        <div class="col-md-3">
            <img style="display:inline-block" src="{$image_interpagos|escape:'htmlall':'UTF-8'}" alt="Interpagos" width="{$image_size[0]|intval}" height="{$image_size[1]|intval}" />
        </div>
        <div class="col-md-9">
            <p class="cheque-indent">
                <strong class="dark">
                    {l s='You have chosen to pay by Interpagos.' mod='interpagos'} {l s='Here is a short summary of your order:' mod='interpagos'}
                </strong>
            </p>
            <ul>
                <li> {l s='The total amount of your order is' mod='interpagos'} <span id="amount" class="price">{displayPrice price=$total}</span> {if $use_taxes == 1}{l s='(tax incl.)' mod='interpagos'}{/if}</li>
                <li>{l s='You will be redirect to Interpagos secure server.' mod='interpagos'}</li>
                <li>{l s='Please confirm your order by clicking "I confirm my order"' mod='interpagos'}.</li>
            </ul>
            <hr />
            <p>
                <strong>{l s='With Interpagos you will be able to make your payments via:' mod='interpagos'}</strong>
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <strong>{l s='Credit cards' mod='interpagos'}</strong>
                            <hr />
                            <ul class="list-group">
                                <li class="list-group-item"><i class="fa fa-check-square-o"></i> {l s='American Express' mod='interpagos'}</li>
                                <li class="list-group-item"><i class="fa fa-check-square-o"></i> {l s='Diners Club' mod='interpagos'}</li>
                                <li class="list-group-item"><i class="fa fa-check-square-o"></i> {l s='MasterCard' mod='interpagos'}</li>
                                <li class="list-group-item"><i class="fa fa-check-square-o"></i> {l s='Visa' mod='interpagos'}</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <strong>{l s='Debit cards' mod='interpagos'}</strong>
                            <hr />
                            <ul>
                                <li><i class="fa fa-check-square-o"></i> {l s='PSE (All banks afilliate to ACH)' mod='interpagos'}</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <strong>{l s='Cash money' mod='interpagos'}</strong>
                            <hr />
                            <ul>
                                <li><i class="fa fa-check-square-o"></i> {l s='Carulla' mod='interpagos'}</li>
                                <li><i class="fa fa-check-square-o"></i> {l s='Vivero' mod='interpagos'}</li>
                                <li><i class="fa fa-check-square-o"></i> {l s='Exito' mod='interpagos'}</li>
                                <li><i class="fa fa-check-square-o"></i> {l s='Surtimax' mod='interpagos'}</li>
                                <li><i class="fa fa-check-square-o"></i> {l s='Baloto' mod='interpagos'}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </p>
        </div>
        <div class="clearfix"></div>
    </div>
    <form id="interpagos_form" name="interpagos_form" action="https://secure.interpagos.net/gateway/" method="post" class="hidden">
    	{foreach from=$form_params key=key item=value}
    		<input type="hidden" name="{$key|escape:'htmlall':'UTF-8'}" value="{$value|escape:'htmlall':'UTF-8'}" />
    	{/foreach}
    </form>
    <form id="validateInterpagos" action="#" method="post">
        <p class="cart_navigation clearfix" id="cart_navigation">
        	<a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'htmlall':'UTF-8'}">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='interpagos'}
            </a>
            <button id="submitValidate" class="button btn btn-default button-medium pull-right" type="submit">
                <span>{l s='I confirm my order' mod='interpagos'} <i class="fa fa-external-link"></i></span>
            </button>
            <div class="clearfix"></div>
            <div id="message_cart"></div>
        </p>
    </form>

    <script type="text/javascript">
        var url = "{$link->getModuleLink('interpagos', 'payment', [], true)|addslashes}";
        var id_cart = {$id_cart|intval};
        $(document).ready(function() {
            $('#submitValidate').on('click', function(e) {
                e.preventDefault();
                var message = "{l s='Processing form...' mod='interpagos'}";
                $('#message_cart').html('<div class="alert alert-info">'+message+'</div>');
                $("#submitValidate").attr("disabled", true);
        		processInterpagos();
        	});
        });

        function processInterpagos()
        {
            console.log(url);
            $.ajax({
    			type: "POST",
    			url: url,
    			async: true,
    			dataType: "json",
    			data: {
    				ajax: "1",
    				action: "validateInterpagos",
                    id_cart: id_cart
    			},
    			success: function(r) {
                    console.log(r);
                    if (r == 'OK') {
                        document.interpagos_form.submit();
                    } else {
                        $('#message_cart').html('<div class="alert alert-danger">'+r+'</div>');
                    }
    			},
                error: function(e) {
                    console.log(e);
                    $('#message_cart').html(e.responseText);
                }
    		});
        }
    </script>
{/if}