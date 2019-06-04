<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/6/3
 * Time: 18:28
 */

namespace ESD\Plugins\Tracing\Aspect;

use ESD\Core\Server\Config\ServerConfig;
use ESD\Plugins\Aop\OrderAspect;
use ESD\Plugins\Pack\Aspect\PackAspect;
use ESD\Plugins\Tracing\TracingBuilder;
use ESD\Server\Co\Server;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Before;

class BuildTracingAspect extends OrderAspect
{
    /**
     * @var TracingBuilder
     */
    private $tracingBuilder;

    /**
     * @var ServerConfig
     */
    private $serverConfig;

    public function __construct()
    {
        $this->tracingBuilder = DIGet(TracingBuilder::class);
        $this->serverConfig = Server::$instance->getServerConfig();
        $this->atBefore(PackAspect::class);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "BuildTracingAspect";
    }

    /**
     * around onHttpRequest
     *
     * @param MethodInvocation $invocation Invocation
     * @return mixed
     * @throws \Throwable
     * @Before("within(ESD\Core\Server\Port\IServerPort+) && execution(public **->onHttpRequest(*))")
     */
    protected function aroundHttpRequest(MethodInvocation $invocation)
    {
        $tracer = $this->tracingBuilder->buildTracer($this->serverConfig->getName());
        defer(function () use ($tracer) {
            $tracer->flush();
        });
        setContextValue("tracer", $tracer);
    }

    /**
     * around onTcpReceive
     *
     * @param MethodInvocation $invocation Invocation
     * @throws \Throwable
     * @Before("within(ESD\Core\Server\Port\IServerPort+) && execution(public **->onTcpReceive(*))")
     */
    protected function aroundTcpReceive(MethodInvocation $invocation)
    {
        $tracer = $this->tracingBuilder->buildTracer($this->serverConfig->getName());
        defer(function () use ($tracer) {
            $tracer->flush();
        });
        setContextValue("tracer", $tracer);
    }

    /**
     * around onWsMessage
     *
     * @param MethodInvocation $invocation Invocation
     * @throws \Throwable
     * @Before("within(ESD\Core\Server\Port\IServerPort+) && execution(public **->onWsMessage(*))")
     */
    protected function aroundWsMessage(MethodInvocation $invocation)
    {
        $tracer = $this->tracingBuilder->buildTracer($this->serverConfig->getName());
        defer(function () use ($tracer) {
            $tracer->flush();
        });
        setContextValue("tracer", $tracer);
    }

    /**
     * around onUdpPacket
     *
     * @param MethodInvocation $invocation Invocation
     * @throws("within(ESD\Core\Server\Port\IServerPort+) && execution(public **->onUdpPacket(*))")
     * @throws \Throwable
     */
    protected function aroundUdpPacket(MethodInvocation $invocation)
    {
        $tracer = $this->tracingBuilder->buildTracer($this->serverConfig->getName());
        defer(function () use ($tracer) {
            $tracer->flush();
        });
        setContextValue("tracer", $tracer);
    }
}