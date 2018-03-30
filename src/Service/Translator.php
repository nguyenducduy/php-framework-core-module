<?php
namespace Core\Service;

use Phalcon\{
    Translate\Adapter\NativeArray as PhTranslateArray,
    Events\Event as PhEvent,
    Mvc\Dispatcher as PhDispatcher
};
use Shirou\Service\Locator as ShServiceLocator;
use Moment\Moment;

class Translator extends ShServiceLocator
{
    const DEFAULT_LANG_PACK = 'Core';
    const CONFIG_CACHE_DIR = '/app/storage/cache/data/';

    public function beforeDispatch(PhEvent $event, PhDispatcher $dispatcher)
    {
        $di = $this->getDI();

        $cookies = $this->getDI()->get('cookies');
        $config = $this->getDI()->get('config');

        $languageCode = 'en-us';
        $locale = 'en_US';
        if ($cookies->has('locale')) {
            $cookieLang = (string) $cookies->get('locale')->getValue();

            switch ($cookieLang) {
                case 'en':
                    $languageCode = 'en-us';
                    $locale = 'en_US';
                    break;
                case 'vi':
                    $languageCode = 'vi-vn';
                    $locale = 'vi_VN';
                    break;
            }
        }

        if (!in_array($languageCode, $di->get('config')->default->locales->toArray())) {
            $languageCode = 'en-us';
            $locale = 'en_US';
        }

        // Change locale to support date
        Moment::setLocale($locale);
        Moment::setDefaultTimezone($config->default->timezone);
        date_default_timezone_set($config->default->timezone);

        $messages = [];
        $cacheFile = ROOT_PATH . self::CONFIG_CACHE_DIR
            . 'language.'
            . strtolower($dispatcher->getModuleName())
            . '.'
            . $languageCode . '.php';

        if (file_exists($cacheFile)) {
            $content = (array) include($cacheFile);

            $translate = new PhTranslateArray([
                'content' => $content
            ]);
        } else {
            $directory = $di->get('registry')->directories['modules']
                . ucfirst($dispatcher->getModuleName())
                . '/Locales/'
                . $languageCode;

            // Get all toml file in this folder
            if (is_dir($directory)) {
                if ($dh = opendir($directory)) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != "." && $file != "..") {
                            $filePath = $directory . DS . $file;

                            $messages = \Spyc::YAMLLoad($filePath);
                        }
                    }
                    closedir($dh);
                }
            }

            // Load default language package
            $defaultLangPath =  $di->get('registry')->directories['modules']
                . self::DEFAULT_LANG_PACK
                . '/Locales/'
                . $languageCode
                . '/default.yaml';
            $default = \Spyc::YAMLLoad($defaultLangPath);

            $content = array_merge($messages, $default);

            // cache this file
            file_put_contents($cacheFile, $this->_toConfigurationString($content));

            // added Default and Messages variables to translation service
            $translate = new PhTranslateArray([
                'content' => $content
            ]);
        }

        $di->set('lang', $translate, true);

        return !$event->isStopped();
    }

    /**
     * Save language messages to file.
     *
     * @param array|null $data Messages data.
     *
     * @return void
     */
    protected function _toConfigurationString($data = null)
    {
        $configText = var_export($data, true);

        // Fix pathes. This related to windows directory separator.
        $configText = str_replace('\\\\', DS, $configText);

        $configText = str_replace("'" . ROOT_PATH, "ROOT_PATH . '", $configText);
        $headerText = '<?php
/**
* WARNING
*
* Manual changes to this file may cause a malfunction of the system.
* Be careful when changing settings!
*
*/

return ';
        return $headerText . $configText . ';';
    }
}
