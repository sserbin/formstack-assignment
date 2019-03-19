<?php

namespace App\Controller;

use App\Controller\Transformer\UserTransformer;
use App\Domain\User;
use App\Service\Exception\UniqueConstraintViolation;
use App\Service\UserMapperInterface;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

class UserController
{
    /** @var UserMapperInterface */
    private $userMapper;

    /** @var UserTransformer */
    private $userTransformer;

    /** @var string */
    private $avatarDir;

    public function __construct(
        UserMapperInterface $userMapper,
        UserTransformer $userTransformer,
        string $avatarDir
    ) {
        $this->userMapper = $userMapper;
        $this->userTransformer = $userTransformer;
        $this->avatarDir = $avatarDir;
    }

    public function getOne(Request $request, Response $response): Response
    {
        /** @var \Slim\Route */
        $route = $request->getAttribute('route');
        /** @var ?string */
        $id = $route->getArgument('id');

        try {
            $user = $this->assertUser($id);
        } catch (InvalidArgumentException $e) {
            return $response->withStatus(404);
        }

        return $response->withJson($this->userTransformer->transform($user));
    }

    public function getAll(Request $request, Response $response): Response
    {
        $users = $this->userMapper->getAll();

        return $response->withJson(array_map([$this->userTransformer, 'transform'], $users));
    }

    public function create(Request $request, Response $response): Response
    {
        /** @var array */
        $body = $request->getParsedBody();
        if ($failures = $this->validateRequestUserData($body)) {
            return $response->withStatus(422, join(',', $failures));
        }

        try {
            $user = new User($body['firstName'], $body['lastName'], $body['email'], $body['password']);
        } catch (InvalidArgumentException $e) {
            return $response->withStatus(422, 'invalid email');
        }

        try {
            $this->userMapper->add($user);
        } catch (UniqueConstraintViolation $e) {
            return $response->withStatus(409, 'duplicate email');
        }

        $response = $response->withStatus(201);
        $response = $response->withHeader('Location', '/users/' . $user->getId()->toString());
        return $response;
    }

    public function update(Request $request, Response $response): Response
    {
        /** @var \Slim\Route */
        $route = $request->getAttribute('route');
        /** @var ?string */
        $id = $route->getArgument('id');

        try {
            $user = $this->assertUser($id);
        } catch (InvalidArgumentException $e) {
            return $response->withStatus(404);
        }

        /** @var array */
        $body = $request->getParsedBody();
        if ($failures = $this->validateRequestUserData($body)) {
            return $response->withStatus(422, join(',', $failures));
        }

        try {
            $user->update($body['firstName'], $body['lastName'], $body['email'], $body['password']);
        } catch (InvalidArgumentException $e) {
            return $response->withStatus(422, 'invalid email');
        }

        try {
            $this->userMapper->update($user);
        } catch (UniqueConstraintViolation $e) {
            return $response->withStatus(409, 'duplicate email');
        }

        return $response->withJson($this->userTransformer->transform($user));
    }

    public function changeAvatar(Request $request, Response $response): Response
    {
        /** @var \Slim\Route */
        $route = $request->getAttribute('route');
        /** @var ?string */
        $id = $route->getArgument('id');

        try {
            $user = $this->assertUser($id);
        } catch (InvalidArgumentException $e) {
            return $response->withStatus(404);
        }

        /** @var array<string,UploadedFile> */
        $uploadedFiles = $request->getUploadedFiles();
        $avatar = $uploadedFiles['avatar'] ?? null;
        if (!$avatar || $avatar->getError() !== UPLOAD_ERR_OK) {
            return $response->withStatus(422, 'avatar wasnt uploaded');
        }

        if (!$this->isUploadedFileImage($avatar)) {
            return $response->withStatus(422, 'uploaded avatar is not an image');
        }

        $filename = $this->generateAvatarFilename($avatar);
        $avatar->moveTo($this->avatarDir . DIRECTORY_SEPARATOR . $filename);

        $user->changeAvatar($filename);

        $this->userMapper->update($user);

        return $response->withJson($this->userTransformer->transform($user));
    }

    public function delete(Request $request, Response $response): Response
    {
        /** @var \Slim\Route */
        $route = $request->getAttribute('route');
        /** @var ?string */
        $id = $route->getArgument('id');

        try {
            $user = $this->assertUser($id);
        } catch (InvalidArgumentException $e) {
            return $response->withStatus(404);
        }

        $this->userMapper->delete($user);

        return $response->withStatus(204);
    }

    /**
     * @psalm-assert \array{firstName:string,lastName:string,email:string,password:string} $body
     */
    private function validateRequestUserData(array $body): array
    {
        $errors = [];

        foreach (['firstName', 'lastName', 'email', 'password'] as $field) {
            if (!isset($body[$field]) || empty($body[$field])) {
                $errors[] = "$field required";
            }
        }
        return $errors;
    }

    private function assertUser(?string $id): User
    {
        if (empty($id)) {
            throw new InvalidArgumentException;
        }

        $user = $this->userMapper->find($id);
        if (!$user instanceof User) {
            throw new InvalidArgumentException;
        }
        return $user;
    }

    private function isUploadedFileImage(UploadedFile $file): bool
    {
        $allowed = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF];
        return in_array(exif_imagetype($file->file), $allowed);
    }

    private function generateAvatarFilename(UploadedFile $avatar): string
    {
        $extension = pathinfo($avatar->getClientFilename() ?? 'avatar.jpg', PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8));
        return sprintf('%s.%0.8s', $basename, $extension);
    }
}
