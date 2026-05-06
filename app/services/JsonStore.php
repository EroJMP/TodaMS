<?php

class JsonStore
{
    private string $dataDir;

    public function __construct()
    {
        $this->dataDir = __DIR__ . '/../../storage/data';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }
    }

    public function all(string $file): array
    {
        $path = $this->path($file);
        if (!file_exists($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public function write(string $file, array $data): void
    {
        file_put_contents($this->path($file), json_encode($data, JSON_PRETTY_PRINT));
    }

    public function nextId(string $file): int
    {
        $rows = $this->all($file);
        $max = 0;
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > $max) {
                $max = $id;
            }
        }

        return $max + 1;
    }

    private function path(string $file): string
    {
        return $this->dataDir . '/' . $file . '.json';
    }
}
