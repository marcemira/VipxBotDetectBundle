<?php

/*
 * This file is part of the VipxBotDetectBundle package.
 *
 * (c) Lennart Hildebrandt <http://github.com/lennerd>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vipx\BotDetectBundle\Bot;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;

class BotDetector implements BotDetectorInterface
{

    private $metadatas;
    private $loader;
    private $resource;
    private $options;

    /**
     * @param \Symfony\Component\Config\Loader\LoaderInterface $loader
     * @param $resource
     * @param array $options
     */
    public function __construct(LoaderInterface $loader, $resource, array $options = array())
    {
        $this->loader = $loader;
        $this->resource = $resource;

        $this->setOptions($options);
    }

    /**
     * @param array $options
     * @throws \InvalidArgumentException
     */
    public function setOptions(array $options)
    {
        $this->options = array(
            'cache_dir'             => null,
            'debug'                 => false,
            'metadata_cache_file'   => 'project_vipx_bot_detect_metadata.php',
            'metadata_dumper_class' => 'Vipx\\BotDetectBundle\\Bot\\Metadata\\Dumper\\MetadataDumper',
        );

        // check option names and live merge, if errors are encountered Exception will be thrown
        $invalid = array();
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
            } else {
                $invalid[] = $key;
            }
        }

        if ($invalid) {
            throw new \InvalidArgumentException(sprintf('The BotDetector does not support the following options: "%s".', implode('\', \'', $invalid)));
        }
    }

    /**
     * @return \Vipx\BotDetectBundle\Bot\Metadata\Metadata[]
     */
    public function getMetadatas()
    {
        if (null !== $this->metadatas) {
            return $this->metadatas;
        }

        if (null === $this->options['cache_dir'] || null === $this->options['metadata_cache_file']) {
            /** @var $metadataCollection \Vipx\BotDetectBundle\Bot\Metadata\MetadataCollection */
            $metadataCollection = $this->loader->load($this->resource);

            return $this->metadatas = $metadataCollection->getMetadatas();
        }

        $cache = new ConfigCache($this->options['cache_dir'] . '/' . $this->options['metadata_cache_file'], $this->options['debug']);

        if (!$cache->isFresh()) {
            /** @var $metadataCollection \Vipx\BotDetectBundle\Bot\Metadata\MetadataCollection */
            $metadataCollection = $this->loader->load($this->resource);
            $dumperClass = $this->options['metadata_dumper_class'];

            /** @var $dumper \Vipx\BotDetectBundle\Bot\Metadata\Dumper\MetadataDumper */
            $dumper = new $dumperClass($metadataCollection->getMetadatas());

            $cache->write($dumper->dump(), $metadataCollection->getResources());
        }

        return $this->metadatas = require $cache->getPath();
    }

    /**
     * {@inheritdoc}
     */
    public function detect(Request $request)
    {
        $agent = $request->server->get('HTTP_USER_AGENT');
        $ip = $request->getClientIp();

        foreach ($this->getMetadatas() as $metadata) {
            if ($metadata->match($agent, $ip)) {
                return $metadata;
            }
        }

        return null;
    }

}
