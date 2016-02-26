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

include_once(__DIR__ . '/BlockCMSModel.php');
include_once(__DIR__ . '/src/BlockCmsRepository.php');

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
        $this->_clearCache('blockcms.tpl');

        $repository = new CmsBlockRepository(
            Db::getInstance(),
            $this->context->shop
        );

        return parent::install()
            && $this->installTab()
            && $this->registerHook('leftColumn')
            && $this->registerHook('rightColumn')
            && $this->registerHook('header')
            && $this->registerHook('footer')
            && $this->registerHook('actionObjectCmsUpdateAfter')
            && $this->registerHook('actionObjectCmsDeleteAfter')
            && $this->registerHook('actionShopDataDuplication')
            && $this->registerHook('actionAdminStoresControllerUpdate_optionsAfter')
            && $repository->createTables();
    }

    public function uninstall()
    {
        $this->_clearCache('blockcms.tpl');

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

    public function initToolbar()
    {
        $current_index = AdminController::$currentIndex;
        $token = Tools::getAdminTokenLite('AdminModules');
        $back = Tools::safeOutput(Tools::getValue('back', ''));
        if (!isset($back) || empty($back)) {
            $back = $current_index.'&amp;configure='.$this->name.'&token='.$token;
        }

        switch ($this->_display) {
            case 'add':
                $this->toolbar_btn['cancel'] = array(
                    'href' => $back,
                    'desc' => $this->l('Cancel')
                );
                break;
            case 'edit':
                $this->toolbar_btn['cancel'] = array(
                    'href' => $back,
                    'desc' => $this->l('Cancel')
                );
                break;
            case 'index':
                $this->toolbar_btn['new'] = array(
                    'href' => $current_index.'&amp;configure='.$this->name.'&amp;token='.$token.'&amp;addBlockCMS',
                    'desc' => $this->l('Add new')
                );
                break;
            default:
                break;
        }
        return $this->toolbar_btn;
    }

    protected function initForm()
    {
        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = 'blockcms';
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $this->context->controller->_languages;
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $this->context->controller->default_form_language;
        $helper->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = $this->initToolbar();

        return $helper;
    }

    protected function changePosition()
    {
        if (!Validate::isInt(Tools::getValue('position')) ||
            (Tools::getValue('location') != BlockCMSModel::LEFT_COLUMN &&
             Tools::getValue('location') != BlockCMSModel::RIGHT_COLUMN) ||
            (Tools::getValue('way') != 0 && Tools::getValue('way') != 1)) {
            Tools::displayError();
        }

        $this->_html .= 'pos change!';
        $position = (int)Tools::getValue('position');
        $location = (int)Tools::getValue('location');
        $id_cms_block = (int)Tools::getValue('id_cms_block');

        if (Tools::getValue('way') == 0) {
            $new_position = $position + 1;
        } elseif (Tools::getValue('way') == 1) {
            $new_position = $position - 1;
        }

        BlockCMSModel::updateCMSBlockPositions($id_cms_block, $position, $new_position, $location);
        Tools::redirectAdmin('index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'));
    }

    protected function _postValidation()
    {
        $this->_errors = array();

        if (Tools::isSubmit('submitBlockCMS')) {
            $this->context->controller->getLanguages();
            $cmsBoxes = Tools::getValue('cmsBox');

            if (!Validate::isInt(Tools::getValue('display_stores')) || (Tools::getValue('display_stores') != 0 && Tools::getValue('display_stores') != 1)) {
                $this->_errors[] = $this->l('Invalid store display value.');
            }
            if (!Validate::isInt(Tools::getValue('block_location')) || (Tools::getValue('block_location') != BlockCMSModel::LEFT_COLUMN && Tools::getValue('block_location') != BlockCMSModel::RIGHT_COLUMN)) {
                $this->_errors[] = $this->l('Invalid block location.');
            }
            if (!is_array($cmsBoxes)) {
                $this->_errors[] = $this->l('You must choose at least one page -- or subcategory -- in order to create a CMS block.');
            } else {
                foreach ($cmsBoxes as $cmsBox) {
                    if (!preg_match('#^[01]_[0-9]+$#', $cmsBox)) {
                        $this->_errors[] = $this->l('Invalid CMS page and/or category.');
                    }
                }
                foreach ($this->context->controller->_languages as $language) {
                    if (strlen(Tools::getValue('block_name_'.$language['id_lang'])) > 40) {
                        $this->_errors[] = $this->l('The block name is too long.');
                    }
                }
            }
        } elseif (Tools::isSubmit('deleteBlockCMS') && !Validate::isInt(Tools::getValue('id_cms_block'))) {
            $this->_errors[] = $this->l('Invalid id_cms_block');
        } elseif (Tools::isSubmit('submitFooterCMS')) {
            if (Tools::getValue('footerBox') && is_array(Tools::getValue('footerBox'))) {
                foreach (Tools::getValue('footerBox') as $cmsBox) {
                    if (!preg_match('#^[01]_[0-9]+$#', $cmsBox)) {
                        $this->_errors[] = $this->l('Invalid CMS page and/or category.');
                    }
                }
            }

            $empty_footer_text = true;
            $footer_text = array((int)Configuration::get('PS_LANG_DEFAULT') => Tools::getValue('footer_text_'.(int)Configuration::get('PS_LANG_DEFAULT')));

            $this->context->controller->getLanguages();
            foreach ($this->context->controller->_languages as $language) {
                if ($language['id_lang'] == (int)Configuration::get('PS_LANG_DEFAULT')) {
                    continue;
                }

                $footer_text_value = Tools::getValue('footer_text_'.(int)$language['id_lang']);
                if (!empty($footer_text_value)) {
                    $empty_footer_text = false;
                    $footer_text[(int)$language['id_lang']] = $footer_text_value;
                } else {
                    $footer_text[(int)$language['id_lang']] = $footer_text[(int)Configuration::get('PS_LANG_DEFAULT')];
                }
            }

            if (!$empty_footer_text && empty($footer_text[(int)Configuration::get('PS_LANG_DEFAULT')])) {
                $this->_errors[] = $this->l('Please provide footer text for the default language.');
            } else {
                foreach ($this->context->controller->_languages as $language) {
                    Configuration::updateValue('FOOTER_CMS_TEXT_'.(int)$language['id_lang'], $footer_text[(int)$language['id_lang']], true);
                }
            }

            if ((Tools::getValue('cms_footer_on') != 0) && (Tools::getValue('cms_footer_on') != 1)) {
                $this->_errors[] = $this->l('Invalid footer activation.');
            }
        }
        if (count($this->_errors)) {
            foreach ($this->_errors as $err) {
                $this->_html .= '<div class="alert alert-danger">'.$err.'</div>';
            }

            return false;
        }
        return true;
    }

    protected function _postProcess()
    {
        if ($this->_postValidation() == false) {
            return false;
        }

        $this->_clearCache('blockcms.tpl');

        $this->_errors = array();
        if (Tools::isSubmit('submitBlockCMS')) {
            $this->context->controller->getLanguages();
            $id_cms_category = (int)Tools::getvalue('id_category');
            $display_store = (int)Tools::getValue('display_stores');
            $location = (int)Tools::getvalue('block_location');
            $position = BlockCMSModel::getMaxPosition($location);

            if (Tools::isSubmit('addBlockCMS')) {
                $id_cms_block = BlockCMSModel::insertCMSBlock($id_cms_category, $location, $position, $display_store);

                if ($id_cms_block !== false) {
                    foreach ($this->context->controller->_languages as $language) {
                        BlockCMSModel::insertCMSBlockLang($id_cms_block, $language['id_lang']);
                    }

                    $shops = Shop::getContextListShopID();

                    foreach ($shops as $shop) {
                        BlockCMSModel::insertCMSBlockShop($id_cms_block, $shop);
                    }
                }

                $this->_errors[] = $this->l('Cannot create a block!');
            } elseif (Tools::isSubmit('editBlockCMS')) {
                $id_cms_block = Tools::getvalue('id_cms_block');
                $old_block = BlockCMSModel::getBlockCMS($id_cms_block);

                BlockCMSModel::deleteCMSBlockPage($id_cms_block);

                if ($old_block[1]['location'] != (int)Tools::getvalue('block_location')) {
                    BlockCMSModel::updatePositions($old_block[1]['position'], $old_block[1]['position'] + 1, $old_block[1]['location']);
                }

                BlockCMSModel::updateCMSBlock($id_cms_block, $id_cms_category, $position, $location, $display_store);

                foreach ($this->context->controller->_languages as $language) {
                    $block_name = Tools::getValue('block_name_'.$language['id_lang']);
                    BlockCMSModel::updateCMSBlockLang($id_cms_block, $block_name, $language['id_lang']);
                }
            }

            $cmsBoxes = Tools::getValue('cmsBox');
            if ($cmsBoxes) {
                foreach ($cmsBoxes as $cmsBox) {
                    $cms_properties = explode('_', $cmsBox);
                    BlockCMSModel::insertCMSBlockPage($id_cms_block, $cms_properties[1], $cms_properties[0]);
                }
            }

            if (Tools::isSubmit('addBlockCMS')) {
                $redirect = 'addBlockCMSConfirmation';
            } elseif (Tools::isSubmit('editBlockCMS')) {
                $redirect = 'editBlockCMSConfirmation';
            }

            Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&'.$redirect);
        } elseif (Tools::isSubmit('deleteBlockCMS') && Tools::getValue('id_cms_block')) {
            $id_cms_block = Tools::getvalue('id_cms_block');

            if ($id_cms_block) {
                BlockCMSModel::deleteCMSBlock((int)$id_cms_block);
                BlockCMSModel::deleteCMSBlockPage((int)$id_cms_block);

                Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&deleteBlockCMSConfirmation');
            } else {
                $this->_html .= $this->displayError($this->l('Error: You are trying to delete a non-existing CMS block.'));
            }
        } elseif (Tools::isSubmit('addBlockCMSConfirmation')) {
            $this->_html .= $this->displayConfirmation($this->l('CMS block added.'));
        } elseif (Tools::isSubmit('editBlockCMSConfirmation')) {
            $this->_html .= $this->displayConfirmation($this->l('CMS block edited.'));
        } elseif (Tools::isSubmit('deleteBlockCMSConfirmation')) {
            $this->_html .= $this->displayConfirmation($this->l('Deletion successful.'));
        } elseif (Tools::isSubmit('updatePositions')) {
            $this->updatePositionsDnd();
        }
        if (count($this->_errors)) {
            foreach ($this->_errors as $err) {
                $this->_html .= '<div class="alert error">'.$err.'</div>';
            }
        }
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminLinkWidget')
        );
    }

    public function displayBlockCMS($column)
    {
        if (!$this->isCached('blockcms.tpl', $this->getCacheId($column))) {
            $cms_titles = BlockCMSModel::getCMSTitles($column);

            $this->smarty->assign(array(
                'block' => 1,
                'cms_titles' => $cms_titles,
                'contact_url' => (_PS_VERSION_ >= 1.5) ? 'contact' : 'contact-form'
            ));
        }
        return $this->display(__FILE__, 'blockcms.tpl', $this->getCacheId($column));
    }

    protected function getCacheId($name = null)
    {
        return parent::getCacheId('blockcms|'.$name);
    }

    public function hookActionAdminStoresControllerUpdate_optionsAfter()
    {
        if (Tools::getIsset('PS_STORES_DISPLAY_FOOTER')) {
            $this->_clearCache('blockcms.tpl');
        }
    }

    public function hookActionObjectCmsUpdateAfter()
    {
        $this->_clearCache('blockcms.tpl');
    }

    public function hookActionObjectCmsDeleteAfter()
    {
        $this->_clearCache('blockcms.tpl');
    }

    public function hookHeader($params)
    {
        $this->context->controller->addCSS(($this->_path).'blockcms.css', 'all');
    }

    public function hookLeftColumn()
    {
        return $this->displayBlockCMS(BlockCMSModel::LEFT_COLUMN);
    }

    public function hookRightColumn()
    {
        return $this->displayBlockCMS(BlockCMSModel::RIGHT_COLUMN);
    }

    public function hookFooter()
    {
        if (!($block_activation = Configuration::get('FOOTER_BLOCK_ACTIVATION'))) {
            return;
        }

        if (!$this->isCached('blockcms.tpl', $this->getCacheId(BlockCMSModel::FOOTER))) {
            $display_poweredby = Configuration::get('FOOTER_POWEREDBY');
            $this->smarty->assign(
                array(
                    'block' => 0,
                    'contact_url' => 'contact',
                    'cmslinks' => BlockCMSModel::getCMSTitlesFooter(),
                    'display_stores_footer' => Configuration::get('PS_STORES_DISPLAY_FOOTER'),
                    'display_poweredby' => ((int)$display_poweredby === 1 || $display_poweredby === false),
                    'footer_text' => Configuration::get('FOOTER_CMS_TEXT_'.(int)$this->context->language->id),
                    'show_price_drop' => Configuration::get('FOOTER_PRICE-DROP'),
                    'show_new_products' => Configuration::get('FOOTER_NEW-PRODUCTS'),
                    'show_best_sales' => Configuration::get('FOOTER_BEST-SALES'),
                    'show_contact' => Configuration::get('FOOTER_CONTACT'),
                    'show_sitemap' => Configuration::get('FOOTER_SITEMAP')
                )
            );
        }
        return $this->display(__FILE__, 'blockcms.tpl', $this->getCacheId(BlockCMSModel::FOOTER));
    }

    protected function updatePositionsDnd()
    {
        if (Tools::getValue('cms_block_0')) {
            $positions = Tools::getValue('cms_block_0');
        } elseif (Tools::getValue('cms_block_1')) {
            $positions = Tools::getValue('cms_block_1');
        } else {
            $positions = array();
        }

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);

            if (isset($pos[2])) {
                BlockCMSModel::updateCMSBlockPosition($pos[2], $position);
            }
        }
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
