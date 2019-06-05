<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/6/4
 * Time: 10:16
 */

namespace ESD\Plugins\Tracing;


use Zipkin\Endpoint;
use Zipkin\Reporters\Http;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Samplers\PercentageSampler;
use Zipkin\TracingBuilder as ZipkinTracingBuilder;
use ZipkinOpenTracing\Tracer;

class TracingBuilder
{
    /**
     * @var TracingConfig
     */
    private $tracingConfig;

    public function __construct()
    {
        $this->tracingConfig = DIGet(TracingConfig::class);
    }

    /**
     * @param $serviceName
     * @param null $ipv4
     * @param null $port
     * @return Tracer
     */
    public function buildTracer($serviceName, $ipv4 = null, $port = null)
    {
        $endpoint = Endpoint::create($serviceName, $ipv4, null, $port);
        $clientFactory = SaberClientFactory::create();
        $url = $this->tracingConfig->getHost() . ":" . $this->tracingConfig->getPort();
        $reporter = new Http($clientFactory, [
            'endpoint_url' => "http://$url/api/v2/spans",
        ]);
        $sampler = PercentageSampler::create($this->tracingConfig->getSamplingRatio());
        $tracing = ZipkinTracingBuilder::create()
            ->havingLocalEndpoint($endpoint)
            ->havingSampler($sampler)
            ->havingReporter($reporter)
            ->build();
        return new Tracer($tracing);
    }
}