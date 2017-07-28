<?php

// @see https://developer.apple.com/fonts/TrueType-Reference-Manual/RM06/Chap6cmap.html

include_once __DIR__ . '/../define.php';
include_once __DIR__ . '/Name.php';

class TrueType_Table_Cmap
{
    protected static $subtableFormat = array(
        'platformID'         => TRUE_TYPE_UINT16,
        'platformSpecificID' => TRUE_TYPE_UINT16,
        'offset'             => TRUE_TYPE_UINT32
    );
    protected static $subtableFormatStr;

    protected $subtables;

    public function __construct($data)
    {
        $len = strlen($data);
        if ($len < 4) {
            throw new InvalidArgumentException("table cmap expects at least 4 bytes, got $len bytes");
        }

        // version
        $version = unpack(TRUE_TYPE_UINT16, substr($data, 0, 2));
        if ($version[1] != 0) {
            throw new InvalidArgumentException('table cmap: invalid version');
        }
        // numberSubtables
        $numberSubtables = unpack(TRUE_TYPE_UINT16, substr($data, 2, 2));
        if ($numberSubtables[1] < 1) {
            throw new InvalidArgumentException('table cmap: invalid numberSubtables');
        }
        $numberSubtables = $numberSubtables[1];
        $minLen = 4 + $numberSubtables * 8;
        if ($len < $minLen) {
            throw new InvalidArgumentException("table cmap expects at least $minLen bytes, got $len bytes");
        }
        // subtables
        if (!self::$subtableFormatStr) {
            $formatStr = array();
            foreach (self::$subtableFormat as $name => $type) {
                $formatStr[] = $type . $name;
            }
            self::$subtableFormatStr = implode('/', $formatStr);
        }
        $offset = 4;
        for ($i = 0; $i < $numberSubtables; $i++) {
            $subtable = unpack(self::$subtableFormatStr, substr($data, $offset, 8));
            $offset += 8;
            if (isset(TrueType_Table_Name::$platformIDNameMap[$subtable['platformID']])) {
                $subtable['platformIDName'] = TrueType_Table_Name::$platformIDNameMap[$subtable['platformID']];
            }
            $platformSpecificIDNameMap = TrueType_Table_Name::$platformSpecificIDNameMap[$subtable['platformIDName']];
            if (isset($platformSpecificIDNameMap[$subtable['platformSpecificID']])) {
                $subtable['platformSpecificIDName'] = $platformSpecificIDNameMap[$subtable['platformSpecificID']];
            }
            $minLen = $subtable['offset'] + 6;
            if ($len < $minLen) {
                throw new InvalidArgumentException("table cmap expects at least $minLen bytes, got $len bytes");
            }
            $this->subtables[] = $subtable;
        }
        // subtable
        foreach ($this->subtables as $idx => $subtable) {
            $format = unpack(TRUE_TYPE_UINT16, substr($data, $subtable['offset'], 2));
            $format = $format[1];
            switch ($format) {
            case 4:
            case 6:
                $length   = unpack(TRUE_TYPE_UINT16, substr($data, $subtable['offset'] + 2, 2));
                $language = unpack(TRUE_TYPE_UINT16, substr($data, $subtable['offset'] + 4, 2));
                $length   = $length[1];
                $language = $language[1];
                $minLen = $subtable['offset'] + $length;
                if ($len < $minLen) {
                    throw new InvalidArgumentException("table cmap expects at least $minLen bytes, got $len bytes");
                }
                $method = 'parseFormat' . $format;
                $this->subtables[$idx] += array(
                    'format'   => $format,
                    'length'   => $length,
                    'language' => $language
                ) + $this->$method(substr($data, $subtable['offset'] + 6, $length - 6));
                break;
            case 12:
                $minLen = $subtable['offset'] + 12;
                if ($len < $minLen) {
                    throw new InvalidArgumentException("table cmap expects at least $minLen bytes, got $len bytes");
                }
                $format2 = unpack(TRUE_TYPE_UINT16, substr($data, $subtable['offset'] + 2, 2));
                if ($format2[1] != 0) {
                    throw new InvalidArgumentException("table cmap format 12: invalid format 12.0");
                }
                $format   = '12.0';
                $length   = unpack(TRUE_TYPE_UINT32, substr($data, $subtable['offset'] + 4, 4));
                $language = unpack(TRUE_TYPE_UINT32, substr($data, $subtable['offset'] + 8, 4));
                $length   = $length[1];
                $language = $language[1];
                $minLen = $subtable['offset'] + $length;
                if ($len < $minLen) {
                    throw new InvalidArgumentException("table cmap expects at least $minLen bytes, got $len bytes");
                }
                $this->subtables[$idx] += array(
                    'format'   => $format,
                    'length'   => $length,
                    'language' => $language
                ) + $this->parseFormat12(substr($data, $subtable['offset'] + 12, $length - 12));
                break;
            }
        }
    }

    protected function parseFormat4($data)
    {
        $formatStr =   TRUE_TYPE_UINT16 . 'segCountX2/'
                     . TRUE_TYPE_UINT16 . 'searchRange/'
                     . TRUE_TYPE_UINT16 . 'entrySelector/'
                     . TRUE_TYPE_UINT16 . 'rangeShift';
        $info = unpack($formatStr, $data);
        $data = substr($data, 8);

        $segCount = $info['segCountX2'] / 2;

        // endCode[segCount]
        $info['endCode'] = unpack(TRUE_TYPE_UINT16 . $segCount, $data);
        $data = substr($data, 2 * $segCount + 2);
        // startCode[segCount]
        $info['startCode'] = unpack(TRUE_TYPE_UINT16 . $segCount, $data);
        $data = substr($data, 2 * $segCount);
        // idDelta[segCount]
        $info['idDelta'] = unpack(TRUE_TYPE_UINT16 . $segCount, $data);
        $data = substr($data, 2 * $segCount);
        // idRangeOffset[segCount]
        $info['idRangeOffset'] = unpack(TRUE_TYPE_UINT16 . $segCount, $data);
        $data = substr($data, 2 * $segCount);
        // glyphIndexArray
        if ($data) {
            $info['glyphIndexArray'] = unpack(TRUE_TYPE_UINT16 . '*', $data);
        }

        return $info;
    }

    // format 6: primarily useful for BMP-only Unicode fonts
    // what is BMP ?
    // @see http://www.sttmedia.com/unicode-basiclingualplane
    protected function parseFormat6($data)
    {
        $formatStr = TRUE_TYPE_UINT16 . 'firstCode/' . TRUE_TYPE_UINT16 . 'entryCount';
        $info = unpack($formatStr, $data);
        $data = substr($data, 4);
        // glyphIndexArray
        $info['glyphIndexArray'] = unpack(TRUE_TYPE_UINT16 . $info['entryCount'], $data);

        return $info;
    }

    protected function parseFormat12($data)
    {
        $nGroups = unpack(TRUE_TYPE_UINT32, $data);
        $nGroups = $nGroups[1];
        $data = substr($data, 4);

        $groupFormatStr =   TRUE_TYPE_UINT32 . 'startCharCode/'
                          . TRUE_TYPE_UINT32 . 'endCharCode/'
                          . TRUE_TYPE_UINT32 . 'startGlyphCode';
        $groups = array();
        for ($i = 0; $i < $nGroups; $i++) {
            $groups[] = unpack($groupFormatStr, $data);
            $data = substr($data, 12);
        }

        return array('groups' => $groups);
    }

    public function toArray()
    {
        return $this->subtables;
    }
}
