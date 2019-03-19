<?php
namespace App\Controller\Transformer;

use App\Domain\User;

class UserTransformer
{
    public function transform(User $user): array
    {
        return [
            'id' => $user->getId()->toString(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
        ];
    }
}
