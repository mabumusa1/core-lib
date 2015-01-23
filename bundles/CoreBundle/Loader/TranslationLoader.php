<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Loader;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Class TranslationLoader
 */
class TranslationLoader extends ArrayLoader implements LoaderInterface
{

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $core   = $this->factory->getParameter('bundles');
        $addons = $this->factory->getEnabledAddons();

        $bundles = array_merge($core, $addons);
        unset($core, $addons);

        $catalogue = new MessageCatalogue($locale);

        //Bundle translations
        foreach ($bundles as $name => $bundle) {
            //load translations
            $translations = $bundle['directory'] . '/Translations/' . $locale;
            if (file_exists($translations)) {

                $iniFiles = new Finder();
                $iniFiles->files()->in($translations)->name('*.ini');

                foreach ($iniFiles as $file) {
                    $this->loadTranslations($catalogue, $locale, $file);
                }
            }
        }

        //Theme translations
        $themeDir = $this->factory->getSystemPath('currentTheme', true);
        if (file_exists($themeTranslation = $themeDir . '/translations/' . $locale)) {
            $iniFiles = new Finder();
            $iniFiles->files()->in($themeTranslation)->name('*.ini');
            foreach ($iniFiles as $file) {
                $this->loadTranslations($catalogue, $locale, $file);
            }
        }

        //3rd Party translations
        $translationsDir = $this->factory->getSystemPath('translations', true) . '/' . $locale;
        if (file_exists($translationsDir)) {
            $iniFiles = new Finder();
            $iniFiles->files()->in($translationsDir)->name('*.ini');

            foreach ($iniFiles as $file) {
                $this->loadTranslations($catalogue, $locale, $file);
            }
        }

        //Overrides
        $overridesDir = $this->factory->getSystemPath('translations', true) . '/overrides/' . $locale;
        if (file_exists($overridesDir)) {
            $iniFiles = new Finder();
            $iniFiles->files()->in($overridesDir)->name('*.ini');

            foreach ($iniFiles as $file) {
                $this->loadTranslations($catalogue, $locale, $file);
            }
        }

        return $catalogue;
    }

    /**
     * Load the translation into the catalogue
     *
     * @param $catalogue
     * @param $locale
     * @param $file
     */
    private function loadTranslations($catalogue, $locale, $file)
    {
        $iniFile  = $file->getRealpath();
        $messages = parse_ini_file($iniFile, true);
        $domain   = substr($file->getFilename(), 0, -4);

        $thisCatalogue = parent::load($messages, $locale, $domain);
        $catalogue->addCatalogue($thisCatalogue);
    }
}
