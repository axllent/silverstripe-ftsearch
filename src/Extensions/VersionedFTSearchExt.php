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

    // Remove from DB if class has changed
    public function onBeforePublish()
    {
        $original = FTSearchLib::getLiveVersionObject($this->owner);
        if ($original->ClassName != $this->owner->ClassName) {
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
