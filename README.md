# LLMLens 大语言模型的 Typecho 站点透镜

## 插件简介

LLMLens 是一款 Typecho 插件，将站点的文章、页面与分类整理为符合 [llmstxt.org](https://llmstxt.org/) 规范的 `llms.txt` 文件，供大语言模型（LLM）抓取与理解站点内容。

本项目衍生自 Bingyin 的 [LLMsTXT](https://github.com/9bingyin/typecho-llmstxt) 插件，
由 [栀渊Yuenji](https://github.com/FurYuenji/) 修改维护。

## 适用版本

本插件在 Typecho 1.3.0 + PHP 7.4 环境下测试通过，其它版本请自行测试。

## 修改内容

本版本相较原始版本的改动：

- 将落盘文件改为动态路由
- 增删设置选项，调整默认值与提示信息
- 增加对隐藏页面的控制
- 优化查询性能，批量查询文章对应分类
- 添加自定义过滤规则功能
- 优化过滤规则，减少 Markdown 标签截断
- 优化截断规则，改为按标点语义终止
- 增强代码健壮性

## 安装使用

1. 通过 Code 按钮下的 Download ZIP 功能下载压缩包
2. 上传至 `/usr/plugins/` 目录，将解压出的文件夹重命名为 `LLMLens`
3. 登录后台，在「控制台 → 插件」中启用 LLMLens
4. 访问 `https://你的站点/llms.txt` 验证输出

## 配置选项

在插件设置页面可调整以下选项：

| 配置项 | 说明 | 默认值 |
| --- | --- | --- |
| 网站描述 | 留空则使用系统设置中的站点描述 | 空 |
| 文章数量 | 包含的文章条数，填 `0` 表示不限制 | `10` |
| 包含公开页面 | 是否包含状态为「公开」的独立页面 | 是 |
| 包含隐藏页面 | 隐藏页面可能含非公开内容，建议保持关闭 | 否 |
| 包含分类 | 是否包含分类链接 | 是 |
| 摘要字数 | 每条内容摘要的最大字符数 | `120` |
| 可选内容 | 追加的链接，每行一个，格式 `[标题](链接): 描述` | 空 |
| 自定义过滤规则 | 每行一个，格式 `正则\|\|\|替换内容` | 空 |

## 输出示例

```markdown
# 站点标题

> 站点描述

## 文章

- [文章标题](https://example.com/post/1): 文章摘要……
- [另一篇文章](https://example.com/post/2): 文章摘要……

## 页面

- [关于](https://example.com/about): 页面摘要……

## 分类

- [技术](https://example.com/category/tech): 分类描述
```

[示例链接 热衷于的博客](https://zooyoo.top/llms.txt)

## 开源许可

本项目采用 [MIT License](https://opensource.org/license/mit) 开源。

原始版本版权归 Bingyin 所有，当前修改版本版权归 栀渊Yuenji 所有。

## 特别鸣谢

- 原始作者：[Bingyin](https://github.com/9bingyin/)
