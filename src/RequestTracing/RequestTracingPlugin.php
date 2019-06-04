<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/6/3
 * Time: 17:22
 */

namespace ESD\Plugins\RequestTracing;


use ESD\Core\Context\Context;
use ESD\Core\PlugIn\AbstractPlugin;
use ESD\Core\PlugIn\PluginInterfaceManager;
use ESD\Plugins\Aop\AopConfig;
use ESD\Plugins\RequestTracing\Aspect\RequestTracingAspect;
use ESD\Plugins\Tracing\TracingPlugin;


class RequestTracingPlugin extends AbstractPlugin
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
     */
    public function init(Context $context)
    {
        parent::init($context);
        $aopConfig = DIget(AopConfig::class);
        $aopConfig->addAspect(new RequestTracingAspect());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "RequestTracing";
    }

    /**
     * 初始化
     * @param Context $context
     * @return mixe     */
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