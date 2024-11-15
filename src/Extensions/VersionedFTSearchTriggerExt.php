<?php

namespace Axllent\FTSearch\Extensions;

use Axllent\FTSearch\Lib\FTSearchLib;
use SilverStripe\Core\Extension;

class VersionedFTSearchTriggerExt extends Extension
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
