<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Models\Department;

/**
 * @extends Factory<Department>
 */
final class DepartmentFactory extends Factory
{
    /**
     * @var class-string<Department>
     */
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('???'),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }

    public function under(Department $parent): self
    {
        return $this->state(['parent_id' => $parent->id]);
    }
}
