<?php

// @see https://developer.apple.com/fonts/TrueType-Reference-Manual/RM06/Chap6loca.html

include_once __DIR__ . '/../define.php';

class TrueType_Table_Loca
{
    const FORMAT_SHORT_OFFSET = 0;
    const FORMAT_LONG_OFFSET  = 1;

    protected $totalGlyf;
    protected $glyf = array();

    public function __construct($data, $format)
    {
        if ($format != self::FORMAT_SHORT_OFFSET && $format != self::FORMAT_LONG_OFFSET) {
            throw new InvalidArgumentException("table loca: invalid format");
        }

        $len = strlen($data);

        if ($format == self::FORMAT_SHORT_OFFSET) {
            if ($len < 4) {
                throw new InvalidArgumentException("table loca expects at least 4 bytes, got $len bytes");
            }
            $data = unpack(TRUE_TYPE_UINT16 . '*', $data);
            foreach ($data as $idx => $value) {
                $data[$idx] = $value * 2;
            }
        } else {
            if ($len < 8) {
                throw new InvalidArgumentException("table loca expects at least 8 bytes, got $len bytes");
            }
            $data = unpack(TRUE_TYPE_UINT32 . '*', $data);
        }

        $count = count($data);
        for ($i = 1; $i < $count; $i++) {
            $this->glyf[$i-1] = array(
                'offset' => $data[$i],
                'length' => $data[$i+1] - $data[$i]
            );
        }
        $this->totalGlyf = count($this->glyf);
    }

    public function getGlyfPos($index)
    {
        if (isset($this->glyf[$index])) {
            return $this->glyf[$index];
        }
    }

    public function toArray()
    {
        return $this->glyf;
    }
}
