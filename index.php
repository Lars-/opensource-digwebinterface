<?php
session_start();

require_once 'classes/DNSQuery.php';
require_once 'classes/OutputFormatter.php';

$config = require 'config/config.php';

$dnsQuery = new DNSQuery($config);
$formatter = new OutputFormatter();

$results = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET['hostnames'])) {
    try {
        $data = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        
        // Parse hostnames from textarea
        $hostnamesText = isset($data['hostnames']) ? $data['hostnames'] : '';
        if (is_array($hostnamesText)) {
            $hostnames = $hostnamesText;
        } else {
            $hostnames = array_filter(array_map('trim', explode("\n", $hostnamesText)), 'strlen');
        }
        
        $recordType = isset($data['type']) ? $data['type'] : '';
        $options = isset($data['options']) ? $data['options'] : [];
        $nsType = isset($data['ns_type']) ? $data['ns_type'] : 'resolver';
        $resolver = isset($data['resolver']) ? $data['resolver'] : 'default';
        
        // Determine nameservers based on ns_type selection
        $nameserverGroups = [];
        
        if ($nsType === 'all') {
            // Query all resolvers
            foreach ($config['resolvers'] as $key => $resolverConfig) {
                if (!empty($resolverConfig['servers'])) {
                    $nameserverGroups[$resolverConfig['name']] = $resolverConfig['servers'];
                }
            }
        } elseif ($nsType === 'custom') {
            // Parse custom nameservers from textarea
            $customNS = isset($data['custom_nameservers']) ? $data['custom_nameservers'] : '';
            $customServers = array_filter(array_map('trim', explode("\n", $customNS)), 'strlen');
            if (!empty($customServers)) {
                $nameserverGroups['Custom'] = $customServers;
            }
        } elseif ($nsType === 'authoritative') {
            // Will be handled specially in DNSQuery class
            $nameserverGroups['Authoritative'] = ['authoritative'];
        } elseif ($nsType === 'nic') {
            // Will be handled specially in DNSQuery class
            $nameserverGroups['NIC'] = ['nic'];
        } elseif ($nsType === 'resolver') {
            // Single resolver selected
            if (isset($config['resolvers'][$resolver])) {
                $resolverConfig = $config['resolvers'][$resolver];
                if (!empty($resolverConfig['servers'])) {
                    $nameserverGroups[$resolverConfig['name']] = $resolverConfig['servers'];
                } else {
                    // Default resolver (system)
                    $nameserverGroups['System Default'] = [];
                }
            }
        }
        
        $results = [];
        
        foreach ($hostnames as $hostname) {
            if (isset($data['fix']) && $data['fix']) {
                if (strpos($hostname, '@') !== false) {
                    $hostname = $dnsQuery->parseIPFromEmail($hostname);
                } elseif (strpos($hostname, '://') !== false || strpos($hostname, '/') !== false) {
                    $hostname = $dnsQuery->parseHostFromURL($hostname);
                }
            }
            
            // Query each nameserver group
            foreach ($nameserverGroups as $groupName => $nameservers) {
                $queryResult = $dnsQuery->query($hostname, $recordType, $nameservers, $options);
                
                $formatOptions = [
                    'colorize' => isset($data['colorize']) && $data['colorize'],
                    'clickable' => true
                ];
                
                // Check if we should use simplified output
                $useSimplified = empty($options['short']) && empty($options['trace']) && 
                                empty($options['dnssec']) && empty($options['noquestion']) && 
                                empty($options['nocomments']) && empty($options['nostats']);
                
                if ($queryResult['multiple']) {
                    // Multiple nameservers queried separately
                    $formatted = $formatter->formatMultipleResults($queryResult['results'], $formatOptions);
                    $plain = '';
                    $commands = [];
                    
                    foreach ($queryResult['results'] as $res) {
                        $plain .= "=== Nameserver: {$res['nameserver']} ===\n";
                        $plain .= $res['output'] . "\n\n";
                        $commands[] = $res['command'];
                    }
                    
                    $results[] = [
                        'hostname' => $hostname,
                        'type' => $recordType,
                        'resolver_name' => $groupName,
                        'nameservers' => $nameservers,
                        'formatted' => $formatted,
                        'plain' => $plain,
                        'command' => implode("\n", $commands),
                        'multiple' => true
                    ];
                } else {
                    // Single query
                    $result = $queryResult['results'][0];
                    
                    $results[] = [
                        'hostname' => $hostname,
                        'type' => $recordType,
                        'resolver_name' => $groupName,
                        'nameservers' => $nameservers,
                        'formatted' => $formatter->format($result['lines'], $formatOptions),
                        'plain' => $result['output'],
                        'command' => isset($queryResult['command']) ? $queryResult['command'] : $result['command'],
                        'multiple' => false
                    ];
                }
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = $config['site_name'];
$pageDescription = $config['site_description'];

// Note: This file handles synchronous form submissions for non-JS users
// The AJAX version is handled by api/query.php and assets/script-ajax.js

include 'templates/layout.php';