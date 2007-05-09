<?php

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

require_once(BASEDIR . '/plugins/dokuwiki/inc/utf8.php');

// a-z A-Z . _ -, extended latin chars, Cyrillic and Greek and @
$UTF8_ALPHA_CHARS = array(0x40,
  0x41, 0x42, 0x43, 0x44, 0x45, 0x46, 0x47, 0x48, 0x49, 0x4a, 0x4b, 0x4c,
  0x4d, 0x4e, 0x4f, 0x50, 0x51, 0x52, 0x53, 0x54, 0x55, 0x56, 0x57, 0x58,
  0x59, 0x5a, 0x61, 0x62, 0x63, 0x64, 0x65, 0x66, 0x67, 0x68, 0x69, 0x6a,
  0x6b, 0x6c, 0x6d, 0x6e, 0x6f, 0x70, 0x71, 0x72, 0x73, 0x74, 0x75, 0x76,
  0x77, 0x78, 0x79, 0x7a, 0x30, 0x31, 0x32, 0x33, 0x34, 0x35, 0x36, 0x37,
  0x38, 0x39, 0x2e, 0x2d, 0x5f, 0x20, 0x00c1, 0x00e1, 0x0106, 0x0107,
  0x00c9, 0x00e9, 0x00cd, 0x00ed, 0x0139, 0x013a, 0x0143, 0x0144, 0x00d3,
  0x00f3, 0x0154, 0x0155, 0x015a, 0x015b, 0x00da, 0x00fa, 0x00dd, 0x00fd,
  0x0179, 0x017a, 0x010f, 0x013d, 0x013e, 0x0165, 0x0102, 0x0103, 0x011e,
  0x011f, 0x016c, 0x016d, 0x010c, 0x010d, 0x010e, 0x011a, 0x011b, 0x0147,
  0x0148, 0x0158, 0x0159, 0x0160, 0x0161, 0x0164, 0x017d, 0x017e, 0x00c7,
  0x00e7, 0x0122, 0x0123, 0x0136, 0x0137, 0x013b, 0x013c, 0x0145, 0x0146,
  0x0156, 0x0157, 0x015e, 0x015f, 0x0162, 0x0163, 0x00c2, 0x00e2, 0x0108,
  0x0109, 0x00ca, 0x00ea, 0x011c, 0x011d, 0x0124, 0x0125, 0x00ce, 0x00ee,
  0x0134, 0x0135, 0x00d4, 0x00f4, 0x015c, 0x015d, 0x00db, 0x00fb, 0x0174,
  0x0175, 0x0176, 0x0177, 0x00c4, 0x00e4, 0x00cb, 0x00eb, 0x00cf, 0x00ef,
  0x00d6, 0x00f6, 0x00dc, 0x00fc, 0x0178, 0x00ff, 0x010a, 0x010b, 0x0116,
  0x0117, 0x0120, 0x0121, 0x0130, 0x0131, 0x017b, 0x017c, 0x0150, 0x0151,
  0x0170, 0x0171, 0x00c0, 0x00e0, 0x00c8, 0x00e8, 0x00cc, 0x00ec, 0x00d2,
  0x00f2, 0x00d9, 0x00f9, 0x01a0, 0x01a1, 0x01af, 0x01b0, 0x0100, 0x0101,
  0x0112, 0x0113, 0x012a, 0x012b, 0x014c, 0x014d, 0x016a, 0x016b, 0x0104,
  0x0105, 0x0118, 0x0119, 0x012e, 0x012f, 0x0172, 0x0173, 0x00c5, 0x00e5,
  0x016e, 0x016f, 0x0110, 0x0111, 0x0126, 0x0127, 0x0141, 0x0142, 0x00d8,
  0x00f8, 0x00c3, 0x00e3, 0x00d1, 0x00f1, 0x00d5, 0x00f5, 0x00c6, 0x00e6,
  0x0152, 0x0153, 0x00d0, 0x00f0, 0x00de, 0x00fe, 0x00df, 0x017f, 0x0391,
  0x0392, 0x0393, 0x0394, 0x0395, 0x0396, 0x0397, 0x0398, 0x0399, 0x039a,
  0x039b, 0x039c, 0x039d, 0x039e, 0x039f, 0x03a0, 0x03a1, 0x03a3, 0x03a4,
  0x03a5, 0x03a6, 0x03a7, 0x03a8, 0x03a9, 0x0386, 0x0388, 0x0389, 0x038a,
  0x038c, 0x038e, 0x038f, 0x03aa, 0x03ab, 0x03b1, 0x03b2, 0x03b3, 0x03b4,
  0x03b5, 0x03b6, 0x03b7, 0x03b8, 0x03b9, 0x03ba, 0x03bb, 0x03bc, 0x03bd,
  0x03be, 0x03bf, 0x03c0, 0x03c1, 0x03c3, 0x03c2, 0x03c4, 0x03c5, 0x03c6,
  0x03c7, 0x03c8, 0x03c9, 0x03ac, 0x03ad, 0x03ae, 0x03af, 0x03cc, 0x03cd,
  0x03ce, 0x03ca, 0x03cb, 0x0390, 0x03b0, 0x0410, 0x0411, 0x0412, 0x0413,
  0x0414, 0x0415, 0x0401, 0x0416, 0x0417, 0x0406, 0x0419, 0x041a, 0x041b,
  0x041c, 0x041d, 0x041e, 0x041f, 0x0420, 0x0421, 0x0422, 0x0423, 0x040e,
  0x0424, 0x0425, 0x0426, 0x0427, 0x0428, 0x042b, 0x042c, 0x042d, 0x042e,
  0x042f, 0x0430, 0x0431, 0x0432, 0x0433, 0x0434, 0x0435, 0x0451, 0x0436,
  0x0437, 0x0456, 0x0439, 0x043a, 0x043b, 0x043c, 0x043d, 0x043e, 0x043f,
  0x0440, 0x0441, 0x0442, 0x0443, 0x045e, 0x0444, 0x0445, 0x0446, 0x0447,
  0x0448, 0x044b, 0x044c, 0x044d, 0x044e, 0x044f, 0x0418, 0x0429, 0x042a,
  0x0438, 0x0449, 0x044a, 0x0403, 0x0405, 0x0408, 0x0409, 0x040a, 0x040c,
  0x040f, 0x0453, 0x0455, 0x0458, 0x0459, 0x045a, 0x045c, 0x045f, 0x0402,
  0x040b, 0x0452, 0x045b, 0x0490, 0x0404, 0x0407, 0x0491, 0x0454, 0x0457,
  0x04e8, 0x04ae, 0x04e9, 0x04af,     
);

function utf8_keepalphanum($string)
{
    global $UTF8_ALPHA_CHARS;
    $chars = utf8_to_unicode($string);

    for ($i = 0, $size = count($chars); $i < $size; ++$i)
    {
        if (!in_array($chars[$i], $UTF8_ALPHA_CHARS))
        {
            unset($chars[$i]);
        }
    }
    return unicode_to_utf8($chars);
}

?>
