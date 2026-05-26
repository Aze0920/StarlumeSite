# 星澜云站 / StarlumeSite

这是一个最简版 PHP 原生网站系统，可直接上传宝塔运行。

## 目录

```text
admin/       后台页面
api/         后台接口
assets/      CSS 和 JS
config/      站点配置
data/        运行数据
logs/        日志
index.php    前台首页
update.php   后台一键更新脚本
```

## 默认后台

```text
后台地址：/admin/
账号：admin
密码：123456
```

## 建议 GitHub 仓库名

```text
StarlumeSite
```

你可以去 GitHub 创建这个仓库。创建后，把 `C:\Users\Administrator\Desktop\github2\upload-starlume.ps1` 里的仓库地址确认一下，然后双击 `一键上传StarlumeSite.bat` 即可上传。

## 上传宝塔

把 `C:\Users\Administrator\Desktop\jmweb` 目录内所有文件上传到宝塔网站根目录。

## 后台一键更新

后台的一键更新默认从下面仓库拉取：

```text
https://github.com/Aze0920/StarlumeSite.git
```

后续如果仓库名或 GitHub 用户名变化，请同步修改：

```text
config/app.php
```
