<?php

include_once __DIR__ . '/../define.php';

class TrueType_Table_Glyf
{
    protected static $headerFormat = array(
        'numberOfContours' => TRUE_TYPE_UINT16,
        'xMin'             => TRUE_TYPE_FWORD,
        'yMin'             => TRUE_TYPE_FWORD,
        'xMax'             => TRUE_TYPE_FWORD,
        'yMax'             => TRUE_TYPE_FWORD
    );
    protected static $headerFormatStr;

    protected $items = array();

    public function __construct($data, TrueType_Table_Loca $loca)
    {
        $len = strlen($data);

        foreach ($loca->toArray() as $idx => $pos) {
            $offset = $pos['offset'];
            $length = $pos['length'];
            if ($length == 0) {
                $this->items[$idx] = null;
            } else {
                $end = $offset + $length;
                if ($end > $len) {
                    throw new InvalidArgumentException("table glyf expects at least $end bytes, got $len bytes");
                }
                $this->items[$idx] = $this->parseItem(substr($data, $offset, $length));
            }
        }
    }

    protected function parseItem($data)
    {
        if (!self::$headerFormatStr) {
            $formatStr = array();
            foreach (self::$headerFormat as $name => $type) {
                $formatStr[] = $type . $name;
            }
            self::$headerFormatStr = implode('/', $formatStr);
        }

        $item = unpack(self::$headerFormatStr, substr($data, 0, 10));
        // uint16 -> int16
        foreach ($item as $key => $value) {
            if ($value > 32767) {
                $item[$key] -= 65536;
            }
        }
        if ($item['numberOfContours'] == 0) {
            return $item;
        }
        if ($item['numberOfContours'] > 0) {
            $item['type'] = 'simple';
            return $this->parseSimple($item, substr($data, 10));
        }
        $item['type'] = 'compound';
        return $this->parseCompound($item, substr($data, 10));
    }

    protected function parseSimple($item, $data)
    {
        // endPtsOfContours[n]
        $endPtsOfContoursLen = 2 * $item['numberOfContours'];
        $endPtsOfContours    = array_values(unpack(
            TRUE_TYPE_UINT16 . $item['numberOfContours'],
            substr($data, 0, $endPtsOfContoursLen)
        ));
        $item['endPtsOfContours'] = implode(',', $endPtsOfContours);
        $data = substr($data, $endPtsOfContoursLen);
        // instruction
        $instructionLen = unpack(TRUE_TYPE_UINT16, substr($data, 0, 2));
        // ignore instructions
        $data = substr($data, 2 + $instructionLen[1]);

        // points flags
        $points = array();
        $count  = max($endPtsOfContours) + 1;
        for ($i = 0; $i < $count; $i++) {
            $flag = unpack(TRUE_TYPE_UINT8, $data[0]);
            $flag = $flag[1];
            $data = substr($data, 1);
            $flagInfo = array(
                'flag'     => '0x' . dechex($flag),
                'onCurve'  => $flag & 0x01,
                'xOneByte' => $flag & 0x02 ? 1 : 0,
                'yOneByte' => $flag & 0x04 ? 1 : 0,
                'repeat'   => $flag & 0x08 ? 1 : 0,
                'xSame'    => $flag & 0x10 ? 1 : 0,
                'ySame'    => $flag & 0x20 ? 1 : 0
            );
            $points[] = $flagInfo;
            if ($flagInfo['repeat']) {
                $repeatTimes = unpack(TRUE_TYPE_UINT8, $data[0]);
                $repeatTimes = $repeatTimes[1];
                $i += $repeatTimes;
                $data = substr($data, 1);
                while ($repeatTimes--) {
                    $points[] = $flagInfo;
                }
            }
        }
        // x
        foreach ($points as $idx => $point) {
            $prevX = $idx == 0 ? 0 : $points[$idx-1]['x'];
            if ($point['xOneByte']) {
                $value = unpack('C', $data[0]);
                $value = $value[1];
                if ($point['xSame']) {
                    $point['x'] = $prevX + $value;
                } else {
                    $point['x'] = $prevX - $value;
                }
                $data = substr($data, 1);
            } else {
                if ($point['xSame']) {
                    $point['x'] = $prevX;
                } else {
                    $value = substr($data, 0, 2);
                    $value = unpack(TRUE_TYPE_UINT16, $value);
                    $value = $value[1];
                    if ($value > 32767) {
                        $value -= 65536;
                    }
                    $point['x'] = $prevX + $value;
                    $data = substr($data, 2);
                }
            }
            if ($point['x'] < $item['xMin']) {
                $point['x'] = $item['xMin'];
            }
            if ($point['x'] > $item['xMax']) {
                $point['x'] = $item['xMax'];
            }
            $points[$idx] = $point;
        }
        // y
        foreach ($points as $idx => $point) {
            $prevY = $idx == 0 ? 0 : $points[$idx-1]['y'];
            if ($point['yOneByte']) {
                $value = unpack('C', $data[0]);
                $value = $value[1];
                if ($point['ySame']) {
                    $point['y'] = $prevY + $value;
                } else {
                    $point['y'] = $prevY - $value;
                }
                $data = substr($data, 1);
            } else {
                if ($point['ySame']) {
                    $point['y'] = $prevY;
                } else {
                    $value = substr($data, 0, 2);
                    $value = unpack(TRUE_TYPE_UINT16, $value);
                    $value = $value[1];
                    if ($value > 32767) {
                        $value -= 65536;
                    }
                    $point['y'] = $prevY + $value;
                    $data = substr($data, 2);
                }
            }
            if ($point['y'] < $item['yMin']) {
                $point['y'] = $item['yMin'];
            }
            if ($point['y'] > $item['yMax']) {
                $point['y'] = $item['yMax'];
            }
            $points[$idx] = $point;
        }
        // mark end
        foreach ($endPtsOfContours as $idx) {
            $points[$idx]['end'] = true;
        }

        $item['points'] = $points;

        return $item;
    }

    protected function parseCompound($item, $data)
    {
        return $item;
    }

    public function get($idx)
    {
        if (isset($this->items[$idx])) {
            return $this->items[$idx];
        }
    }

    public function toArray()
    {
        return $this->items;
    }
}
