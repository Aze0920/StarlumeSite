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


## 宝塔部署重点

上传整个 `jmweb` 项目到宝塔网站目录后，必须把网站运行目录设置为：

```text
/public
```

首次访问网站会自动跳转到：

```text
/install.php
```

在安装界面填写数据库信息，系统会自动：

1. 创建数据库表。
2. 写入 `config/database.php`。
3. 创建 `data/install.lock` 安装锁。
4. 创建后台管理员账号。

## 后台登录

后台地址：

```text
/admin/
```

后台账号密码是在安装界面设置的，不再使用固定默认密码。

## 建议 GitHub 仓库名

```text
StarlumeSite
```

你可以去 GitHub 创建这个仓库。创建后，把 `C:\Users\Administrator\Desktop\github2\upload-starlume.ps1` 里的仓库地址确认一下，然后双击 `一键上传StarlumeSite.bat` 即可上传。

## 宝塔上传

把 `C:\Users\Administrator\Desktop\jmweb` 整个目录上传到宝塔网站目录，然后在宝塔站点设置里把运行目录设置为 `/public`。

如果宝塔网站根目录就是上传后的 `jmweb`，那么运行目录选：

```text
/public
```

## 后台一键更新

后台的一键更新默认从下面仓库拉取：

```text
https://github.com/Aze0920/StarlumeSite.git
```

后续如果仓库名或 GitHub 用户名变化，请同步修改：

```text
config/app.php
```
