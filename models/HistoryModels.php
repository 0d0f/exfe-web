<?php

// CREATE TABLE `history` (
//     `id`         bigint(20) unsigned NOT NULL AUTO_INCREMENT,
//     `updated_by` bigint(20) unsigned NOT NULL,
//     `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
//     `module`     varchar(255) NOT NULL,
//     `action`     varchar(255) NOT NULL,
//     `module_id`  varchar(255),
//     `data`       text,
//     `index_id`   bigint(20) unsigned NOT NULL,
//     PRIMARY KEY (`id`)
// ) ENGINE = MyISAM DEFAULT CHARSET = utf8mb4;

class HistoryModels extends DataModel {

    public function log($by_identity_id, $module, $module_id, $action, $data, $index_id = '') {
        $by_identity_id = (int) $by_identity_id;
        $module         = dbescape($module);
        $module_id      = dbescape($module_id);
        $action         = dbescape($action);
        $data           = dbescape(json_encode($data));
        $index_id       = dbescape($index_id);
        return $this->query(
            "INSERT INTO `history` SET
             `updated_by` =  {$by_identity_id},
             `module`     = '{$module}',
             `module_id`  = '{$module_id}',
             `action`     = '{$action}',
             `data`       = '{$data}',
             `index_id`   =  {$index_id};"
        );
    }


    public function getLogs($index_id, $module = '', $module_id = '', $limit = 1000) {
        $arrWhere = ["`index_id` = '" . dbescape($index_id) . "'"];
        if ($module) {
            $arrWhere[] = "`module`    = '" . dbescape($module)    . "'";
        }
        if ($module_id) {
            $arrWhere[] = "`module_id` = '" . dbescape($module_id) . "'";
        }
        $strWhere = implode(' AND ', $arrWhere);
        return $this->getAll(
            "SELECT * FROM `history` WHERE {$strWhere} ORDER BY `id` DESC LIMIT {$limit};"
        );
    }

}
