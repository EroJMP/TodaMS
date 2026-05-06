<?php

return [
    'super_admin' => [
        'users.manage',
        'system.monitor',
        'reports.generate',
        'audit.view',
    ],
    'vice_president' => [
        'violations.approve',
        'members.approve',
        'disputes.resolve',
    ],
    'compliance_officer' => [
        'violations.validate',
        'records.audit',
        'reports.audit',
    ],
    'secretary' => [
        'members.manage',
        'violations.encode',
        'documents.upload',
    ],
    'treasurer' => [
        'payments.manage',
        'reports.financial',
        'collections.monitor',
    ],
    'driver' => [
        'profile.view',
        'violations.view_own',
        'payments.view_own',
        'notifications.view_own',
    ],
];
