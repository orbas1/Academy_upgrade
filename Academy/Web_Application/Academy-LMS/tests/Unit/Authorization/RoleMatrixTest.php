<?php

namespace Tests\Unit\Authorization;

use App\Models\User;
use App\Support\Authorization\Resolvers\GlobalRoleResolver;
use App\Support\Authorization\Resolvers\OwnershipRoleResolver;
use App\Support\Authorization\RoleMatrix;
use Tests\TestCase;

class RoleMatrixTest extends TestCase
{
    private function makeMatrix(): RoleMatrix
    {
        return new RoleMatrix(
            config('authorization.matrix'),
            config('authorization.aliases'),
            config('authorization.default_role'),
            [
                new GlobalRoleResolver(),
                new OwnershipRoleResolver(),
            ]
        );
    }

    public function test_member_permissions(): void
    {
        $user = new User(['role' => 'student']);
        $matrix = $this->makeMatrix();

        $this->assertTrue($matrix->allows($user, 'community.view'));
        $this->assertTrue($matrix->allows($user, 'community.post'));
        $this->assertFalse($matrix->allows($user, 'community.moderate'));
    }

    public function test_admin_role_allows_everything(): void
    {
        $user = new User(['role' => 'admin']);
        $matrix = $this->makeMatrix();

        $this->assertTrue($matrix->allows($user, 'community.view'));
        $this->assertTrue($matrix->allows($user, 'member.ban'));
        $this->assertTrue($matrix->allows($user, 'paywall.manage'));
    }

    public function test_ownership_context_grants_update(): void
    {
        $user = new User(['role' => 'student']);
        $user->id = 1;
        $matrix = $this->makeMatrix();

        $post = new class($user->id) {
            public function __construct(private readonly int $ownerId)
            {
            }

            public function getAttribute(string $key): mixed
            {
                return match ($key) {
                    'user_id', 'author_id' => $this->ownerId,
                    default => null,
                };
            }
        };

        $this->assertTrue($matrix->allows($user, 'post.update', [
            'target' => $post,
            'is_owner' => true,
        ]));
    }
}
