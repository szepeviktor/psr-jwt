<?php

declare(strict_types=1);

namespace PsrJwt;

use PsrJwt\JwtFactory;
use PsrJwt\JwtValidate;
use PsrJwt\JwtParse;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReallySimpleJWT\Exception\ValidateException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Throwable;

class JwtAuthHandler implements RequestHandlerInterface
{
    private $tokenKey;

    private $secret;

    public function __construct(string $tokenKey, string $secret)
    {
        $this->tokenKey = $tokenKey;

        $this->secret = $secret;
    }

    protected function getSecret(): string
    {
        return $this->secret;
    }

    protected function validate(string $token): ResponseInterface
    {
        $parse = JwtFactory::parser($token, $this->getSecret());

        $validate = new JwtValidate($parse);

        $validationState = $validate->validate();

        $validationState = $validate->validateNotBefore($validationState);

        return $this->validationResponse(
            $validationState['code'],
            $validationState['message']
        );
    }

    private function validationResponse(int $code, string $message): ResponseInterface
    {
        $factory = new Psr17Factory();

        if (in_array($code, [1, 2, 3, 4, 5], true)) {
            return $factory->createResponse(401, 'Unauthorized: ' . $message);
        }

        return $factory->createResponse(200, 'Ok');
    }

    protected function hasJwt(array $data): bool
    {
        return array_key_exists($this->tokenKey, $data);
    }

    protected function getToken(ServerRequestInterface $request): string
    {
        $parse = new JwtParse($this->tokenKey);

        $token = $parse->findToken($request);

        if ($this->hasJwt($token)) {
            return $token[$this->tokenKey];
        }

        throw new ValidateException('JSON Web Token not set.', 11);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $token = $this->getToken($request);
        } catch (ValidateException $e) {
            $factory = new Psr17Factory();
            return $factory->createResponse(400, 'Bad Request: ' . $e->getMessage());
        }

        return $this->validate($token);
    }
}
