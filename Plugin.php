<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 为大语言模型配备 /llms.txt，详见 llmstxt.org
 * @package LLMLens
 * @author 热衷于
 * @version 1.0.0
 * @link https://zooyoo.top/
 * @license MIT
 * @copyright Copyright (c) 2025 Bingyin, 2026 栀渊Yuenji
 */
// 衍生自 Bingyin 的 LLMsTXT 插件 (https://github.com/9bingyin/typecho-llmstxt)
// 由 热衷于 修改维护 (https://github.com/FurYuenji/TypechoLLMLens)
class LLMLens_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('index.php')->begin = array('LLMLens_Plugin', 'route');
        return '插件已激活 ' . Typecho_Common::url('llms.txt', Helper::options()->index);
    }

    public static function deactivate()
    {
        return '插件已禁用';
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        echo '<p class="description">| 自由 | 开放 | 协作 | 共享 |</p>';
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('siteDescription', NULL, '', _t('网站描述'), _t('留空则使用系统设置中的描述')));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('postCount', NULL, '10', _t('文章数量'), _t('默认 10，填 0 代表不限制')));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('includePages', array('1' => _t('是'), '0' => _t('否')), '1', _t('包含公开页面'), _t('是否包含状态为“公开”的独立页面')));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('includeHiddenPages', array('1' => _t('是'), '0' => _t('否')), '0', _t('包含隐藏页面'), _t('隐藏页面可能包含非公开内容，建议保持关闭')));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('includeCategories', array('1' => _t('是'), '0' => _t('否')), '1', _t('包含分类'), _t('是否包含分类链接')));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('excerptLength', NULL, '120', _t('摘要字数'), _t('默认 120 字符')));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('optionalContent', NULL, '', _t('可选内容'), _t('每行一个链接：[标题](链接): 描述')));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('customFilters', NULL, '', _t('自定义过滤规则'), _t('每行一个规则：正则|||替换内容')));
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    public static function route()
    {
        $request = Typecho_Request::getInstance();
        $path = trim((string) $request->getPathInfo(), '/');
        if ($path === '') {
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            $path = ltrim((string) $uri, '/');
            $root = trim((string) parse_url(Helper::options()->index, PHP_URL_PATH), '/');
            if ($root !== '') {
                $rootPrefix = $root . '/';
                if (strpos($path, $rootPrefix) === 0) $path = substr($path, strlen($rootPrefix));
            }
        }
        if (strtolower($path) === 'llms.txt') {
            require_once __DIR__ . '/Widget.php';
            Typecho_Widget::widget('LLMLens_Widget@index')->render();
            exit;
        }
    }
}