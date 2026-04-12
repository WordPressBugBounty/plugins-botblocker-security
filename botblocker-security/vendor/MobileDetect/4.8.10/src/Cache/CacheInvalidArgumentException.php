<?php

declare (strict_types=1);
namespace BotBlocker\Vendor\Detection\Cache;

use BotBlocker\Vendor\Psr\SimpleCache\InvalidArgumentException;
class CacheInvalidArgumentException extends CacheException implements InvalidArgumentException
{
}
