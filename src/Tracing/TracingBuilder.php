<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/6/4
 * Time: 10:16
 */

namespace ESD\Plugins\Tracing;


use ESD\Server\Co\Server;
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
    /**
     * @var PercentageSampler
     */
    private $sampler;
    /**
     * @var \Zipkin\DefaultTracing
     */
    private $tracing;

    public function __construct()
    {
        $this->tracingConfig = DIGet(TracingConfig::class);
        $this->sampler = PercentageSampler::create($this->tracingConfig->getSamplingRatio());
        $endpoint = Endpoint::create(Server::$instance->getServerConfig()->getName());
        $clientFactory = SaberClientFactory::create();
        $url = $this->tracingConfig->getHost() . ":" . $this->tracingConfig->getPort();
        $reporter = new Http($clientFactory, [
            'endpoint_url' => "http://$url/api/v2/spans",
        ]);
        $this->tracing = ZipkinTracingBuilder::create()
            ->havingLocalEndpoint($endpoint)
            ->havingReporter($reporter)
            ->havingSampler(BinarySampler::createAsAlwaysSample())
            ->build();
    }

    /**
     * @return Tracer
     */
    public function buildTracer()
    {
        $isSampled = $this->sampler->isSampled(null);
        if($isSampled) {
            return new Tracer($this->tracing);
        }else{
            return null;
        }
    }
}