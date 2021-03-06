<?php

namespace Dafiti\Silex;

use Dafiti\Silex\Exception\InvalidCacheConfig as InvalidCacheConfigException;
use Doctrine\Common\Cache\CacheProvider;
use Silex\ServiceProviderInterface;
use Silex\Application;

class CacheServiceProvider implements ServiceProviderInterface
{
    /**
     * @var string
     */
    private $prefix = 'cache';

    public function register(Application $app)
    {
        $app[$this->prefix] = $app->share(
            function () use ($app) {

                if (!isset($app['config']['cache'])) {
                    throw new InvalidCacheConfigException('Cache Config Not Defined');
                }

                $cacheSettings = $app['config']['cache'];
                $cacheClassName = sprintf('\Doctrine\Common\Cache\%sCache', $cacheSettings['adapter']);

                if (!class_exists($cacheClassName)) {
                    throw new InvalidCacheConfigException('Cache Adapter Not Supported!');
                }

                $cacheAdapter = new $cacheClassName();

                if ($cacheSettings['connectable'] === true) {
                    $this->addConnection($cacheAdapter, $cacheSettings);
                }

                return $cacheAdapter;
            }
        );
    }

    /**
     * @param CacheProvider $cacheAdapter
     * @param array         $cacheSettings
     */
    private function addConnection(CacheProvider $cacheAdapter, array $cacheSettings)
    {
        $proxy = new \Dafiti\Silex\Cache\Proxy();
        $connection = $proxy->getAdapter($cacheSettings);

        $setMethod = sprintf('set%s', $cacheSettings['adapter']);
        $cacheAdapter->$setMethod($connection);
    }

    public function boot(Application $app)
    {
    }
}
