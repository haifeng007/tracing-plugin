<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/6/3
 * Time: 17:23
 */

namespace ESD\Plugins\Tracing;


use ESD\Core\Plugins\Config\BaseConfig;

class TracingConfig extends BaseConfig
{
    const key = "tracing";
    /**
     * @var bool
     */
    protected $enable = true;
    /**
     * 服务地址
     * @var string
     */
    protected $host = "localhost";
    /**
     * 服务端口
     * @var int
     */
    protected $port = 9411;
    /**
     * 取样比例
     * @var float
     */
    protected $sampling_ratio = 1;

    public function __construct()
    {
        parent::__construct(self::key);
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host): void
    {
        $this->host = $host;
    }

    /**
     * @return float
     */
    public function getSamplingRatio(): float
    {
        return $this->sampling_ratio;
    }

    /**
     * @param float $sampling_ratio
     */
    public function setSamplingRatio(float $sampling_ratio): void
    {
        $this->sampling_ratio = $sampling_ratio;
    }

    /**
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->enable;
    }

    /**
     * @param bool $enable
     */
    public function setEnable(bool $enable): void
    {
        $this->enable = $enable;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port): void
    {
        $this->port = $port;
    }
}