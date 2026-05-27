<?php
// includes/QRCodeGenerator.php
// Simple QR Code generator using data URI

class QRCodeGenerator {
    
    /**
     * Generate QR Code as base64 data URI using Google Charts API
     * Falls back to a simple text representation if API fails
     */
    public static function generateDataURI($data, $size = 200) {
        $url = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&chld=M|0&cht=qr&chl=' . urlencode($data);
        
        // Try to fetch the QR code image
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        
        $imageData = @file_get_contents($url, false, $context);
        
        if ($imageData !== false) {
            // Convert to base64 data URI
            return 'data:image/png;base64,' . base64_encode($imageData);
        }
        
        // Fallback: return the Google Charts URL directly
        return $url;
    }
    
    /**
     * Generate QR Code using alternative method (SVG)
     * This is a simple fallback that creates a text-based representation
     */
    public static function generateSVG($data, $size = 200) {
        // For now, return a placeholder SVG
        // In production, you'd use a library like endroid/qr-code
        $svg = '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">
            <rect width="100%" height="100%" fill="white"/>
            <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-size="12" fill="black">
                QR Code
            </text>
        </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
?>
