<?php
return [
    'site_name' => 'Open Source DIG Web Interface',
    'site_description' => 'A web-based DNS lookup tool',
    
    'dns_method' => 'dig',
    'dig_path' => '/usr/bin/dig', // In DDEV this path is correct
    'default_timeout' => 5,
    
    
    'resolvers' => [
        'default' => [
            'name' => 'Default',
            'servers' => [] // Use system default
        ],
        'adguard' => [
            'name' => 'AdGuard (CY)',
            'servers' => ['94.140.14.14', '94.140.15.15']
        ],
        'att' => [
            'name' => 'AT&T (US)',
            'servers' => ['68.94.156.1', '68.94.157.1']
        ],
        'cloudflare' => [
            'name' => 'Cloudflare',
            'servers' => ['1.1.1.1', '1.0.0.1']
        ],
        'comodo' => [
            'name' => 'Comodo (US)',
            'servers' => ['8.26.56.26', '8.20.247.20']
        ],
        'google' => [
            'name' => 'Google',
            'servers' => ['8.8.8.8', '8.8.4.4']
        ],
        'hinet' => [
            'name' => 'HiNet (TW)',
            'servers' => ['168.95.1.1', '168.95.192.1']
        ],
        'opendns' => [
            'name' => 'OpenDNS',
            'servers' => ['208.67.222.222', '208.67.220.220']
        ],
        'quad9' => [
            'name' => 'Quad9',
            'servers' => ['9.9.9.9', '149.112.112.112']
        ],
        'securolytics' => [
            'name' => 'Securolytics (CA)',
            'servers' => ['89.233.43.71']
        ],
        'uunet_ch' => [
            'name' => 'UUNET (CH)',
            'servers' => ['195.129.12.122', '195.129.12.83']
        ],
        'uunet_de' => [
            'name' => 'UUNET (DE)',
            'servers' => ['62.134.11.4']
        ],
        'uunet_uk' => [
            'name' => 'UUNET (UK)',
            'servers' => ['195.99.66.220']
        ],
        'uunet_us' => [
            'name' => 'UUNET (US)',
            'servers' => ['64.105.202.138', '64.105.199.76']
        ],
        'verisign' => [
            'name' => 'Verisign (US)',
            'servers' => ['64.6.64.6', '64.6.65.6']
        ],
        'yandex' => [
            'name' => 'Yandex (RU)',
            'servers' => ['77.88.8.8', '77.88.8.1']
        ]
    ],
    
    'record_types' => [
        '' => 'Unspecified',
        'A' => 'A',
        'AAAA' => 'AAAA',
        'ANY' => 'ANY',
        'AXFR' => 'AXFR',
        'CNAME' => 'CNAME',
        'MX' => 'MX',
        'NS' => 'NS',
        'PTR' => 'PTR',
        'SOA' => 'SOA',
        'TXT' => 'TXT',
        'Reverse' => 'Reverse',
        'A6' => 'A6',
        'AFSDB' => 'AFSDB',
        'APL' => 'APL',
        'ATMA' => 'ATMA',
        'CAA' => 'CAA',
        'CERT' => 'CERT',
        'DNAME' => 'DNAME',
        'DNSKEY' => 'DNSKEY',
        'DS' => 'DS',
        'EID' => 'EID',
        'GPOS' => 'GPOS',
        'HINFO' => 'HINFO',
        'HTTPS' => 'HTTPS',
        'ISDN' => 'ISDN',
        'KEY' => 'KEY',
        'KX' => 'KX',
        'LOC' => 'LOC',
        'MB' => 'MB',
        'MD' => 'MD',
        'MF' => 'MF',
        'MG' => 'MG',
        'MINFO' => 'MINFO',
        'MR' => 'MR',
        'NAPTR' => 'NAPTR',
        'NIMLOC' => 'NIMLOC',
        'NSAP' => 'NSAP',
        'NSAP-PTR' => 'NSAP-PTR',
        'NSEC' => 'NSEC',
        'NSEC3' => 'NSEC3',
        'NSEC3PARAM' => 'NSEC3PARAM',
        'NULL' => 'NULL',
        'NXT' => 'NXT',
        'OPT' => 'OPT',
        'PX' => 'PX',
        'RP' => 'RP',
        'RRSIG' => 'RRSIG',
        'RT' => 'RT',
        'SIG' => 'SIG',
        'SINK' => 'SINK',
        'SPF' => 'SPF',
        'SRV' => 'SRV',
        'SSHFP' => 'SSHFP',
        'SVCB' => 'SVCB',
        'TLSA' => 'TLSA',
        'TSIG' => 'TSIG',
        'UID' => 'UID',
        'UINFO' => 'UINFO',
        'UNSPEC' => 'UNSPEC',
        'WKS' => 'WKS',
        'X25' => 'X25'
    ],
    
    'max_hostnames' => 10,
    'max_nameservers' => 5,
    
    'options' => [
        'short' => 'Short output (+short)',
        'trace' => 'Trace query path (+trace)', 
        'dnssec' => 'DNSSEC validation (+dnssec)',
        'noquestion' => 'Hide question section (+noquestion)',
        'nocomments' => 'Hide comments (+nocomments)',
        'nostats' => 'Hide statistics (+nostats)',
        'recurse' => 'Recursive query (default)',
        'tcp' => 'Use TCP instead of UDP (+tcp)',
        'show_command' => 'Show command'
    ]
];