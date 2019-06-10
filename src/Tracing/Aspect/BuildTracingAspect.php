<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/6/3
 * Time: 18:28
 */

namespace ESD\Plugins\Tracing\Aspect;

use ESD\Core\Server\Beans\Request;
use ESD\Core\Server\Config\ServerConfig;
use ESD\Plugins\Aop\OrderAspect;
use ESD\Plugins\Pack\Aspect\PackAspect;
use ESD\Plugins\Tracing\SpanStack;
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

    private function createTracer($force = false)
    {
        $tracer = $this->tracingBuilder->buildTracer($force);
        if ($tracer != null) {
            $spanStack = new SpanStack($tracer);
            defer(function () use ($tracer, $spanStack) {
                $tracer->flush();
                $spanStack->destroy();
            });
            setContextValue("tracer", $tracer);
            setContextValue("spanStack", $spanStack);
        }
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
        /** @var Request $request */
        list($request, $response) = $invocation->getArguments();
        if (!empty($request->getHeader("x-b3-traceid"))) {
            $this->createTracer(true);
        } else {
            $this->createTracer();
        }
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
        $this->createTracer();
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
        $this->createTracer();
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
        $this->createTracer();
    }
}