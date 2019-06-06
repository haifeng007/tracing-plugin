<?php
/**
 * Created by PhpStorm.
 * User: 白猫
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
use const OpenTracing\Tags\COMPONENT;
use const OpenTracing\Tags\ERROR;
use const OpenTracing\Tags\HTTP_STATUS_CODE;
use const OpenTracing\Tags\HTTP_URL;
use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_SERVER;
use const Zipkin\Tags\HTTP_METHOD;

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
        $spanStack = SpanStack::get();
        $clientData = getDeepContextValueByClassName(ClientData::class);
        $traceContext = $spanStack->buildContext($clientData->getRequest()->getHeaders());
        $span = $spanStack->startSpan($clientData->getRequest()->getMethod() . "  " . $clientData->getPath(), $traceContext);
        $span->setTag(SPAN_KIND, SPAN_KIND_RPC_SERVER);
        $span->setTag(HTTP_URL, $clientData->getRequest()->getUri()->__toString());
        $span->setTag(HTTP_METHOD, $clientData->getRequest()->getMethod());
        $span->setTag(COMPONENT, "ESD Server");
        defer(function () use ($span, $clientData, $spanStack) {
            $e = getContextValue("lastException");
            if ($e != null) {
                $span->setTag(ERROR, $e->getMessage());
            }
            $span->setTag(HTTP_STATUS_CODE, $clientData->getResponse()->getStatusCode());
            $spanStack->pop();
        });
        return $invocation->proceed();
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
        $spanStack = SpanStack::get();
        $clientData = getDeepContextValueByClassName(ClientData::class);
        $span = $spanStack->startSpan($clientData->getRequest()->getMethod());
        $span->setTag(SPAN_KIND, SPAN_KIND_RPC_SERVER);
        $span->setTag("method", "tcp");
        $span->setTag("path", $clientData->getPath());
        $span->setTag(COMPONENT, "ESD Server");
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
        $spanStack = SpanStack::get();
        $clientData = getDeepContextValueByClassName(ClientData::class);
        $span = $spanStack->startSpan($clientData->getRequest()->getMethod());
        $span->setTag(SPAN_KIND, SPAN_KIND_RPC_SERVER);
        $span->setTag("method", "ws");
        $span->setTag("path", $clientData->getPath());
        $span->setTag(COMPONENT, "ESD Server");
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
        $spanStack = SpanStack::get();
        $clientData = getDeepContextValueByClassName(ClientData::class);
        $span = $spanStack->startSpan($clientData->getRequest()->getMethod());
        $span->setTag(SPAN_KIND, SPAN_KIND_RPC_SERVER);
        $span->setTag("method", "udp");
        $span->setTag("path", $clientData->getPath());
        $span->setTag(COMPONENT, "ESD Server");
        defer(function () use ($span, $spanStack) {
            $spanStack->pop();
        });
        $invocation->proceed();
    }
}