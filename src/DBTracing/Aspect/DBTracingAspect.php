<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/6/3
 * Time: 18:28
 */

namespace ESD\Plugins\DBTracing\Aspect;

use ESD\Plugins\Aop\OrderAspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;
use ZipkinOpenTracing\Tracer;
use const OpenTracing\Tags\SPAN_KIND;

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
     * around onUdpPacket
     *
     * @param MethodInvocation $invocation Invocation
     * @Around("within(ESD\Psr\DB\DBInterface+) && execution(public **->execute(*))")
     * @return mixed
     * @throws \Throwable
     */
    protected function aroundDBExecute(MethodInvocation $invocation)
    {
        $db = $invocation->getThis();

        $tracer = getDeepContextValueByClassName(Tracer::class);
        $requestSpan = getDeepContextValue("requestSpan");
        if ($requestSpan != null) {
            $span = $tracer->startSpan($db->getType()." Execute", [
                'child_of' => $requestSpan
            ]);
        } else {
            $span = $tracer->startSpan("Execute");
        }
        defer(function ()use ($span){
            $span->finish();
        });
        $span->setTag(SPAN_KIND, 'Client');
        $span->setTag("db.type", $db->getType());
        $span->setTag("component", "ESD DB");
        $result = $invocation->proceed();
        $span->setTag("db.statement", $db->getLastQuery());
        $span->finish();
        return $result;
    }
}