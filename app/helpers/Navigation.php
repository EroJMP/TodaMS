<?php

class Navigation
{
    /**
     * @return array<int, array{label: string, path: string}>
     */
    public static function itemsForRole(string $role): array
    {
        $common = [
            ['label' => 'Dashboard', 'path' => '/dashboard'],
            ['label' => 'Notifications', 'path' => '/notifications'],
        ];

        $roleItems = match ($role) {
            'super_admin' => [
                ['label' => 'Users', 'path' => '/users'],
                ['label' => 'Members', 'path' => '/members'],
                ['label' => 'Violations', 'path' => '/violations'],
                ['label' => 'Payments', 'path' => '/payments'],
                ['label' => 'Reports', 'path' => '/reports'],
                ['label' => 'Audit Logs', 'path' => '/audit-logs'],
                ['label' => 'System Tools', 'path' => '/system-tools'],
            ],
            'vice_president' => [
                ['label' => 'Members', 'path' => '/members'],
                ['label' => 'Violations', 'path' => '/violations'],
                ['label' => 'Reports', 'path' => '/reports'],
            ],
            'compliance_officer' => [
                ['label' => 'Violations', 'path' => '/violations'],
                ['label' => 'Payments', 'path' => '/payments'],
                ['label' => 'Members', 'path' => '/members'],
                ['label' => 'Reports', 'path' => '/reports'],
                ['label' => 'Audit Logs', 'path' => '/audit-logs'],
            ],
            'secretary' => [
                ['label' => 'Members', 'path' => '/members'],
                ['label' => 'Violations', 'path' => '/violations'],
            ],
            'treasurer' => [
                ['label' => 'Payments', 'path' => '/payments'],
                ['label' => 'Reports', 'path' => '/reports'],
            ],
            'driver' => [
                ['label' => 'Violations', 'path' => '/violations'],
                ['label' => 'Payments', 'path' => '/payments'],
            ],
            default => [],
        };

        return array_merge($common, $roleItems);
    }
}
