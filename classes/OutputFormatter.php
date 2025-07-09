<?php
class OutputFormatter {
    private $colorMap = [
        ';' => 'comment',
        'A' => 'record-a',
        'AAAA' => 'record-aaaa',
        'MX' => 'record-mx',
        'CNAME' => 'record-cname',
        'NS' => 'record-ns',
        'PTR' => 'record-ptr',
        'SOA' => 'record-soa',
        'TXT' => 'record-txt',
        'SRV' => 'record-srv',
        'CAA' => 'record-caa',
        'DS' => 'record-ds',
        'DNSKEY' => 'record-dnskey'
    ];
    
    public function format($output, $options = []) {
        $lines = is_array($output) ? $output : explode("\n", $output);
        $formatted = [];
        
        foreach ($lines as $line) {
            $line = htmlspecialchars($line);
            
            if (!empty($options['colorize'])) {
                $line = $this->colorizeLine($line);
            }
            
            if (!empty($options['clickable'])) {
                $line = $this->makeClickable($line);
            }
            
            $formatted[] = $line;
        }
        
        return '<pre class="dns-output">' . implode("\n", $formatted) . '</pre>';
    }
    
    public function formatMultipleResults($results, $options = []) {
        $html = '<div class="multi-nameserver-results">';
        
        foreach ($results as $result) {
            $html .= '<div class="nameserver-result">';
            $html .= '<div class="nameserver-label">Nameserver: <strong>' . htmlspecialchars($result['nameserver']) . '</strong></div>';
            $html .= $this->format($result['lines'], $options);
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    public function extractAnswerSection($lines) {
        $inAnswer = false;
        $answers = [];
        
        foreach ($lines as $line) {
            if (strpos($line, 'ANSWER SECTION') !== false) {
                $inAnswer = true;
                continue;
            }
            
            if ($inAnswer && (strpos($line, 'SECTION') !== false || trim($line) === '')) {
                break;
            }
            
            if ($inAnswer && strpos($line, ';') !== 0 && trim($line) !== '') {
                $answers[] = $line;
            }
        }
        
        return $answers;
    }
    
    private function colorizeLine($line) {
        if (strpos($line, ';') === 0) {
            return '<span class="comment">' . $line . '</span>';
        }
        
        if (strpos($line, '<<>>') !== false) {
            return '<span class="header">' . $line . '</span>';
        }
        
        if (strpos($line, 'SECTION') !== false) {
            return '<span class="section">' . $line . '</span>';
        }
        
        // Parse DNS answer line format: domain TTL class type data
        $tokens = preg_split('/\s+/', $line, 5);
        
        if (count($tokens) >= 5 && in_array($tokens[2], ['IN', 'CH', 'HS']) && isset($this->colorMap[$tokens[3]])) {
            // This looks like a DNS answer line
            $colored = [];
            $colored[] = '<span class="domain">' . $tokens[0] . '</span>';
            $colored[] = '<span class="ttl">' . $tokens[1] . '</span>';
            $colored[] = '<span class="class">' . $tokens[2] . '</span>';
            $colored[] = '<span class="' . $this->colorMap[$tokens[3]] . '">' . $tokens[3] . '</span>';
            
            // Color the data part based on type
            $data = $tokens[4];
            if ($tokens[3] === 'A' || $tokens[3] === 'AAAA') {
                $data = '<span class="ip-address">' . $data . '</span>';
            } elseif (in_array($tokens[3], ['CNAME', 'NS', 'PTR', 'MX'])) {
                $data = '<span class="domain">' . $data . '</span>';
            }
            $colored[] = $data;
            
            return implode(' ', $colored);
        }
        
        // Fallback to simple tokenization
        $tokens = preg_split('/\s+/', $line);
        $recordTypeFound = false;
        
        foreach ($tokens as $i => $token) {
            if (isset($this->colorMap[$token])) {
                $tokens[$i] = '<span class="' . $this->colorMap[$token] . '">' . $token . '</span>';
                $recordTypeFound = true;
            } elseif ($recordTypeFound && $this->isIPAddress($token)) {
                $tokens[$i] = '<span class="ip-address">' . $token . '</span>';
            } elseif ($this->isDomain($token)) {
                $tokens[$i] = '<span class="domain">' . $token . '</span>';
            }
        }
        
        return implode(' ', $tokens);
    }
    
    private function makeClickable($line) {
        $line = preg_replace_callback(
            '/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/',
            function($matches) {
                return '<a href="#" class="clickable-ip" data-ip="' . $matches[0] . '">' . $matches[0] . '</a>';
            },
            $line
        );
        
        $line = preg_replace_callback(
            '/\b((?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,})\b/',
            function($matches) {
                if (strpos($matches[1], '.') !== false && !$this->isIPAddress($matches[1])) {
                    return '<a href="#" class="clickable-domain" data-domain="' . $matches[1] . '">' . $matches[1] . '</a>';
                }
                return $matches[0];
            },
            $line
        );
        
        $line = preg_replace_callback(
            '/\b((?:[0-9a-fA-F]{0,4}:){2,7}[0-9a-fA-F]{0,4})\b/',
            function($matches) {
                return '<a href="#" class="clickable-ip" data-ip="' . $matches[0] . '">' . $matches[0] . '</a>';
            },
            $line
        );
        
        return $line;
    }
    
    private function isIPAddress($str) {
        return filter_var($str, FILTER_VALIDATE_IP) !== false;
    }
    
    private function isDomain($str) {
        return preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $str);
    }
    
    public function formatPlain($output) {
        if (is_array($output)) {
            $output = implode("\n", $output);
        }
        
        return '<pre class="dns-output-plain">' . htmlspecialchars($output) . '</pre>';
    }
    
    public function formatSimplified($lines, $options = []) {
        // Extract just the answer section when no special options are selected
        $answers = $this->extractAnswerSection($lines);
        
        if (empty($answers)) {
            // If no answer section, show the full output
            return $this->format($lines, $options);
        }
        
        $formatted = [];
        foreach ($answers as $answer) {
            $line = htmlspecialchars($answer);
            
            // Simple formatting for answer lines
            $parts = preg_split('/\s+/', $line, 5);
            if (count($parts) >= 5) {
                // Format: domain TTL class type data
                $formatted[] = sprintf(
                    '<span class="answer-domain">%s</span> <span class="answer-ttl">%s</span> <span class="answer-class">%s</span> <span class="answer-type %s">%s</span> <span class="answer-data">%s</span>',
                    $parts[0],
                    $parts[1],
                    $parts[2],
                    'record-' . strtolower($parts[3]),
                    $parts[3],
                    $parts[4]
                );
            } else {
                $formatted[] = $line;
            }
        }
        
        return '<pre class="dns-output dns-output-simple">' . implode("\n", $formatted) . '</pre>';
    }
    
    public function highlightQuery($output, $query) {
        $query = preg_quote($query, '/');
        return preg_replace("/($query)/i", '<mark>$1</mark>', $output);
    }
}