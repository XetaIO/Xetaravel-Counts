<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Xetaio\Counts\Concerns\HasBelongsToManyCounts;

class Material extends Model
{
    protected $table = 'materials';

    protected $fillable = ['name', 'parts_count'];

    public function parts()
    {
        return $this->belongsToMany(Part::class, 'material_part')
            ->using(MaterialPart::class)
            ->withTimestamps();
    }
}

class Part extends Model
{
    protected $table = 'parts';

    protected $fillable = ['name', 'materials_count'];

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'material_part')
            ->using(MaterialPart::class)
            ->withTimestamps();
    }
}

class MaterialPart extends Pivot
{
    use HasBelongsToManyCounts;

    protected $table = 'material_part';

    protected static array $countsConfig = [
        'material' => 'parts_count',
        'part'     => 'materials_count',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function part()
    {
        return $this->belongsTo(Part::class, 'part_id');
    }
}

it('increments counts when a relation is attached', function () {
    $material = Material::create(['name' => 'Machine A'])->refresh();
    $part = Part::create(['name' => 'Piece X'])->refresh();


    expect($material->parts_count)->toBe(0);
    expect($part->materials_count)->toBe(0);

    $material->parts()->attach($part->id);

    $material->refresh();
    $part->refresh();

    expect($material->parts_count)->toBe(1);
    expect($part->materials_count)->toBe(1);
});

it('decrements counts when a relation is detached', function () {
    $material = Material::create(['name' => 'Machine A']);
    $part     = Part::create(['name' => 'Piece X']);

    $material->parts()->attach($part->id);

    $material->refresh();
    $part->refresh();

    expect($material->parts_count)->toBe(1);
    expect($part->materials_count)->toBe(1);

    $material->parts()->detach($part->id);

    $material->refresh();
    $part->refresh();

    expect($material->parts_count)->toBe(0);
    expect($part->materials_count)->toBe(0);
});

it('handles sync calls correctly', function () {
    $material = Material::create(['name' => 'Machine A']);

    $part1 = Part::create(['name' => 'Piece X']);
    $part2 = Part::create(['name' => 'Piece Y']);

    // sync part1
    $material->parts()->sync([$part1->id]);

    $material->refresh();
    $part1->refresh();
    $part2->refresh();

    expect($material->parts_count)->toBe(1);
    expect($part1->materials_count)->toBe(1);
    expect($part2->materials_count)->toBe(0);

    // sync part1 + part2
    $material->parts()->sync([$part1->id, $part2->id]);

    $material->refresh();
    $part1->refresh();
    $part2->refresh();

    expect($material->parts_count)->toBe(2);
    expect($part1->materials_count)->toBe(1);
    expect($part2->materials_count)->toBe(1);

    // sync vide = tout détaché
    $material->parts()->sync([]);

    $material->refresh();
    $part1->refresh();
    $part2->refresh();

    expect($material->parts_count)->toBe(0);
    expect($part1->materials_count)->toBe(0);
    expect($part2->materials_count)->toBe(0);
});