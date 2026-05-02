<?php

declare(strict_types = 1);

namespace Fusio\Model\Backend;

/** PSR-4 in impl so Create/Update extend {@see Operation} (usability) instead of vendor-only resolution. */
class OperationCreate extends Operation implements \JsonSerializable, \PSX\Record\RecordableInterface
{
}
