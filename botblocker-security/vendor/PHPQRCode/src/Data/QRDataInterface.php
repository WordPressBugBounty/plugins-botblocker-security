<?php

declare (strict_types=1);
namespace BotBlocker\Vendor\GlobusStudio\QRCode\Data;

use BotBlocker\Vendor\GlobusStudio\QRCode\Encoder\BitBuffer;
interface QRDataInterface
{
    public function getMode(): int;
    public function getLength(): int;
    public function getLengthInBits(int $version): int;
    public function write(BitBuffer $buffer): void;
}
