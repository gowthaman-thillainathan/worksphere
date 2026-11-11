<?php

declare(strict_types=1);

use Meridian\Shared\Domain\ValueObjects\SortDirection;
use Meridian\Shared\Http\Query\QueryString;

it('parses comma-separated lists, ignoring blanks and whitespace', function (): void {
    expect(QueryString::list(' active, on_leave,,notice '))->toBe(['active', 'on_leave', 'notice'])
        ->and(QueryString::list(''))->toBe([]);
});

it('parses sort instructions with a minus prefix for descending', function (): void {
    $sort = QueryString::sort('last_name,-hire_date');

    expect($sort)->toHaveCount(2)
        ->and($sort[0]->field)->toBe('last_name')
        ->and($sort[0]->direction)->toBe(SortDirection::Ascending)
        ->and($sort[1]->field)->toBe('hire_date')
        ->and($sort[1]->direction)->toBe(SortDirection::Descending)
        ->and(QueryString::sortFields('-a,b'))->toBe(['a', 'b']);
});
