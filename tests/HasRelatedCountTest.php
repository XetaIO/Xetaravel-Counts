<?php

use Illuminate\Database\Eloquent\Model;
use Xetaio\Counts\Concerns\HasRelatedCount;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = ['name', 'articles_count'];

    public function articles()
    {
        return $this->hasMany(Article::class, 'category_id');
    }
}

class Article extends Model
{
    use HasRelatedCount;

    protected $table = 'articles';

    protected $fillable = ['title', 'category_id'];

    protected static array $countedRelations = [
        'category' => 'articles_count',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}

it('increments count on parent when child is created', function () {
    $category = Category::create(['name' => 'Cat A'])->refresh();

    expect($category->articles_count)->toBe(0);

    Article::create([
        'title'       => 'Article 1',
        'category_id' => $category->id,
    ]);

    $category->refresh();

    expect($category->articles_count)->toBe(1);
});

it('decrements count on parent when child is deleted', function () {
    $category = Category::create(['name' => 'Cat A']);

    $article = Article::create([
        'title'       => 'Article 1',
        'category_id' => $category->id,
    ]);

    $category->refresh();
    expect($category->articles_count)->toBe(1);

    $article->delete();
    $category->refresh();

    expect($category->articles_count)->toBe(0);
});

it('syncs counts when belongsTo relation changes', function () {
    $catA = Category::create(['name' => 'Cat A']);
    $catB = Category::create(['name' => 'Cat B']);

    $article = Article::create([
        'title'       => 'Article 1',
        'category_id' => $catA->id,
    ]);

    $catA->refresh();
    $catB->refresh();

    expect($catA->articles_count)->toBe(1);
    expect($catB->articles_count)->toBe(0);

    // On change la catégorie
    $article->update(['category_id' => $catB->id]);

    $catA->refresh();
    $catB->refresh();

    expect($catA->articles_count)->toBe(0);
    expect($catB->articles_count)->toBe(1);
});