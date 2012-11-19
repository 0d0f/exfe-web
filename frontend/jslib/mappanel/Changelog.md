* v0.0.1 2012-11-19T14:11:13 UTC+0800
  * 由于 Google Places `textSearch` API 是以 Callback
    方式调用，为防止多异步加载的问题，添加了 `cb.id` 记录 callback id
