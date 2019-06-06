<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/6/3
 * Time: 18:28
 */

namespace ESD\Plugins\HttpClientTracing\Aspect;

use ESD\Plugins\Aop\OrderAspect;
use ESD\Plugins\Tracing\SpanStack;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use const OpenTracing\Tags\COMPONENT;
use const OpenTracing\Tags\ERROR;
use const OpenTracing\Tags\HTTP_METHOD;
use const OpenTracing\Tags\HTTP_STATUS_CODE;
use const OpenTracing\Tags\HTTP_URL;
use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_CLIENT;

class GuzzleTracingAspect extends OrderAspect
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return "GuzzleTracingAspect";
    }

    /**
     * around aroundClientInterfaceSend
     * @param MethodInvocation $invocation Invocation
     * @Around("within(GuzzleHttp\ClientInterface+) && execution(public **->send(*))")
     * @return mixed
     * @throws \Throwable
     */
    protected function aroundClientInterfaceSend(MethodInvocation $invocation)
    {
        /** @var RequestInterface $request */
        $request = $invocation->getArguments()[0];
        $name = $request->getUri();
        $spanStack = SpanStack::get();
        $span = $spanStack->startSpan($request->getMethod() . " $name");
        $headers = $spanStack->injectHeaders($span);
        foreach ($headers as $key => $value) {
            $request->withHeader($key, $value);
        }
        $span->setTag(HTTP_URL, $request->getUri()->__toString());
        $span->setTag(HTTP_METHOD, $request->getMethod());
        $span->setTag(SPAN_KIND, SPAN_KIND_RPC_CLIENT);
        $span->setTag(COMPONENT, "ESD Guzzle Client");
        defer(function () use ($span) {
            $span->finish();
        });
        $result = $invocation->proceed();
        if ($result instanceof ResponseInterface) {
            $span->setTag(HTTP_STATUS_CODE, $result->getStatusCode());
            if ($result->getStatusCode() != 200) {
                $span->setTag(ERROR, $result->getBody()->__toString());
            }
        }
        $spanStack->pop();
        return $result;
    }

    /**
     * around aroundClientInterfaceRequest
     * @param MethodInvocation $invocation Invocation
     * @Around("within(GuzzleHttp\ClientInterface+) && execution(public **->request(*))")
     * @return mixed
     * @throws \Throwable
     */
    protected function aroundClientInterfaceRequest(MethodInvocation $invocation)
    {
        $method = $invocation->getArguments()[0];
        $uri = $invocation->getArguments()[1];
        $options = $invocation->getArguments()[2] ?? [];
        $name = $uri;
        $spanStack = SpanStack::get();
        $span = $spanStack->startSpan($method . " $name");
        $headers = $spanStack->injectHeaders($span);
        foreach ($headers as $key => $value) {
            $options["headers"][$key] = $value;
        }
        $span->setTag(HTTP_URL, $uri . "");
        $span->setTag(HTTP_METHOD, $method);
        $span->setTag(SPAN_KIND, SPAN_KIND_RPC_CLIENT);
        $span->setTag(COMPONENT, "ESD Guzzle Client");
        defer(function () use ($span) {
            $span->finish();
        });
        $invocation->setArguments([$method, $uri, $options]);
        $result = $invocation->proceed();
        if ($result instanceof ResponseInterface) {
            $span->setTag(HTTP_STATUS_CODE, $result->getStatusCode());
        }
        $spanStack->pop();
        return $result;
    }
}