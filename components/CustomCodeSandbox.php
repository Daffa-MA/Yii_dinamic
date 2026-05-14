<?php

namespace app\components;

class CustomCodeSandbox
{
    public static function sanitizeHtml(?string $html): string
    {
        if ($html === null) {
            return '';
        }
        return preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? '';
    }

    public static function sanitizeCss(?string $css): string
    {
        if ($css === null) {
            return '';
        }
        return str_replace(['</style>', '<style>'], '', $css);
    }

    public static function sanitizeJs(?string $js): string
    {
        if ($js === null) {
            return '';
        }

        $denyPatterns = [
            '/\bdocument\.cookie\b/i',
            '/\blocalStorage\b/i',
            '/\bsessionStorage\b/i',
            '/\beval\s*\(/i',
            '/\bFunction\s*\(/i',
            '/\bXMLHttpRequest\b/i',
            '/\bfetch\s*\(/i',
        ];

        $sanitized = $js;
        foreach ($denyPatterns as $pattern) {
            $sanitized = preg_replace($pattern, '/* blocked */', $sanitized) ?? $sanitized;
        }

        return $sanitized;
    }
}

