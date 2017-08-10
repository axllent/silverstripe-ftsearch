<?php

namespace Axllent\FTSearch\Extensions;

use Axllent\FTSearch\Lib\FTSearchLib;
use SilverStripe\ORM\DataExtension;

class VersionedFTSearchExt extends DataExtension
{
    public function updateFTSearch()
    {
        FTSearchLib::updateSearchRecord($this->owner);
    }

    // Remove from DB if ClassName has changed
    public function onBeforePublish()
    {
        $original = FTSearchLib::getLiveVersionObject($this->owner);
        if ($original && $original->ClassName != $this->owner->ClassName) {
            FTSearchLib::removeFromFTSearchDB($original);
        }
    }

    public function onAfterPublish()
    {
        $this->updateFTSearch();
    }

    public function onBeforeUnpublish()
    {
        FTSearchLib::removeFromFTSearchDB($this->owner);
    }
}
