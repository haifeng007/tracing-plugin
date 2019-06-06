<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/6/3
 * Time: 18:28
 */

namespace ESD\Plugins\DBTracing\Aspect;

use ESD\Plugins\Aop\OrderAspect;
use ESD\Plugins\Tracing\SpanStack;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use const OpenTracing\Tags\COMPONENT;
use const OpenTracing\Tags\DATABASE_STATEMENT;
use const OpenTracing\Tags\DATABASE_TYPE;
use const OpenTracing\Tags\ERROR;
use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_CLIENT;

class DBTracingAspect extends OrderAspect
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return "DBTracingAspect";
    }

    /**
     * around dbExecute
     * @param MethodInvocation $invocation Invocation
     * @Around("within(ESD\Psr\DB\DBInterface+) && execution(public **->execute(*))")
     * @return mixed
     * @throws \Throwable
     */
    protected function aroundDBExecute(MethodInvocation $invocation)
    {
        $spanStack = SpanStack::get();
        if ($spanStack != null) {
            list($name, $call) = $invocation->getArguments();
            $db = $invocation->getThis();
            $span = $spanStack->startSpan($db->getType() . " Execute $name");
            defer(function () use ($span) {
                $span->finish();
            });
            $span->setTag(SPAN_KIND, SPAN_KIND_RPC_CLIENT);
            $span->setTag(DATABASE_TYPE, $db->getType());
            $span->setTag(COMPONENT, "ESD DB");
            $result = null;
            try {
                $result = $invocation->proceed();
            } catch (\Throwable $e) {
                $span->setTag(ERROR, $e->getMessage());
                throw $e;
            } finally {
                $span->setTag(DATABASE_STATEMENT, $db->getLastQuery());
                $spanStack->pop();
            }
        }else{
            $result = $invocation->proceed();
        }
        return $result;
    }
}