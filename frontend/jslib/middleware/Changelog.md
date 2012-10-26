* v0.0.6 2012-10-25T16:02:01 UTC+0800
  * 调整 OAuth 返回参数

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
