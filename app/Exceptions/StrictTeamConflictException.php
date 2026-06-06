<?php

namespace App\Exceptions;

use App\Models\Relations\TeamMembership;
use DomainException;

/**
 * Thrown when a product would end up in more than one strict team.
 *
 * A product may belong to AT MOST ONE strict team (and any number of liberal teams),
 * which is what keeps strict teams a disjoint partition of the catalogue. The guard
 * lives on {@see TeamMembership} so it fires on every attach/sync,
 * whether from a seeder, factory, tinker, or the Filament Products form.
 */
class StrictTeamConflictException extends DomainException
{
    public static function make(): self
    {
        return new self(
            'A product can belong to at most one strict team. Remove it from the other strict team first, or make this team liberal.'
        );
    }
}
