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
