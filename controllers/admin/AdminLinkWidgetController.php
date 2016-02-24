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
                    'values' => array(
                        0 => BlockCMSModel::getCMSBlocksByLocation(BlockCMSModel::LEFT_COLUMN, Shop::getContextShopID()),
                        1 => BlockCMSModel::getCMSBlocksByLocation(BlockCMSModel::RIGHT_COLUMN, Shop::getContextShopID()))
                )
            ),
            'buttons' => array(
                'newBlock' => array(
                    'title' => $this->module->l('New block'),
                    'href' => 'TODO',//$current_index.'&amp;configure='.$this->name.'&amp;token='.$token.'&amp;addBlockCMS',
                    'class' => 'pull-right',
                    'icon' => 'process-icon-new'
                )
            )
        );

        $this->getLanguages();


        $helper = $this->initForm();
        $helper->submit_action = '';
        $helper->title = $this->module->l('CMS Block configuration');

        $helper->fields_value = $this->fields_value;

        return $helper->generateForm($this->fields_form);

/*
        return $this->context->smarty->fetch(
            'module:blockcms/views/templates/admin/blocklist.tpl'
        );*/
    }

    protected function initForm()
    {
        $helper = new HelperForm();

        $helper->module = $this;
        $helper->override_folder = 'linkwidget/';
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $this->context->controller->_languages;
        $helper->currentIndex = 'TODO';
        $helper->default_form_language = $this->context->controller->default_form_language;
        $helper->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = $this->initToolbar();

        return $helper;
    }
}
