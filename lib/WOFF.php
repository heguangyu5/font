<?php

// @see http://www.w3.org/TR/WOFF/

define('WOFF_UInt32', 'N'); // N: unsigned long (always 32 bit, big endian byte order)
define('WOFF_UInt16', 'n'); // n: unsigned short (always 16 bit, big endian byte order)

class WOFF
{
    protected static $headerFormat = array(
        // 'signature'   => WOFF_UInt32,    // 0x774F4646 'wOFF'
        'flavor'         => WOFF_UInt32,    // The "sfnt version" of the input font.
        'length'         => WOFF_UInt32,    // Total size of the WOFF file.
        'numTables'      => WOFF_UInt16,    // Number of entries in directory of font tables.
        'reserved'       => WOFF_UInt16,    // Reserved; set to zero.
        'totalSfntSize'  => WOFF_UInt32,    // Total size needed for the uncompressed font data,
                                            // including the sfnt header, directory, and font tables
                                            // (including padding).
        'majorVersion'   => WOFF_UInt16,    // Major version of the WOFF file.
        'minorVersion'   => WOFF_UInt16,    // Minor version of the WOFF file.
        'metaOffset'     => WOFF_UInt32,    // Offset to metadata block, from beginning of WOFF file.
        'metaLength'     => WOFF_UInt32,    // Length of compressed metadata block.
        'metaOrigLength' => WOFF_UInt32,    // Uncompressed size of metadata block.
        'privOffset'     => WOFF_UInt32,    // Offset to private data block, from beginning of WOFF
                                            // file.
        'privLength'     => WOFF_UInt32     // Length of private data block.
    );
    protected static $headerFormatStr;

    protected static $tableDirectoryEntryFormat = array(
        // 'tag'       => WOFF_UInt32,  // 4-byte sfnt table identifier.
        'offset'       => WOFF_UInt32,  // Offset to the data, from beginning of WOFF file.
        'compLength'   => WOFF_UInt32,  // Length of the compressed data, excluding padding.
        'origLength'   => WOFF_UInt32,  // Length of the uncompressed table, excluding padding.
        'origChecksum' => WOFF_UInt32   // Checksum of the uncompressed table.
    );
    protected static $tableDirectoryEntryFormatStr;

    protected $fontFilesize;
    protected $header;
    protected $tableDirectory = array();
    protected $fontTables;
    protected $metadata;
    protected $privateData;

    public function loadFromFile($fontFile)
    {
        $fp = fopen($fontFile, 'r');
        if (!$fp) {
            throw new InvalidArgumentException("cannot open font $fontFile");
        }
        $this->fontFilesize = filesize($fontFile);
        // header
        $this->parseHeader(fread($fp, 44));
        // tableDirectory
        for ($i = 0; $i < $this->header['numTables']; $i++) {
            $this->parseTableDirectoryEntry(fread($fp, 20));
        }
        // load font tables
        foreach ($this->tableDirectory as $entry) {
            fseek($fp, $entry['offset'], SEEK_SET);
            $this->fontTables[$entry['tag']] = fread($fp, $entry['compLength']);
        }
        $this->uncompress();
        // metadata
        if ($this->header['metaOffset'] && $this->header['metaLength']) {
            fseek($fp, $this->header['metaOffset'], SEEK_SET);
            $this->parseMetadata(fread($fp, $this->header['metaLength']));
        }
        // privateData
        if ($this->header['privOffset'] && $this->header['privLength']) {
            fseek($fp, $this->header['privOffset'], SEEK_SET);
            $this->privateData = fread($fp, $this->header['privLength']);
        }

        fclose($fp);
    }

    public function loadFromBase64String($str)
    {
        $str = base64_decode($str);
        if (!$str) {
            throw new InvalidArgumentException('base64_decode failed');
        }
        $this->fontFilesize = strlen($str);
        // header
        $this->parseHeader(substr($str, 0, 44));
        // tableDirectory
        $offset = 44;
        for ($i = 0; $i < $this->header['numTables']; $i++) {
            $this->parseTableDirectoryEntry(substr($str, $offset, 20));
            $offset += 20;
        }
        // load font tables
        foreach ($this->tableDirectory as $entry) {
            $this->fontTables[$entry['tag']] = substr($str, $entry['offset'], $entry['compLength']);
        }
        $this->uncompress();
        // metadata
        if ($this->header['metaOffset'] && $this->header['metaLength']) {
            $this->parseMetadata(substr($str, $this->header['metaOffset'], $this->header['metaLength']));
        }
        // privateData
        if ($this->header['privOffset'] && $this->header['privLength']) {
            $this->privateData = substr($str, $this->header['privOffset'], $this->header['privLength']);
        }
    }

    protected function parseHeader($header)
    {
        $len = strlen($header);
        if ($len != 44) {
            throw new InvalidArgumentException("expect 44 bytes header, actually got $len bytes.");
        }
        if (strncmp($header, 'wOFF', 4) != 0) {
            throw new InvalidArgumentException('header: invalid signature');
        }
        $header = substr($header, 4);

        // parse
        if (!self::$headerFormatStr) {
            $headerFormatStr = array();
            foreach (self::$headerFormat as $name => $type) {
                $headerFormatStr[] = $type . $name;
            }
            self::$headerFormatStr = implode('/', $headerFormatStr);
        }

        $header = array('signature' => 'wOFF') + unpack(self::$headerFormatStr, $header);

        // validate
        if ($header['length'] != $this->fontFilesize) {
            throw new InvalidArgumentException('header: invalid length, not matched with fontFilesize');
        }
        if ($header['totalSfntSize'] % 4 != 0) {
            throw new InvalidArgumentException('header: invalid totalSfntSize, should be a multiple of 4');
        }
        if ($header['reserved'] != 0) {
            throw new InvalidArgumentException('header: invalid reserved, should be zero');
        }
        if ($header['metaOffset'] + $header['metaLength'] > $this->fontFilesize) {
            throw new InvalidArgumentException('header: invalid metaOffset and metaLength');
        }
        if ($header['privOffset'] + $header['privLength'] > $this->fontFilesize) {
            throw new InvalidArgumentException('header: invalid privOffset and privLength');
        }

        $header['flavor'] = sprintf("0x%08X", $header['flavor']);
        $this->header = $header;
    }

    protected function parseTableDirectoryEntry($entry)
    {
        $len = strlen($entry);
        if ($len != 20) {
            throw new InvalidArgumentException("expect 20 bytes table directory entry, actually got $len bytes");
        }
        $tag   = substr($entry, 0, 4);
        $entry = substr($entry, 4);

        // parse
        if (!self::$tableDirectoryEntryFormatStr) {
            $entryFormatStr = array();
            foreach (self::$tableDirectoryEntryFormat as $name => $type) {
                $entryFormatStr[] = $type . $name;
            }
            self::$tableDirectoryEntryFormatStr = implode('/', $entryFormatStr);
        }

        $entry = array('tag' => $tag) + unpack(self::$tableDirectoryEntryFormatStr, $entry);

        // validate
        if ($entry['offset'] % 4 != 0) {
            throw new InvalidArgumentException("table directory entry: invalid offset, should be a multiple of 4");
        }
        if ($entry['offset'] + $entry['compLength'] > $this->fontFilesize) {
            throw new InvalidArgumentException("table directory entry: invalid offset and compLength");
        }
        if ($entry['compLength'] > $entry['origLength']) {
            throw new InvalidArgumentException("table directory entry: invalid compLength and origLength");
        }
        $entry['compressed'] = $entry['compLength'] < $entry['origLength'];

        $this->tableDirectory[] = $entry;
    }

    protected function uncompress()
    {
        foreach ($this->tableDirectory as $entry) {
            if ($entry['compressed']) {
                $tag = $entry['tag'];
                // FIXME: why need substr(, 2)
                $uncompressed = gzinflate(substr($this->fontTables[$tag], 2));
                if (strlen($uncompressed) != $entry['origLength']) {
                    throw new InvalidArgumentException("uncompress table $tag: origLength not matched");
                }
                $this->fontTables[$tag] = $uncompressed;
            }
        }
    }

    protected function parseMetadata($metadata)
    {
        // FIXME: why need substr(, 2)
        $uncompressed = gzinflate(substr($metadata, 2));
        if (strlen($uncompressed) != $this->header['metaOrigLength']) {
            throw new InvalidArgumentException("uncompress metadata: metaOrigLength not matched");
        }
        $this->metadata = $uncompressed;
    }

    public function dump($outputDir = null)
    {
        if (!$outputDir) {
            $outputDir = '.';
        }

        $info = array(
            '',
            'filesize = ' . number_format($this->fontFilesize) . ' bytes',
            '',
            '# header'
        );
        // header
        foreach ($this->header as $key => $value) {
            $info[] = '    ' . $key . ' = ' . $value;
        }
        $info[] = '';
        $info[] = '# table Directory';
        // tableDirectory
        foreach ($this->tableDirectory as $idx => $entry) {
            $idx++;
            $info[] = sprintf(
                '    %02d. %s     offset=% -10d compLength=% -10d origLength=% -10d',
                $idx,
                $entry['tag'],
                $entry['offset'],
                $entry['compLength'],
                $entry['origLength']
            );
        }
        $info[] = '';
        // metadata
        if ($this->metadata) {
            $info[] = '# metadata';
            $info[] = '    length = ' . strlen($this->metadata);
            file_put_contents($outputDir . '/woff.metadata', $this->metadata);
        }
        // privateData
        if ($this->privateData) {
            $info[] = '# privateData';
            $info[] = '    length = ' . strlen($this->privateData);
            file_put_contents($outputDir . '/woff.privateData', $this->privateData);
        }
        // table head
        if (isset($this->fontTables['head'])) {
            include_once __DIR__ . '/TrueType/Table/Head.php';
            $head = new TrueType_Table_Head($this->fontTables['head']);
            $info[] = '# table head';
            foreach ($head->toArray() as $key => $value) {
                $info[] = '    ' . $key . ' = ' . $value;
            }
            $info[] = '';
        }
        // table name
        if (isset($this->fontTables['name'])) {
            include_once __DIR__ . '/TrueType/Table/Name.php';
            $name = new TrueType_Table_Name($this->fontTables['name']);
            $info[] = '# table name';
            foreach ($name->getNameRecords() as $idx => $nameRecord) {
                $idx++;
                $info[] = '    ' . $idx . ':';
                foreach ($nameRecord as $key => $value) {
                    $info[] = '        ' . $key . ' = ' . $value;
                }
            }
            $info[] = '';
        }
        // table loca
        if (isset($this->fontTables['head'])) {
            if (isset($this->fontTables['loca'])) {
                include_once __DIR__ . '/TrueType/Table/Loca.php';
                $loca = new TrueType_Table_Loca($this->fontTables['loca'], $head->indexToLocFormat);
                $info[] = '# table loca';
                foreach ($loca->toArray() as $idx => $glyfPos) {
                    $info[] = '    ' . $idx . '. ' . 'offset = ' . $glyfPos['offset'] . ' length = ' . $glyfPos['length'] . ($glyfPos['length'] ? '' : ' (no outline)');
                }
                $info[] = '';
                if (isset($this->fontTables['glyf'])) {
                    include_once __DIR__ . '/TrueType/Table/Glyf.php';
                    $glyf = new TrueType_Table_Glyf($this->fontTables['glyf'], $loca);
                    $info[] = '# table glyf';
                    foreach ($glyf->toArray() as $idx => $item) {
                        $info[] = '    '. $idx . '.';
                        if ($item) {
                            $info[] = '        ' . $item['type'];
                            if ($item['type'] == 'simple') {
                                foreach ($item['points'] as $point) {
                                    $info[] = '        (' . $point['x'] . ',' . $point['y'] . ')';
                                    if (isset($point['end'])) {
                                        $info[] = '        (end)';
                                    }
                                }
                            }
                        } else {
                            $info[] = '        null';
                        }
                    }
                    $info[] = '';
                }
            }
        }
        if (isset($this->fontTables['cmap'])) {
            include_once __DIR__ . '/TrueType/Table/Cmap.php';
            $cmap = new TrueType_Table_Cmap($this->fontTables['cmap']);
            $info[] = '# table cmap';
            $info[] = var_export($cmap->toArray(), true);
            $info[] = '';
        }
        $info = implode("\n", $info) . "\n";
        file_put_contents($outputDir . '/woff.info', $info);
        echo $info;
        // fontTables
        foreach ($this->fontTables as $tag => $tableData) {
            file_put_contents(
                $outputDir . '/woff-table-' . str_replace('/', '', $tag) . '.data',
                $tableData
            );
        }
    }
}
