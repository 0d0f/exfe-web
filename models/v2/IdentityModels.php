<?php

class IdentityModels extends DataModel {
    
    private $salt = '_4f9g18t9VEdi2if';

    public function getIdentityById($id)
    {
        $rawIdentity = $this->getRow("SELECT * FROM `identities` WHERE `id` = {$id}");
        if ($rawIdentity) {
            $rawUserIdentity = $this->getRow("SELECT * FROM `user_identity` WHERE `identityid` = {$id} AND `status` = 3");
            $objIdentity = new Identity($rawIdentity['id'],
                                        $rawIdentity['name'],
                                        '', // $rawIdentity['nickname'], // @todo;
                                        $rawIdentity['bio'],
                                        $rawIdentity['provider'],
                                        $rawUserIdentity ? $rawUserIdentity['userid'] : 0,
                                        $rawIdentity['external_identity'],
                                        $rawIdentity['external_username'],
                                        $rawIdentity['avatar_file_name'],
                                        $rawIdentity['avatar_updated_at'],
                                        $rawIdentity['created_at'],
                                        $rawIdentity['updated_at']);
            return $objIdentity;
        } else {
            return null;
        }
    }


    /**
     * add a new identity into database
     * all parameters allow in $identityDetail:
     * {
     *     $name,
     *     $nickname,
     *     $bio,
     *     $provider,
     *     $connected_user_id,
     *     $external_id,
     *     $external_username,
     *     $avatar_filename,
     * } 
     */
    public function addIdentity($user_id, $provider, $external_identity, $identityDetail = array()) {
        $activecode = createToken();
        
        $name = mysql_real_escape_string($identityDetail['name']);
        $nickname = 
        $bio = mysql_real_escape_string($identityDetail['bio']);
        $provider,
        $connected_user_id,
        $external_id = mysql_real_escape_string($external_identity);
        $external_username = trim(mysql_real_escape_string($identityDetail["external_username"]));
        $avatar_filename =mysql_real_escape_string($identityDetail["avatar_file_name"]);

        
        
        

        $sql="select id from identities where external_identity='$external_identity' limit 1";
        $row=$this->getRow($sql);
        if(intval($row["id"])>0){
            return  intval($row["id"]);
        }


        //set identity avatar as Gravatar img
        if($provider == "email" && $avatar_file_name == ""){
            $avatar_file_name = "http://www.gravatar.com/avatar/";
            $avatar_file_name .= md5(strtolower(trim($external_identity)));
            $avatar_file_name .= "?d=".urlencode(DEFAULT_AVATAR_URL);
        }

        if($external_username == ""){
            $external_username = $external_identity;
        }

        $sql="insert into identities (provider,external_identity,created_at,name,bio,avatar_file_name,avatar_content_type,avatar_file_size,avatar_updated_at,external_username) values ('$provider','$external_identity',FROM_UNIXTIME($time),'$name','$bio','$avatar_file_name','$avatar_content_type','$avatar_file_size','$avatar_updated_at','$external_username')";
        $result=$this->query($sql);
        $identityid=intval($result["insert_id"]);
        if($identityid > 0)
        {
            $sql="select name,bio,avatar_file_name from users where id=$user_id;";
            $userrow=$this->getRow($sql);
            if($userrow["name"]==""){
                $userrow["name"]=$name;
            }
            if($userrow["bio"]==""){
                $userrow["bio"]=$bio;
            }
            if($userrow["avatar_file_name"]==""){
                $userrow["avatar_file_name"]=$avatar_file_name;
            }

            $sql="update users set name='".$userrow["name"]."', bio='".$userrow["bio"]."', avatar_file_name='".$userrow["avatar_file_name"]."', default_identity=".$identityid." where id=$user_id;";
            $this->query($sql);

            //TOdO: commit as a transaction
            $time=time();
            $sql="insert into user_identity (identityid,userid,created_at,activecode) values ($identityid,$user_id,FROM_UNIXTIME($time),'{$activecode}')";
            $this->query($sql);

            $verifyTokenArray = array(
                "identityid"    =>$identityid,
                "activecode"    =>$activecode
            );
            $verifyToken = packArray($verifyTokenArray);

            $args = array(
                     'identityid'           =>$identityid,
                     'external_identity'    =>$external_identity,
                     'name'                 =>$name,
                     'avatar_file_name'     =>$avatar_file_name,
                     'activecode'           =>$activecode,
                     'token'                =>$verifyToken
            );
            /*
            if($provider=="email")
            {
                $helper=$this->getHelperByName("identity");
                $helper->sentActiveEmail($args);
            }
             */
            //send welcome and active e-mail
            if($provider=="email")
            {
                $helper=$this->getHelperByName("identity");
                $helper->sentWelcomeAndActiveEmail($args);
            }
            return $identityid;
        }
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

}
