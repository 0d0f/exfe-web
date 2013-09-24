<?php

class WidgetModels extends DataModel {

    public function getByCrossId($cross_id) {
        $cross_id   = (int) $cross_id;
        $rawWidgets = $this->getAll("SELECT * FROM `widgets` WHERE {$cross_id}");
        return $rawWidgets;
    }


    public function getByCrossIdAndType($cross_id, $type) {
        $cross_id   = (int) $cross_id;
        $type       = dbescape(strtolower(trim($type)));
        $rawWidgets = $this->getAll(
            "SELECT * FROM `widgets`
             WHERE `cross_id` =  {$cross_id}
             AND   `type`     = '{$type}'
             ORDER BY `updated_at` DESC;"
        );
        return $rawWidgets;
    }


    public function getByCrossIdsAndType($cross_ids, $type) {
        $type = dbescape(strtolower(trim($type)));
        $cids = [];
        foreach ($cross_ids ?: [] as $id) {
            if (($id = (int) $id)) {
                $cids[] = $id;
            }
        }
        if ($cids) {
            $cids = implode(', ', $cids);
            $rawWidgets = $this->getAll(
                "SELECT * FROM `widgets`
                 WHERE `cross_id` in ({$cids})
                 AND   `type`      = '{$type}'
                 ORDER BY `updated_at` DESC;"
            );
            return $rawWidgets;
        }
        return null;
    }


    public function create($cross_id, $type, $created_by) {
        $cross_id   = (int) $cross_id;
        $created_by = (int) $created_by;
        $type       = dbescape(strtolower(trim($type)));
        return $this->query(
            "INSERT INTO `widgets` SET
             `cross_id`   =  {$cross_id},
             `type`       = '{$type}',
             `created_by` =  {$created_by},
             `updated_by` =  {$created_by},
             `updated_at` =  NOW();"
        );
    }


    public function updateByCrossIdAndType($cross_id, $type, $updated_by) {
        $cross_id   = (int) $cross_id;
        $updated_by = (int) $updated_by;
        $type       = dbescape(strtolower(trim($type)));
        $widget     = $this->getByCrossIdAndType($cross_id, $type);
        if ($widget) {
            return $this->query(
                "UPDATE `widgets`
                 SET    `updated_by` =  {$updated_by},
                        `updated_at` =  NOW()
                 WHERE  `cross_id`   =  {$cross_id}
                 AND    `type`       = '{$type}';"
            );
        }
        return $this->create($cross_id, $type, $updated_by);
    }

}
