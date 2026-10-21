<?php

namespace Deyoni\Device;

class DeyoniDevice
{
    public function appearance(string $mode): void
    {
        if (function_exists('nativephp_call') && ! app()->runningUnitTests()) {
            nativephp_call('DeyoniDevice.Appearance', json_encode(['mode' => $mode]));
        }
    }

    public function documents(string $action, string $path = ''): bool
    {
        if (! function_exists('nativephp_call')) {
            return false;
        }
        $result = json_decode(nativephp_call('DeyoniDevice.Documents', json_encode(['action' => $action, 'path' => $path])), true);

        return (bool) ($result['started'] ?? false);
    }
}
