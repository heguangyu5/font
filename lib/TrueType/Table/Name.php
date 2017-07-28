<?php

include_once __DIR__ . '/../define.php';

class TrueType_Table_Name
{
    protected static $nameHeaderFormat = array(
        'format'       => TRUE_TYPE_UINT16,
        'count'        => TRUE_TYPE_UINT16,
        'stringOffset' => TRUE_TYPE_UINT16
    );
    protected static $nameHeaderFormatStr;

    protected static $nameRecordFormat = array(
        'platformID'         => TRUE_TYPE_UINT16,
        'platformSpecificID' => TRUE_TYPE_UINT16,
        'languageID'         => TRUE_TYPE_UINT16,
        'nameID'             => TRUE_TYPE_UINT16,
        'length'             => TRUE_TYPE_UINT16,
        'offset'             => TRUE_TYPE_UINT16,
    );
    protected static $nameRecordFormatStr;

    protected static $platformIDNameMap = array(
        0 => 'Unicode',
        1 => 'Macintosh',
        3 => 'Microsoft'
    );
    protected static $platformSpecificIDNameMap = array(
        'Unicode'   => array(
            0 => 'Default semantics',
            1 => 'Version 1.1 semantics',
            2 => 'ISO 10646 1993 semantics (deprecated)',
            3 => 'Unicode 2.0 or later semantics (BMP only)',
            4 => 'Unicode 2.0 or later semantics (non-BMP characters allowed)',
            5 => 'Unicode Variation Sequences',
            6 => 'Full Unicode coverage (used with type 13.0 cmaps by OpenType)'
        ),
        'Macintosh' => array(
            0 => 'Roman',
            1 => 'Japanese',
            2 => 'Traditional Chinese',
            3 => 'Korean',
            4 => 'Arabic',
            5 => 'Hebrew',
            6 => 'Greek',
            7 => 'Russian',
            8 => 'RSymbol',
            9 => 'Devanagari',
            10 => 'Gurmukhi',
            11 => 'Gujarati',
            12 => 'Oriya',
            13 => 'Bengali',
            14 => 'Tamil',
            15 => 'Telugu',
            16 => 'Kannada',
            17 => 'Malayalam',
            18 => 'Sinhalese',
            19 => 'Burmese',
            20 => 'Khmer',
            21 => 'Thai',
            22 => 'Laotian',
            23 => 'Georgian',
            24 => 'Armenian',
            25 => 'Simplified Chinese',
            26 => 'Tibetan',
            27 => 'Mongolian',
            28 => 'Geez',
            29 => 'Slavic',
            30 => 'Vietnamese',
            31 => 'Sindhi',
            32 => '(Uninterpreted)'
        ),
        'Microsoft' => array(
            0 => 'Symbol',
            1 => 'Unicode BMP (UCS-2)',
            2 => 'ShiftJIS',
            3 => 'PRC',
            4 => 'Big5',
            5 => 'Wansung',
            6 => 'Johab',
            7 => 'Reserved',
            8 => 'Reserved',
            9 => 'Reserved',
            10 => 'Unicode UCS-4'
        )
    );

    protected static $languageIDNameMap = array(
        'Macintosh' => array(
            0 => 'English',
            1 => 'French',
            2 => 'German',
            3 => 'Italian',
            4 => 'Dutch',
            5 => 'Swedish',
            6 => 'Spanish',
            7 => 'Danish',
            8 => 'Portuguese',
            9 => 'Norwegian',
            10 => 'Hebrew',
            11 => 'Japanese',
            12 => 'Arabic',
            13 => 'Finnish',
            14 => 'Greek',
            15 => 'Icelandic',
            16 => 'Maltese',
            17 => 'Turkish',
            18 => 'Croatian',
            19 => 'Chinese (traditional)',
            20 => 'Urdu',
            21 => 'Hindi',
            22 => 'Thai',
            23 => 'Korean',
            24 => 'Lithuanian',
            25 => 'Polish',
            26 => 'Hungarian',
            27 => 'Estonian',
            28 => 'Latvian',
            29 => 'Sami',
            30 => 'Faroese',
            31 => 'Farsi/Persian',
            32 => 'Russian',
            33 => 'Chinese (simplified)',
            34 => 'Flemish',
            35 => 'Irish Gaelic',
            36 => 'Albanian',
            37 => 'Romanian',
            38 => 'Czech',
            39 => 'Slovak',
            40 => 'Slovenian',
            41 => 'Yiddish',
            42 => 'Serbian',
            43 => 'Macedonian',
            44 => 'Bulgarian',
            45 => 'Ukrainian',
            46 => 'Byelorussian',
            47 => 'Uzbek',
            48 => 'Kazakh',
            49 => 'Azerbaijani (Cyrillic script)',
            50 => 'Azerbaijani (Arabic script)',
            51 => 'Armenian',
            52 => 'Georgian',
            53 => 'Moldavian',
            54 => 'Kirghiz',
            55 => 'Tajiki',
            56 => 'Turkmen',
            57 => 'Mongolian (Mongolian script)',
            58 => 'Mongolian (Cyrillic script)',
            59 => 'Pashto',
            60 => 'Kurdish',
            61 => 'Kashmiri',
            62 => 'Sindhi',
            63 => 'Tibetan',
            64 => 'Nepali',
            65 => 'Sanskrit',
            66 => 'Marathi',
            67 => 'Bengali',
            68 => 'Assamese',
            69 => 'Gujarati',
            70 => 'Punjabi',
            71 => 'Oriya',
            72 => 'Malayalam',
            73 => 'Kannada',
            74 => 'Tamil',
            75 => 'Telugu',
            76 => 'Sinhalese',
            77 => 'Burmese',
            78 => 'Khmer',
            79 => 'Lao',
            80 => 'Vietnamese',
            81 => 'Indonesian',
            82 => 'Tagalog',
            83 => 'Malay (Roman script)',
            84 => 'Malay (Arabic script)',
            85 => 'Amharic',
            86 => 'Tigrinya',
            87 => 'Galla',
            88 => 'Somali',
            89 => 'Swahili',
            90 => 'Kinyarwanda/Ruanda',
            91 => 'Rundi',
            92 => 'Nyanja/Chewa',
            93 => 'Malagasy',
            94 => 'Esperanto',
            128 => 'Welsh',
            129 => 'Basque',
            130 => 'Catalan',
            131 => 'Latin',
            132 => 'Quechua',
            133 => 'Guarani',
            134 => 'Aymara',
            135 => 'Tatar',
            136 => 'Uighur',
            137 => 'Dzongkha',
            138 => 'Javanese (Roman script)',
            139 => 'Sundanese (Roman script)',
            140 => 'Galician',
            141 => 'Afrikaans',
            142 => 'Breton',
            143 => 'Inuktitut',
            144 => 'Scottish Gaelic',
            145 => 'Manx Gaelic',
            146 => 'Irish Gaelic (with dot above)',
            147 => 'Tongan',
            148 => 'Greek (polytonic)',
            149 => 'Greenlandic',
            150 => 'Azerbaijani (Roman script)'
        )
    );

    protected static $nameIDNameMap = array(
        0 => 'Copyright notice',
        1 => 'Font Family',
        2 => 'Font Subfamily',
        3 => 'Unique subfamily identification',
        4 => 'Full name of the font',
        5 => 'Version of the name table',
        6 => 'PostScript name of the font',
        7 => 'Trademark notice',
        8 => 'Manufacturer name',
        9 => 'Designer',
        10 => 'Description',
        11 => 'URL of the font vendor',
        12 => 'URL of the font designer',
        13 => 'License description',
        14 => 'License information URL',
        16 => 'Preferred Family',
        17 => 'Preferred Subfamily',
        18 => 'Compatible Full (Macintosh only)',
        19 => 'Sample text'
    );

    protected $header;
    protected $nameRecords = array();

    public function __construct($data)
    {
        $len = strlen($data);
        if ($len < 6) {
            throw new InvalidArgumentException("table name header is 6 bytes, got $len bytes");
        }

        // header
        if (!self::$nameHeaderFormatStr) {
            $formatStr = array();
            foreach (self::$nameHeaderFormat as $name => $type) {
                $formatStr[] = $type . $name;
            }
            self::$nameHeaderFormatStr = implode('/', $formatStr);
        }
        $header = unpack(self::$nameHeaderFormatStr, substr($data, 0, 6));
        if ($header['format'] != 0) {
            throw new InvalidArgumentException('table name: invalid format');
        }
        if ($header['count'] < 1) {
            return;
        }
        $minLen = 6 + $header['count'] * 12;
        if ($len < $minLen) {
            throw new InvalidArgumentException("table name expects at least $minLen bytes, got $len bytes");
        }
        if ($header['stringOffset'] < $minLen) {
            throw new InvalidArgumentException("table name: invalid stringOffset");
        }
        $this->header = $header;
        // nameRecords
        if (!self::$nameRecordFormatStr) {
            $formatStr = array();
            foreach (self::$nameRecordFormat as $name => $type) {
                $formatStr[] = $type . $name;
            }
            self::$nameRecordFormatStr = implode('/', $formatStr);
        }
        $offset = 6;
        for ($i = 0; $i < $header['count']; $i++) {
            $nameRecord = unpack(self::$nameRecordFormatStr, substr($data, $offset, 12));
            if (!isset(self::$platformIDNameMap[$nameRecord['platformID']])) {
                throw new InvalidArgumentException('table name: invalid platformID');
            }
            $nameRecord['platformIDName'] = self::$platformIDNameMap[$nameRecord['platformID']];
            $platformSpecificIDNameMap = self::$platformSpecificIDNameMap[$nameRecord['platformIDName']];
            if (!isset($platformSpecificIDNameMap[$nameRecord['platformSpecificID']])) {
                throw new InvalidArgumentException('table name: invalid platformSpecificID');
            }
            $nameRecord['platformSpecificIDName'] = $platformSpecificIDNameMap[$nameRecord['platformSpecificID']];
            if ($nameRecord['platformIDName'] == 'Macintosh') {
                $languageIDNameMap = self::$languageIDNameMap['Macintosh'];
                if (!isset($languageIDNameMap[$nameRecord['languageID']])) {
                    throw new InvalidArgumentException('table name: invalid Macintosh languageID');
                }
                $nameRecord['languageIDName'] = $languageIDNameMap[$nameRecord['languageID']];
            }
            if (isset(self::$nameIDNameMap[$nameRecord['nameID']])) {
                $nameRecord['nameIDName'] = self::$nameIDNameMap[$nameRecord['nameID']];
            }
            $nameStringEnd = $header['stringOffset'] + $nameRecord['offset'] + $nameRecord['length'];
            if ($nameStringEnd > $len) {
                throw new InvalidArgumentException("table name expects at least $nameStringEnd bytes, got $len bytes");
            }
            $nameRecord['string'] = substr($data, $header['stringOffset'] + $nameRecord['offset'], $nameRecord['length']);
            if ($nameRecord['platformIDName'] == 'Microsoft') {
                if ($nameRecord['platformSpecificIDName'] == 'Unicode BMP (UCS-2)') {
                    $args = unpack('n*', $nameRecord['string']);
                    array_unshift($args, 'v*');
                    $nameRecord['string'] = iconv('UCS-2', 'UTF-8', call_user_func_array('pack', $args));
                }
            } else if ($nameRecord['platformIDName'] == 'Unicode') {
                $args = unpack('n*', $nameRecord['string']);
                array_unshift($args, 'v*');
                $nameRecord['string'] = iconv('UTF-16', 'UTF-8', call_user_func_array('pack', $args));
            }
            $this->nameRecords[] = $nameRecord;
            $offset += 12;
        }
    }

    public function getNameRecords()
    {
        return $this->nameRecords;
    }
}
