<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Category;
use App\Modules\Identity\Models\User;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * Two categories (Programming > PHP) and an authenticated user.
 *
 * @return array{headers: array{Authorization: string}, root: Category, child: Category}
 */
function categoryFixture(): array
{
    $root = Category::query()->create(['name' => 'Programming', 'slug' => 'programming', 'position' => 1, 'is_active' => true]);
    $child = Category::query()->create(['name' => 'PHP', 'slug' => 'php', 'parent_id' => $root->id, 'position' => 1, 'is_active' => true]);

    return ['headers' => bearer(apiLogin(User::factory()->create()->email)['access_token']), 'root' => $root, 'child' => $child];
}

describe('update (partial)', function () {
    it('updates only the name and keeps the other fields', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        putJson("/api/category/{$child->id}", ['name' => 'PHP 8'], $headers)->assertOk();

        $child = $child->fresh();
        expect($child?->name)->toBe('PHP 8')
            ->and($child?->parent_id)->toBe($root->id)
            ->and($child?->is_active)->toBeTrue()
            ->and($child?->position)->toBe(1);
    });

    it('does not move a category to the root when parent_id is not sent', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        putJson("/api/category/{$child->id}", ['name' => 'PHP 8', 'is_active' => false, 'position' => 2], $headers)
            ->assertOk();

        expect($child->fresh()?->parent_id)->toBe($root->id);
    });

    it('moves a category to the root when parent_id is explicitly null', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        putJson("/api/category/{$child->id}", ['parent_id' => null], $headers)->assertOk();

        expect($child->fresh()?->parent_id)->toBeNull();
    });

    it('rejects a null name', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        putJson("/api/category/{$child->id}", ['name' => null], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });
});

describe('store is_active', function () {
    it('requires is_active', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        postJson('/api/category', ['name' => 'Design', 'position' => 1], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    });

    it('accepts boolean-like values', function (mixed $value, bool $expected) {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        postJson('/api/category', ['name' => 'Design', 'position' => 1, 'is_active' => $value], $headers)
            ->assertCreated();

        expect(Category::query()->where('slug', 'design')->sole()->is_active)->toBe($expected);
    })->with([
        'true' => [true, true],
        'false' => [false, false],
        '"1"' => ['1', true],
        '"0"' => ['0', false],
        '1' => [1, true],
        '0' => [0, false],
    ]);
});

describe('tree rules', function () {
    it('rejects a duplicate name among siblings with 422', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        postJson('/api/category', ['name' => 'PHP', 'parent_id' => $root->id, 'position' => 2, 'is_active' => true], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });

    it('allows the same name in another branch', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        $design = Category::query()->create(['name' => 'Design', 'slug' => 'design', 'position' => 2, 'is_active' => true]);

        postJson('/api/category', ['name' => 'PHP', 'parent_id' => $design->id, 'position' => 1, 'is_active' => true], $headers)
            ->assertCreated();
    });

    it('rejects a duplicate name at the top level', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        postJson('/api/category', ['name' => 'Programming', 'position' => 2, 'is_active' => true], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });

    it('rejects moving into a parent that already has a sibling with the same slug', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        $other = Category::query()->create(['name' => 'Other', 'slug' => 'other', 'position' => 2, 'is_active' => true]);
        $otherPhp = Category::query()->create(['name' => 'PHP', 'slug' => 'php', 'parent_id' => $other->id, 'position' => 1, 'is_active' => true]);

        putJson("/api/category/{$otherPhp->id}", ['parent_id' => $root->id], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });

    it('rejects a non-existent parent', function (string $method) {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        $response = $method === 'store'
            ? postJson('/api/category', ['name' => 'X', 'parent_id' => 999999, 'position' => 1, 'is_active' => true], $headers)
            : putJson("/api/category/{$child->id}", ['parent_id' => 999999], $headers);

        $response->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    })->with(['store', 'update']);

    it('rejects a category as its own parent', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        putJson("/api/category/{$root->id}", ['parent_id' => $root->id], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    });

    it('rejects moving a category under its own descendant', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        $grandchild = Category::query()->create(['name' => 'Laravel', 'slug' => 'laravel', 'parent_id' => $child->id, 'position' => 1, 'is_active' => true]);

        putJson("/api/category/{$root->id}", ['parent_id' => $grandchild->id], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        expect($root->fresh()?->parent_id)->toBeNull();
    });

    it('rejects a name that produces an empty slug', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        postJson('/api/category', ['name' => '!!!', 'position' => 1, 'is_active' => true], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        assertDatabaseCount('categories', 2);
    });
});

describe('destroy', function () {
    it('refuses to delete a category that has subcategories', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        deleteJson("/api/category/{$root->id}", [], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');

        assertDatabaseHas('categories', ['id' => $root->id]);
    });

    it('deletes a leaf category', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        deleteJson("/api/category/{$child->id}", [], $headers)->assertOk();

        expect(Category::query()->whereKey($child->id)->exists())->toBeFalse();
    });
});

describe('index filters', function () {
    it('ignores keys that are not filters instead of crashing', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        getJson('/api/category?category_search=1', $headers)->assertOk();
        getJson('/api/category?anything=1', $headers)->assertOk();
    });

    it('rejects an array as the name filter', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        getJson('/api/category?name[]=x', $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });

    it('drops unvalidated keys with array values', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        getJson('/api/category?Name[]=x', $headers)->assertOk();
    });

    it('searches by name case-insensitively', function () {
        ['headers' => $headers, 'root' => $root, 'child' => $child] = categoryFixture();
        $names = collect((array) getJson('/api/category?name=php', $headers)->assertOk()->json('data'))->pluck('name')->all();

        expect($names)->toBe(['PHP']);
    });

    it('does not filter by is_active when the value is empty', function () {
        ['headers' => $headers] = categoryFixture();
        Category::query()->create(['name' => 'Hidden', 'slug' => 'hidden', 'position' => 2, 'is_active' => false]);

        getJson('/api/category?is_active=', $headers)->assertOk()->assertJsonCount(3, 'data');
        getJson('/api/category?is_active=0', $headers)->assertOk()->assertJsonCount(1, 'data');
    });
});
