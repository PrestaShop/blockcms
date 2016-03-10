<?php

class AdminLinkWidget extends ModuleAdminController
{
    public $identifier = 'LinkBlock';

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

        $this->repository = new LinkBlockRepository(
            Db::getInstance(),
            $this->context->shop
        );
    }

    public function init()
    {
        if (Tools::isSubmit('edit'.$this->identifier)) {
            $this->display = 'edit';
        } elseif (Tools::isSubmit('addLinkBlock')) {
            $this->display = 'add';
        }

        parent::init();
    }

    public function postProcess()
    {
        $this->addNameArrayToPost();
        if (!$this->validateForm($_POST)) {
            return false;
        }

        if (Tools::isSubmit('submit'.$this->identifier)) {
            $block = new LinkBlock(Tools::getValue('id_link_block'));
            $block->name = Tools::getValue('name');
            $block->id_hook = Tools::getValue('id_hook');
            $block->content['cms'] = (array)Tools::getValue('cms');
            $block->save();

            $hook_name = Hook::getNameById(Tools::getValue('id_hook'));
            if (!Hook::isModuleRegisteredOnHook($this->module, $hook_name, $this->context->shop->id)) {
                Hook::registerHook($this->module, $hook_name);
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('Admin'.$this->name));
        } elseif (Tools::isSubmit('delete'.$this->identifier)) {
            $block = new LinkBlock(Tools::getValue('id_link_block'));
            $block->delete();

            if (!$this->repository->getCountByIdHook($block->id_hook)) {
                Hook::unregisterHook($this->module, Hook::getNameById($block->id_hook));
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('Admin'.$this->name));
        }

        return parent::postProcess();
    }

    public function renderView()
    {
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
                    'href' => $this->context->link->getAdminLink('Admin'.$this->name).'&amp;addLinkBlock',
                    'class' => 'pull-right',
                    'icon' => 'process-icon-new'
                ),
            ),
        );

        $this->getLanguages();


        $helper = $this->buildHelper();
        $helper->submit_action = '';
        $helper->title = $this->module->l('CMS Block configuration');

        $helper->fields_value = $this->fields_value;

        return $helper->generateForm($this->fields_form);
    }

    public function renderForm()
    {
        $block = new LinkBlock((int)Tools::getValue('id_link_block'));

        $this->fields_form[0]['form'] = array(
            'tinymce' => true,
            'legend' => array(
                'title' => isset($block) ? $this->l('Edit the CMS block.') : $this->l('New CMS block'),
                'icon' => isset($block) ? 'icon-edit' : 'icon-plus-square'
            ),
            'input' => array(
                array(
                    'type' => 'hidden',
                    'name' => 'id_link_block',
                ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Name of the CMS block'),
                        'name' => 'name',
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
                    'name' => 'cms[]',
                    'values' => $this->repository->getCmsPages(),
                    'desc' => $this->l('Please mark every page that you want to display in this block.')
                ),
            ),
            'buttons' => array(
                'cancelBlock' => array(
                    'title' => $this->l('Cancel'),
                    'href' => (Tools::safeOutput(Tools::getValue('back', false)))
                                ?: $this->context->link->getAdminLink('Admin'.$this->name),
                    'icon' => 'process-icon-cancel'
                )
            ),
            'submit' => array(
                'name' => 'submit'.$this->identifier,
                'title' => $this->l('Save'),
            )
        );

        if ($id_hook = Tools::getValue('id_hook')) {
            $block->id_hook = $id_hook;
        }

        if (Tools::getValue('name')) {
            $block->name = Tools::getValue('name');
        }

        $helper = $this->buildHelper();
        if (isset($id_link_block)) {
            $helper->currentIndex = AdminController::$currentIndex.'&id_link_block='.$id_link_block;
            $helper->submit_action = 'edit'.$this->identifier;
        } else {
            $helper->submit_action = 'addLinkBlock';
        }

        $helper->fields_value = (array)$block;

        return $helper->generateForm($this->fields_form);
    }

    protected function buildHelper()
    {
        $helper = new HelperForm();

        $helper->module = $this->module;
        $helper->override_folder = 'linkwidget/';
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('Admin'.$this->name);
        $helper->languages = $this->_languages;
        $helper->currentIndex = $this->context->link->getAdminLink('Admin'.$this->name);
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = $this->initToolbar();

        return $helper;
    }

    public function validateForm($data)
    {
        return true;
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

    private function addNameArrayToPost()
    {
        $languages = Language::getLanguages();
        $names = [];
        foreach ($languages as $lang) {
            if ($name = Tools::getValue('name_'.$lang['id_lang'])) {
                $names[$lang['id_lang']] = $name;
            }
        }
        $_POST['name'] = $names;
    }
}
