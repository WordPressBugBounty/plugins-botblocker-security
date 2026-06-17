<?php

declare (strict_types=1);
namespace BotBlocker\Vendor\GlobusStudio\QRCode\Util;

use BotBlocker\Vendor\GlobusStudio\QRCode\Data\AbstractQRData;
use BotBlocker\Vendor\GlobusStudio\QRCode\Data\AlphanumericData;
use BotBlocker\Vendor\GlobusStudio\QRCode\Data\KanjiData;
use BotBlocker\Vendor\GlobusStudio\QRCode\Data\NumericData;
final class ModeDetector
{
    public static function detect(string $data): int
    {
        if ($data === '') {
            return AbstractQRData::MODE_8BIT_BYTE;
        }
        if (NumericData::isValid($data)) {
            return AbstractQRData::MODE_NUMBER;
        }
        if (AlphanumericData::isValid($data)) {
            return AbstractQRData::MODE_ALPHA_NUM;
        }
        if (KanjiData::isValid($data)) {
            return AbstractQRData::MODE_KANJI;
        }
        return AbstractQRData::MODE_8BIT_BYTE;
    }
}
