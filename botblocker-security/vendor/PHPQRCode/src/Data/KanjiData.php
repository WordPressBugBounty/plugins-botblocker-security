<?php

declare (strict_types=1);
namespace BotBlocker\Vendor\GlobusStudio\QRCode\Data;

use BotBlocker\Vendor\GlobusStudio\QRCode\Encoder\BitBuffer;
final class KanjiData extends AbstractQRData
{
    public function __construct(string $data)
    {
        if (!self::isValid($data)) {
            throw new \InvalidArgumentException('Data is not valid Shift-JIS Kanji');
        }
        parent::__construct(self::MODE_KANJI, $data);
    }
    public static function isValid(string $data): bool
    {
        $len = strlen($data);
        if ($len === 0 || $len % 2 !== 0) {
            return \false;
        }
        $i = 0;
        while ($i + 1 < $len) {
            $c = (0xff & ord($data[$i])) << 8 | 0xff & ord($data[$i + 1]);
            if (!(0x8140 <= $c && $c <= 0x9ffc) && !(0xe040 <= $c && $c <= 0xebbf)) {
                return \false;
            }
            $i += 2;
        }
        return \true;
    }
    public function getLength(): int
    {
        return (int) (strlen($this->data) / 2);
    }
    public function write(BitBuffer $buffer): void
    {
        $data = $this->getData();
        $len = strlen($data);
        $i = 0;
        while ($i + 1 < $len) {
            $c = (0xff & ord($data[$i])) << 8 | 0xff & ord($data[$i + 1]);
            if (0x8140 <= $c && $c <= 0x9ffc) {
                $c -= 0x8140;
            } elseif (0xe040 <= $c && $c <= 0xebbf) {
                $c -= 0xc140;
            }
            $c = ($c >> 8 & 0xff) * 0xc0 + ($c & 0xff);
            $buffer->put($c, 13);
            $i += 2;
        }
    }
}
