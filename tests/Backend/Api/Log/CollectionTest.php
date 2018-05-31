<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Impl\Tests\Backend\Api\Log;

use Fusio\Impl\Tests\Fixture;
use PSX\Framework\Test\ControllerDbTestCase;

/**
 * CollectionTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class CollectionTest extends ControllerDbTestCase
{
    public function getDataSet()
    {
        return Fixture::getDataSet();
    }

    public function testDocumentation()
    {
        $response = $this->sendRequest('/doc/*/backend/log', 'GET', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ));

        $actual = (string) $response->getBody();
        $expect = <<<'JSON'
{
    "path": "\/backend\/log",
    "version": "*",
    "status": 1,
    "description": null,
    "schema": {
        "$schema": "http:\/\/json-schema.org\/draft-04\/schema#",
        "id": "urn:schema.phpsx.org#",
        "definitions": {
            "GET-query": {
                "type": "object",
                "title": "query",
                "properties": {
                    "startIndex": {
                        "type": "integer"
                    },
                    "count": {
                        "type": "integer"
                    },
                    "from": {
                        "type": "string",
                        "format": "date-time"
                    },
                    "to": {
                        "type": "string",
                        "format": "date-time"
                    },
                    "routeId": {
                        "type": "integer"
                    },
                    "appId": {
                        "type": "integer"
                    },
                    "userId": {
                        "type": "integer"
                    },
                    "ip": {
                        "type": "string"
                    },
                    "userAgent": {
                        "type": "string"
                    },
                    "method": {
                        "type": "string"
                    },
                    "path": {
                        "type": "string"
                    },
                    "header": {
                        "type": "string"
                    },
                    "body": {
                        "type": "string"
                    },
                    "search": {
                        "type": "string"
                    }
                }
            },
            "Log": {
                "type": "object",
                "title": "Log",
                "properties": {
                    "id": {
                        "type": "integer"
                    },
                    "ip": {
                        "type": "string"
                    },
                    "userAgent": {
                        "type": "string"
                    },
                    "method": {
                        "type": "string"
                    },
                    "path": {
                        "type": "string"
                    },
                    "header": {
                        "type": "string"
                    },
                    "body": {
                        "type": "string"
                    },
                    "date": {
                        "type": "string",
                        "format": "date-time"
                    },
                    "errors": {
                        "type": "array",
                        "items": {
                            "$ref": "#\/definitions\/Log_Error"
                        }
                    }
                }
            },
            "Log_Error": {
                "type": "object",
                "title": "Log Error",
                "properties": {
                    "message": {
                        "type": "string"
                    },
                    "trace": {
                        "type": "string"
                    },
                    "file": {
                        "type": "string"
                    },
                    "line": {
                        "type": "integer"
                    }
                }
            },
            "Log_Collection": {
                "type": "object",
                "title": "Log Collection",
                "properties": {
                    "totalResults": {
                        "type": "integer"
                    },
                    "startIndex": {
                        "type": "integer"
                    },
                    "entry": {
                        "type": "array",
                        "items": {
                            "$ref": "#\/definitions\/Log"
                        }
                    }
                }
            },
            "GET-200-response": {
                "$ref": "#\/definitions\/Log_Collection"
            }
        }
    },
    "methods": {
        "GET": {
            "queryParameters": "#\/definitions\/GET-query",
            "responses": {
                "200": "#\/definitions\/GET-200-response"
            }
        }
    },
    "links": [
        {
            "rel": "openapi",
            "href": "\/export\/openapi\/*\/backend\/log"
        },
        {
            "rel": "swagger",
            "href": "\/export\/swagger\/*\/backend\/log"
        },
        {
            "rel": "raml",
            "href": "\/export\/raml\/*\/backend\/log"
        }
    ]
}
JSON;

        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testGet()
    {
        $response = $this->sendRequest('/backend/log?from=2015-06-25T00:00:00&to=2015-06-25T23:59:59', 'GET', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ));

        $body = (string) $response->getBody();

        $expect = <<<'JSON'
{
    "totalResults": 2,
    "startIndex": 0,
    "itemsPerPage": 16,
    "entry": [
        {
            "id": 2,
            "appId": 3,
            "routeId": 1,
            "ip": "127.0.0.1",
            "userAgent": "Mozilla\/5.0 (Windows NT 6.3; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/43.0.2357.130 Safari\/537.36",
            "method": "GET",
            "path": "\/bar",
            "date": "2015-06-25T22:49:09Z"
        },
        {
            "id": 1,
            "appId": 3,
            "routeId": 1,
            "ip": "127.0.0.1",
            "userAgent": "Mozilla\/5.0 (Windows NT 6.3; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/43.0.2357.130 Safari\/537.36",
            "method": "GET",
            "path": "\/bar",
            "date": "2015-06-25T22:49:09Z"
        }
    ]
}
JSON;

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString($expect, $body, $body);
    }

    public function testGetSearch()
    {
        $response = $this->sendRequest('/backend/log?from=2015-06-25T00:00:00&to=2015-06-25T23:59:59&search=' . urlencode('/bar'), 'GET', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ));

        $body = (string) $response->getBody();

        $expect = <<<'JSON'
{
    "totalResults": 2,
    "startIndex": 0,
    "itemsPerPage": 16,
    "entry": [
        {
            "id": 2,
            "appId": 3,
            "routeId": 1,
            "ip": "127.0.0.1",
            "userAgent": "Mozilla\/5.0 (Windows NT 6.3; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/43.0.2357.130 Safari\/537.36",
            "method": "GET",
            "path": "\/bar",
            "date": "2015-06-25T22:49:09Z"
        },
        {
            "id": 1,
            "appId": 3,
            "routeId": 1,
            "ip": "127.0.0.1",
            "userAgent": "Mozilla\/5.0 (Windows NT 6.3; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/43.0.2357.130 Safari\/537.36",
            "method": "GET",
            "path": "\/bar",
            "date": "2015-06-25T22:49:09Z"
        }
    ]
}
JSON;

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString($expect, $body, $body);
    }

    public function testGetCount()
    {
        $response = $this->sendRequest('/backend/log?count=80&from=2015-06-25T00:00:00&to=2015-06-25T23:59:59', 'GET', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ));

        $body = (string) $response->getBody();

        $expect = <<<'JSON'
{
    "totalResults": 2,
    "startIndex": 0,
    "itemsPerPage": 80,
    "entry": [
        {
            "id": 2,
            "appId": 3,
            "routeId": 1,
            "ip": "127.0.0.1",
            "userAgent": "Mozilla\/5.0 (Windows NT 6.3; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/43.0.2357.130 Safari\/537.36",
            "method": "GET",
            "path": "\/bar",
            "date": "2015-06-25T22:49:09Z"
        },
        {
            "id": 1,
            "appId": 3,
            "routeId": 1,
            "ip": "127.0.0.1",
            "userAgent": "Mozilla\/5.0 (Windows NT 6.3; WOW64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/43.0.2357.130 Safari\/537.36",
            "method": "GET",
            "path": "\/bar",
            "date": "2015-06-25T22:49:09Z"
        }
    ]
}
JSON;

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString($expect, $body, $body);
    }

    public function testPost()
    {
        $response = $this->sendRequest('/backend/log', 'POST', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ), json_encode([
            'foo' => 'bar',
        ]));

        $body = (string) $response->getBody();

        $this->assertEquals(405, $response->getStatusCode(), $body);
    }

    public function testPut()
    {
        $response = $this->sendRequest('/backend/log', 'PUT', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ), json_encode([
            'foo' => 'bar',
        ]));

        $body = (string) $response->getBody();

        $this->assertEquals(405, $response->getStatusCode(), $body);
    }

    public function testDelete()
    {
        $response = $this->sendRequest('/backend/log', 'DELETE', array(
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Bearer da250526d583edabca8ac2f99e37ee39aa02a3c076c0edc6929095e20ca18dcf'
        ), json_encode([
            'foo' => 'bar',
        ]));

        $body = (string) $response->getBody();

        $this->assertEquals(405, $response->getStatusCode(), $body);
    }
}
