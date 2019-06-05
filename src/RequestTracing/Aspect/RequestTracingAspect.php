<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/6/3
 * Time: 18:28
 */

namespace ESD\Plugins\RequestTracing\Aspect;

use ESD\Plugins\Aop\OrderAspect;
use ESD\Plugins\EasyRoute\Aspect\RouteAspect;
use ESD\Plugins\Pack\Aspect\PackAspect;
use ESD\Plugins\Pack\ClientData;
use ESD\Plugins\Tracing\SpanStack;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use const OpenTracing\Tags\SPAN_KIND;

class RequestTracingAspect extends OrderAspect
{
    public function __construct()
    {
        $this->atAfter(PackAspect::class);
        $this->atBefore(RouteAspect::class);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "RequestTracingAspect";
    }

    /**
     * around onHttpRequest
     *
     * @param MethodInvocation $invocation Invocation
     * @return mixed
     * @throws \Throwable
     * @Around("within(ESD\Core\Server\Port\IServerPort+) && execution(public **->onHttpRequest(*))")
     */
    protected function aroundHttpRequest(MethodInvocation $invocation)
    {
        $spanStack = getDeepContextValueByClassName(SpanStack::class);
        $clientData = getDeepContextValueByClassName(ClientData::class);
        $span = $spanStack->startSpan($clientData->getRequest()->getMethod() . "  " . $clientData->getPath());
        $span->setTag(SPAN_KIND, 'SERVER');
        $span->setTag("http.url", $clientData->getRequest()->getUri()->__toString());
        $span->setTag("http.method", $clientData->getRequest()->getMethod());
        $span->setTag("component", "ESD Server");
        defer(function () use ($span, $clientData, $spanStack) {
            $span->setTag("http.status_code", $clientData->getResponse()->getStatusCode());
            $spanStack->pop();
        });
        $invocation->proceed();
    }

    /**
     * around onTcpReceive
     *
     * @param MethodInvocation $invocation Invocation
     * @throws \Throwable
     * @Around("within(ESD\Core\Server\Port\IServerPort+) && execution(public **->onTcpReceive(*))")
     */
    protected function aroundTcpReceive(MethodInvocation $invocation)
    {
        $spanStack = getDeepContextValueByClassName(SpanStack::class);
        $clientData = getDeepContextValueByClassName(ClientData::class);
        $span = $spanStack->startSpan($clientData->getRequest()->getMethod());
        $span->setTag(SPAN_KIND, 'SERVER');
        $span->setTag("method", "tcp");
        $span->setTag("path", $clientData->getPath());
        $span->setTag("component", "ESD Server");
        defer(function () use ($span, $spanStack) {
            $spanStack->pop();
        });
        $invocation->proceed();
    }

    /**
     * around onWsMessage
     *
     * @param MethodInvocation $invocation Invocation
     * @throws \Throwable
     * @Around("within(ESD\Core\Server\Port\IServerPort+) && execution(public **->onWsMessage(*))")
     */
    protected function aroundWsMessage(MethodInvocation $invocation)
    {
        $spanStack = getDeepContextValueByClassName(SpanStack::class);
        $clientData = getDeepContextValueByClassName(ClientData::class);
        $span = $spanStack->startSpan($clientData->getRequest()->getMethod());
        $span->setTag(SPAN_KIND, 'SERVER');
        $span->setTag("method", "ws");
        $span->setTag("path", $clientData->getPath());
        $span->setTag("component", "ESD Server");
        defer(function () use ($span, $spanStack) {
            $spanStack->pop();
        });
        $invocation->proceed();
    }

    /**
     * around onUdpPacket
     *
     * @param MethodInvocation $invocation Invocation
     * @Around("within(ESD\Core\Server\Port\IServerPort+) && execution(public **->onUdpPacket(*))")
     * @throws \Throwable
     */
    protected function aroundUdpPacket(MethodInvocation $invocation)
    {
        $spanStack = getDeepContextValueByClassName(SpanStack::class);
        $clientData = getDeepContextValueByClassName(ClientData::class);
        $span = $spanStack->startSpan($clientData->getRequest()->getMethod());
        $span->setTag(SPAN_KIND, 'SERVER');
        $span->setTag("method", "udp");
        $span->setTag("path", $clientData->getPath());
        $span->setTag("component", "ESD Server");
        defer(function () use ($span, $spanStack) {
            $spanStack->pop();
        });
        $invocation->proceed();
    }
}