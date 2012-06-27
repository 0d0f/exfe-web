<?php

class OauthModels extends DataModel {

	public function resetSession() {
		// @todo 不能直接像老韩这样毁掉session，因为oauth失败可能用户还在登录其他账号。
		session_start();
        session_destroy();
	}

}
