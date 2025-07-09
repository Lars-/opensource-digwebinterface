<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';
require_once '../classes/DNSQuery.php';
require_once '../classes/OutputFormatter.php';

$config = require '../config/config.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $hostname = $input['hostname'] ?? '';
    $recordType = $input['type'] ?? '';
    $nameserver = $input['nameserver'] ?? null;
    $options = $input['options'] ?? [];
    $resolverName = $input['resolver_name'] ?? 'Default';
    
    // Special mode to just get nameservers
    if (!empty($input['get_ns_only'])) {
        $dnsQuery = new DNSQuery($config);
        
        // Get NS records
        $nsCommand = '/usr/bin/dig +short ' . escapeshellarg($hostname) . ' NS';
        $output = [];
        exec($nsCommand, $output, $returnCode);
        
        $nameservers = [];
        foreach ($output as $line) {
            $line = trim($line);
            if (!empty($line) && strpos($line, ';') !== 0) {
                $nameservers[] = rtrim($line, '.');
            }
        }
        
        echo json_encode([
            'success' => true,
            'nameservers' => $nameservers,
            'command' => $nsCommand
        ]);
        exit;
    }
    
    // Special mode for getting TLD nameservers for NIC queries
    if (!empty($input['get_tld_ns'])) {
        $tld = $input['tld'] ?? '';
        $rootServers = ['a.root-servers.net', 'b.root-servers.net', 'c.root-servers.net'];
        $tldNameservers = [];
        
        foreach ($rootServers as $root) {
            $tldCommand = '/usr/bin/dig @' . escapeshellarg($root) . ' ' . escapeshellarg($tld) . ' NS +noall +authority +answer';
            $output = [];
            exec($tldCommand, $output, $returnCode);
            
            foreach ($output as $line) {
                if (preg_match('/\s+NS\s+(\S+)\.?$/i', $line, $matches)) {
                    $ns = rtrim($matches[1], '.');
                    if (!in_array($ns, $tldNameservers)) {
                        $tldNameservers[] = $ns;
                    }
                }
            }
            
            if (!empty($tldNameservers)) {
                break;
            }
        }
        
        echo json_encode([
            'success' => true,
            'nameservers' => $tldNameservers,
            'command' => $tldCommand
        ]);
        exit;
    }
    
    // Initialize DNS query
    $dnsQuery = new DNSQuery($config);
    $formatter = new OutputFormatter();
    
    // Fix hostnames if requested
    if (!empty($input['fix'])) {
        if (filter_var($hostname, FILTER_VALIDATE_EMAIL)) {
            $hostname = $dnsQuery->parseIPFromEmail($hostname);
        } elseif (preg_match('/^https?:\/\//i', $hostname) || strpos($hostname, '/') !== false) {
            $hostname = $dnsQuery->parseHostFromURL($hostname);
        }
    }
    
    // Prepare nameservers array
    $nameservers = $nameserver ? [$nameserver] : [];
    
    // Perform query
    $queryResult = $dnsQuery->query($hostname, $recordType, $nameservers, $options);
    
    // Format options
    $formatOptions = [
        'colorize' => !empty($input['colorize']),
        'clickable' => true
    ];
    
    // Get the result (should be single since we're querying one nameserver)
    $result = $queryResult['results'][0];
    
    $response = [
        'success' => true,
        'data' => [
            'hostname' => $hostname,
            'type' => $recordType,
            'resolver_name' => $resolverName,
            'nameserver' => $nameserver,
            'formatted' => $formatter->format($result['lines'], $formatOptions),
            'plain' => $result['output'],
            'command' => $result['command']
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response);