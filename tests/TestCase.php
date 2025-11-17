<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;
use Xetaio\Counts\CountsServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CountsServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function setUpDatabase(): void
    {
        $schema = $this->app['db']->connection()->getSchemaBuilder();

        // Parent table: categories
        $schema->create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('articles_count')->default(0);
            $table->timestamps();
        });

        // Child table: articles
        $schema->create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });

        // Parent tables for belongsToMany: materials, parts
        $schema->create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('parts_count')->default(0);
            $table->timestamps();
        });

        $schema->create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('materials_count')->default(0);
            $table->timestamps();
        });

        $schema->create('material_part', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['material_id', 'part_id']);
        });
    }
}
