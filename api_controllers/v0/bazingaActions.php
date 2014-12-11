<?php

class BazingaActions extends ActionController {

    public function doExfee() {
        apiResponse(["exfee" => [
            "id"          => 233,
            "name"        => "·X· Stony Wang, Googol Lee with a loooonnnng s, joy",
            "invitations" => [
                [
                    "id"         => 100,
                    "identity"   => [
                        "avatar_filename" => "http://www.gravatar.com/avatar/5dd675e149a1210d0097e646cd30c995",
                        "bio" => "",
                        "connected_user_id" => 396,
                        "created_at" => "2013-02-25 11:49:53 +0000",
                        "external_id" => "stonyw@gmail.com",
                        "external_username" => "stonyw@gmail.com",
                        "id" => 598,
                        "name" => "Stony Wang",
                        "nickname" => "",
                        "order" => 0,
                        "provider" => "email",
                        "type" => "identity",
                        "unreachable" => false,
                        "updated_at" => "2013-02-25 12:02:53 +0000"
                    ],
                    "invited_by" => [
                        "avatar_filename" => "http://www.gravatar.com/avatar/5dd675e149a1210d0097e646cd30c995",
                        "bio" => "",
                        "connected_user_id" => 396,
                        "created_at" => "2013-02-25 11:49:53 +0000",
                        "external_id" => "stonyw@gmail.com",
                        "external_username" => "stonyw@gmail.com",
                        "id" => 598,
                        "name" => "Stony Wang",
                        "nickname" => "",
                        "order" => 0,
                        "provider" => "email",
                        "type" => "identity",
                        "unreachable" => false,
                        "updated_at" => "2013-02-25 12:02:53 +0000"
                    ],
                    "updated_by" => [
                        "avatar_filename" => "http://www.gravatar.com/avatar/5dd675e149a1210d0097e646cd30c995",
                        "bio" => "",
                        "connected_user_id" => 396,
                        "created_at" => "2013-02-25 11:49:53 +0000",
                        "external_id" => "stonyw@gmail.com",
                        "external_username" => "stonyw@gmail.com",
                        "id" => 598,
                        "name" => "Stony Wang",
                        "nickname" => "",
                        "order" => 0,
                        "provider" => "email",
                        "type" => "identity",
                        "unreachable" => false,
                        "updated_at" => "2013-02-25 12:02:53 +0000"
                    ],
                    "response"   => "ACCEPTED",
                    "via"        => "EXFE",
                    "created_at" => "2013-06-04 10:45:18 +0000",
                    "updated_at" => "2013-06-04 10:45:18 +0000",
                    "token"      => "13f8b04f92590f29cd715ce9a78e40bf",
                    "host"       => true,
                    "mates"      => 0,
                    "type"       => "invitation",
                    "notification_identities" => [
                        "233@leaskh.com@email",
                        "233meow@twitter"
                    ]
                ],
                [
                    "id"         => 101,
                    "identity"   => [
                        "avatar_filename" => "http://www.gravatar.com/avatar/f39341ae83525f4cdf2a412ae7a2db79",
                        "bio" => "",
                        "connected_user_id" => 479,
                        "created_at" => "2013-03-28 13:08:38 +0000",
                        "external_id" => "joy@hengdm.com",
                        "external_username" => "joy@hengdm.com",
                        "id" => 617,
                        "name" => "joy",
                        "nickname" => "",
                        "order" => 0,
                        "provider" => "email",
                        "type" => "identity",
                        "unreachable" => false,
                        "updated_at" => "2013-03-28 13:08:38 +0000"
                    ],
                    "invited_by" => [
                        "avatar_filename" => "http://www.gravatar.com/avatar/5dd675e149a1210d0097e646cd30c995",
                        "bio" => "",
                        "connected_user_id" => 396,
                        "created_at" => "2013-02-25 11:49:53 +0000",
                        "external_id" => "stonyw@gmail.com",
                        "external_username" => "stonyw@gmail.com",
                        "id" => 598,
                        "name" => "Stony Wang",
                        "nickname" => "",
                        "order" => 0,
                        "provider" => "email",
                        "type" => "identity",
                        "unreachable" => false,
                        "updated_at" => "2013-02-25 12:02:53 +0000"
                    ],
                    "updated_by" => [
                        "avatar_filename" => "http://www.gravatar.com/avatar/f39341ae83525f4cdf2a412ae7a2db79",
                        "bio" => "",
                        "connected_user_id" => 479,
                        "created_at" => "2013-03-28 13:08:38 +0000",
                        "external_id" => "joy@hengdm.com",
                        "external_username" => "joy@hengdm.com",
                        "id" => 617,
                        "name" => "joy",
                        "nickname" => "",
                        "order" => 0,
                        "provider" => "email",
                        "type" => "identity",
                        "unreachable" => false,
                        "updated_at" => "2013-03-28 13:08:38 +0000"
                    ],
                    "response"   => "DECLINED",
                    "via"        => "EXFE",
                    "created_at" => "2013-06-04 10:45:18 +0000",
                    "updated_at" => "2013-06-04 10:45:18 +0000",
                    "token"      => "13f8b04f92590f29cd715ce9a78e40bf",
                    "host"       => false,
                    "mates"      => 1,
                    "type"       => "invitation",
                    "notification_identities" => [
                        "googollee@twitter"
                    ]
                ]
            ],
            "total"       => 3,
            "accepted"    => 3,
            "type"        => "exfee",
            "created_at"  => "2013-06-04 09:27:55 +0000",
            "updated_at"  => "2013-06-04 09:27:55 +0000"
        ]]);
    }

}
