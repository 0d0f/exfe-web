* v0.0.9 2012-11-08T22:44:11 UTC+0800
  * 修复：如果连续删除多个身份，会全部删除的 bug。

* v0.0.8 2012-11-08T00:51:27 UTC+0800
  * fixed bug: 由于 `time.begin_at` 中的 `date` 和 `time` 是服务端 UTC 时间，
    计算时，加 +0000 进行计算

* v0.0.7 2012-11-06T12:22:42 UTC+0800
  * [+] `Facebook` 身份支持

* v0.0.6 2012-10-26T12:52:39 UTC+0800
  * fixed: Accepted 计算： 不管 accepted 和 总人数，都要加上 已经 accepted 的 mates

* v0.0.5 2012-10-23T22:36:59 UTC+0800
  * 如果 cross.time.outputformat == 0 且 time.begin_at 都位空时,输出 `Sometime`
  * add Twitter OAuth identity
  * fixed: 如果 `time.outputformat` = 1 & `time.origin` = '' 时，应该显示 `Sometime`

* v0.0.4 12:31:54 08/31/2012   
  \+ 如果是 OAuth identity, 双击修改 identity name 时，提示红色文字   
  \* 修复 newbieguide.js 重复加载问题, newbieguide 升级到 v0.0.2   
  \* Fix change identity name bug.

* v0.0.3 16:27:04 08/30/2012   
  Updated exfee 输出，条件（当前 user 下身份/操作的 出外).
