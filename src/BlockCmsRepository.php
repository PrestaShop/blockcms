<?php

class BlockCmsRepository
{
    private $db;
    private $shop;
    private $db_prefix;

    public function __construct(Db $db, Shop $shop)
    {
        $this->db = $db;
        $this->shop = $shop;
        $this->db_prefix = $db->getPrefix();
    }

    public function createTables()
    {
        $engine = _MYSQL_ENGINE_;
        $success = true;
        $this->dropTables();

        $queries = [
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}cms_block`(
    			`id_cms_block` int(10) unsigned NOT NULL auto_increment,
    			`id_hook` int(1) unsigned DEFAULT NULL,
    			`position` int(10) unsigned NOT NULL default '0',
    			`content` text default NULL,
    			PRIMARY KEY (`id_cms_block`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}cms_block_lang`(
    			`id_cms_block` int(10) unsigned NOT NULL,
    			`id_lang` int(10) unsigned NOT NULL,
    			`name` varchar(40) NOT NULL default '',
    			PRIMARY KEY (`id_cms_block`, `id_lang`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}cms_block_shop` (
    			`id_cms_block` int(10) unsigned NOT NULL auto_increment,
    			`id_shop` int(10) unsigned NOT NULL,
    			PRIMARY KEY (`id_cms_block`, `id_shop`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8"
        ];

        foreach ($queries as $query) {
            $success &= $this->db->execute($query);
        }

        return $success;
    }

    public function dropTables()
    {
        $sql = "DROP TABLE IF EXISTS
			`{$this->db_prefix}cms_block`,
			`{$this->db_prefix}cms_block_lang`,
			`{$this->db_prefix}cms_block_shop`";

        return Db::getInstance()->execute($sql);
    }

    public function getCMSBlocksSortedByHook($id_shop = null, $id_lang = null)
    {
        $id_lang = ($id_lang) ?: (int)Context::getContext()->language->id;
        $id_shop = ($id_shop) ?: (int)Context::getContext()->shop->id;

        $sql = 'SELECT
                bc.`id_cms_block`,
                bcl.`name` as block_name,
                bc.`id_hook`,
                h.`name` as hook_name,
                h.`title` as hook_title,
                h.`description` as hook_description,
                bc.`position`
            FROM `'._DB_PREFIX_.'cms_block` bc
                INNER JOIN `'._DB_PREFIX_.'cms_block_lang` bcl
                    ON (bc.`id_cms_block` = bcl.`id_cms_block`)
                LEFT JOIN `'._DB_PREFIX_.'hook` h
                    ON (bc.`id_hook` = h.`id_hook`)
            WHERE bcl.`id_lang` = '.$id_lang.'
            ORDER BY bc.`position`';

        $blocks = Db::getInstance()->executeS($sql);

        $orderedBlocks = [];
        foreach ($blocks as $block) {
            if (!isset($orderedBlocks[$block['id_hook']])) {
                $id_hook = ($block['id_hook']) ?: 'not_hooked';
                $orderedBlocks[$id_hook] = [
                    'id_hook' => $block['id_hook'],
                    'hook_name' => $block['hook_name'],
                    'hook_title' => $block['hook_title'],
                    'hook_description' => $block['hook_description'],
                    'blocks' => [],
                ];
            }
        }

        foreach ($blocks as $block) {
            $id_hook = ($block['id_hook']) ?: 'not_hooked';
            unset($block['id_hook']);
            unset($block['hook_name']);
            unset($block['hook_title']);
            unset($block['hook_description']);
            $orderedBlocks[$id_hook]['blocks'][] = $block;
        }

        return $orderedBlocks;
    }

    public function getDisplayHooksForHelper()
    {
        $sql = "SELECT h.id_hook as id, h.name as name
                FROM {$this->db_prefix}hook h
                ORDER BY h.name ASC
            ";
        return $this->db->executeS($sql);
    }
}
