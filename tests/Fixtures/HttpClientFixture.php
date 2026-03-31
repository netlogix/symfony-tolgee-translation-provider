<?php

declare(strict_types=1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\Fixtures;

class HttpClientFixture
{
    private static function getPath(string $fixture): string
    {
        return __DIR__ . '/' . $fixture;
    }

    public static function getData(string $fixture, string $path, string  $method): string
    {
        return  file_get_contents(sprintf(
            '%s/%s.%s.json',
            self::getPath($fixture),
            $path,
            strtolower($method)
        ));
    }

    public static function getPagedData(string $fixture, string $path, string  $method, int $page=0): string
    {
        return  file_get_contents(sprintf(
            '%s/%s.%s.%d.json',
            self::getPath($fixture),
            $path,
            strtolower($method),
            $page
        ));
    }

    public static function getExportZip(string $fixture, string $namespace, string $languages): string
    {
        $zip = new \ZipArchive();
        $tmpZip = tempnam(sys_get_temp_dir(), 'tolgee_zip_') . ".zip";
        $res = $zip->open($tmpZip, \ZipArchive::CREATE);
        if ($res !== true) {
            throw new \RuntimeException('Unable to open ZIP file for writing at ' . $tmpZip . ': error ' . $res);
        }

        $namespaces = explode(',', $namespace);
        $langArray = explode(',', $languages);

        // For each namespace and language combination, try to add the fixture file
        foreach ($namespaces as $ns) {
            foreach ($langArray as $lang) {
                $filename = sprintf('%s/%s.json', $ns, $lang);
                $jsonPath = sprintf(
                    '%s/export.%s.%s.get.json',
                    self::getPath($fixture),
                    $ns,
                    $lang
                );
                if (file_exists($jsonPath)) {
                    $content = file_get_contents($jsonPath);
                    if (!$zip->addFromString($filename, $content)) {
                        $zip->close();
                        unlink($tmpZip);
                        throw new \RuntimeException('Failed to add file to ZIP: ' . $filename);
                    }
                }
            }
        }
        $zip->close();
        $content = file_get_contents($tmpZip);
        unlink($tmpZip);
        return $content;
    }
}
