* v0.0.8 2012-11-08T14:49:09 UTC+0800
  * 修复 添加 Twitter Oauth 时，取消无法跳回到 Profile 时的bug

* v0.0.7 2012-11-06T14:29:53 UTC+0800
  * [+] 修复 `Facebook` callback url

* v0.0.6 2012-10-25T16:02:01 UTC+0800
  * 调整 OAuth 返回参数
  * [*] OAuth 重新设置密码

* v0.0.5 03:06:53 09/25/2012
  * fixed: 刷新 `Authenticate` 回来的本地 token
  * fixed: authMeta.callback 为 undefined 时，默认调到 '/'
  * fixed: authMeta.authorization = null

* v0.0.4 18:22:16 09/18/2012
  + `cleanupAppTmp` middleware, clean up widgets in #app-tmp

* v0.0.3 22:29:25 09/12/2012
  + 如果有 OAuth-callback, 则 window.location.href = oauth.callback

* v0.0.2 16:38:21 09/07/2012
  小调整
