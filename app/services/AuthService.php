<?php

class AuthService
{
    /**
     * Phase 1 placeholder auth.
     * Replace with database-backed authentication in Phase 2.
     */
    public function attempt(string $username, string $password): ?array
    {
        $demoUsers = [
            'admin' => ['password' => 'admin123', 'role' => 'super_admin', 'name' => 'Super Admin'],
            'vp' => ['password' => 'vp123', 'role' => 'vice_president', 'name' => 'Vice President'],
            'secretary' => ['password' => 'secretary123', 'role' => 'secretary', 'name' => 'Secretary'],
            'treasurer' => ['password' => 'treasurer123', 'role' => 'treasurer', 'name' => 'Treasurer'],
            'compliance' => ['password' => 'compliance123', 'role' => 'compliance_officer', 'name' => 'Compliance Officer'],
            'driver' => ['password' => 'driver123', 'role' => 'driver', 'name' => 'Driver'],
        ];

        if (!isset($demoUsers[$username])) {
            return null;
        }

        $user = $demoUsers[$username];
        if ($user['password'] !== $password) {
            return null;
        }

        return [
            'username' => $username,
            'name' => $user['name'],
            'role' => $user['role'],
        ];
    }
}
