<?php
/*
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
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2015 PrestaShop SA
 *  @version  Release: $Revision: 7060 $
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_CAN_LOAD_FILES_')) {
    exit;
}

include_once(__DIR__ . '/src/CmsBlockRepository.php');
include_once(__DIR__ . '/src/CmsBlock.php');

class blockcms extends Module
{
    protected $_html;
    protected $_display;

    public function __construct()
    {
        $this->name = 'blockcms';
        $this->tab = 'front_office_features';
        $this->version = '2.1.1';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('CMS block');
        $this->description = $this->l('Adds a block with several CMS links.');
        $this->secure_key = Tools::encrypt($this->name);
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        $repository = new CmsBlockRepository(
            Db::getInstance(),
            $this->context->shop
        );

        return parent::install()
            && $this->installTab()
            && $repository->createTables();
    }

    public function uninstall()
    {
        $repository = new CmsBlockRepository(
            Db::getInstance(),
            $this->context->shop
        );

        return parent::uninstall()
            && $this->uninstallTab()
            && $repository->dropTables();
    }

    public function installTab()
    {
		$tab = new Tab();
		$tab->active = 1;
		$tab->class_name = "AdminLinkWidget";
		$tab->name = array();
		foreach (Language::getLanguages(true) as $lang)
			$tab->name[$lang['id_lang']] = "Link Widget";
		$tab->id_parent = (int)Tab::getIdFromClassName('AdminThemes');
		$tab->module = $this->name;
		return $tab->add();
    }

    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminLinkWidget');
        $tab = new Tab($id_tab);
        return $tab->delete();
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminLinkWidget')
        );
    }

    public function hookActionShopDataDuplication($params)
    {
        //get all cmd block to duplicate in new shop
        $cms_blocks = Db::getInstance()->executeS('
			SELECT * FROM `'._DB_PREFIX_.'cms_block` cb
			JOIN `'._DB_PREFIX_.'cms_block_shop` cbf
				ON (cb.`id_cms_block` = cbf.`id_cms_block` AND cbf.`id_shop` = '.(int)$params['old_id_shop'].') ');

        if (count($cms_blocks)) {
            foreach ($cms_blocks as $cms_block) {
                Db::getInstance()->execute('
					INSERT IGNORE INTO '._DB_PREFIX_.'cms_block (`id_cms_block`, `id_cms_category`, `location`, `position`, `display_store`)
					VALUES (NULL, '.(int)$cms_block['id_cms_category'].', '.(int)$cms_block['location'].', '.(int)$cms_block['position'].', '.(int)$cms_block['display_store'].');');

                $id_block_cms =  Db::getInstance()->Insert_ID();

                Db::getInstance()->execute('INSERT IGNORE INTO '._DB_PREFIX_.'cms_block_shop (`id_cms_block`, `id_shop`) VALUES ('.(int)$id_block_cms.', '.(int)$params['new_id_shop'].');');

                $langs = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'cms_block_lang` WHERE `id_cms_block` = '.(int)$cms_block['id_cms_block']);

                foreach ($langs as $lang) {
                    Db::getInstance()->execute('
						INSERT IGNORE INTO `'._DB_PREFIX_.'cms_block_lang` (`id_cms_block`, `id_lang`, `name`)
						VALUES ('.(int)$id_block_cms.', '.(int)$lang['id_lang'].', \''.pSQL($lang['name']).'\');');
                }

                $pages =  Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'cms_block_page` WHERE `id_cms_block` = '.(int)$cms_block['id_cms_block']);

                foreach ($pages as $page) {
                    Db::getInstance()->execute('
						INSERT IGNORE INTO `'._DB_PREFIX_.'cms_block_page` (`id_cms_block_page`, `id_cms_block`, `id_cms`, `is_category`)
						VALUES (NULL, '.(int)$id_block_cms.', '.(int)$page['id_cms'].', '.(int)$page['is_category'].');');
                }
            }
        }
    }
}
