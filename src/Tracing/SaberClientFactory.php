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
use Swlib\SaberGM;
use Zipkin\Reporters\Http\ClientFactory;

class SaberClientFactory implements ClientFactory
{
    use GetLogger;

    /**
     * @return SaberClientFactory
     * @throws \BadFunctionCallException if the curl extension is not installed.
     */
    public static function create()
    {
        return new self();
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
            $response = SaberGM::post($options['endpoint_url'], $payload, ["headers" => [
                'Content-Type' => ContentType::JSON,
                'Content-Length' => strlen($payload)
            ]]);
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 202) {
                $this->warn(sprintf('Reporting of spans failed, status code %d', $statusCode));
            }
        };
    }
}