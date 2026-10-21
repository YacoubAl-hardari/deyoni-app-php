<?php

namespace App\Services;

use App\Models\AppSetting;
use Deyoni\Device\DeyoniDevice;
use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\UI\Theme;

class AppearanceService
{
    public function apply(): void
    {
        $tokens = require config_path('native-ui.php');
        $mode = AppSetting::read('appearance', 'system');
        if ($mode !== 'system') {
            $palette = $tokens['theme'][$mode];
            $tokens['theme']['light'] = $palette;
            $tokens['theme']['dark'] = $palette;
        }
        Theme::load($tokens['theme']);
        TailwindParser::clearCache();
        app(DeyoniDevice::class)->appearance($mode);
    }
}
