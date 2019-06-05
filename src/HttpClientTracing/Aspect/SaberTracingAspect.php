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
use Go\Lang\Annotation\Before;
use Psr\Http\Message\ResponseInterface;
use const OpenTracing\Tags\COMPONENT;
use const OpenTracing\Tags\HTTP_METHOD;
use const OpenTracing\Tags\HTTP_STATUS_CODE;
use const OpenTracing\Tags\HTTP_URL;
use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_CLIENT;

class SaberTracingAspect extends OrderAspect
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return "SaberTracingAspect";
    }

    /**
     * around beforeSaberExecute
     * @param MethodInvocation $invocation Invocation
     * @Before("execution(public Swlib\Saber\Request->exec(*))")
     * @return mixed
     * @throws \Throwable
     */
    protected function beforeSaberExecute(MethodInvocation $invocation)
    {
        $request = $invocation->getThis();
        $name = $request->getUri();
        $spanStack = SpanStack::get();
        $span = $spanStack->startSpan($request->getMethod() . " $name");
        $headers = $spanStack->injectHeaders($span);
        $request->withHeaders($headers);
        $span->setTag(HTTP_URL, $request->getUri()->__toString());
        $span->setTag(HTTP_METHOD, $request->getMethod());
        $span->setTag(SPAN_KIND, SPAN_KIND_RPC_CLIENT);
        $span->setTag(COMPONENT, "ESD Saber Client");
        $request->span = $span;
        defer(function () use ($span, $request) {
            $span->finish();
            $request->span = null;
        });
    }

    /**
     * around aroundSaberExecute
     * @param MethodInvocation $invocation Invocation
     * @Around("execution(public Swlib\Saber\Request->recv(*))")
     * @return mixed
     * @throws \Throwable
     */
    protected function aroundSaberExecute(MethodInvocation $invocation)
    {
        $request = $invocation->getThis();
        $result = $invocation->proceed();
        $span = $request->span;
        if ($result instanceof ResponseInterface) {
            $span->setTag(HTTP_STATUS_CODE, $result->getStatusCode());
        }
        $spanStack = SpanStack::get();
        $spanStack->pop();
        return $result;
    }
}