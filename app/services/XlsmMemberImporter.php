<?php

class XlsmMemberImporter
{
    /**
     * @return array<int, array<string, string>>
     */
    public function import(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $sharedXml = $this->readArchiveEntry($filePath, 'xl/sharedStrings.xml');
        $sheetXml = $this->readArchiveEntry($filePath, 'xl/worksheets/sheet1.xml');

        if ($sheetXml === null) {
            return [];
        }

        $sharedStrings = $this->readSharedStringsFromXml($sharedXml);

        $xml = simplexml_load_string($sheetXml);
        if ($xml === false) {
            return [];
        }

        $rows = $xml->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]');
        if (!is_array($rows) || $rows === []) {
            return [];
        }

        $headers = [];
        $items = [];

        foreach ($rows as $row) {
            $rowIndex = (int) ($row['r'] ?? 0);
            $rowData = [];
            $cells = $row->xpath('./*[local-name()="c"]');
            if (!is_array($cells)) {
                continue;
            }
            foreach ($cells as $cell) {
                $cellRef = (string) ($cell['r'] ?? '');
                $column = $this->extractColumnFromCellRef($cellRef);
                if ($column === '') {
                    continue;
                }
                $value = $this->cellValue($cell, $sharedStrings);
                $rowData[$column] = trim($value);
            }

            if ($rowIndex === 1) {
                $headers = $rowData;
                continue;
            }

            if ($headers === []) {
                continue;
            }

            $mapped = $this->mapRow($headers, $rowData);
            if (($mapped['name'] ?? '') === '') {
                continue;
            }
            $items[] = $mapped;
        }

        return $items;
    }

    private function readArchiveEntry(string $filePath, string $entryPath): ?string
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($filePath) === true) {
                $content = $zip->getFromName($entryPath);
                $zip->close();
                if ($content !== false) {
                    return $content;
                }
            }
        }

        if (!class_exists('PharData')) {
            return null;
        }

        try {
            $phar = new PharData($filePath);
            if (!$phar->offsetExists($entryPath)) {
                return null;
            }
            /** @var PharFileInfo $file */
            $file = $phar[$entryPath];
            return $file->getContent();
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStringsFromXml(?string $sharedXml): array
    {
        if ($sharedXml === null || $sharedXml === '') {
            return [];
        }

        $xml = simplexml_load_string($sharedXml);
        if ($xml === false) {
            return [];
        }

        $siNodes = $xml->xpath('//*[local-name()="si"]');
        if (!is_array($siNodes)) {
            return [];
        }

        $strings = [];
        foreach ($siNodes as $si) {
            $value = '';
            $texts = $si->xpath('.//*[local-name()="t"]');
            if (is_array($texts)) {
                foreach ($texts as $text) {
                    $value .= (string) $text;
                }
            }
            $strings[] = $value;
        }

        return $strings;
    }

    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');
        if ($type === 'inlineStr') {
            $inlineText = $cell->xpath('./*[local-name()="is"]/*[local-name()="t"]');
            if (is_array($inlineText) && isset($inlineText[0])) {
                return (string) $inlineText[0];
            }
        }

        $valueNode = $cell->xpath('./*[local-name()="v"]');
        $raw = (is_array($valueNode) && isset($valueNode[0])) ? (string) $valueNode[0] : '';

        if ($type === 's') {
            $index = (int) $raw;
            return $sharedStrings[$index] ?? '';
        }

        return $raw;
    }

    private function extractColumnFromCellRef(string $cellRef): string
    {
        if ($cellRef === '') {
            return '';
        }
        preg_match('/^[A-Z]+/', strtoupper($cellRef), $matches);
        return $matches[0] ?? '';
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $rowData
     * @return array<string, string>
     */
    private function mapRow(array $headers, array $rowData): array
    {
        $normalizedHeaders = [];
        foreach ($headers as $column => $header) {
            $normalizedHeaders[$column] = strtolower(trim($header));
        }

        $result = [
            'name' => '',
            'address' => '',
            'contact_number' => '',
            'toda_id' => '',
            'body_number' => '',
        ];

        foreach ($rowData as $column => $value) {
            $header = $normalizedHeaders[$column] ?? '';
            if ($header === 'driver name') {
                $result['name'] = $value;
            } elseif ($header === 'address') {
                $result['address'] = $value;
            } elseif ($header === 'contact no.') {
                $result['contact_number'] = $value;
            } elseif ($header === 'toda id') {
                $result['toda_id'] = $value;
            } elseif ($header === 'body no.') {
                $result['body_number'] = $value;
            }
        }

        return $result;
    }
}
