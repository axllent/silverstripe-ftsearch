<?php

namespace Axllent\FTSearch\Extensions;

use Axllent\FTSearch\Lib\FTSearchLib;
use SilverStripe\ORM\DataExtension;

class NonVersionedFTSearchExt extends DataExtension
{
    public function updateFTSearch()
    {
        FTSearchLib::updateSearchRecord($this->owner);
    }

    // Remove from DB if ClassName has changed
    public function onBeforeWrite()
    {
        $original = FTSearchLib::getLiveVersionObject($this->owner);
        if ($original && $original->ClassName != $this->owner->ClassName) {
            FTSearchLib::removeFromFTSearchDB($original);
        }
    }

    public function onAfterWrite()
    {
        $this->updateFTSearch();
    }

    public function onBeforeDelete()
    {
        FTSearchLib::removeFromFTSearchDB($this->owner);
    }
}
