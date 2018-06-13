<?php
/**
 * Salesforce - Freeform Web to Lead plugin for Craft CMS 3.x
 *
 * Created by Grand Creative Ltd., Auckland
 *
 * @link      andykarwal.com
 * @copyright Copyright (c) 2018 Andy Karwal
 */

namespace grand\salesforcefreeformwebtolead;


use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;

use Solspace\Freeform\Services\FormsService;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Andy Karwal
 * @package   SalesforceFreeformWebToLead
 * @since     1.0.0
 *
 */
class SalesforceFreeformWebToLead extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * SalesforceFreeformWebToLead::$plugin
     *
     * @var SalesforceFreeformWebToLead
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * SalesforceFreeformWebToLead::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'salesforce-freeform-web-to-lead/salesforce';
            }
        );

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'salesforce-freeform-web-to-lead/salesforce/do-something';
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );

        Event::on(FormsService::class, FormsService::EVENT_BEFORE_SUBMIT,
            function () {
                $request = $_REQUEST["p"];

                $listFormsSalesforce = array(
                    'book-a-home-assessment',
                    'request-a-home-ventilation-design',
                    'register-a-product',
                    'contact');

                if (in_array($request, $listFormsSalesforce, true)) {
                    // Clone
                    $postObject = $_POST;
                    //Mutate
                    $postObject["action"] = "https://webto.salesforce.com/servlet/servlet.WebToLead?encoding=UTF-8";

                    $sfurl = $postObject["action"];
                    $sffields = array_slice($postObject, 4);
                    $fieldstring = "";

                    foreach ($sffields as $key => $value) {
                        $fieldstring .= $key . '=' . $value . '&';
                    }
                    rtrim($fieldstring, '&');

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $sfurl);
                    curl_setopt($ch, CURLOPT_POST, count($sffields));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldstring);
                    curl_exec($ch);
                    curl_close($ch);
                }
            });


        /**
         * Logging in Craft involves using one of the following methods:
         *
         * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
         * Craft::info(): record a message that conveys some useful information.
         * Craft::warning(): record a warning message that indicates something unexpected has happened.
         * Craft::error(): record a fatal error that should be investigated as soon as possible.
         *
         * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
         *
         * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
         * the category to the method (prefixed with the fully qualified class name) where the constant appears.
         *
         * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
         * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
         *
         * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
         */
        Craft::info(
            Craft::t(
                'salesforce-freeform-web-to-lead',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }
    // Protected Methods
    // =========================================================================
}
