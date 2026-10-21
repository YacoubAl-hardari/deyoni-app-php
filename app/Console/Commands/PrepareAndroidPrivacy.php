<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('deyoni:prepare-android {--platform=} {--build-path=} {--plugin-path=} {--app-id=} {--config=} {--plugins=}')]
#[Description('Apply offline privacy settings to the generated Android shell')]
class PrepareAndroidPrivacy extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('platform') !== 'android') {
            return self::SUCCESS;
        }
        $root = realpath(base_path('nativephp/android'));
        $requested = realpath($this->option('build-path') ?: base_path('nativephp/android'));
        if (! $root || $requested !== $root) {
            $this->error('Unexpected Android build path.');

            return self::FAILURE;
        }
        $path = $root.'/app/src/main/AndroidManifest.xml';
        $document = new \DOMDocument;
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        $document->load($path);
        $ns = 'http://schemas.android.com/apk/res/android';
        $application = $document->getElementsByTagName('application')->item(0);
        $application->setAttributeNS($ns, 'android:allowBackup', 'false');
        $application->setAttributeNS($ns, 'android:usesCleartextTraffic', 'false');
        foreach (iterator_to_array($document->getElementsByTagName('uses-permission')) as $permission) {
            $permission->parentNode->removeChild($permission);
        }
        $document->save($path);
        $this->prepareSelectLayout($root);
        $rules = '';
        foreach (['root', 'file', 'database', 'sharedpref', 'external', 'device_root', 'device_file', 'device_database', 'device_sharedpref'] as $domain) {
            $rules .= '<exclude domain="'.$domain.'" path="."/>';
        }
        file_put_contents($root.'/app/src/main/res/xml/backup_rules.xml', '<?xml version="1.0" encoding="utf-8"?><full-backup-content>'.$rules.'</full-backup-content>');
        file_put_contents($root.'/app/src/main/res/xml/data_extraction_rules.xml', '<?xml version="1.0" encoding="utf-8"?><data-extraction-rules><cloud-backup>'.$rules.'</cloud-backup><device-transfer>'.$rules.'</device-transfer></data-extraction-rules>');

        return self::SUCCESS;
    }

    private function prepareSelectLayout(string $root): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/app/src', \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getFilename() !== 'SelectRenderer.kt') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if (! str_contains($source, 'import androidx.compose.foundation.layout.fillMaxWidth')) {
                $source = str_replace('import androidx.compose.ui.Modifier', "import androidx.compose.foundation.layout.fillMaxWidth\nimport androidx.compose.ui.Modifier", $source);
            }
            $source = str_replace('modifier = Modifier.menuAnchor(', 'modifier = Modifier.fillMaxWidth().menuAnchor(', $source);
            $source = str_replace('textStyle = TextStyle(color = theme.onSurface)', 'textStyle = TextStyle(color = theme.onSurface, fontFamily = nuiDefaultFontFamily())', $source);
            file_put_contents($file->getPathname(), $source);
        }
    }
}
