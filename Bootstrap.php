<?php declare(strict_types=1);
/**
 * @package Plugin\dh_sentry_loader
 * @author Dennis Heinrich
 */

namespace Plugin\dh_sentry_loader;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;

/**
 * Class Bootstrap
 * @package Plugin\dh_sentry_loader
 */
class Bootstrap extends Bootstrapper
{
    /**
     * Executed on each plugin call (e.g. on each page visit)
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
        try {
            if(Shop::isFrontend()) {
                $this->updateDsnAlways();
            }
        } catch (\Exception $e) {
            Shop::Container()->getLogService()->error('Sentry-SDK: '.$e->getMessage());
        }
    }

    /**
     * Executed on plugin installation
     * @return void
     */
    public function installed(): void
    {
        parent::installed();
        try {
            // Add the sentry dependency
            $this->composerRequireSentry();
            // Add the Sentry DSN to the globalinclude.php file
            $this->sentryDsnInclude();
        } catch (\Exception $e) {
            Shop::Container()->getLogService()->error($e->getMessage());
        }
    }

    private function updateDsnAlways(): void
    {
        $sentryDsnInstalled = $this->isSentryDsnInstalled();
        $sentryDsn = $this->getPlugin()->getConfig()->getValue('sentry_sdk_dsn');

        // Skip if the Sentry DSN is not set
        if(isset($sentryDsn) === false || empty($sentryDsn)) {
            return;
        }

        if($sentryDsnInstalled) {
            // Update the Sentry DSN in the globalinclude.php file when it is not installed
            $file = file_get_contents($this->getIncludeFilePath());
            if(strpos($file, $sentryDsn) === false) {
                $this->updateSentryDsn($sentryDsn);
            }
        } else {
            // Add the Sentry DSN to the globalinclude.php file if it is not installed
            $this->updateSentryDsn($sentryDsn);
        }
    }

    /**
     * Executed on plugin uninstallation
     * @param bool $deleteData
     * @return void
     */
    public function uninstalled(bool $deleteData = true): void
    {
        parent::uninstalled($deleteData);
        try {
            // Remove the sentry dependency
            $this->composerRequireSentry(true);
            // Remove the Sentry DSN from the globalinclude.php file
            $this->sentryDsnInclude(true);
        } catch (\Exception $e) {
            Shop::Container()->getLogService()->error($e->getMessage());
        }
    }

    /**
     * Update the Sentry DSN in the globalinclude.php file of the shop. It 
     * returns false if the file does not exist or the DSN could not be updated.
     * @param string $sentryDsn The Sentry DSN to update
     * @return int|false The number of bytes that were written to the file or false
     */
    private function updateSentryDsn(string $sentryDsn)
    {
        $this->removeLastLineOfSentryDsn();
        $content = sprintf("try { \Sentry\init(['dsn' => '%s']); } catch(\Exception \$e) { Shop::Container()->getLogService()->error('Sentry-SDK: '.\$e->getMessage()); }", $sentryDsn);
        return file_put_contents($this->getIncludeFilePath(), $content, FILE_APPEND);
    }

    /**
     * Remove the last line of the Sentry DSN in the globalinclude.php file
     * @return bool True if the last line was removed, false otherwise
     */
    private function removeLastLineOfSentryDsn(): bool
    {
        if($this->isSentryDsnInstalled()) {
            $lines = file($this->getIncludeFilePath()); 
            $last = sizeof($lines) - 1 ; 
            unset($lines[$last]);
            file_put_contents($this->getIncludeFilePath(), $lines); 
        }
        return false;
    }

    /**
     * Install or uninstall the Sentry DSN in the globalinclude.php file.
     * The installation is done by writing the Sentry DSN to the globalinclude.php
     */
    private function sentryDsnInclude(bool $uninstall = false)
    {
        $filePath = $this->getIncludeFilePath();
        if($filePath !== false) {
            if($uninstall) {
                $this->removeLastLineOfSentryDsn();
            } else {
                $this->updateSentryDsn('https://localhost@localhost/0');
            }
        }
    }

    /**
     * Check if the Sentry DSN is installed in the globalinclude.php file
     * @return bool True if the Sentry DSN is installed in the include, false otherwise
     */
    private function isSentryDsnInstalled(): bool {
        $filePath = $this->getIncludeFilePath();
        $searchString = "try { \Sentry\init(";
        if($filePath !== false) {
            $content = file_get_contents($filePath);
            return strpos($content, $searchString) !== false;
        } else {
            return false;
        }
    }

    /**
     * Get the path of the globalinclude.php file of the shop installation.
     * The path is a realpath, so it is a valid path or it returns false if the 
     * file does not exist. This file is called each time the shop is loaded and
     * automatically includes the composer autoload.
     * @return string|bool The path of the globalinclude.php file or false if it does not exist
     */
    private function getIncludeFilePath(): string|bool
    {
        $file = 'globalinclude.php';
        if(file_exists(sprintf('%s/%s', $this->getIncludeFolderPath(), $file))) {
            return sprintf('%s/%s', $this->getIncludeFolderPath(), $file);
        }
        return false;
    }

    /**
     * Get the includes folder of the shop installation.
     * The path is a realpath, so it is a valid path or
     * it returns false if the folder does not exist.
     * @return string|bool
     */
    private function getIncludeFolderPath(): string|bool {
        $folder = __DIR__.'/../../includes';
        return realpath($folder);
    }

    /**
     * Install or uninstall the sentry/sentry composer dependency
     * in the includes folder of the shop, because the plugin is not loaded in the
     * early stages of the shop lifecycle. But for exception interception, the
     * sentry/sentry package is needed as soon as possible to submit exceptions.
     * @param bool $uninstall If true, the package will be uninstalled
     * @return void
     */
    private function composerRequireSentry(bool $uninstall = false): void {
        if(($folder = $this->getIncludeFolderPath()) !== false) {
            $output = [];
            $return = -1;
            // Run the composer require command
            chdir($folder);
            if($uninstall) {
                exec('composer remove sentry/sentry', $output, $return);
            } else {
                exec('composer require sentry/sentry', $output, $return);
            }
        }
    }
}