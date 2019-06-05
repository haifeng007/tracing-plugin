<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/6/3
 * Time: 18:28
 */

namespace ESD\Plugins\MethodTracing\Aspect;

use ESD\Core\Order\Order;
use ESD\Plugins\Aop\OrderAspect;
use ESD\Plugins\Tracing\SpanStack;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_SERVER;

class MethodTracingAspect extends OrderAspect
{
    public function getOrderIndex(Order $root, int $layer): int
    {
        return -1;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "MethodTracingAspect";
    }

    /**
     * around aroundMethodExecute
     *
     * @param MethodInvocation $invocation Invocation
     * @Around("within(ESD\Psr\Tracing\TracingInterface+) && execution(public|protected|private **->*(*))")
     * @return mixed
     * @throws \Throwable
     */
    protected function aroundMethodExecute(MethodInvocation $invocation)
    {
        $name = $invocation->getMethod()->name;
        $spanStack = getDeepContextValueByClassName(SpanStack::class);
        $span = $spanStack->startSpan($invocation->getMethod()->getDeclaringClass()->getShortName() . "::$name");
        defer(function () use ($span) {
            $span->finish();
        });
        $span->setTag(SPAN_KIND, SPAN_KIND_RPC_SERVER);
        $result = $invocation->proceed();
        $spanStack->pop();
        return $result;
    }
}