<?php

declare (strict_types=1);
namespace BotBlocker\Vendor\GlobusStudio\QRCode\Data;

use BotBlocker\Vendor\GlobusStudio\QRCode\Encoder\BitBuffer;
final class ByteData extends AbstractQRData
{
    public function __construct(string $data)
    {
        parent::__construct(self::MODE_8BIT_BYTE, $data);
    }
    public function write(BitBuffer $buffer): void
    {
        $data = $this->getData();
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $buffer->put(ord($data[$i]), 8);
        }
    }
}
