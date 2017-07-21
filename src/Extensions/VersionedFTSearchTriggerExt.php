<?php

namespace Axllent\FTSearch\Extensions;

use Axllent\FTSearch\Lib\FTSearchLib;
use SilverStripe\ORM\DataExtension;

class VersionedFTSearchTriggerExt extends DataExtension
{
    public function onAfterPublish()
    {
        FTSearchLib::triggerLinkedObjects($this->owner);
    }

    public function onBeforeUnpublish()
    {
        FTSearchLib::triggerLinkedObjects($this->owner);
    }
}
