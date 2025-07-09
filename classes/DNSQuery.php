<?php

/**
 * DNS Query Handler
 * 
 * Handles DNS lookups using the dig command with support for various record types,
 * nameservers, and query options. Provides security through input sanitization.
 * 
 * @package OpenSourceDIG
 * @since 1.0.0
 */
class DNSQuery {
    /**
     * @var array Configuration array containing DNS settings
     */
    private array $config;
    
    /**
     * @var string Path to the dig executable
     */
    private string $digPath;
    
    /**
     * Constructor
     * 
     * @param array $config Configuration array with dig_path and other settings
     * @throws \Exception If dig command is not found or not executable
     */
    public function __construct(array $config) {
        $this->config = $config;
        $this->digPath = $config['dig_path'];
        
        if (!file_exists($this->digPath) || !is_executable($this->digPath)) {
            throw new Exception("dig command not found or not executable at: {$this->digPath}");
        }
    }
    
    /**
     * Perform DNS query
     * 
     * Executes DNS queries with support for multiple nameservers, authoritative lookups,
     * and NIC (registry) queries.
     * 
     * @param string $hostname The hostname or IP address to query
     * @param string $recordType The DNS record type (A, AAAA, MX, etc.)
     * @param array $nameservers Array of nameserver IPs or special values ('authoritative', 'nic')
     * @param array $options Query options (short, trace, dnssec, etc.)
     * @return array Query results with 'multiple' flag and 'results' array
     * @throws \Exception If query fails or invalid parameters provided
     */
    public function query(string $hostname, string $recordType = 'A', array $nameservers = [], array $options = []): array {
        $hostname = $this->sanitizeHostname($hostname);
        
        // Handle reverse lookup
        if ($recordType === 'Reverse' || ($recordType === '' && $this->isIPAddress($hostname))) {
            $recordType = 'PTR';
            if ($this->isIPAddress($hostname)) {
                $hostname = $this->getReverseDNS($hostname);
            }
        }
        
        $recordType = $this->sanitizeRecordType($recordType);
        $nameservers = $this->sanitizeNameservers($nameservers);
        
        // Handle authoritative nameserver queries
        if (!empty($nameservers) && $nameservers[0] === 'authoritative') {
            // Extract domain from hostname (e.g., www.example.com -> example.com)
            $domain = $this->extractDomain($hostname);
            
            // First, get the NS records for the domain
            $nsCommand = escapeshellcmd($this->digPath) . ' +short ' . escapeshellarg($domain) . ' NS';
            $nsResult = $this->executeCommand($nsCommand);
            
            $authNameservers = [];
            foreach ($nsResult['lines'] as $line) {
                $line = trim($line);
                if (!empty($line) && strpos($line, ';') !== 0) {
                    // Remove trailing dot if present
                    $authNameservers[] = rtrim($line, '.');
                }
            }
            
            if (empty($authNameservers)) {
                throw new Exception("No authoritative nameservers found for $domain");
            }
            
            // Now query each authoritative nameserver
            $results = [];
            foreach ($authNameservers as $ns) {
                $command = $this->buildCommand($hostname, $recordType, [$ns], $options);
                $result = $this->executeCommand($command);
                $results[] = [
                    'nameserver' => $ns,
                    'command' => $result['command'],
                    'output' => $result['output'],
                    'lines' => $result['lines'],
                    'return_code' => $result['return_code']
                ];
            }
            
            return [
                'multiple' => true,
                'results' => $results,
                'authoritative' => true,
                'ns_lookup_command' => $nsCommand
            ];
        }
        
        // Handle NIC (registry) nameserver queries
        if (!empty($nameservers) && $nameservers[0] === 'nic') {
            // Extract TLD from hostname
            $tld = $this->extractTLD($hostname);
            
            if (empty($tld)) {
                throw new Exception("Cannot determine TLD for NIC query");
            }
            
            // Query root servers for TLD NS records
            $rootServers = ['a.root-servers.net', 'b.root-servers.net', 'c.root-servers.net'];
            $tldNameservers = [];
            
            foreach ($rootServers as $root) {
                $tldCommand = escapeshellcmd($this->digPath) . ' @' . escapeshellarg($root) . ' ' . escapeshellarg($tld) . ' NS +noall +authority +answer';
                $tldResult = $this->executeCommand($tldCommand);
                
                // Parse TLD nameservers from the response
                foreach ($tldResult['lines'] as $line) {
                    if (preg_match('/\s+NS\s+(\S+)\.?$/i', $line, $matches)) {
                        $ns = rtrim($matches[1], '.');
                        if (!in_array($ns, $tldNameservers)) {
                            $tldNameservers[] = $ns;
                        }
                    }
                }
                
                if (!empty($tldNameservers)) {
                    break; // Got nameservers, no need to query other root servers
                }
            }
            
            if (empty($tldNameservers)) {
                throw new Exception("No TLD nameservers found for .$tld");
            }
            
            // Now query each TLD nameserver
            $results = [];
            foreach ($tldNameservers as $ns) {
                $command = $this->buildCommand($hostname, $recordType, [$ns], $options);
                $result = $this->executeCommand($command);
                $results[] = [
                    'nameserver' => $ns,
                    'command' => $result['command'],
                    'output' => $result['output'],
                    'lines' => $result['lines'],
                    'return_code' => $result['return_code']
                ];
            }
            
            return [
                'multiple' => true,
                'results' => $results,
                'nic' => true,
                'tld_lookup_command' => $tldCommand
            ];
        }
        
        // If multiple nameservers, query each one separately
        if (count($nameservers) > 1 && $nameservers[0] !== 'nic') {
            $results = [];
            foreach ($nameservers as $ns) {
                $command = $this->buildCommand($hostname, $recordType, [$ns], $options);
                $result = $this->executeCommand($command);
                $results[] = [
                    'nameserver' => $ns,
                    'command' => $result['command'],
                    'output' => $result['output'],
                    'lines' => $result['lines'],
                    'return_code' => $result['return_code']
                ];
            }
            return [
                'multiple' => true,
                'results' => $results
            ];
        } else {
            $command = $this->buildCommand($hostname, $recordType, $nameservers, $options);
            $result = $this->executeCommand($command);
            return [
                'multiple' => false,
                'results' => [$result],
                'command' => $command
            ];
        }
    }
    
    /**
     * Sanitize hostname input
     * 
     * Removes potentially dangerous characters and validates hostname format
     * 
     * @param string $hostname Raw hostname input
     * @return string Sanitized hostname
     * @throws \InvalidArgumentException If hostname is empty or invalid
     */
    private function sanitizeHostname(string $hostname): string {
        $hostname = trim($hostname);
        
        if (empty($hostname)) {
            throw new InvalidArgumentException("Hostname cannot be empty");
        }
        
        $hostname = preg_replace('/[^a-zA-Z0-9\.\-_:]/', '', $hostname);
        
        if (filter_var($hostname, FILTER_VALIDATE_IP)) {
            return $hostname;
        }
        
        if (!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)*[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?$/', $hostname)) {
            throw new InvalidArgumentException("Invalid hostname format");
        }
        
        return $hostname;
    }
    
    /**
     * Sanitize and validate record type
     * 
     * @param string $recordType DNS record type
     * @return string Validated record type
     * @throws \InvalidArgumentException If record type is invalid
     */
    private function sanitizeRecordType(string $recordType): string {
        $recordType = trim($recordType);
        
        // Handle empty/unspecified - keep it empty
        if ($recordType === '') {
            return '';
        }
        
        // Special cases that don't need uppercase
        if (in_array($recordType, ['Reverse'])) {
            return $recordType;
        }
        
        $recordType = strtoupper($recordType);
        
        if (!isset($this->config['record_types'][$recordType]) && $recordType !== '') {
            throw new InvalidArgumentException("Invalid record type");
        }
        
        return $recordType;
    }
    
    /**
     * Sanitize nameserver addresses
     * 
     * Validates IP addresses and hostnames for nameservers
     * 
     * @param array $nameservers Array of nameserver addresses
     * @return array Sanitized nameserver addresses
     */
    private function sanitizeNameservers(array $nameservers): array {
        $sanitized = [];
        
        foreach ($nameservers as $ns) {
            $ns = trim($ns);
            
            if (empty($ns)) {
                continue;
            }
            
            if (!filter_var($ns, FILTER_VALIDATE_IP)) {
                $ns = preg_replace('/[^a-zA-Z0-9\.\-]/', '', $ns);
                
                if (!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)*[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?$/', $ns)) {
                    continue;
                }
            }
            
            $sanitized[] = $ns;
        }
        
        return $sanitized;
    }
    
    /**
     * Build dig command with proper escaping
     * 
     * @param string $hostname Hostname to query
     * @param string $recordType DNS record type
     * @param array $nameservers Nameserver addresses
     * @param array $options Query options
     * @return string Complete dig command
     */
    private function buildCommand(string $hostname, string $recordType, array $nameservers, array $options): string {
        $parts = [escapeshellcmd($this->digPath)];
        
        // Add nameservers
        if (!empty($nameservers)) {
            foreach ($nameservers as $ns) {
                $parts[] = '@' . escapeshellarg($ns);
            }
        }
        
        $parts[] = escapeshellarg($hostname);
        
        // Only add record type if it's not empty
        if ($recordType !== '') {
            $parts[] = escapeshellarg($recordType);
        }
        
        // Default to clean output (just answer section) unless other options override
        $hasOutputOptions = false;
        
        if (isset($options['short']) && $options['short']) {
            $parts[] = '+short';
            $hasOutputOptions = true;
        }
        
        if (isset($options['trace']) && $options['trace']) {
            $parts[] = '+trace';
            $hasOutputOptions = true;
        }
        
        if (isset($options['dnssec']) && $options['dnssec']) {
            $parts[] = '+dnssec';
        }
        
        if (isset($options['noquestion']) && $options['noquestion']) {
            $parts[] = '+noquestion';
            $hasOutputOptions = true;
        }
        
        if (isset($options['nocomments']) && $options['nocomments']) {
            $parts[] = '+nocomments';
            $hasOutputOptions = true;
        }
        
        if (isset($options['nostats']) && $options['nostats']) {
            $parts[] = '+nostats';
            $hasOutputOptions = true;
        }
        
        // If no specific output options were set, use clean answer-only format
        if (!$hasOutputOptions && empty($options['trace'])) {
            $parts[] = '+noall';
            $parts[] = '+answer';
        }
        
        if (isset($options['tcp']) && $options['tcp']) {
            $parts[] = '+tcp';
        }
        
        if (isset($options['recurse']) && !$options['recurse']) {
            $parts[] = '+norecurse';
        }
        
        $parts[] = '+time=' . intval($this->config['default_timeout']);
        
        return implode(' ', $parts);
    }
    
    /**
     * Execute dig command and capture output
     * 
     * @param string $command The dig command to execute
     * @return array Command output with lines, full output, and return code
     * @throws \Exception If command execution fails
     */
    private function executeCommand(string $command): array {
        $output = [];
        $returnCode = 0;
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0 && $returnCode !== 1) {
            throw new Exception("DNS query failed: " . implode("\n", $output));
        }
        
        // Check if there's no answer section and we should try with authority
        $hasAnswer = false;
        foreach ($output as $line) {
            if (trim($line) && strpos($line, ';') !== 0) {
                $hasAnswer = true;
                break;
            }
        }
        
        // If no answer and command doesn't already have authority, retry with authority
        if (!$hasAnswer && strpos($command, '+authority') === false && strpos($command, '+noall +answer') !== false) {
            $authorityCommand = str_replace('+noall +answer', '+noall +authority', $command);
            $authorityOutput = [];
            $authorityReturnCode = 0;
            
            exec($authorityCommand . ' 2>&1', $authorityOutput, $authorityReturnCode);
            
            if ($authorityReturnCode === 0 || $authorityReturnCode === 1) {
                // Use authority output if it has content
                $hasAuthority = false;
                foreach ($authorityOutput as $line) {
                    if (trim($line) && strpos($line, ';') !== 0) {
                        $hasAuthority = true;
                        break;
                    }
                }
                
                if ($hasAuthority) {
                    return [
                        'command' => $authorityCommand,
                        'output' => implode("\n", $authorityOutput),
                        'lines' => $authorityOutput,
                        'return_code' => $authorityReturnCode
                    ];
                }
            }
        }
        
        return [
            'command' => $command,
            'output' => implode("\n", $output),
            'lines' => $output,
            'return_code' => $returnCode
        ];
    }
    
    /**
     * Extract domain from email address
     * 
     * @param string $email Email address
     * @return string Domain part of email
     */
    public function parseIPFromEmail(string $email): string {
        $email = trim($email);
        if (strpos($email, '@') !== false) {
            $parts = explode('@', $email);
            return isset($parts[1]) ? $parts[1] : '';
        }
        return $email;
    }
    
    /**
     * Extract hostname from URL
     * 
     * @param string $url URL to parse
     * @return string Hostname extracted from URL
     */
    public function parseHostFromURL(string $url): string {
        $url = trim($url);
        
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'http://' . $url;
        }
        
        $parsed = parse_url($url);
        
        return isset($parsed['host']) ? $parsed['host'] : $url;
    }
    
    /**
     * Check if string is a valid IP address
     * 
     * @param string $str String to check
     * @return bool True if valid IP address
     */
    private function isIPAddress(string $str): bool {
        return filter_var($str, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Convert IP address to reverse DNS format
     * 
     * @param string $ip IP address (IPv4 or IPv6)
     * @return string Reverse DNS format (in-addr.arpa or ip6.arpa)
     */
    private function getReverseDNS(string $ip): string {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4 reverse DNS
            $octets = explode('.', $ip);
            return implode('.', array_reverse($octets)) . '.in-addr.arpa';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6 reverse DNS
            $addr = inet_pton($ip);
            $unpack = unpack('H*hex', $addr);
            $hex = $unpack['hex'];
            $arpa = implode('.', array_reverse(str_split($hex))) . '.ip6.arpa';
            return $arpa;
        }
        return $ip;
    }
    
    /**
     * Extract base domain from hostname
     * 
     * @param string $hostname Full hostname
     * @return string Base domain (e.g., example.com from www.example.com)
     */
    private function extractDomain(string $hostname): string {
        // For IP addresses, return as is
        if ($this->isIPAddress($hostname)) {
            return $hostname;
        }
        
        // Remove trailing dot if present
        $hostname = rtrim($hostname, '.');
        
        // Split into parts
        $parts = explode('.', $hostname);
        
        // If it's already a domain (2 parts), return as is
        if (count($parts) <= 2) {
            return $hostname;
        }
        
        // For subdomains, try to extract the main domain
        // This is simplified - in production you'd use a proper TLD list
        return implode('.', array_slice($parts, -2));
    }
    
    /**
     * Extract top-level domain from hostname
     * 
     * @param string $hostname Hostname
     * @return string TLD (e.g., com, org, net)
     */
    private function extractTLD(string $hostname): string {
        // For IP addresses, return empty
        if ($this->isIPAddress($hostname)) {
            return '';
        }
        
        // Remove trailing dot if present
        $hostname = rtrim($hostname, '.');
        
        // Split into parts
        $parts = explode('.', $hostname);
        
        // Get the last part (TLD)
        if (count($parts) >= 1) {
            return end($parts);
        }
        
        return '';
    }
}