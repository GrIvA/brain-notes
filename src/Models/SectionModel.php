<?php

declare(strict_types=1);

namespace App\Models;

use Medoo\Medoo;

class SectionModel
{
    private Medoo $db;
    private string $table = 'sections';

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        return $this->db->get($this->table, '*', ['id' => $id]);
    }

    public function findByNotebookId(int $notebookId): array
    {
        return $this->db->select($this->table, '*', [
            'notebook_id' => $notebookId,
            'ORDER' => ['sort_order' => 'ASC']
        ]);
    }

    /**
     * Get tree structure for a notebook.
     */
    public function getTree(int $notebookId): array
    {
        $sections = $this->findByNotebookId($notebookId);
        return $this->buildTree($sections);
    }

    private function buildTree(array $elements, $parentId = null): array
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    public function create(array $data): int|string|null
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->id();
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = $this->db->update($this->table, $data, ['id' => $id]);
        return $result->rowCount() > 0;
    }

    /**
     * Move section to a new parent.
     * Includes cycle check.
     */
    public function move(int $id, ?int $newParentId): bool
    {
        if ($newParentId !== null) {
            if ($id === $newParentId) {
                return false;
            }
            if ($this->isDescendant($id, $newParentId)) {
                return false;
            }
        }

        return $this->update($id, ['parent_id' => $newParentId]);
    }

    /**
     * Check if potentialParentId is a descendant of sectionId.
     */
    private function isDescendant(int $sectionId, int $potentialParentId): bool
    {
        $currentId = $potentialParentId;
        while ($currentId !== null) {
            $parent = $this->db->get($this->table, ['parent_id'], ['id' => $currentId]);
            if (!$parent) break;
            
            if ($parent['parent_id'] == $sectionId) {
                return true;
            }
            $currentId = $parent['parent_id'];
        }
        return false;
    }

    public function delete(int $id): bool
    {
        // Check if there are notes in this section
        $notesCount = $this->db->count('notes', ['section_id' => $id]);
        if ($notesCount > 0) {
            return false;
        }

        $result = $this->db->delete($this->table, ['id' => $id]);
        return $result->rowCount() > 0;
    }
}
