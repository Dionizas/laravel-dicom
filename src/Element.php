<?php

namespace Dionizas\LaravelDicom;

/**
 * This class defines DICOM file elements.
 *
 * @author   Xavier Noguer <xnoguer@php.net>
 * @package  File_DICOM
 */
class Element
{
    const VR_TYPE_EXPLICIT_32_BITS = 0;
    const VR_TYPE_EXPLICIT_16_BITS = 1;
    const VR_TYPE_IMPLICIT = 2;
    /**
     * Value Representations (DICOM Standard PS 3.5 Sect 6.2)
     * @var array
     */
    public $VR = [
        'AE' => ['Application Entity', 16, 0],
        'AS' => ['Age String', 4, 1],
        'AT' => ['Attribute Tag', 4, 1],
        'CS' => ['Code String', 16, 0],
        'DA' => ['Date', 8, 1],
        'DS' => ['Decimal String', 16, 0],
        'DT' => ['Date Time', 26, 0],
        'FL' => ['Floating Point Single', 4, 1],
        'FD' => ['Floating Point Double', 8, 1],
        'IS' => ['Integer String', 12, 0],
        'LO' => ['Long String', 64, 0],
        'LT' => ['Long Text', 10240, 0],
        'OB' => ['Other Byte', 0, 0],
        'OW' => ['Other Word', 0, 0],
        'PN' => ['Person Name', 64, 0],
        'SH' => ['Short String', 16, 0],
        'SL' => ['Signed Long', 4, 1],
        'SQ' => ['Sequence of Items', 0, 0],
        'SS' => ['Signed Short', 2, 1],
        'ST' => ['Short Text', 1024, 0],
        'TM' => ['Time', 14, 0],
        'UI' => ['Unique Identifier', 64, 0],
        'UL' => ['Unsigned Long', 4, 1],
        'UN' => ['Unknown', 0, 0],
        'US' => ['Unsigned Short', 2, 1],
        'UT' => ['Unlimited Text', 0, 0]
    ];

    /**
     * Type of VR for this element
     * @var int
     */
    public $vr_type;

    /**
     * Element value
     * @var mixed
     */
    public $value;

    /**
     * Element code
     * @var string
     */
    public $code;

    /**
     * Element length
     * @var int
     */
    public $length;

    /**
     * Complete header of this element
     * @var string
     */
    public $header;

    /**
     * Group this element belongs to
     * @var int
     */
    public $group;

    /**
     * Element identifier
     * @var int
     */
    public $element;

    /**
     * Position inside the current field for the element
     * @var int
     */
    public $offset;

    /**
     * Name for this element
     * @var string
     */
    public $name;

    /**
     * Create DICOM file element from contents of the file given.
     * It assumes the element begins at the current position of the given file pointer.
     *
     * @param resource $IN       File handle for the file currently being parsed
     * @param array    &$dictref Reference to the dictionary of DICOM headers
     */
    public function __construct($IN, &$dictref)
    {
        $this->offset  = ftell($IN);
        $this->group   = $this->_readInt($IN, 2, 2);
        $this->element = $this->_readInt($IN, 2, 2);
        $this->length  = $this->_readLength($IN);

        $diff = ftell($IN) - $this->offset;
        fseek($IN, $this->offset);
        $this->header = fread($IN, $diff);

        if (isset($dictref[$this->group][$this->element])) {
            [$this->code,, $this->name] = $dictref[$this->group][$this->element];
        } else {
            [$this->code,, $this->name] = ["--", "UNKNOWN", "UNKNOWN"];
        }

        $this->value = $this->length > 0 ? $this->_readValue($IN, $this->code, $this->length) : '';
    }

    /**
     * Return the Value Field length, and length before Value Field.
     * Implicit VR: Length is a 4-byte int.
     * Explicit VR: 2 bytes hold VR, then 2-byte length.
     *
     * @param resource $IN File handle for the file currently being parsed
     * @return int The length for the current field
     */
    private function _readLength($IN)
    {
        $buff = fread($IN, 4);
        if (strlen($buff) < 4) {
            return 0;
        }

        $b = unpack("C4", $buff);
        // Temp string to test for explicit VR
        $vrstr = pack("C", $b[1]) . pack("C", $b[2]);

        # Assume that this is explicit VR if b[1] and b[2] match a known VR code.
        # Possibility (prob 26/16384) exists that the two low order field length
        # bytes of an implicit VR field will match a VR code.

        # DICOM PS 3.5 Sect 7.1.2: Data Element Structure with Explicit VR
        # Explicit VRs store VR as text chars in 2 bytes.
        # VRs of OB, OW, SQ, UN, UT have VR chars, then 0x0000, then 32 bit VL:
        #
        # +-----------------------------------------------------------+
        # |  0 |  1 |  2 |  3 |  4 |  5 |  6 |  7 |  8 |  9 | 10 | 11 |
        # +----+----+----+----+----+----+----+----+----+----+----+----+
        # |<Group-->|<Element>|<VR----->|<0x0000->|<Length----------->|<Value->
        #
        # Other Explicit VRs have VR chars, then 16 bit VL:
        #
        # +---------------------------------------+
        # |  0 |  1 |  2 |  3 |  4 |  5 |  6 |  7 |
        # +----+----+----+----+----+----+----+----+
        # |<Group-->|<Element>|<VR----->|<Length->|<Value->
        #
        # Implicit VRs have no VR field, then 32 bit VL:
        #
        # +---------------------------------------+
        # |  0 |  1 |  2 |  3 |  4 |  5 |  6 |  7 |
        # +----+----+----+----+----+----+----+----+
        # |<Group-->|<Element>|<Length----------->|<Value->

        foreach (array_keys($this->VR) as $vr) {
            if ($vrstr === $vr) {
                // Have a code for an explicit VR: Retrieve VR element
                [$name, $bytes, $fixed] = $this->VR[$vr];
                if ($bytes === 0) {
                    $this->vr_type = self::VR_TYPE_EXPLICIT_32_BITS;
                    // This is an OB, OW, SQ, UN or UT: 32 bit VL field.
                    // Have seen in some files length 0xffff here...
                    return $this->_readInt($IN, 4, 4);
                } else {
                    // This is an explicit VR with 16 bit length.
                    $this->vr_type = self::VR_TYPE_EXPLICIT_16_BITS;
                    return ($b[4] << 8) + $b[3];
                }
            }
        }

        // Made it to here: Implicit VR, 32 bit length.
        $this->vr_type = self::VR_TYPE_IMPLICIT;
        return ($b[4] << 24) + ($b[3] << 16) + ($b[2] << 8) + $b[1];
    }

    /**
     * Read an integer field from a file handle
     *
     * @param resource $IN file handle for the file currently being parsed
     * @param int $bytes Number of bytes for integer (2 => short, 4 => integer)
     * @param int $len   Optional total number of bytes in the field
     * @return mixed integer value if $len == $bytes, an array of integers if $len > $bytes
     */
    private function _readInt($IN, $bytes, $len)
    {
        $format = $bytes === 2 ? "v" : "V";
        $buff = fread($IN, $len);

        if ($len === $bytes) {
            $val = unpack($format, $buff);
            return $val[1] ?? '';
        } else {
            // Multiple values: Create array.
            // Change this!!!
            $vals = [];
            for ($pos = 0; $pos < $len; $pos += $bytes) {
                $vals[] = unpack($format, substr($buff, $pos, $bytes))[1];
            }
            return "[" . implode(", ", $vals) . "]";
        }
    }

    /**
     * Read a float field from a file handle
     *
     * @param resource $IN file handle for the file currently being parsed
     * @param int $bytes Number of bytes for float (4 => float, 8 => double)
     * @param int $len   Total number of bytes in the field
     * @return mixed double value if $len == $bytes, an array of doubles if $len > $bytes
     */
    private function _readFloat($IN, $bytes, $len)
    {
        $format = $bytes === 4 ? 'f' : 'd';
        $buff = fread($IN, $len);

        if ($len === $bytes) {
            $val = unpack($format, $buff);
            return $val[1] ?? '';
        } else {
            // Multiple values: Create array.
            // Change this!!!
            $vals = [];
            for ($pos = 0; $pos < $len; $pos += $bytes) {
                $vals[] = unpack($format, substr($buff, $pos, $bytes))[1];
            }
            return "[" . implode(", ", $vals) . "]";
        }
    }

    /**
     * Read the value field for this element
     *
     * @param resource $IN file handle for the file currently being parsed
     * @param string $code Value representation code
     * @param int $length Length of the value field
     * @return mixed
     */
    private function _readValue($IN, $code, $length)
    {
        switch ($code) {
            case 'UL':
                return $this->_readInt($IN, 4, $length);
            case 'US':
                return $this->_readInt($IN, 2, $length);
            case 'FL':
            case 'FD':
                return $this->_readFloat($IN, $code === 'FL' ? 4 : 8, $length);
            case 'OW':
            case 'OB':
            case 'OX':
                $value = ftell($IN);
                fseek($IN, $length, SEEK_CUR);
                return $value;
            default:
                return fread($IN, $length);
        }
    }

    /**
     * Retrieves the value field for this element.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
