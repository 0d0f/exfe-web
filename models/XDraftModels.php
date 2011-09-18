<?php

class XDraftModels extends DataModel
{

    public function delDraft($identity_id)
    {
        return $this->query("DELETE FROM `cross_drafts` WHERE `creator_id` = {$identity_id}");
    }

    public function saveDraft($identity_id, $title, $json)
    {
        $this->delDraft($identity_id);
        $json = addslashes($json);
        return $this->query("INSERT INTO `cross_drafts` SET `creator_id` = {$identity_id}, `title` = '{$title}', `json` = '{$json}'");
    }

    public function getDraft($identity_id)
    {
        $draft = $this->getRow("SELECT `json` FROM `cross_drafts` WHERE `creator_id` = {$identity_id}");
        return $draft ? $draft['json'] : null;
    }

}
