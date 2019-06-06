<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/6/4
 * Time: 10:22
 */

namespace ESD\Plugins\Tracing;


use ESD\Core\Plugins\Logger\GetLogger;
use Swlib\Http\ContentType;
use Zipkin\Reporters\Http\ClientFactory;

class SaberClientFactory implements ClientFactory
{
    use GetLogger;
    private $cli;

    /**
     * @var array
     */
    protected $channel;
    /**
     * @var TracingConfig
     */
    private $config;

    /**
     * @return SaberClientFactory
     * @throws \BadFunctionCallException if the curl extension is not installed.
     */
    public static function create()
    {
        return new self();
    }

    public function __construct()
    {
        $this->channel = [];
        $this->config = DIGet(TracingConfig::class);
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $options = [])
    {
        /**
         * @param string $payload
         * @return void
         */
        return function ($payload) use ($options) {
            go(function () use ($payload) {
                $cli = array_shift($this->channel);
                if ($cli == null) {
                    $cli = new \Swoole\Coroutine\Http\Client($this->config->getHost(), $this->config->getPort());
                }
                $cli->setHeaders([
                    'Content-Type' => ContentType::JSON,
                    'Content-Length' => strlen($payload),
                    'Accept-Encoding' => 'gzip'
                ]);
                $cli->post('/api/v2/spans', $payload);
                $statusCode = $cli->statusCode;
                if ($statusCode !== 202) {
                    $this->warn(sprintf('Reporting of spans failed, status code %d', $statusCode));
                }
                $this->channel[] = $cli;
            });
        };
    }
}