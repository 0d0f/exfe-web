<?php

class ResponseModels extends DataModel {

    public function getResponsesByObjectTypeAndObjectIds($object_type, $object_ids, $identity_id = 0) {
        if ($object_type && $object_ids && is_array($object_ids)) {
            $options = $this->getOptionsByObjectType($object_type);
            if ($options) {
                $object_ids = implode(',', $object_ids);
                $result = $this->getAll(
                    "SELECT * FROM `responses`
                     WHERE `object_type` = '{$object_type}'
                     AND   `object_id`  in ({$object_ids})"
                  . ($identity_id ? "AND `by_identity_id` = {$identity_id}" : '')
                );
                if ($result && is_array($result)) {
                    $hlpIdentity = $this->getHelperByName('Identity');
                    $return = [];
                    foreach ($result as $item) {
                        $identity = $hlpIdentity->getIdentityById($item['by_identity_id']);
                        $response = isset($options['index'][(int) $item['response_id']])
                                  ? $options['index'][(int) $item['response_id']] : null;
                        if ($identity && $response !== null) {
                            $item['object_id'] = (int) $item['object_id'];
                            if (!isset($return[$item['object_id']])) {
                                $return[$item['object_id']] = [];
                            }
                            $return[$item['object_id']][] = new Response(
                                $item['id'],
                                $item['object_type'],
                                $item['object_id'],
                                $response,
                                $identity,
                                $item['created_at'],
                                $item['updated_at']
                            );
                        }
                    }
                    return $return;
                }
            }
        }
        return null;
    }


    public function getResponseByObjectTypeAndObjectId($object_type, $object_id, $identity_id = 0) {
        $result = $this->getResponsesByObjectTypeAndObjectIds(
            $object_type, [$object_id], $identity_id
        );
        return $result && isset($result[$object_id]) ? $result[$object_id] : null;
    }


    public function getResponseByObjectTypeAndObjectIdAndIdentityId($object_type, $object_id, $identity_id) {
        $result = $this->getResponseByObjectTypeAndObjectId(
            $object_type, $object_id, $identity_id
        );
        return $result ? $result[0] : null;
    }


    public function responseToObject($object_type, $object_id, $identity_id, $response) {
        if ($object_type && $object_id && $identity_id) {
            $options = $this->getOptionsByObjectType($object_type);
            if ($options) {
                $opt_idx = isset($options['value'][$response])
                         ? $options['value'][$response] : null;
                if ($opt_idx) {
                    $cur_rsp = $this->getResponseByObjectTypeAndObjectIdAndIdentityId(
                        $object_type, $object_id, $identity_id
                    );
                    if ($cur_rsp) {
                        if ($cur_rsp->response === $response) {
                            return $cur_rsp;
                        }
                        $this->query(
                            "UPDATE `responses`
                             SET    `response_id` = {$opt_idx},
                                    `updated_at`  = NOW()
                             WHERE  `id`          = {$cur_rsp->id}"
                        );
                        return $this->getResponseByObjectTypeAndObjectIdAndIdentityId(
                            $object_type, $object_id, $identity_id
                        );
                    }
                    $this->query(
                        "INSERT INTO `responses`
                         SET `object_type`    = '{$object_type}',
                             `object_id`      =  {$object_id},
                             `response_id`    =  {$opt_idx},
                             `by_identity_id` =  {$identity_id},
                             `created_at`     =  NOW(),
                             `updated_at`     =  NOW()"
                    );
                    return $this->getResponseByObjectTypeAndObjectIdAndIdentityId(
                        $object_type, $object_id, $identity_id
                    );
                }
            }
        }
        return null;
    }


    public function clearResponseBy($object_type, $object_ids, $identity_id) {
        $object_ids = implode(', ', $object_ids);
        if ($object_type && $object_ids && $identity_id) {
            $options = $this->getOptionsByObjectType($object_type);
            if ($options) {
                $opt_idx = isset($options['value'][''])
                         ? $options['value'][''] : null;
                if ($opt_idx) {
                    return $this->query(
                        "UPDATE `responses`
                         SET    `response_id`    = {$opt_idx},
                                `updated_at`     = NOW()
                         WHERE  `object_type`    = '{$object_type}'
                         AND    `object_id`     in ({$object_ids})
                         AND    `by_identity_id` =  {$identity_id}"
                    );
                }
            }
        }
        return null;
    }


    public function getOptionsByObjectType($object_type) {
        if ($object_type) {
            $result = $this->getAll(
                "SELECT * FROM `response_options`
                 WHERE `object_type` = '{$object_type}'"
            );
            if ($result && is_array($result)) {
                $return = ['index' => [], 'value' => []];
                foreach ($result as $item) {
                    $return['index'][(int) $item['id']] = $item['option'];
                    $return['value'][$item['option']] = (int) $item['id'];
                }
                return $return;
            }
        }
        return null;
    }


    public function getOptionIdByObjectTypeAndResponse($object_type, $response) {
        if ($object_type && $response) {
            $result = $this->getRow(
                "SELECT `id` FROM `response_options`
                 WHERE  `object_type` = '{$object_type}'
                 AND    `option`      = '{$response}'"
            );
            return $result && isset($result['id']) ? (int) $result['id'] : null;
        }
        return null;
    }

}
