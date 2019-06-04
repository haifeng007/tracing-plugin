<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/6/3
 * Time: 17:22
 */

namespace ESD\Plugins\Tracing;


use ESD\Core\Context\Context;
use ESD\Core\PlugIn\AbstractPlugin;
use ESD\Core\PlugIn\PluginInterfaceManager;
use ESD\Plugins\Aop\AopConfig;
use ESD\Plugins\Aop\AopPlugin;
use ESD\Plugins\Pack\PackPlugin;
use ESD\Plugins\Saber\SaberPlugin;
use ESD\Plugins\Tracing\Aspect\BuildTracingAspect;


class TracingPlugin extends AbstractPlugin
{

    /**
     * @var TracingConfig
     */
    private $tracingConfig;

    /**
     * TracingPlugin constructor.
     * @param TracingConfig|null $tracingConfig
     * @throws \ReflectionException
     */
    public function __construct(?TracingConfig $tracingConfig = null)
    {
        parent::__construct();
        if ($tracingConfig == null) {
            $tracingConfig = new TracingConfig();
        }
        $this->tracingConfig = $tracingConfig;
        $this->atAfter(AopPlugin::class);
        $this->atAfter(SaberPlugin::class);
        $this->atAfter(PackPlugin::class);
    }

    /**
     * @param PluginInterfaceManager $pluginInterfaceManager
     * @return mixed|void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \ESD\Core\Exception
     * @throws \ReflectionException
     */
    public function onAdded(PluginInterfaceManager $pluginInterfaceManager)
    {
        parent::onAdded($pluginInterfaceManager);
        $pluginInterfaceManager->addPlug(new AopPlugin());
        $pluginInterfaceManager->addPlug(new SaberPlugin());
        $pluginInterfaceManager->addPlug(new PackPlugin());
    }

    /**
     * @param Context $context
     * @return mixed|void
     * @throws \ESD\Core\Plugins\Config\ConfigException
     */
    public function init(Context $context)
    {
        parent::init($context);
        $aopConfig = DIget(AopConfig::class);
        $this->tracingConfig->merge();
        $aopConfig->addAspect(new BuildTracingAspect());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "Tracing";
    }

    /**
     * 初始化
     * @param Context $context
     * @return mixed
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