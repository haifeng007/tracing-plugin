<?php
/**
 * Created by PhpStorm.
 * User: administrato
 * Date: 2019/6/5
 * Time: 10:17
 */

namespace ESD\Plugins\Tracing;


use Zipkin\Span;
use ZipkinOpenTracing\Tracer;

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
        $this->spans[] = $span;
    }

    public function pop()
    {
        $span = array_pop($this->spans);
        $span->finish();
        return $span;
    }

    /**
     * @param $name
     * @return \ZipkinOpenTracing\Span
     */
    public function startSpan($name)
    {
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
}