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
                    'values' => BlockCMSModel::getCMSBlocksSortedByHook(),
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
