<?php

namespace Tests\Parser;

use PHPUnit\Framework\TestCase;
use PsrJwt\Parser\Parse;
use Psr\Http\Message\ServerRequestInterface;
use PsrJwt\Parser\Bearer;
use PsrJwt\Parser\Cookie;
use PsrJwt\Parser\Body;
use PsrJwt\Parser\Query;
use ReflectionMethod;
use Mockery as m;
use stdClass;

class ParseTest extends TestCase
{
    /**
     * @covers PsrJwt\Parser\Parse::__construct
     */
    public function testParse()
    {
        $parse = new Parse(['token_key' => 'jwt']);
        $this->assertInstanceOf(Parse::class, $parse);
    }

    /**
     * @covers PsrJwt\Parser\Parse::findToken
     * @covers PsrJwt\Parser\Parse::addParser
     * @uses PsrJwt\Parser\Parse
     * @uses PsrJwt\Parser\Bearer
     */
    public function testFindToken()
    {
        $request = m::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeader')
            ->with('authorization')
            ->once()
            ->andReturn(['Bearer abc.def.ghi']);

        $parse = new Parse(['token_key' => 'jwt']);
        $parse->addParser(Bearer::class);

        $result = $parse->findToken($request);

        $this->assertSame('abc.def.ghi', $result);
    }

    /**
     * @covers PsrJwt\Parser\Parse::findToken
     * @covers PsrJwt\Parser\Parse::addParser
     * @uses PsrJwt\Parser\Parse
     * @uses PsrJwt\Parser\Bearer
     * @uses PsrJwt\Parser\Body
     * @uses PsrJwt\Parser\Query
     */
    public function testFindTokenMultiParser()
    {
        $request = m::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeader')
            ->with('authorization')
            ->once()
            ->andReturn([]);
        $request->shouldReceive('getParsedBody')
            ->once()
            ->andReturn(['jwt' => 'abc.123.ghi']);

        $parse = new Parse(['token_key' => 'jwt']);
        $parse->addParser(Bearer::class);
        $parse->addParser(Body::class);
        $parse->addParser(Query::class);

        $result = $parse->findToken($request);

        $this->assertSame('abc.123.ghi', $result);
    }

    /**
     * @covers PsrJwt\Parser\Parse::findToken
     * @uses PsrJwt\Parser\Parse
     */
    public function testFindTokenFail()
    {
        $request = m::mock(ServerRequestInterface::class);

        $parse = new Parse(['token_key' => 'jwt']);
        $result = $parse->findToken($request);

        $this->assertEmpty($result);
    }

    public function tearDown()
    {
        m::close();
    }
}
