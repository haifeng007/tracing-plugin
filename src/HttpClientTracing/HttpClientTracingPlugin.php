<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/6/3
 * Time: 17:22
 */

namespace ESD\Plugins\HttpClientTracing;


use ESD\Core\Context\Context;
use ESD\Core\PlugIn\AbstractPlugin;
use ESD\Core\PlugIn\PluginInterfaceManager;
use ESD\Core\Server\Server;
use ESD\Plugins\Aop\AopConfig;
use ESD\Plugins\HttpClientTracing\Aspect\GuzzleTracingAspect;
use ESD\Plugins\HttpClientTracing\Aspect\SaberTracingAspect;
use ESD\Plugins\Tracing\TracingConfig;
use ESD\Plugins\Tracing\TracingPlugin;


class HttpClientTracingPlugin extends AbstractPlugin
{
    /**
     * TracingPlugin constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->atAfter(TracingPlugin::class);
    }

    /**
     * @param PluginInterfaceManager $pluginInterfaceManager
     * @return mixed|void
     * @throws \ESD\Core\Exception
     * @throws \ReflectionException
     */
    public function onAdded(PluginInterfaceManager $pluginInterfaceManager)
    {
        parent::onAdded($pluginInterfaceManager);
        $pluginInterfaceManager->addPlug(new TracingPlugin());
    }

    /**
     * @param Context $context
     * @return mixed|void
     * @throws \ESD\Core\Exception
     */
    public function init(Context $context)
    {
        parent::init($context);
        $aopConfig = DIget(AopConfig::class);
        $tracingConfig = DIget(TracingConfig::class);
        if ($tracingConfig->isEnable()) {
            if ($aopConfig instanceof AopConfig) {
                $aopConfig->addIncludePath(Server::$instance->getServerConfig()->getVendorDir() . "/swlib/saber/src");
                $aopConfig->addIncludePath(Server::$instance->getServerConfig()->getVendorDir() . "/guzzlehttp/guzzle/src");
                $aopConfig->addExcludePath(Server::$instance->getServerConfig()->getVendorDir() . "/guzzlehttp/guzzle/src/Cookie");
                $aopConfig->addExcludePath(Server::$instance->getServerConfig()->getVendorDir() . "/guzzlehttp/guzzle/src/Exception");
                $aopConfig->addExcludePath(Server::$instance->getServerConfig()->getVendorDir() . "/guzzlehttp/guzzle/src/Handler");
            }
            $aopConfig->addAspect(new SaberTracingAspect());
            $aopConfig->addAspect(new GuzzleTracingAspect());
            $aopConfig->merge();
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "HttpClientTracingPlugin";
    }

    /**
     * 初始化
     * @param Context $context
     */
    public function beforeServerStart(Context $context)
    {

    }

    /**
     * 在进程启动前
     * @param Context $context
     * @return mixed
     */
    public function beforeProcessStart(Context $context)
    {
        $this->ready();
    }
}