<?php
/**
* 2007-2015 PrestaShop
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
*  @copyright 2007-20154 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Function used to update your module from previous versions to the version 1.1,
 * Don't forget to create one file per version.
 */
function upgrade_module_1_0_3($module)
{
    /**
     * Do everything you want right there,
     * You could add a column in one of your module's tables
     */
    if (Configuration::updateValue('INTERPAGOS_ID_USUARIO', Configuration::get('POL_IDUSUARIO'))
    && Configuration::deleteByName('POL_IDUSUARIO')
    && Configuration::updateValue('INTERPAGOS_LLAVE', Configuration::get('POL_LLAVE'))
    && Configuration::deleteByName('POL_LLAVE')

    && Configuration::updateValue('INTERPAGOS_MINIMUM', Configuration::get('POL_MINIMUM'))
    && Configuration::deleteByName('POL_MINIMUM')
    && Configuration::updateValue('INTERPAGOS_MAXIMUM', Configuration::get('POL_MAXIMUM'))
    && Configuration::deleteByName('POL_MAXIMUM')
    && Configuration::updateValue('INTERPAGOS_PRODUCCION', Configuration::get('POL_MODO'))
    && Configuration::deleteByName('POL_MODO')) {
        return $module;
    }

    return false;
}
