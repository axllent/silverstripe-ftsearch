<?php

namespace Axllent\FTSearch\Extensions;

use Axllent\FTSearch\Lib\FTSearchLib;
use Axllent\FTSearch\Model\FTSearch;
use SilverStripe\Core\Extension;

class NonVersionedFTSearchExt extends Extension
{
    public function updateFTSearch()
    {
        FTSearchLib::updateSearchRecord($this->owner);
    }

    // Remove from DB if ClassName has changed
    public function onBeforeWrite()
    {
        if (!$this->owner->isInDB()) {
            return;
        }
        $changed = $this->owner->getChangedFields(['ClassName']);
        if (empty($changed['ClassName']['before'])) {
            return;
        }
        $oldClass = $changed['ClassName']['before'];
        if ($oldClass === $this->owner->ClassName) {
            return;
        }
        $stale = FTSearch::get()->filter([
            'ObjectClass' => $oldClass,
            'ObjectID'    => $this->owner->ID,
        ])->first();
        if ($stale) {
            $stale->delete();
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
