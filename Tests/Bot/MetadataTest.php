<?php

/*
 * This file is part of the VipxBotDetectBundle package.
 *
 * (c) Lennart Hildebrandt <http://github.com/lennerd>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vipx\BotDetectBundle\Tests\Bot;

use Vipx\BotDetectBundle\Bot\Metadata\Metadata;

class MetadataTest extends \PHPUnit_Framework_TestCase
{

    public function testMatchExact()
    {
        $metadata = $this->createMetadata('test', null);

        $this->assertTrue($metadata->match('test', '127.0.0.1'));
    }

    public function testMatchRegexp()
    {
        $metadata = $this->createMetadata('test', null, Metadata::AGENT_MATCH_REGEXP);

        $this->assertTrue($metadata->match('test-agent', '127.0.0.1'));
    }

    public function testMatchIp()
    {
        $metadata = $this->createMetadata('test', '127.0.0.1');

        $this->assertTrue($metadata->match('test', '127.0.0.1'));
    }

    public function testMatchIpArray()
    {
        $metadata = $this->createMetadata('test', array('127.0.0.0', '127.0.0.1'));

        $this->assertTrue($metadata->match('test', '127.0.0.1'));
    }

    private function createMetadata($agent, $ip, $agentMatch = Metadata::AGENT_MATCH_EXACT)
    {
        return new Metadata('TestBot', $agent, $ip, Metadata::TYPE_BOT, array(), $agentMatch);
    }

}
