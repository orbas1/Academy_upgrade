<?php

namespace App\Domain\Search\DataSources;

class MemberDataSource extends AbstractSearchDataSource
{
    protected function table(): string
    {
        return 'users';
    }
}
