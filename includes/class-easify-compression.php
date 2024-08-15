<?php

/**
 * Copyright (C) 2024  Easify Ltd (email:support@easify.co.uk)
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Provides facilities to decompress zipped Json data streams from an Easify Server.
 *
 * @class       class-easify-compression
 * @version     4.36
 * @package     easify-woocommerce-connector
 * @author      Easify
 */

function decompressString($compressedText) {
    $gZipBuffer = base64_decode($compressedText);

    $dataLength = unpack('V', substr($gZipBuffer, 0, 4))[1];
    $buffer = substr($gZipBuffer, 4);

    // Open a memory stream for reading the gzip data
    $memoryStream = fopen('php://temp', 'r+');
    fwrite($memoryStream, $buffer);
    rewind($memoryStream);

    // Decompress the buffer using gzinflate directly
    $decompressedData = gzdecode($buffer);

    fclose($memoryStream);

    // Decode JSON string if it's JSON encoded
    $decompressedData = json_decode($decompressedData, true);

    return $decompressedData;
}