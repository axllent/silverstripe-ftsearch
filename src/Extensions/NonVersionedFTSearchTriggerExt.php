<?php

namespace Axllent\FTSearch\Extensions;

use Axllent\FTSearch\Lib\FTSearchLib;
use SilverStripe\ORM\DataExtension;

class NonVersionedFTSearchTriggerExt extends DataExtension
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
