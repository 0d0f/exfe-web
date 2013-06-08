<?php

class BazingaActions extends ActionController {

    public function doExfee() {
        apiResponse(["exfee" => [
            "id"          => 233,
            "name"        => "Star Trek Into Darkness",
            "invitations" => [
                [
                    "id"         => 100,
                    "identity"   => null,
                    "invited_by" => null,
                    "updated_by" => null,
                    "response"   => "ACCEPTED",
                    "via"        => "EXFE",
                    "created_at" => "2013-06-04 10:45:18 +0800",
                    "updated_at" => "2013-06-04 10:45:18 +0800",
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
                    "identity"   => null,
                    "invited_by" => null,
                    "updated_by" => null,
                    "response"   => "DECLINED",
                    "via"        => "EXFE",
                    "created_at" => "2013-06-04 10:45:18 +0800",
                    "updated_at" => "2013-06-04 10:45:18 +0800",
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
            "accepted"    => 1,
            "type"        => "exfee"
        ]]);
    }

}
