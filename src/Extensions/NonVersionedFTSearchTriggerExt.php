<?php

namespace Axllent\FTSearch\Extensions;

use Axllent\FTSearch\Lib\FTSearchLib;
use SilverStripe\Core\Extension;

class NonVersionedFTSearchTriggerExt extends Extension
{
    public function onAfterWrite()
    {
        FTSearchLib::triggerLinkedObjects($this->owner);
    }

    public function onAfterDelete()
    {
        FTSearchLib::triggerLinkedObjects($this->owner);
    }
}
