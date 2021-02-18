<?php

namespace Metaventis\Edusharing;

/*
 * This is a map of edusharing objects, that are being instantiated and don't have their final content id yet.
 */

class EdusharingObjectsInitMap
{
    private static $instance = null;

    // Temporary content ID => Array<EdusharingObject>
    private $map = array();

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new EdusharingObjectsInitMap();
        }
        return self::$instance;
    }

    public function push(string $temporaryContentId, EdusharingObject $edusharingObject): void
    {
        $this->map[$temporaryContentId][] = $edusharingObject;
    }

    public function pop(string $temporaryContentId): array
    {
        $edusharingObjects = $this->map[$temporaryContentId] ?? [];
        unset($this->map[$temporaryContentId]);
        return $edusharingObjects;
    }

    private function __construct()
    { }
}
