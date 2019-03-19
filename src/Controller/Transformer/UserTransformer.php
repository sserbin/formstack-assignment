<?php
namespace App\Controller\Transformer;

use App\Domain\User;

class UserTransformer
{
    /** @var string */
    private $avatarBaseUrl;

    public function __construct(string $avatarBaseUrl)
    {
        $this->avatarBaseUrl = $avatarBaseUrl;
    }

    public function transform(User $user): array
    {
        $avatarUrl = null;
        if ($avatar = $user->getAvatar()) {
            $avatarUrl = $this->avatarBaseUrl . '/' . $avatar;
        }

        return [
            'id' => $user->getId()->toString(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'avatar' => $avatarUrl,
        ];
    }
}
