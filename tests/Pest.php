<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit/Jobs', 'Unit/Mail', 'Unit/Models', 'Unit/Seeders', 'Unit/Services');

// Real MySQL, rows committed for real (parallel processes must see them): truncate between tests
// instead of wrapping each one in a transaction. Only phpunit-mysql.xml includes this folder.
pest()->extend(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->group('mysql')
    ->in('Mysql');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Logs an admin in on the web guard (a fresh active one unless given) and returns the test case.
 */
function actingAsAdmin(?User $admin = null): TestCase
{
    return test()->actingAs($admin ?? User::factory()->create());
}

/**
 * A fake uploaded image of the given pixel size and file size in KB.
 */
function fakeImage(int $width, int $height, int $kb = 100): UploadedFile
{
    return UploadedFile::fake()->image('image.jpg', $width, $height)->size($kb);
}
