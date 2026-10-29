<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CategoryModel — categories with listing counts. Ported from the Discover
 * taxonomy: top-level categories (parent = 0) each with their children.
 */
class CategoryModel extends Model
{
    protected $table         = 'category';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'slug', 'parent', 'thumbnail'];

    /** Top-level categories (parent 0/empty), each with a listing count. */
    public function topLevel(int $limit = 40): array
    {
        $rows = $this->where('parent', 0)->orWhere('parent', null)->orderBy('name', 'ASC')->findAll($limit);
        foreach ($rows as &$r) {
            $r['listing_count'] = $this->countForCategory((int) $r['id']);
            $r['sub_count']     = $this->where('parent', (int) $r['id'])->countAllResults();
        }
        return $rows;
    }

    /** Children of a category. */
    public function children(int $parentId): array
    {
        return $this->where('parent', $parentId)->orderBy('name', 'ASC')->findAll();
    }

    public function bySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    /** Count active listings whose categories JSON contains this id. */
    public function countForCategory(int $id): int
    {
        $db = \Config\Database::connect();
        // Property counts here too — it is listed on the category page like
        // anything else, just on a different card.
        return $db->table('listing')
            ->where('status', 'active')
            ->groupStart()
                ->like('categories', '"' . $id . '"')
                ->orLike('categories', '[' . $id . ']')
            ->groupEnd()
            ->countAllResults();
    }
}
