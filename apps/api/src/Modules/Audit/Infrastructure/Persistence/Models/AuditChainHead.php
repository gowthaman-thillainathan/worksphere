<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per audit chain holding the latest hash. Locking this row
 * serialises appends, so concurrent writers can never fork the chain.
 *
 * @property string $chain
 * @property string|null $last_hash
 */
final class AuditChainHead extends Model
{
    public const DEFAULT_CHAIN = 'global';

    public const CREATED_AT = null;

    public $incrementing = false;

    protected $table = 'audit_chain_heads';

    protected $primaryKey = 'chain';

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = ['chain', 'last_hash'];
}
