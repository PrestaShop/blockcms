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

        $this->repository = new CmsBlockRepository(
            Db::getInstance(),
            $this->context->shop
        );
    }

    public function init()
    {
        if (Tools::isSubmit('editCmsBlock')) {
            $this->display = 'edit';
        } elseif (Tools::isSubmit('addCmsBlock')) {
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

        if (Tools::isSubmit('submitCmsBlock')) {
            $cmsBlock = new CmsBlock(Tools::getValue('id_cms_block'));
            $cmsBlock->name = Tools::getValue('name');
            $cmsBlock->id_hook = Tools::getValue('id_hook');
            $cmsBlock->content['cms'] = (array)Tools::getValue('cms');
            $cmsBlock->save();
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminLinkWidget'));
        } elseif (Tools::isSubmit('deleteCmsBlock')) {
            $cmsBlock = new CmsBlock(Tools::getValue('id_cms_block'));
            $cmsBlock->delete();
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminLinkWidget'));
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
                    'href' => $this->context->link->getAdminLink('AdminLinkWidget').'&amp;addCmsBlock',
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
        $this->fields_form[0]['form'] = array(
            'tinymce' => true,
            'legend' => array(
                'title' => isset($cmsBlock) ? $this->l('Edit the CMS block.') : $this->l('New CMS block'),
                'icon' => isset($cmsBlock) ? 'icon-edit' : 'icon-plus-square'
            ),
            'input' => array(
                array(
                    'type' => 'hidden',
                    'name' => 'id_cms_block',
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
                                ?: $this->context->link->getAdminLink('AdminLinkWidget'),
                    'icon' => 'process-icon-cancel'
                )
            ),
            'submit' => array(
                'name' => 'submitCmsBlock',
                'title' => $this->l('Save'),
            )
        );

        $cmsBlock = new CmsBlock((int)Tools::getValue('id_cms_block'));

        if ($id_hook = Tools::getValue('id_hook')) {
            $cmsBlock->id_hook = $id_hook;
        }

        if (Tools::getValue('name')) {
            $cmsBlock->name = Tools::getValue('name');
        }

        $helper = $this->buildHelper();
        if (isset($id_cms_block)) {
            $helper->currentIndex = AdminController::$currentIndex.'&id_cms_block='.$id_cms_block;
            $helper->submit_action = 'editCmsBlock';
        } else {
            $helper->submit_action = 'addCmsBlock';
        }

        $helper->fields_value = (array)$cmsBlock;

        return $helper->generateForm($this->fields_form);
    }

    protected function buildHelper()
    {
        $helper = new HelperForm();

        $helper->module = $this->module;
        $helper->override_folder = 'linkwidget/';
        $helper->identifier = 'CmsBlock';
        $helper->token = Tools::getAdminTokenLite('AdminLinkWidget');
        $helper->languages = $this->_languages;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminLinkWidget');
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
