<?php

class AdminLinkWidgetController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();
        $this->meta_title = $this->module->l('Link Widget');

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }

        $this->name = 'LinkWidget';

        $this->repository = new BlockCmsRepository(
            Db::getInstance(),
            $this->context->shop
        );
    }

    public function init()
    {
        if (Tools::isSubmit('editBlockCMS')) {
            $this->display = 'edit';
        } elseif (Tools::isSubmit('addBlockCMS')) {
            $this->display = 'add';
        }

        parent::init();
    }

    public function renderView()
    {
        $current_index = AdminController::$currentIndex;
        $token = Tools::getAdminTokenLite('AdminModules');

        $this->_display = 'index';
        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $this->module->l('CMS block configuration'),
                'icon' => 'icon-list-alt'
            ),
            'input' => array(
                array(
                    'type' => 'cms_blocks',
                    'label' => $this->module->l('CMS Blocks'),
                    'name' => 'cms_blocks',
                    'values' => $this->repository->getCMSBlocksSortedByHook(),
                ),
            ),
            'buttons' => array(
                'newBlock' => array(
                    'title' => $this->module->l('New block'),
                    'href' => $this->context->link->getAdminLink('AdminLinkWidget').'&amp;addBlockCMS',
                    'class' => 'pull-right',
                    'icon' => 'process-icon-new'
                ),
            ),
        );

        $this->getLanguages();


        $helper = $this->initForm();
        $helper->submit_action = '';
        $helper->title = $this->module->l('CMS Block configuration');

        $helper->fields_value = $this->fields_value;

        return $helper->generateForm($this->fields_form);
    }

    public function renderForm()
    {
        $token = Tools::getAdminTokenLite('AdminModules');
        $back = Tools::safeOutput(Tools::getValue('back', ''));
        $current_index = AdminController::$currentIndex;
        if (!isset($back) || empty($back)) {
            $back = $current_index.'&amp;configure='.$this->name.'&token='.$token;
        }

        if (Tools::isSubmit('editBlockCMS') && Tools::getValue('id_cms_block')) {
            $this->_display = 'edit';
            $id_cms_block = (int)Tools::getValue('id_cms_block');
            $cmsBlock = BlockCMSModel::getBlockCMS($id_cms_block);
            $cmsBlockCategories = BlockCMSModel::getCMSBlockPagesCategories($id_cms_block);
            $cmsBlockPages = BlockCMSModel::getCMSBlockPages(Tools::getValue('id_cms_block'));
        } else {
            $this->_display = 'add';
        }

        $this->fields_form[0]['form'] = array(
            'tinymce' => true,
            'legend' => array(
                'title' => isset($cmsBlock) ? $this->l('Edit the CMS block.') : $this->l('New CMS block'),
                'icon' => isset($cmsBlock) ? 'icon-edit' : 'icon-plus-square'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Name of the CMS block'),
                    'name' => 'block_name',
                    'lang' => true,
                    'desc' => $this->l('If you leave this field empty, the block name will use the category name by default.')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Hook'),
                    'name' => 'id_hook',
                    'class' => 'input-lg',
                    'options' => array(
                        'query' => $this->repository->getDisplayHooksForHelper(),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'cms_pages',
                    'label' => $this->l('CMS content'),
                    'name' => 'cmsBox[]',
                    'values' => BlockCMSModel::getAllCMSStructure(),
                    'desc' => $this->l('Please mark every page that you want to display in this block.')
                ),
            ),
            'buttons' => array(
                'cancelBlock' => array(
                    'title' => $this->l('Cancel'),
                    'href' => $back,
                    'icon' => 'process-icon-cancel'
                )
            ),
            'submit' => array(
                'name' => 'submitBlockCMS',
                'title' => $this->l('Save'),
            )
        );

        $this->context->controller->getLanguages();
        foreach ($this->context->controller->_languages as $language) {
            if (Tools::getValue('block_name_'.$language['id_lang'])) {
                $this->fields_value['block_name'][$language['id_lang']] = Tools::getValue('block_name_'.$language['id_lang']);
            } elseif (isset($cmsBlock) && isset($cmsBlock[$language['id_lang']]['name'])) {
                $this->fields_value['block_name'][$language['id_lang']] = $cmsBlock[$language['id_lang']]['name'];
            } else {
                $this->fields_value['block_name'][$language['id_lang']] = '';
            }
        }

        if (Tools::getValue('display_stores')) {
            $this->fields_value['display_stores'] = Tools::getValue('display_stores');
        } elseif (isset($cmsBlock) && isset($cmsBlock[1]['display_store'])) {
            $this->fields_value['display_stores'] = $cmsBlock[1]['display_store'];
        } else {
            $this->fields_value['display_stores'] = '';
        }

        if (Tools::getValue('id_category')) {
            $this->fields_value['id_category'] = (int)Tools::getValue('id_category');
        } elseif (isset($cmsBlock) && isset($cmsBlock[1]['id_cms_category'])) {
            $this->fields_value['id_category'] = $cmsBlock[1]['id_cms_category'];
        }

        if (Tools::getValue('id_hook')) {
            $this->fields_value['id_hook'] = Tools::getValue('id_hook');
        } elseif (isset($cmsBlock) && isset($cmsBlock[1]['id_hook'])) {
            $this->fields_value['id_hook'] = $cmsBlock[1]['id_hook'];
        } else {
            $this->fields_value['id_hook'] = 0;
        }

        if ($cmsBoxes = Tools::getValue('cmsBox')) {
            foreach ($cmsBoxes as $key => $value) {
                $this->fields_value[$value] = true;
            }
        } else {
            if (isset($cmsBlockPages) && is_array($cmsBlockPages) && count($cmsBlockPages) > 0) {
                foreach ($cmsBlockPages as $item) {
                    $this->fields_value['0_'.$item['id_cms']] = true;
                }
            }
            if (isset($cmsBlockCategories) && is_array($cmsBlockCategories) && count($cmsBlockCategories) > 0) {
                foreach ($cmsBlockCategories as $item) {
                    $this->fields_value['1_'.$item['id_cms']] = true;
                }
            }
        }

        $helper = $this->initForm();

        if (isset($id_cms_block)) {
            $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name.'&id_cms_block='.$id_cms_block;
            $helper->submit_action = 'editBlockCMS';
        } else {
            $helper->submit_action = 'addBlockCMS';
        }

        $helper->fields_value = isset($this->fields_value) ? $this->fields_value : array();


        return $helper->generateForm($this->fields_form);
    }

    protected function initForm()
    {
        $helper = new HelperForm();

        $helper->module = $this->module;
        $helper->override_folder = 'linkwidget/';
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminLinkWidget');
        $helper->languages = $this->_languages;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminLinkWidget');
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = $this->initToolbar();

        return $helper;
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->module->l('Themes');
        $this->toolbar_title[] = $this->module->l('Link Widget');
    }

    public function setMedia()
    {
        $this->addJqueryPlugin('tablednd');
        $this->addJS(_PS_JS_DIR_.'admin/dnd.js');

        return parent::setMedia();
    }
}
