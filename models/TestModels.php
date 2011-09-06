<?php
class TestModels extends DataModel{

    public function getEvents()
    {
        $sql="select * from events";
        $events=$this->getAll($sql);
        return $events;
    }
}
