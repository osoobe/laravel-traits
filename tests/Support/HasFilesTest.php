<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Osoobe\LaravelTraits\Support\HasFiles;
use Osoobe\LaravelTraits\Tests\TestCase;

class FileModel extends Model
{
    use HasFiles;

    protected $table = 'file_models';
    protected $guarded = [];
}

class HasFilesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('private');

        Schema::create('file_models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::drop('file_models');
        parent::tearDown();
    }

    // --- kekabPath ---

    public function test_kekab_path_returns_kebab_class_name_with_id(): void
    {
        $model = FileModel::create(['name' => 'test']);

        $path = $model->kekabPath();

        // Expected: "file-models/file-model-{id}"
        $this->assertStringContainsString('file-model', $path);
        $this->assertStringContainsString((string) $model->id, $path);
    }

    public function test_kekab_path_uses_plural_for_directory(): void
    {
        $model = FileModel::create(['name' => 'test']);

        $path = $model->kekabPath();

        // The directory part should be plural
        $parts = explode('/', $path);
        $this->assertCount(2, $parts);
        $this->assertEquals('file-models', $parts[0]);
    }

    public function test_kekab_path_includes_model_id(): void
    {
        $model = FileModel::create(['name' => 'test']);

        $path = $model->kekabPath();

        $this->assertStringEndsWith("-{$model->id}", $path);
    }

    public function test_get_folder_path_is_alias_for_kekab_path(): void
    {
        $model = FileModel::create(['name' => 'test']);

        $this->assertEquals($model->kekabPath(), $model->getFolderPath());
    }

    // --- storeFile ---

    public function test_store_public_file_saves_to_public_disk(): void
    {
        $model = FileModel::create(['name' => 'test']);

        $result = $model->storePublicFile('document.txt', 'hello world');

        $this->assertTrue($result);

        $expectedPath = $model->kekabPath() . '/document.txt';
        Storage::disk('public')->assertExists($expectedPath);
    }

    public function test_store_private_file_saves_to_private_disk(): void
    {
        $model = FileModel::create(['name' => 'test']);

        $result = $model->storePrivateFile('secret.txt', 'confidential');

        $this->assertTrue($result);

        $expectedPath = $model->kekabPath() . '/secret.txt';
        Storage::disk('private')->assertExists($expectedPath);
    }

    public function test_store_file_saves_to_specified_disk(): void
    {
        $model = FileModel::create(['name' => 'test']);

        $model->storeFile('data.txt', 'content', 'public');

        Storage::disk('public')->assertExists($model->kekabPath() . '/data.txt');
    }

    // --- getStorePath ---

    public function test_get_store_path_returns_array_with_expected_keys(): void
    {
        $model = FileModel::create(['name' => 'test']);

        $storePath = $model->getStorePath('public');

        $this->assertIsArray($storePath);
        $this->assertArrayHasKey('disk_relative_path', $storePath);
        $this->assertArrayHasKey('disk', $storePath);
        $this->assertArrayHasKey('absolute_path', $storePath);
    }

    public function test_get_store_path_disk_relative_path_matches_kekab_path(): void
    {
        $model = FileModel::create(['name' => 'test']);

        $storePath = $model->getStorePath('public');

        $this->assertEquals($model->kekabPath(), $storePath['disk_relative_path']);
    }

    // --- getPublicFile / getPrivateFile ---

    public function test_get_public_file_returns_absolute_path_string(): void
    {
        $model = FileModel::create(['name' => 'test']);

        $path = $model->getPublicFile('photo.jpg');

        $this->assertIsString($path);
        $this->assertStringContainsString('photo.jpg', $path);
    }

    public function test_get_private_file_returns_absolute_path_string(): void
    {
        $model = FileModel::create(['name' => 'test']);

        $path = $model->getPrivateFile('report.pdf');

        $this->assertIsString($path);
        $this->assertStringContainsString('report.pdf', $path);
    }

    public function test_different_models_get_different_paths(): void
    {
        $model1 = FileModel::create(['name' => 'first']);
        $model2 = FileModel::create(['name' => 'second']);

        $this->assertNotEquals($model1->kekabPath(), $model2->kekabPath());
    }
}
