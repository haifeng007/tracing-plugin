<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/6/5
 * Time: 10:17
 */

namespace ESD\Plugins\Tracing;


use ZipkinOpenTracing\Span;
use ZipkinOpenTracing\Tracer;
use const OpenTracing\Formats\TEXT_MAP;

class SpanStack
{
    /**
     * @var Span[]
     */
    private $spans;
    /**
     * @var Tracer
     */
    private $tracer;

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    public function push($span)
    {
        if ($span != null) {
            $this->spans[] = $span;
        }
    }

    public function now()
    {
        $count = count($this->spans);
        if ($count <= 0) {
            return null;
        }
        return $this->spans[$count - 1];
    }

    public function pop()
    {
        $span = array_pop($this->spans);
        $span->finish();
        return $span;
    }

    /**
     * @param Span $span
     * @return array
     */
    public function injectHeaders(Span $span)
    {
        $headers = [];
        $this->tracer->inject($span->getContext(), TEXT_MAP, $headers);
        return $headers;
    }

    public function buildContext(array $carrier)
    {
        return $this->tracer->extract(TEXT_MAP, $carrier);
    }

    /**
     * @param $name
     * @param null $context
     * @return \ZipkinOpenTracing\Span
     */
    public function startSpan($name, $context = null)
    {
        if ($context != null) {
            $span = $this->tracer->startSpan($name, [
                'child_of' => $context
            ]);
        } else {
            $count = count($this->spans);
            $parentSpan = null;
            if ($count > 0) {
                $parentSpan = $this->spans[$count - 1];
            }
            if ($parentSpan != null) {
                $span = $this->tracer->startSpan($name, [
                    'child_of' => $parentSpan
                ]);
            } else {
                $span = $this->tracer->startSpan($name);
            }
        }
        $this->push($span);
        return $span;
    }

    public function destroy()
    {
        foreach ($this->spans as $span) {
            $span->finish();
        }
        $this->spans = null;
    }

    public static function get()
    {
        $result = getContextValueByClassName(SpanStack::class);
        if ($result == null) {
            $trace = getDeepContextValueByClassName(Tracer::class);
            $parentSpanStack = getDeepContextValueByClassName(SpanStack::class);
            $spanStack = new SpanStack($trace);
            $spanStack->push($parentSpanStack->now());
            setContextValue("spanStack", $spanStack);
            return $spanStack;
        }
        return $result;
    }
}