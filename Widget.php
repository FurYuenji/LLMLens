<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class LLMLens_Widget extends Typecho_Widget
{
    public function execute()
    {
        $this->response->setStatus(200);
        $this->response->setContentType('text/plain');
        $this->response->setCharset('UTF-8');
        $this->response->setHeader('X-Content-Type-Options', 'nosniff');
        echo $this->buildContent();
    }

    private function buildContent()
    {
        $db = Typecho_Db::get();
        $options = Typecho_Widget::widget('Widget_Options');
        $p = $options->plugin('LLMLens');
        $siteTitle = $this->plainText($options->title);
        $siteDesc = !empty($p->siteDescription) ? $this->plainText($p->siteDescription) : $this->plainText($options->description);
        $content = "# {$siteTitle}\n\n";
        if ($siteDesc !== '') $content .= "> {$siteDesc}\n\n";
        $postCount = isset($p->postCount) ? (int) $p->postCount : 10;
        $excerptLength = !empty($p->excerptLength) ? (int) $p->excerptLength : 120;
        $customFilters = $this->parseCustomFilters(!empty($p->customFilters) ? $p->customFilters : '');
        $content .= "## 文章\n\n";
        $select = $db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.created <= ?', time())
            ->where('(table.contents.password IS NULL OR table.contents.password = ?)', '')
            ->order('table.contents.created', Typecho_Db::SORT_DESC);
        if ($postCount > 0) $select->limit($postCount);
        $posts = $db->fetchAll($select);
        if (!empty($posts)) {
            $catMap = $this->getCategoryMap($db, array_column($posts, 'cid'));
            foreach ($posts as $post) {
                $url = $this->getPostUrl($post, $catMap, $options);
                $title = $this->escapeMdLink($this->plainText($post['title']));
                $content .= "- [{$title}]({$url}): " . $this->getExcerpt($post['text'], $excerptLength, $customFilters) . "\n";
            }
        } else {
            $content .= "- 暂无文章\n";
        }
        $includePages = !empty($p->includePages) && $p->includePages == '1';
        $includeHidden = !empty($p->includeHiddenPages) && $p->includeHiddenPages == '1';
        if ($includePages || $includeHidden) {
            $content .= "\n## 页面\n\n";
            $select = $db->select()->from('table.contents')
                ->where('table.contents.type = ?', 'page')
                ->where('table.contents.created <= ?', time())
                ->where('(table.contents.password IS NULL OR table.contents.password = ?)', '')
                ->order('table.contents.order', Typecho_Db::SORT_ASC);
            if ($includePages && $includeHidden) {
                $select->where('table.contents.status = ? OR table.contents.status = ?', 'publish', 'hidden');
            } elseif ($includePages) {
                $select->where('table.contents.status = ?', 'publish');
            } else {
                $select->where('table.contents.status = ?', 'hidden');
            }
            $pages = $db->fetchAll($select);
            if (!empty($pages)) {
                foreach ($pages as $page) {
                    $url = Typecho_Router::url('page', array('cid' => $page['cid'], 'slug' => $page['slug']), $options->index);
                    $title = $this->escapeMdLink($this->plainText($page['title']));
                    $content .= "- [{$title}]({$url}): " . $this->getExcerpt($page['text'], $excerptLength, $customFilters) . "\n";
                }
            } else {
                $content .= "- 暂无页面\n";
            }
        }
        if (!empty($p->includeCategories) && $p->includeCategories == '1') {
            $content .= "\n## 分类\n\n";
            $cats = $db->fetchAll($db->select()->from('table.metas')->where('table.metas.type = ?', 'category')->order('table.metas.order', Typecho_Db::SORT_ASC));
            foreach ($cats as $cat) {
                $url = Typecho_Router::url('category', array('slug' => $cat['slug']), $options->index);
                $name = $this->escapeMdLink($this->plainText($cat['name']));
                $desc = !empty($cat['description']) ? $this->plainText($cat['description']) : '分类页面';
                $content .= "- [{$name}]({$url}): {$desc}\n";
            }
        }
        if (!empty($p->optionalContent)) {
            $content .= "\n## 其它页面或链接\n\n";
            foreach (explode("\n", trim($p->optionalContent)) as $line) {
                $line = trim($line);
                if ($line !== '') $content .= "- {$line}\n";
            }
        }
        return $content;
    }

    private function parseCustomFilters($raw)
    {
        $filters = array();
        if (empty($raw)) return $filters;
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $parts = explode('|||', $line, 2);
            if (count($parts) !== 2) continue;
            $pattern = trim($parts[0]);
            if ($pattern === '') continue;
            if (@preg_match($pattern, '') === false) continue;
            $filters[] = array($pattern, $parts[1]);
        }
        return $filters;
    }

    private function applyCustomFilters($text, array $filters)
    {
        foreach ($filters as $filter) {
            $result = @preg_replace($filter[0], $filter[1], $text);
            if ($result !== null) $text = $result;
        }
        return $text;
    }

    private function getCategoryMap($db, array $cids)
    {
        if (empty($cids)) return array();
        $map = array();
        try {
            $rows = $db->fetchAll($db->select('table.relationships.cid', 'table.metas.slug')
                ->from('table.relationships')
                ->join('table.metas', 'table.metas.mid = table.relationships.mid')
                ->where('table.relationships.cid IN ?', $cids)
                ->where('table.metas.type = ?', 'category'));
            foreach ($rows as $row) {
                if (!isset($map[$row['cid']])) $map[$row['cid']] = $row['slug'];
            }
        } catch (Exception $e) {}
        return $map;
    }

    private function getPostUrl(array $post, array $catMap, $options)
    {
        $params = array('cid' => $post['cid'], 'slug' => $post['slug']);
        if (!empty($catMap[$post['cid']])) $params['category'] = $catMap[$post['cid']];
        return Typecho_Router::url('post', $params, $options->index);
    }

    private function plainText($text)
    {
        $text = preg_replace('/[\r\n]+/u', ' ', (string) $text);
        return trim($text);
    }

    private function escapeMdLink($text)
    {
        return str_replace(array('[', ']'), array('\\[', '\\]'), $text);
    }

    private function getExcerpt($text, $max = 120, array $customFilters = array())
    {
        if ($max <= 0) $max = 120;
        $text = $this->applyCustomFilters($text, $customFilters);
        $text = preg_replace('/<!--more-->.*$/s', '', $text);
        $text = preg_replace('/```[\s\S]*?(?:```|$)/u', '', $text);
        $text = preg_replace('/~~~[\s\S]*?(?:~~~|$)/u', '', $text);
        $text = preg_replace('/!!!.*?(?:!!!|$)/su', '', $text);
        $text = preg_replace('/card\{[^}]*\}/su', '', $text);
        $text = preg_replace('/`[^`\n]*`/u', '', $text);
        $text = preg_replace('/<[^>]+>/u', '', $text);
        $text = strip_tags($text);
        $text = preg_replace('/!\[.*?\]\(.*?\)/u', '', $text);
        $text = preg_replace('/^\s*\[\d+\]:\s*\S+.*$/mu', '', $text);
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/u', '$1', $text);
        $text = preg_replace('/\[([^\]]+)\]\[\d+\]/u', '$1', $text);
        $text = preg_replace('/\[\d+\]/u', '', $text);
        $text = preg_replace('/^\s*#{1,6}\s+/mu', '', $text);
        $text = preg_replace('/^\s*[-=]{3,}\s*$/mu', '', $text);
        $text = preg_replace('/[*_]{2,}([^*_]+)[*_]{2,}/u', '$1', $text);
        $text = preg_replace('/[*_]([^*_]+)[*_]/u', '$1', $text);
        $text = preg_replace('/~~([^~]+)~~/u', '$1', $text);
        $text = preg_replace('/^[\s]*[-*+]\s+/mu', '', $text);
        $text = preg_replace('/^\s*\d+\.\s+/mu', '', $text);
        $text = preg_replace('/^\s{0,3}([-*_]\s*){3,}$/mu', '', $text);
        $text = preg_replace('/^\s*>\s*/mu', '', $text);
        $text = preg_replace('/^\s*\|.*\|\s*$/mu', '', $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        $text = preg_replace('/"([^"]*)"/u', '“$1”', $text);
        if ($text === '') return '暂无摘要';
        if (mb_strlen($text, 'UTF-8') <= $max) return $text;
        $cut = mb_substr($text, 0, $max, 'UTF-8');
        if (preg_match('/^(.*[。！？；.!?;])/us', $cut, $m)) $cut = $m[1];
        return rtrim($cut, '，,、') . '...';
    }
}