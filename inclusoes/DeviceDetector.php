<?php
// includes/DeviceDetector.php

class DeviceDetector {
    
    public static function getInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip = self::getIP();
        
        return [
            'ip' => $ip,
            'type' => self::getDeviceType($userAgent),
            'brand' => self::getDeviceBrand($userAgent),
            'user_agent' => $userAgent
        ];
    }

    public static function getIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }

    private static function getDeviceType($userAgent) {
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $userAgent)) {
            return 'Tablet';
        }
        
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            return 'Mobile';
        }
        
        return 'Computador';
    }

    private static function getDeviceBrand($userAgent) {
        $userAgent = strtolower($userAgent);
        
        if (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false || strpos($userAgent, 'macintosh') !== false) {
            return 'Apple';
        }
        
        if (strpos($userAgent, 'samsung') !== false || strpos($userAgent, 'sm-') !== false) {
            return 'Samsung';
        }
        
        if (strpos($userAgent, 'huawei') !== false) {
            return 'Huawei';
        }
        
        if (strpos($userAgent, 'xiaomi') !== false || strpos($userAgent, 'mi ') !== false) {
            return 'Xiaomi';
        }

        if (strpos($userAgent, 'oppo') !== false) {
            return 'Oppo';
        }

        if (strpos($userAgent, 'pixel') !== false) {
            return 'Google';
        }

        if (strpos($userAgent, 'windows') !== false) {
            return 'Microsoft / Windows PC';
        }

        if (strpos($userAgent, 'linux') !== false) {
            return 'Linux';
        }
        
        return 'Genérico / Outro';
    }
    public static function getLocation($ip) {
        // Mock for Localhost
        if ($ip == '127.0.0.1' || $ip == '::1') {
            return [
                'country' => 'Angola (Localhost)',
                'city' => 'Luanda',
                'region' => 'Luanda',
                'isp' => 'Rede Local',
                'lat' => -8.839988,
                'lon' => 13.289437
            ];
        }

        try {
            // Using ip-api.com (Free for non-commercial)
            $ctx = stream_context_create(['http' => ['timeout' => 2]]);
            $json = @file_get_contents("http://ip-api.com/json/{$ip}", false, $ctx);
            
            if ($json) {
                $data = json_decode($json, true);
                if ($data && $data['status'] == 'success') {
                    return [
                        'country' => $data['country'] ?? 'Desconhecido',
                        'city' => $data['city'] ?? 'Desconhecido',
                        'region' => $data['regionName'] ?? 'Desconhecido',
                        'isp' => $data['isp'] ?? 'Desconhecido',
                        'lat' => $data['lat'] ?? 0,
                        'lon' => $data['lon'] ?? 0
                    ];
                }
            }
        } catch (Exception $e) {
            // Silent fail
        }

        return [
            'country' => 'Desconhecido',
            'city' => '-',
            'region' => '-',
            'isp' => '-',
            'lat' => 0,
            'lon' => 0
        ];
    }
}
