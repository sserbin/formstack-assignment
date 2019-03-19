<?php
namespace Tests;

use App\Controller\UserController;
use App\Domain\User;
use InvalidArgumentException;
use League\Container\Container as LeagueContainer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\UuidInterface;
use Slim\App;
use Slim\Container as SlimContainer;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Stream;
use Slim\Http\UploadedFile;
use Slim\Http\Uri;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class UserControllerTest extends TestCase
{
    /** @var App */
    private $app;

    public function setUp(): void
    {
        /** @var LeagueContainer */
        $container = require dirname(__DIR__) . '/config/container.php';

        $app = $this->app = new App([
            'settings' => [
                'displayErrorDetails'  => true,
            ],
        ]);

        /** @var UserController */
        $userController = $container->get(UserController::class);

        /** @var callable */
        $configureRoutes = require dirname(__DIR__) . '/config/routes.php';

        $configureRoutes($app, $container);
    }

    /** @test */
    public function creatingWithEmptyUserDataRejected(): void
    {
        $payload = [];

        $response = $this->process($this->request('POST', '/users', $payload));

        $this->assertEquals(422, $response->getStatusCode());
    }

    /** @test */
    public function creatingWithDuplicateEmailRejected(): void
    {
        // creating first user
        $payload = [
            'firstName' => 'john',
            'lastName' => 'doe',
            'email' => bin2hex(random_bytes(10)) . '@example.com',
            'password' => 'pass',
        ];

        $response = $this->process($this->request('POST', '/users', $payload));
        $this->assertEquals(201, $response->getStatusCode());

        // creating second user should fail
        $response = $this->process($this->request('POST', '/users', $payload));
        $this->assertEquals(409, $response->getStatusCode());
    }

    /** @test */
    public function canCreateWhenRequiredDataProvided(): void
    {
        $payload = [
            'firstName' => 'john',
            'lastName' => 'doe',
            'email' => bin2hex(random_bytes(10)) . '@example.com',
            'password' => 'pass',
        ];

        $response = $this->process($this->request('POST', '/users', $payload));

        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function canRetrieveOne(): void
    {
        // create a new user
        $payload = [
            'firstName' => 'john',
            'lastName' => 'doe',
            'email' => $email = bin2hex(random_bytes(10)) . '@example.com',
            'password' => 'pass',
        ];

        $response = $this->process($this->request('POST', '/users', $payload));

        $uri = $response->getHeader('Location')[0];

        // attempt to retrieve it
        $response = $this->process($this->request('GET', $uri, []));
        $responseJson = $this->parseResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($email, $responseJson['email']);
    }

    /** @test */
    public function canDelete(): void
    {
        // create a new user
        $payload = [
            'firstName' => 'john',
            'lastName' => 'doe',
            'email' => $email = bin2hex(random_bytes(10)) . '@example.com',
            'password' => 'pass',
        ];

        $response = $this->process($this->request('POST', '/users', $payload));

        $uri = $response->getHeader('Location')[0];

        // attempt to delete it
        $response = $this->process($this->request('DELETE', $uri, []));

        $this->assertEquals(204, $response->getStatusCode());
    }

    /** @test */
    public function canUpdate(): void
    {
        // create a new user
        $payload = [
            'firstName' => 'john',
            'lastName' => 'doe',
            'email' => $email = bin2hex(random_bytes(10)) . '@example.com',
            'password' => 'pass',
        ];

        $response = $this->process($this->request('POST', '/users', $payload));

        $uri = $response->getHeader('Location')[0];

        // attempt to update it
        $payload['firstName'] = 'john-updated';
        $payload['lastName'] = 'doe-updated';
        $response = $this->process($this->request('PUT', $uri, $payload));
        $responseJson = $this->parseResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('john-updated', $responseJson['firstName']);
        $this->assertEquals('doe-updated', $responseJson['lastName']);
    }

    /** @test */
    public function canChangeAvatar(): void
    {
        // create a new user
        $payload = [
            'firstName' => 'john',
            'lastName' => 'doe',
            'email' => $email = bin2hex(random_bytes(10)) . '@example.com',
            'password' => 'pass',
        ];

        $response = $this->process($this->request('POST', '/users', $payload));

        $uri = $response->getHeader('Location')[0];

        // upload avatar
        $request = $this->request('POST', $uri . '/avatar', []);

        $tmpfile = tempnam(sys_get_temp_dir(), 'fixture-avatar');
        file_put_contents($tmpfile, file_get_contents(__DIR__.'/fixtures/test.png'));
        $request = $request->withUploadedFiles(['avatar' => new UploadedFile($tmpfile, 'test.png')]);
        $response = $this->process($request);
        $responseJson = $this->parseResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($responseJson['avatar']);
    }

    private function process(ServerRequestInterface $request): ResponseInterface
    {
        return $this->app->process($request, new Response);
    }

    private function request(string $method, string $uri, array $payload): ServerRequestInterface
    {
        $stream = new Stream(fopen('data://application/json,' . json_encode($payload), 'r'));
        $uri = new Uri('http', 'test.test', null, $uri);
        $request = new Request($method, $uri, new Headers(['Content-type' => 'application/json']), [], [], $stream);
        return $request;
    }

    private function parseResponse(ResponseInterface $response): array
    {
        $responseBody = $response->getBody();
        if ($responseBody->isSeekable()) {
            $responseBody->seek(0);
        }
        $contents = $responseBody->getContents();
        /** @var array */
        $decoded = json_decode($contents, true);

        // could have used JSON_THROW_ON_ERROR if were requiring 7.3+ only
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(sprintf(
                'failed json de-coding api response, error:%s, response:%s',
                json_last_error_msg(),
                var_export($contents, true)
            ));
        }

        return $decoded;
    }
}
