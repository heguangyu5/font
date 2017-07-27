<?php

include_once __DIR__ . '/../define.php';

class TrueType_Table_Head
{
    protected static $headFormat = array(
        'version'            => TRUE_TYPE_FIXED,    // 0x00010000
        'fontRevision'       => TRUE_TYPE_FIXED,    // set by font manufacturer
        'checkSumAdjustment' => TRUE_TYPE_UINT32,
        'magicNumber'        => TRUE_TYPE_UINT32,   // 0x5F0F3CF5
        'flags'              => TRUE_TYPE_UINT16,
        'unitsPerEm'         => TRUE_TYPE_UINT16,
        'created'            => TRUE_TYPE_UINT32,
        'created_low'        => TRUE_TYPE_UINT32,
        'modified'           => TRUE_TYPE_UINT32,
        'modified_low'       => TRUE_TYPE_UINT32,
        'xMin'               => TRUE_TYPE_FWORD,
        'yMin'               => TRUE_TYPE_FWORD,
        'xMax'               => TRUE_TYPE_FWORD,
        'yMax'               => TRUE_TYPE_FWORD,
        'macStyle'           => TRUE_TYPE_UINT16,
        'lowestRecPPEM'      => TRUE_TYPE_UINT16,
        'fontDirectionHint'  => TRUE_TYPE_UINT16,
        'indexToLocFormat'   => TRUE_TYPE_UINT16,
        'glyphDataFormat'    => TRUE_TYPE_UINT16
    );
    protected static $headFormatStr;

    // 由于PHP的unpack当前处理不了signed big endian,所以把它们全都转成hex
    // 方便和hexdump的结果对照
    protected static $headHexLen = array(
        'version'            => 8,
        'fontRevision'       => 8,
        'checkSumAdjustment' => 8,
        'magicNumber'        => 8,
        'flags'              => 4,
        'unitsPerEm'         => 4,
        'created'            => 8,
        'created_low'        => 8,
        'modified'           => 8,
        'modified_low'       => 8,
        'xMin'               => 4,
        'yMin'               => 4,
        'xMax'               => 4,
        'yMax'               => 4,
        'macStyle'           => 4,
        'lowestRecPPEM'      => 4,
        'fontDirectionHint'  => 4,
        'indexToLocFormat'   => 4,
        'glyphDataFormat'    => 4
    );

    protected $head;

    public function __construct($data)
    {
        $len = strlen($data);
        if ($len != 54) {
            throw new InvalidArgumentException("table head should be 54 bytes, got $len bytes");
        }

        if (!self::$headFormatStr) {
            $formatStr = array();
            foreach (self::$headFormat as $name => $type) {
                $formatStr[] = $type . $name;
            }
            self::$headFormatStr = implode('/', $formatStr);
        }

        $head = unpack(self::$headFormatStr, $data);
        if ($head['version'] != 0x00010000) {
            throw new InvalidArgumentException("table head: invalid version");
        }
        if ($head['magicNumber'] != 0x5F0F3CF5) {
            throw new InvalidArgumentException("table head: invalid magicNumber");
        }

        foreach ($head as $key => $value) {
            $head[$key] = sprintf('0x%0' . self::$headHexLen[$key] . 'X', $value);
        }

        $head['created']  .= substr($head['created_low'], 2);
        $head['modified'] .= substr($head['modified_low'], 2);
        unset($head['created_low'], $head['modified_low']);

        $this->head = $head;
    }

    public function __get($key)
    {
        if (!isset($this->head[$key])) {
            return $this->head[$key];
        }

        throw new InvalidArgumentException('table head: no such field');
    }

    public function toArray()
    {
        return $this->head;
    }
}
