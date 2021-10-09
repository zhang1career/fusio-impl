<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2021 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Impl\Tests\Service\Event\Sender;

use Fusio\Impl\Service\Event\Message;
use Fusio\Impl\Service\Event\Sender\Noop;
use PHPUnit\Framework\TestCase;

/**
 * NoopTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org
 */
class NoopTest extends TestCase
{
    public function testAccept()
    {
        $sender = new Noop();

        $this->assertTrue($sender->accept(new \stdClass()));
    }

    public function testSend()
    {
        $dispatcher = new \stdClass();
        $message    = new Message('http://google.com', \json_encode(['foo' => 'bar']));

        $sender = new Noop();
        $code   = $sender->send($dispatcher, $message);

        $this->assertEquals(200, $code);
    }
}
