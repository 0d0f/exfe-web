* v0.0.7 2012-11-01T13:30:58 UTC+0800
  * fixed `Welcome` 欢迎窗口弹出 bug
  * [*] OAuth 重新设置密码
  * [x] 登出，清除所有 x 的邀请链接

* v0.0.6 2012-10-11T16:49:32 UTC+0800
  ✔ fixed `user.identities.length === 0` 时，登出

* v0.0.5 14:27:15 09/20/2012
  - `user.default_identity`
  ✔ fixed `verification_token` 时，同一 user，token 不一样的 bug
  ✔ Refactor `routes.resolveShow` and `routes.resolveRequest`

* v0.0.4 00:16:54 09/14/2012
  + user-token 过期时
    清除 session.authorization 和 session.user
    Store.remove('authorization') Store.remove('user')

* v0.0.3 19:37:27 09/11/2012
  + invitation token 32位 交换成 64位，存到本地
  + signout 时，清除 localStorage 中的 user-data

* v0.0.2 22:58:17 09/09/2012
  * 统一 `title`: 'EXFE - xxx'
